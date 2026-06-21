#!/usr/bin/env node

const fs = require("fs");
const path = require("path");
const Common = require("../waziper/common.js");
const { createCallResponderRuntime } = require("../waziper/callresponder_runtime.js");
const {
	areJidsSameUser,
	jidNormalizedUser,
} = require("@itsukichan/baileys");

const session_dir = path.join(__dirname, "..", "sessions");
const runtime = createCallResponderRuntime({
	Common,
	fs,
	path,
	session_dir,
	areJidsSameUser,
	jidNormalizedUser,
});

const {
	parse_call_node,
	track_call_node_metadata,
	normalize_call_event,
	get_callresponder_item,
	get_callresponder_except_data,
	get_callresponder_state,
	is_callresponder_peer_same_as_session,
	is_callresponder_except_match,
} = runtime;

const usage = () => {
	console.error(
		[
			"Usage:",
			"  node app_zapmatic_api/scripts/callresponder_diagnose.js replay-node --instance <id> --node-from <jid> --child-from <jid> --call-id <id> --child-tag <tag>",
			"  node app_zapmatic_api/scripts/callresponder_diagnose.js replay-call --instance <id> --from <jid> --chat-id <jid> --status <status> --call-id <id>",
		].join("\n")
	);
};

const parseArgs = (argv) => {
	const [command, ...rest] = argv;
	const options = {};

	for (let index = 0; index < rest.length; index++) {
		const token = rest[index];
		if (!token.startsWith("--")) {
			continue;
		}

		const key = token.slice(2);
		const next = rest[index + 1];
		if (!next || next.startsWith("--")) {
			options[key] = "1";
			continue;
		}

		options[key] = next;
		index++;
	}

	return { command, options };
};

const isTruthy = (value) => {
	return ["1", "true", "yes", "on"].includes(String(value || "").trim().toLowerCase());
};

const requireOption = (options, key) => {
	if (!options[key]) {
		throw new Error(`Missing required option --${key}`);
	}

	return String(options[key]).trim();
};

const closeDbPool = async () => {
	if (!Common?.db_connect || typeof Common.db_connect.end !== "function") {
		return;
	}

	await new Promise((resolve) => {
		Common.db_connect.end(() => resolve());
	});
};

const loadSessionUser = async (instanceId) => {
	const account = await Common.db_get("sp_accounts", [{ token: instanceId }, { status: 1 }])
		|| await Common.db_get("sp_accounts", [{ token: instanceId }]);

	if (!account) {
		throw new Error(`Account not found for instance ${instanceId}`);
	}

	let accountData = {};
	try {
		accountData = account.data
			? JSON.parse(account.data)
			: (account.tmp ? JSON.parse(account.tmp) : {});
	} catch (error) {
		accountData = {};
	}

	return {
		id: accountData.id || null,
		lid: accountData.lid || null,
	};
};

const buildReplayNode = (options) => {
	const callId = requireOption(options, "call-id");
	const nodeFrom = requireOption(options, "node-from");
	const childFrom = options["child-from"] ? String(options["child-from"]).trim() : null;
	const callCreator = options["call-creator"]
		? String(options["call-creator"]).trim()
		: (options["child-call-creator"] ? String(options["child-call-creator"]).trim() : childFrom);
	const childTag = options["child-tag"] ? String(options["child-tag"]).trim() : "offer";
	const isVideo = isTruthy(options.video);
	const groupJid = options["group-jid"] ? String(options["group-jid"]).trim() : "";
	const childType = options["child-type"] ? String(options["child-type"]).trim() : "";
	const reason = options.reason ? String(options.reason).trim() : "";

	const childAttrs = {
		"call-id": callId,
	};
	if (childFrom) {
		childAttrs.from = childFrom;
	}
	if (callCreator) {
		childAttrs["call-creator"] = callCreator;
	}
	if (groupJid) {
		childAttrs["group-jid"] = groupJid;
	}
	if (childType) {
		childAttrs.type = childType;
	}
	if (reason) {
		childAttrs.reason = reason;
	}

	return {
		tag: "call",
		attrs: {
			from: nodeFrom,
			t: String(Math.floor(Date.now() / 1000)),
			offline: isTruthy(options.offline) ? "true" : ""
		},
		content: [
			{
				tag: childTag,
				attrs: childAttrs,
				content: isVideo ? [{ tag: "video", attrs: {}, content: [] }] : []
			}
		]
	};
};

const buildReplayCall = (options) => {
	const callId = requireOption(options, "call-id");
	const from = requireOption(options, "from");
	const chatId = options["chat-id"] ? String(options["chat-id"]).trim() : from;
	const callCreator = options["call-creator"] ? String(options["call-creator"]).trim() : from;
	const status = options.status ? String(options.status).trim() : "offer";
	const groupJid = options["group-jid"] ? String(options["group-jid"]).trim() : null;

	return {
		id: callId,
		from,
		chatId,
		callCreator,
		date: new Date(),
		offline: isTruthy(options.offline),
		status,
		isVideo: isTruthy(options.video),
		isGroup: isTruthy(options["is-group"]) || !!groupJid,
		groupJid,
	};
};

const diagnoseCallEvent = async ({ command, instanceId, options }) => {
	const user = await loadSessionUser(instanceId);
	const call_item = await get_callresponder_item(instanceId);
	const session = {
		user,
	};

	let wouldReject = false;
	let wouldReply = false;
	let skipReason = null;
	let parsedStatus = null;

	session.rejectCall = async () => {
		wouldReject = true;
		return true;
	};

	const now = Math.floor(Date.now() / 1000);
	let callEvent = null;

	if (command === "replay-node") {
		const node = buildReplayNode(options);
		const parsedNode = parse_call_node(node);
		parsedStatus = parsedNode?.status || null;
		callEvent = await track_call_node_metadata(session, instanceId, parsedNode);
	} else {
		const call = buildReplayCall(options);
		parsedStatus = call.status || null;
		callEvent = await normalize_call_event(session, instanceId, call);
	}

	if (!callEvent || !callEvent.id || !callEvent.from) {
		return {
			parsedStatus,
			rawFrom: callEvent?.from || "",
			rawChatId: callEvent?.chatId || "",
			resolvedRejectJid: callEvent?.resolvedRejectJid || null,
			resolvedReplyJid: callEvent?.resolvedReplyJid || null,
			resolvedVia: callEvent?.resolutionMeta?.resolvedVia || "",
			normalizedPhone: callEvent?.resolutionMeta?.normalizedPhone || "",
			isOutgoing: !!callEvent?.isOutgoing,
			isGroup: !!callEvent?.isGroup,
			sameConnectedAccount: false,
			wouldReject,
			wouldReply,
			skipReason: "invalid_event",
		};
	}

	const chat_id = callEvent.resolvedReplyJid || callEvent.chatId || callEvent.from;
	const rejectJid = callEvent.resolvedRejectJid || chat_id;
	const state = get_callresponder_state(session, now);
	const sameConnectedAccount = is_callresponder_peer_same_as_session(session, callEvent);
	callEvent.sameConnectedAccount = sameConnectedAccount;

	const delayMinutes = Math.max(0, parseInt(call_item?.delay, 10) || 0);
	const sendTo = parseInt(call_item?.send_to, 10) || 0;
	const autoRejectEnabled = parseInt(call_item?.auto_reject, 10) === 1;
	const exceptData = get_callresponder_except_data(call_item);
	const callStatus = String(callEvent.status || "");

	const canSendReply = () => {
		const lastReplyAt = Number(state.lastReplyByChat[chat_id] || 0);
		return !(lastReplyAt && lastReplyAt + delayMinutes * 60 >= now);
	};

	const sendReply = async () => {
		if (!chat_id || state.repliedByCallId[callEvent.id] || !canSendReply()) {
			return false;
		}

		state.repliedByCallId[callEvent.id] = now;
		wouldReply = true;
		state.lastReplyByChat[chat_id] = now;
		return true;
	};

	if (!call_item) {
		skipReason = "callresponder_disabled";
	} else if (callStatus === "timeout" || callStatus === "terminate") {
		skipReason = callStatus;
	} else if (!chat_id || sameConnectedAccount) {
		skipReason = !chat_id ? "missing_chat_id" : "same_connected_account";
	} else if (is_callresponder_except_match(exceptData, callEvent)) {
		skipReason = "except_match";
	} else if (autoRejectEnabled && callEvent.isGroup) {
		skipReason = "group_call";
	} else if (autoRejectEnabled && callStatus === "offer") {
		const alreadyRejected = !!state.rejectedByCallId[callEvent.id];
		if (alreadyRejected) {
			skipReason = "already_rejected";
		} else if (typeof session.rejectCall !== "function") {
			skipReason = "reject_unavailable";
		} else if (!rejectJid) {
			skipReason = "unresolved_peer";
		} else {
			await session.rejectCall(callEvent.id, rejectJid);
			state.rejectedByCallId[callEvent.id] = now;
			if (sendTo === 1 || sendTo === 3) {
				await sendReply();
			}
		}
	} else if (!state.repliedByCallId[callEvent.id]) {
		switch (sendTo) {
			case 2:
				if (callStatus === "accept") {
					await sendReply();
				}
				break;

			case 3:
				if (callStatus === "reject") {
					await sendReply();
				}
				break;

			case 1:
				if (callStatus === "reject" || callStatus === "accept") {
					await sendReply();
				}
				break;
		}
	}

	return {
		parsedStatus,
		rawFrom: callEvent?.resolutionMeta?.rawFrom || callEvent?.from || "",
		rawChatId: callEvent?.resolutionMeta?.rawChatId || callEvent?.chatId || "",
		resolvedRejectJid: callEvent?.resolvedRejectJid || null,
		resolvedReplyJid: callEvent?.resolvedReplyJid || null,
		resolvedVia: callEvent?.resolutionMeta?.resolvedVia || "",
		normalizedPhone: callEvent?.resolutionMeta?.normalizedPhone || "",
		isOutgoing: !!callEvent?.isOutgoing,
		isGroup: !!callEvent?.isGroup,
		sameConnectedAccount,
		wouldReject,
		wouldReply,
		skipReason,
	};
};

const main = async () => {
	const { command, options } = parseArgs(process.argv.slice(2));

	if (!command || !["replay-node", "replay-call"].includes(command)) {
		usage();
		process.exitCode = 1;
		return;
	}

	const instanceId = requireOption(options, "instance");
	const output = await diagnoseCallEvent({ command, instanceId, options });
	console.log(JSON.stringify(output, null, 2));
};

main()
	.catch((error) => {
		console.error(error.message || error);
		process.exitCode = 1;
	})
	.finally(async () => {
		await closeDbPool();
	});
