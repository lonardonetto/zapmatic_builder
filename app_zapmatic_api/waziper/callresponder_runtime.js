const createCallResponderRuntime = ({
	Common,
	fs,
	path,
	session_dir,
	areJidsSameUser,
	jidNormalizedUser,
}) => {
	if (!Common || !fs || !path || !session_dir) {
		throw new Error("callresponder_runtime requires Common, fs, path, and session_dir");
	}

	const CALLRESPONDER_STATE_TTL_SECONDS = 12 * 60 * 60;
	const CALLRESPONDER_STATE_MAX_ITEMS = 5000;
	const CALL_CAMPAIGN_STATE_TTL_SECONDS = 2 * 60 * 60;
	const CALL_CAMPAIGN_STATE_MAX_ITEMS = 5000;
	const CALLRESPONDER_CONFIG_CACHE_TTL_MS = 5 * 1000;
	const callresponder_config_cache = new Map();

	const get_state_entry_timestamp = (value) => {
		if (value && typeof value === "object") {
			return Number(value.updatedAt || value.startedAt || value.timestamp || 0);
		}

		return Number(value || 0);
	};

	const cleanup_state_bucket = (bucket, now, ttlSeconds, maxItems) => {
		if (!bucket || typeof bucket !== "object") return;

		for (const [key, value] of Object.entries(bucket)) {
			const timestamp = get_state_entry_timestamp(value);
			if (!timestamp || timestamp + ttlSeconds <= now) {
				delete bucket[key];
			}
		}

		const keys = Object.keys(bucket);
		if (keys.length <= maxItems) return;

		keys.sort((left, right) => get_state_entry_timestamp(bucket[left]) - get_state_entry_timestamp(bucket[right]));
		const overflow = keys.length - maxItems;
		for (let index = 0; index < overflow; index++) {
			delete bucket[keys[index]];
		}
	};

	const get_callresponder_status_from_tag = (tag, attrs = {}) => {
		switch (tag) {
			case "offer":
			case "offer_notice":
				return "offer";

			case "terminate":
				return attrs.reason === "timeout" ? "timeout" : "terminate";

			case "reject":
				return "reject";

			case "accept":
				return "accept";

			default:
				return "ringing";
		}
	};

	const get_call_identifier_prefix = (value = "") => {
		const safeValue = String(value || "").trim();
		if (!safeValue) {
			return "";
		}

		return safeValue.split(":")[0].split("@")[0].trim();
	};

	const is_numeric_call_identifier = (value = "") => {
		return /^[0-9]+$/.test(get_call_identifier_prefix(value));
	};

	const is_call_lid_jid = (value = "") => {
		return String(value || "").trim().endsWith("@lid");
	};

	const is_call_s_whatsapp_jid = (value = "") => {
		return String(value || "").trim().endsWith("@s.whatsapp.net");
	};

	const is_call_transport_jid = (value = "") => {
		const safeValue = String(value || "").trim();
		return is_call_lid_jid(safeValue) || is_call_s_whatsapp_jid(safeValue);
	};

	const build_call_lid_jid = (value = "") => {
		const prefix = get_call_identifier_prefix(value);
		return prefix ? `${prefix}@lid` : null;
	};

	const build_call_s_whatsapp_jid = (value = "") => {
		const prefix = get_call_identifier_prefix(value);
		return prefix ? `${prefix}@s.whatsapp.net` : null;
	};

	const read_call_mapping_json = (filePath) => {
		try {
			if (!filePath || !fs.existsSync(filePath)) {
				return null;
			}

			const raw = fs.readFileSync(filePath, "utf8").trim();
			if (!raw) {
				return null;
			}

			return JSON.parse(raw);
		} catch (error) {
			return null;
		}
	};

	const resolve_call_lid_from_phone_mapping = (instance_id, phonePrefix) => {
		const prefix = get_call_identifier_prefix(phonePrefix);
		if (!prefix || !/^[0-9]+$/.test(prefix)) {
			return null;
		}

		const mappingPath = path.join(session_dir, String(instance_id), `lid-mapping-${prefix}.json`);
		const mappedLid = get_call_identifier_prefix(read_call_mapping_json(mappingPath));
		if (!mappedLid) {
			return null;
		}

		return {
			jid: `${mappedLid}@lid`,
			phone: prefix,
			resolvedVia: "mapping_phone_to_lid"
		};
	};

	const resolve_call_phone_from_lid_mapping = (instance_id, lidPrefix) => {
		const prefix = get_call_identifier_prefix(lidPrefix);
		if (!prefix || !/^[0-9]+$/.test(prefix)) {
			return null;
		}

		const mappingPath = path.join(session_dir, String(instance_id), `lid-mapping-${prefix}_reverse.json`);
		const mappedPhone = get_call_identifier_prefix(read_call_mapping_json(mappingPath));
		if (!mappedPhone) {
			return null;
		}

		return {
			jid: `${prefix}@lid`,
			phone: mappedPhone,
			resolvedVia: "mapping_lid_reverse"
		};
	};

	const parse_call_node = (node) => {
		if (!node || !Array.isArray(node.content) || node.content.length === 0) {
			return null;
		}

		const [infoChild] = node.content;
		if (!infoChild || !infoChild.attrs) {
			return null;
		}

		const callId = infoChild.attrs["call-id"];
		const from = infoChild.attrs.from || infoChild.attrs["call-creator"];
		if (!callId || !from) {
			return null;
		}

		const childContent = Array.isArray(infoChild.content) ? infoChild.content : [];
		let status = get_callresponder_status_from_tag(infoChild.tag, infoChild.attrs);
		if (
			status === "ringing" &&
			infoChild.tag === "relaylatency" &&
			(is_call_lid_jid(from) || is_call_lid_jid(node?.attrs?.from || ""))
		) {
			status = "offer";
		}

		return {
			chatId: node?.attrs?.from || "",
			from,
			callCreator: infoChild.attrs["call-creator"] || from,
			id: callId,
			date: node?.attrs?.t ? new Date(+node.attrs.t * 1000) : new Date(),
			offline: !!node?.attrs?.offline,
			status,
			isVideo: childContent.some((item) => item?.tag === "video"),
			isGroup: infoChild.attrs.type === "group" || !!infoChild.attrs["group-jid"],
			groupJid: infoChild.attrs["group-jid"] || null
		};
	};

	const summarize_call_node = (node) => {
		const parsed = parse_call_node(node);
		const firstChild = Array.isArray(node?.content) ? node.content[0] : null;

		return {
			nodeTag: node?.tag || "",
			nodeFrom: node?.attrs?.from || "",
			nodeTime: node?.attrs?.t || "",
			childTag: firstChild?.tag || "",
			childFrom: firstChild?.attrs?.from || "",
			childCallCreator: firstChild?.attrs?.["call-creator"] || "",
			childCallId: firstChild?.attrs?.["call-id"] || "",
			parsedStatus: parsed?.status || null,
			parsedFrom: parsed?.from || "",
			parsedChatId: parsed?.chatId || "",
			isGroup: !!parsed?.isGroup,
			isVideo: !!parsed?.isVideo
		};
	};

	const summarize_call_payload = (call) => {
		const callEvent = Array.isArray(call) ? call[0] : call;

		return {
			isArray: Array.isArray(call),
			count: Array.isArray(call) ? call.length : 1,
			callId: callEvent?.id || null,
			status: callEvent?.status || null,
			from: callEvent?.from || "",
			chatId: callEvent?.chatId || "",
			callCreator: callEvent?.callCreator || "",
			isGroup: !!callEvent?.isGroup,
			isVideo: !!callEvent?.isVideo,
			offline: !!callEvent?.offline
		};
	};

	const find_call_subscriber_jid = async (instance_id, callEvent) => {
		const rawCandidates = [callEvent?.from, callEvent?.chatId, callEvent?.callCreator]
			.map((item) => String(item || "").trim())
			.filter((item) => item !== "");
		const exactChatIds = Array.from(new Set(rawCandidates.flatMap((value) => {
			const entries = [];
			const prefix = get_call_identifier_prefix(value);

			if (value.includes("@")) {
				entries.push(value);
			}

			if (prefix) {
				entries.push(build_call_s_whatsapp_jid(prefix));
				entries.push(build_call_lid_jid(prefix));
			}

			return entries.filter((item) => item !== "");
		})));
		const phoneCandidates = Array.from(new Set(rawCandidates
			.map((item) => get_call_identifier_prefix(item))
			.filter((item) => /^[0-9]+$/.test(item))));

		const clauses = [];
		const params = [instance_id];

		if (exactChatIds.length > 0) {
			clauses.push(`chatid IN (${exactChatIds.map(() => "?").join(",")})`);
			params.push(...exactChatIds);
		}

		if (phoneCandidates.length > 0) {
			clauses.push(`(JSON_VALID(contact_data) AND JSON_UNQUOTE(JSON_EXTRACT(contact_data, '$.number')) IN (${phoneCandidates.map(() => "?").join(",")}))`);
			params.push(...phoneCandidates);
		}

		if (!clauses.length) {
			return null;
		}

		const rows = await Common.db_query(
			`SELECT id, chatid, contact_data FROM sp_whatsapp_subscriber WHERE instance_id = ? AND (${clauses.join(" OR ")}) ORDER BY id DESC LIMIT 10`,
			params,
			false
		);

		if (!rows || !rows.length) {
			return null;
		}

		const preferredRow = rows.find((row) => is_call_lid_jid(row?.chatid)) || rows.find((row) => is_call_transport_jid(row?.chatid)) || rows[0];
		const preferredChatId = String(preferredRow?.chatid || "").trim();
		if (!preferredChatId) {
			return null;
		}

		let normalizedPhone = "";
		try {
			const contactData = preferredRow?.contact_data ? JSON.parse(preferredRow.contact_data) : {};
			normalizedPhone = String(contactData?.number || "").trim();
		} catch (error) { }

		if (!normalizedPhone && is_call_s_whatsapp_jid(preferredChatId)) {
			normalizedPhone = get_call_identifier_prefix(preferredChatId);
		}

		return {
			jid: preferredChatId,
			phone: normalizedPhone || null,
			resolvedVia: is_call_lid_jid(preferredChatId) ? "subscriber_chatid_lid" : "subscriber_chatid"
		};
	};

	const resolve_call_peer_jids = async (session, instance_id, callEvent) => {
		const rawFrom = String(callEvent?.from || "").trim();
		const rawChatId = String(callEvent?.chatId || "").trim();
		const rawCallCreator = String(callEvent?.callCreator || "").trim();
		const finalize = (rejectJid, replyJid, resolvedVia, normalizedPhone = "") => ({
			rejectJid: rejectJid ? String(rejectJid).trim() : null,
			replyJid: replyJid ? String(replyJid).trim() : null,
			rawFrom,
			rawChatId,
			rawCallCreator,
			normalizedPhone: normalizedPhone ? String(normalizedPhone).trim() : "",
			resolvedVia
		});
		let preferredReplyJid = null;
		let preferredReplyVia = "";
		let preferredNormalizedPhone = "";

		if (is_call_lid_jid(rawFrom)) {
			const reverseMapping = resolve_call_phone_from_lid_mapping(instance_id, rawFrom);
			return finalize(rawFrom, rawFrom, "from_lid", reverseMapping?.phone || "");
		}

		if (is_call_transport_jid(rawChatId)) {
			if (is_call_lid_jid(rawChatId)) {
				const reverseMapping = resolve_call_phone_from_lid_mapping(instance_id, rawChatId);
				return finalize(rawChatId, rawChatId, "chatid_lid", reverseMapping?.phone || "");
			}

			preferredReplyJid = rawChatId;
			preferredReplyVia = "chatid_transport";
			preferredNormalizedPhone = get_call_identifier_prefix(rawChatId);
		}

		if (is_numeric_call_identifier(rawFrom)) {
			const phoneMappedToLid = resolve_call_lid_from_phone_mapping(instance_id, rawFrom);
			if (phoneMappedToLid) {
				return finalize(
					phoneMappedToLid.jid,
					preferredReplyJid || phoneMappedToLid.jid,
					preferredReplyJid ? `${preferredReplyVia}+${phoneMappedToLid.resolvedVia}` : phoneMappedToLid.resolvedVia,
					phoneMappedToLid.phone || preferredNormalizedPhone
				);
			}
		}

		for (const candidate of [rawFrom, rawChatId]) {
			if (!is_numeric_call_identifier(candidate)) {
				continue;
			}

			const reverseMapping = resolve_call_phone_from_lid_mapping(instance_id, candidate);
			if (reverseMapping) {
				return finalize(
					reverseMapping.jid,
					preferredReplyJid || reverseMapping.jid,
					preferredReplyJid ? `${preferredReplyVia}+${reverseMapping.resolvedVia}` : reverseMapping.resolvedVia,
					reverseMapping.phone || preferredNormalizedPhone
				);
			}
		}

		const subscriberMatch = await find_call_subscriber_jid(instance_id, callEvent);
		if (subscriberMatch) {
			const replyJid = preferredReplyJid && preferredReplyJid !== subscriberMatch.jid
				? preferredReplyJid
				: subscriberMatch.jid;
			const resolvedVia = preferredReplyJid && preferredReplyJid !== subscriberMatch.jid
				? `${preferredReplyVia}+${subscriberMatch.resolvedVia}`
				: subscriberMatch.resolvedVia;
			return finalize(subscriberMatch.jid, replyJid, resolvedVia, subscriberMatch.phone || preferredNormalizedPhone || "");
		}

		if (preferredReplyJid) {
			return finalize(preferredReplyJid, preferredReplyJid, preferredReplyVia, preferredNormalizedPhone);
		}

		const rawJidCandidate = [rawFrom, rawChatId, rawCallCreator].find((item) => item.includes("@"));
		if (rawJidCandidate) {
			const normalizedPhone = is_call_s_whatsapp_jid(rawJidCandidate) ? get_call_identifier_prefix(rawJidCandidate) : "";
			return finalize(rawJidCandidate, rawJidCandidate, "fallback_raw_jid", normalizedPhone);
		}

		const numericFallback = [rawFrom, rawChatId, rawCallCreator]
			.map((item) => get_call_identifier_prefix(item))
			.find((item) => /^[0-9]+$/.test(item));

		if (numericFallback) {
			const fallbackJid = build_call_s_whatsapp_jid(numericFallback);
			return finalize(fallbackJid, fallbackJid, "fallback_s_whatsapp", numericFallback);
		}

		return finalize(null, null, "unresolved", "");
	};

	const get_callresponder_history_phone = (callEvent) => {
		const source = callEvent?.resolvedRejectJid || callEvent?.resolvedReplyJid || callEvent?.from || "";
		return source ? Common.get_phone(source) : "";
	};

	const get_session_user_ids = (session) => {
		return [session?.user?.id, session?.user?.lid].filter((item) => !!item);
	};

	const is_same_jid_user = (left, right) => {
		if (!left || !right) return false;

		try {
			if (typeof areJidsSameUser === "function" && typeof jidNormalizedUser === "function") {
				return areJidsSameUser(jidNormalizedUser(left), jidNormalizedUser(right));
			}
		} catch (error) { }

		return String(left) === String(right);
	};

	const is_callresponder_peer_same_as_session = (session, callEvent) => {
		const sessionUserIds = get_session_user_ids(session);
		const peerCandidates = [
			callEvent?.resolvedRejectJid,
			callEvent?.resolvedReplyJid,
			callEvent?.chatId,
			callEvent?.from,
			callEvent?.callCreator
		].filter((item) => !!item);

		return peerCandidates.some((candidate) => sessionUserIds.some((jid) => is_same_jid_user(candidate, jid)));
	};

	const get_callresponder_log_payload = (instance_id, callEvent, extra = {}) => {
		const normalizedExtra = { ...extra };
		if (normalizedExtra.reason && !normalizedExtra.skipReason) {
			normalizedExtra.skipReason = normalizedExtra.reason;
		}

		if (normalizedExtra.session && normalizedExtra.sameConnectedAccount === undefined) {
			normalizedExtra.sameConnectedAccount = is_callresponder_peer_same_as_session(normalizedExtra.session, callEvent);
		}

		delete normalizedExtra.session;

		const resolutionMeta = callEvent?.resolutionMeta || {};
		return {
			instance_id,
			call_id: callEvent?.id || null,
			status: callEvent?.status || null,
			rawFrom: resolutionMeta.rawFrom || callEvent?.from || "",
			rawChatId: resolutionMeta.rawChatId || callEvent?.chatId || "",
			rawCallCreator: resolutionMeta.rawCallCreator || callEvent?.callCreator || "",
			resolvedRejectJid: callEvent?.resolvedRejectJid || null,
			resolvedReplyJid: callEvent?.resolvedReplyJid || null,
			resolvedVia: resolutionMeta.resolvedVia || "",
			normalizedPhone: resolutionMeta.normalizedPhone || "",
			sameConnectedAccount: normalizedExtra.sameConnectedAccount === true || callEvent?.sameConnectedAccount === true,
			skipReason: normalizedExtra.skipReason || null,
			error: normalizedExtra.error || null,
			action: normalizedExtra.action || null,
			...normalizedExtra
		};
	};

	const log_callresponder_result = (result, instance_id, callEvent, extra = {}) => {
		const payload = get_callresponder_log_payload(instance_id, callEvent, extra);
		if (result === "failure") {
			console.error(`[CALLRESPONDER] ${result}`, payload);
		} else {
			console.log(`[CALLRESPONDER] ${result}`, payload);
		}
	};

	const get_callresponder_item = async (instance_id, forceRefresh = false) => {
		const now = Date.now();
		const cached = callresponder_config_cache.get(instance_id);

		if (!forceRefresh && cached && cached.expiresAt > now) {
			return cached.item;
		}

		const item = await Common.db_get("sp_whatsapp_callresponder", [{ instance_id: instance_id }, { status: 1 }]);
		callresponder_config_cache.set(instance_id, {
			item: item || null,
			expiresAt: now + CALLRESPONDER_CONFIG_CACHE_TTL_MS
		});

		return item;
	};

	const get_callresponder_except_data = (call_item) => {
		return call_item?.except != null
			? String(call_item.except).split(",").map(item => item.trim()).filter(item => item !== "")
			: [];
	};

	const get_callresponder_state = (session, now) => {
		if (!session.callResponderState) {
			session.callResponderState = {
				lastReplyByChat: {},
				repliedByCallId: {},
				rejectedByCallId: {},
				loggedByEventKey: {}
			};
		}

		cleanup_state_bucket(session.callResponderState.lastReplyByChat, now, CALLRESPONDER_STATE_TTL_SECONDS, CALLRESPONDER_STATE_MAX_ITEMS);
		cleanup_state_bucket(session.callResponderState.repliedByCallId, now, CALLRESPONDER_STATE_TTL_SECONDS, CALLRESPONDER_STATE_MAX_ITEMS);
		cleanup_state_bucket(session.callResponderState.rejectedByCallId, now, CALLRESPONDER_STATE_TTL_SECONDS, CALLRESPONDER_STATE_MAX_ITEMS);
		cleanup_state_bucket(session.callResponderState.loggedByEventKey, now, CALLRESPONDER_STATE_TTL_SECONDS, CALLRESPONDER_STATE_MAX_ITEMS * 2);

		return session.callResponderState;
	};

	const should_log_callresponder_event = (state, callId, eventKey, now) => {
		if (!state || !callId || !eventKey) {
			return true;
		}

		const bucketKey = `${callId}:${eventKey}`;
		if (state.loggedByEventKey[bucketKey]) {
			return false;
		}

		state.loggedByEventKey[bucketKey] = now;
		return true;
	};

	const insert_callresponder_history_event = async ({ instance_id, team_id, callEvent, state, eventKey, message, status = 1, time_post = null }) => {
		if (!instance_id || !team_id || !callEvent?.id || !message) {
			return false;
		}

		const now = time_post || Math.floor(Date.now() / 1000);
		if (!should_log_callresponder_event(state, callEvent.id, eventKey, now)) {
			return false;
		}

		await Common.db_insert("sp_whatsapp_history", {
			instance_id,
			team_id,
			phone: get_callresponder_history_phone(callEvent),
			type: "callresponder_event",
			message,
			status,
			time_post: now
		});

		return true;
	};

	const is_callresponder_except_match = (exceptData, callEvent) => {
		if (!Array.isArray(exceptData) || exceptData.length === 0) {
			return false;
		}

		const normalizedPhone = callEvent?.resolutionMeta?.normalizedPhone || "";
		const rawCandidates = [
			callEvent?.resolvedReplyJid,
			callEvent?.resolvedRejectJid,
			callEvent?.chatId,
			callEvent?.from,
			callEvent?.callCreator
		].filter((item) => !!item).map((item) => String(item));
		const phoneCandidates = rawCandidates
			.map((item) => get_call_identifier_prefix(item))
			.filter((item) => item !== "");
		if (normalizedPhone) {
			phoneCandidates.push(String(normalizedPhone));
		}

		const allCandidates = Array.from(new Set([...rawCandidates, ...phoneCandidates]));
		return exceptData.some((item) => allCandidates.some((candidate) => candidate.indexOf(item) !== -1));
	};

	const get_callcampaign_state = (session, now) => {
		if (!session.callCampaignState) {
			session.callCampaignState = {
				outgoingByCallId: {},
				metadataByCallId: {}
			};
		}

		cleanup_state_bucket(session.callCampaignState.outgoingByCallId, now, CALL_CAMPAIGN_STATE_TTL_SECONDS, CALL_CAMPAIGN_STATE_MAX_ITEMS);
		cleanup_state_bucket(session.callCampaignState.metadataByCallId, now, CALL_CAMPAIGN_STATE_TTL_SECONDS, CALL_CAMPAIGN_STATE_MAX_ITEMS);

		return session.callCampaignState;
	};

	const track_call_node_metadata = async (session, instance_id, callEvent) => {
		if (!session || !callEvent || !callEvent.id) {
			return null;
		}

		const now = Math.floor(Date.now() / 1000);
		const state = get_callcampaign_state(session, now);
		const sessionUserIds = get_session_user_ids(session);
		const callCreator = callEvent.callCreator || callEvent.from;
		const isOutgoing = sessionUserIds.some((jid) => is_same_jid_user(callCreator, jid));
		const peerResolution = await resolve_call_peer_jids(session, instance_id, {
			...callEvent,
			callCreator
		});
		const resolutionMeta = {
			rawFrom: peerResolution.rawFrom,
			rawChatId: peerResolution.rawChatId,
			rawCallCreator: peerResolution.rawCallCreator,
			normalizedPhone: peerResolution.normalizedPhone,
			resolvedVia: peerResolution.resolvedVia
		};

		state.metadataByCallId[callEvent.id] = {
			callCreator: callCreator,
			chatId: callEvent.chatId || callEvent.from,
			from: callEvent.from,
			isOutgoing: isOutgoing,
			status: callEvent.status,
			resolvedRejectJid: peerResolution.rejectJid,
			resolvedReplyJid: peerResolution.replyJid,
			resolutionMeta,
			updatedAt: now
		};

		return {
			...callEvent,
			callCreator,
			isOutgoing,
			resolvedRejectJid: peerResolution.rejectJid,
			resolvedReplyJid: peerResolution.replyJid,
			resolutionMeta
		};
	};

	const normalize_call_event = async (session, instance_id, call) => {
		const callEvent = Array.isArray(call) ? call[0] : call;
		if (!callEvent || !callEvent.id || !callEvent.from) {
			return null;
		}

		const now = Math.floor(Date.now() / 1000);
		const state = session ? get_callcampaign_state(session, now) : null;
		const trackedMeta = state?.metadataByCallId?.[callEvent.id] || {};
		const callCreator = callEvent.callCreator || trackedMeta.callCreator || callEvent.from;
		const sessionUserIds = get_session_user_ids(session);
		const isOutgoing = trackedMeta.isOutgoing === true || sessionUserIds.some((jid) => is_same_jid_user(callCreator, jid));
		let resolvedRejectJid = trackedMeta.resolvedRejectJid || callEvent.resolvedRejectJid || null;
		let resolvedReplyJid = trackedMeta.resolvedReplyJid || callEvent.resolvedReplyJid || null;
		let resolutionMeta = trackedMeta.resolutionMeta || callEvent.resolutionMeta || null;

		if (!resolvedRejectJid || !resolvedReplyJid || !resolutionMeta) {
			const peerResolution = await resolve_call_peer_jids(session, instance_id, {
				...callEvent,
				callCreator,
				chatId: callEvent.chatId || trackedMeta.chatId || callEvent.from
			});

			resolvedRejectJid = peerResolution.rejectJid;
			resolvedReplyJid = peerResolution.replyJid;
			resolutionMeta = {
				rawFrom: peerResolution.rawFrom,
				rawChatId: peerResolution.rawChatId,
				rawCallCreator: peerResolution.rawCallCreator,
				normalizedPhone: peerResolution.normalizedPhone,
				resolvedVia: peerResolution.resolvedVia
			};
		}

		if (state) {
			state.metadataByCallId[callEvent.id] = {
				...trackedMeta,
				callCreator,
				chatId: callEvent.chatId || trackedMeta.chatId || callEvent.from,
				from: callEvent.from,
				isOutgoing,
				status: callEvent.status,
				resolvedRejectJid,
				resolvedReplyJid,
				resolutionMeta,
				updatedAt: now
			};
		}

		return {
			...callEvent,
			callCreator,
			chatId: callEvent.chatId || trackedMeta.chatId || callEvent.from,
			isOutgoing,
			resolvedRejectJid,
			resolvedReplyJid,
			resolutionMeta
		};
	};

	return {
		get_callresponder_status_from_tag,
		parse_call_node,
		summarize_call_node,
		summarize_call_payload,
		resolve_call_peer_jids,
		track_call_node_metadata,
		normalize_call_event,
		get_callresponder_history_phone,
		get_callresponder_log_payload,
		log_callresponder_result,
		get_callresponder_item,
		get_callresponder_except_data,
		get_callresponder_state,
		should_log_callresponder_event,
		insert_callresponder_history_event,
		get_session_user_ids,
		is_same_jid_user,
		is_callresponder_peer_same_as_session,
		is_callresponder_except_match,
		get_callcampaign_state,
		get_call_identifier_prefix,
		is_numeric_call_identifier,
		is_call_lid_jid,
		is_call_s_whatsapp_jid,
		is_call_transport_jid,
		build_call_lid_jid,
		build_call_s_whatsapp_jid,
		resolve_call_lid_from_phone_mapping,
		resolve_call_phone_from_lid_mapping,
		find_call_subscriber_jid,
	};
};

module.exports = {
	createCallResponderRuntime,
};
