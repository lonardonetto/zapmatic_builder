const os = require('os');
const fs = require('fs');
const path = require('path');
const http = require('http');
const qrimg = require('qr-image');
const express = require('express');
const rimraf = require('rimraf');
const moment = require('moment-timezone');
const bodyParser = require('body-parser');
const publicIp = require('ip');
const cors = require('cors');
const spintax = require('spintax');
const Boom = require('@hapi/boom');
const P = require('pino');
const app = express();
const axios = require('axios');
const FormData = require('form-data');
const server = http.createServer(app);
const { Server } = require("socket.io");
const config = require("./../config.js");
const Common = require("./common.js");
const Extend = require("./extend.js");
const { createCallResponderRuntime } = require("./callresponder_runtime.js");
const cron = require('node-cron');
const NodeCache = require("node-cache")

let HttpsProxyAgent = null;
try {
	({ HttpsProxyAgent } = require('https-proxy-agent'));
} catch (error) {
	console.warn('Módulo "https-proxy-agent" não encontrado. Proxy para Baileys desabilitado até instalar dependência.', error.message);
}

const normalizeProxyString = (proxyRaw) => {
	if (!proxyRaw || typeof proxyRaw !== 'string') {
		return null;
	}

	let proxy = proxyRaw.trim();
	if (!proxy) {
		return null;
	}

	if (!/^https?:\/\//i.test(proxy)) {
		proxy = 'http://' + proxy;
	}

	return proxy;
};

const createProxyAgent = (proxyRaw) => {
	try {
		if (!HttpsProxyAgent) {
			return null;
		}
		const normalized = normalizeProxyString(proxyRaw);
		if (!normalized) {
			return null;
		}
		return new HttpsProxyAgent(normalized);
	} catch (error) {
		console.error('Failed to create proxy agent', error);
		return null;
	}
};

const normalizeLanguageCode = (langRaw) => {
	if (!langRaw) return 'pt_BR';
	if (typeof langRaw === 'object') {
		return langRaw.code || 'pt_BR';
	}
	if (typeof langRaw !== 'string') return 'pt_BR';
	const s = langRaw.trim();
	if (!s) return 'pt_BR';
	if (s.includes(',')) {
		return s.split(',')[0].trim() || 'pt_BR';
	}
	return s;
};

const parseBodyExampleValues = (raw) => {
	if (!raw) return [];
	if (Array.isArray(raw)) return raw.map(v => String(v));
	if (typeof raw !== 'string') return [];
	return raw
		.split('|')
		.map(v => String(v).trim())
		.filter(v => v !== '');
};

const isMetaOfficialEnabled = (payloadInternal) => {
	try {
		const enabled = payloadInternal?.meta_official?.enabled;
		if (enabled === true) return true;
		if (enabled === 1) return true;
		if (typeof enabled === 'string') {
			const s = enabled.trim().toLowerCase();
			if (s === 'true') return true;
			if (s === '1') return true;
		}
		return parseInt(enabled || 0, 10) === 1;
	} catch (e) {
		return false;
	}
};

const parseApprovedTemplateData = (raw) => {
	try {
		if (!raw) return null;
		if (typeof raw === 'object' && !Buffer.isBuffer(raw)) {
			return raw;
		}
		if (Buffer.isBuffer(raw)) {
			return JSON.parse(raw.toString('utf8'));
		}
		if (typeof raw === 'string') {
			return JSON.parse(raw);
		}
		return null;
	} catch (e) {
		return null;
	}
};

const buildTemplateFlowToken = (seed, recipient, index = 0) => {
	const safeSeed = String(seed || 'flow-template')
		.toLowerCase()
		.replace(/[^a-z0-9_]+/g, '_')
		.replace(/^_+|_+$/g, '') || 'flow_template';
	const suffix = (Common && typeof Common.makeid === 'function')
		? Common.makeid(10)
		: Math.random().toString(36).slice(2, 12);

	return `${safeSeed}_${index}_${suffix}`.slice(0, 64);
};

const extractTemplateFlowButtonDefaults = (payloadInternal, context = {}) => {
	const defaults = [];
	const buttons = Array.isArray(payloadInternal?.templateButtons) ? payloadInternal.templateButtons : [];

	buttons.forEach((button, index) => {
		const flowButton = button?.flowButton;
		if (!flowButton || typeof flowButton !== 'object') {
			return;
		}

		defaults.push({
			index: String(index),
			flow_token: buildTemplateFlowToken(
				flowButton.flowName || flowButton.displayText || context.template_name || 'flow-template',
				context.chat_id || context.instance_id || '',
				index
			),
			flow_action_data: flowButton.flowActionData || ''
		});
	});

	return defaults;
};

const resolveTemplateFlowActionData = async (rawActionData, { instance_id, item, data }) => {
	let jsonRaw = '';

	if (rawActionData && typeof rawActionData === 'object') {
		try {
			jsonRaw = JSON.stringify(rawActionData);
		} catch (e) {
			jsonRaw = '';
		}
	} else if (typeof rawActionData === 'string') {
		jsonRaw = rawActionData.trim();
	}

	if (!jsonRaw) {
		return null;
	}

	try {
		if (jsonRaw.match(/\[.*?\]/) || jsonRaw.match(/%.*?%/)) {
			const sess = (typeof sessions !== 'undefined' && sessions[instance_id]) ? sessions[instance_id] : (WAZIPER.sessions ? WAZIPER.sessions[instance_id] : null);
			jsonRaw = await Extend.common_data(WAZIPER, sess, instance_id, item || null, data?.placeholder_message || null, jsonRaw);
			if (data?.spreadsheet_params) {
				jsonRaw = Common.params(data.spreadsheet_params, jsonRaw);
			}
		}

		const parsed = JSON.parse(jsonRaw);
		if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
			return null;
		}

		return parsed;
	} catch (e) {
		console.error('[FLOW-TEMPLATE] Failed to resolve flow_action_data:', e.message);
		return null;
	}
};

const bulkMetaDebugLogPath = path.join(__dirname, '../../writable/logs/bulk_meta_debug.log');
const bulkMetaDebugLog = (data) => {
	try {
		const line = new Date().toISOString() + ' ' + (typeof data === 'string' ? data : JSON.stringify(data)) + "\n";
		fs.appendFileSync(bulkMetaDebugLogPath, line);
	} catch (e) {
		// ignore
	}
};

const getAutomationContextForMessage = (message, instance_id = '') => {
	if (!message || typeof message !== 'object') {
		return {};
	}

	return Extend.getAutomationContext(
		message,
		instance_id,
		{ instance_id },
		{ official_api: message.official_api === true }
	);
};

const buildAutomationIdentifierCandidates = (automationContext = {}, fallbackValue = '') => {
	const candidates = [
		automationContext.canonicalId,
		automationContext.canonicalNumber,
		automationContext.rawIdentifier,
		fallbackValue,
		...(Array.isArray(automationContext.aliases) ? automationContext.aliases : [])
	];

	return [...new Set(
		candidates
			.map((value) => Extend.stripUndefinedSuffix(String(value || '').trim()))
			.map((value) => value.includes('@') ? Extend.getIdentifierPrefix(value) : value)
			.filter(Boolean)
	)];
};

const loadAutoresponderResponseRecord = async (instance_id, identifiers = []) => {
	const lookupIds = [...new Set((identifiers || []).map((value) => String(value || '').trim()).filter(Boolean))];
	if (!instance_id || lookupIds.length === 0) {
		return false;
	}

	const valuePlaceholders = lookupIds.map(() => '?').join(', ');
	const sql =
		`SELECT * FROM sp_whatsapp_ar_responses ` +
		`WHERE instance_id = ? AND whatsapp IN (${valuePlaceholders}) ` +
		`ORDER BY FIELD(whatsapp, ${valuePlaceholders}), id DESC LIMIT 1`;

	return Common.db_query(sql, [instance_id, ...lookupIds, ...lookupIds], true);
};

const touchAutoresponderResponseRecord = async (instance_id, canonicalId, identifiers = [], lastResponse = new Date()) => {
	const lookupIds = [...new Set([
		canonicalId,
		...(identifiers || [])
	].map((value) => String(value || '').trim()).filter(Boolean))];

	const existingRecord = await loadAutoresponderResponseRecord(instance_id, lookupIds);
	if (existingRecord) {
		const nextWhatsapp = canonicalId || existingRecord.whatsapp;
		if (canonicalId && existingRecord.whatsapp !== canonicalId) {
			console.log('[AUTORESPONDER] Promoting legacy delay identity', {
				instance_id,
				from: existingRecord.whatsapp,
				to: canonicalId
			});
		}

		await Common.db_update("sp_whatsapp_ar_responses", [{
			whatsapp: nextWhatsapp,
			last_response: lastResponse
		}, { id: existingRecord.id }]);

		return {
			...existingRecord,
			whatsapp: nextWhatsapp,
			last_response: lastResponse
		};
	}

	const insertResult = await Common.db_insert("sp_whatsapp_ar_responses", {
		whatsapp: canonicalId,
		instance_id: instance_id,
		last_response: lastResponse
	});

	return {
		id: insertResult?.insertId || 0,
		whatsapp: canonicalId,
		instance_id: instance_id,
		last_response: lastResponse
	};
};

const getMetaApprovedTemplateRow = async ({ team_id, account_ids, source_template_type, source_template_ids }) => {
	try {
		if (!team_id || !account_ids || !source_template_type || !source_template_ids) return null;
		const sql =
			"SELECT id, data FROM sp_whatsapp_template " +
			" WHERE team_id = ? AND type = 66 " +
			"   AND LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')))) = LOWER(TRIM(?)) " +
			"   AND LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')))) = LOWER(TRIM(?)) " +
			"   AND LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')))) = LOWER(TRIM(?)) " +
			"   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) = 'APPROVED' " +
			" ORDER BY changed DESC LIMIT 1";

		const rows = await Common.db_query(
			sql,
			[
				team_id,
				String(account_ids),
				String(source_template_type),
				String(source_template_ids)
			],
			false
		);
		if (!rows || !rows[0]) {
			bulkMetaDebugLog({
				event: 'getMetaApprovedTemplateRow_not_found',
				team_id,
				account_ids: String(account_ids),
				source_template_type: String(source_template_type),
				source_template_ids: String(source_template_ids)
			});
			return null;
		}
		return rows[0];
	} catch (e) {
		bulkMetaDebugLog({
			event: 'getMetaApprovedTemplateRow_error',
			team_id,
			account_ids: String(account_ids),
			source_template_type: String(source_template_type),
			source_template_ids: String(source_template_ids),
			error: e.message
		});
		return null;
	}
};

const resolveProxyForInstance = async (instanceId) => {
	try {
		const account = await Common.db_get('sp_accounts', [{ token: instanceId }]);
		if (!account) {
			return { proxyString: null, agent: null };
		}

		let proxyString = null;

		if (account.proxy) {
			if (/^\d+$/.test(String(account.proxy))) {
				const proxyRecord = await Common.db_get('sp_proxies', [{ id: account.proxy }]);
				proxyString = proxyRecord ? proxyRecord.proxy : null;
			} else {
				proxyString = account.proxy;
			}
		}

		const agent = createProxyAgent(proxyString);

		return { proxyString, agent };
	} catch (error) {
		console.error('Failed to resolve proxy for instance', instanceId, error);
		return { proxyString: null, agent: null };
	}
};

const bulks = {};
const total_contacts = {};
const chatbots = {};
const limit_messages = {};
const stats_history = {};
const cloudParallelCapabilityCache = new NodeCache({ stdTTL: 120, checkperiod: 60 });
const CLOUD_PARALLEL_MAX_LEVEL = 100;
const CLOUD_PARALLEL_SAFE_STANDARD_CAP = 80;
const CLOUD_PARALLEL_RUN_LOCK_SECONDS = 300;
const CLOUD_PARALLEL_BACKOFF_SECONDS = 120;
const CLOUD_PARALLEL_STALE_PROCESSING_SECONDS = 600;
const BULK_SCHEDULE_LOOKAHEAD_DAYS = 366;
const sessions = {};
const new_sessions = {};
const connecting = {}; // New connecting tracking
const session_dir = __dirname + '/../sessions/';
let verify_next = 0;
let verify_response = false;
let verified = false;
let chatbot_delay = 1000;
let wa_version = '';
let retry_on_fail = {};
const autoresponder_recent_messages = new Map();
const AUTORESPONDER_DEDUPE_TTL_MS = 30 * 1000;
const AUTORESPONDER_DEDUPE_MAX_ITEMS = 5000;

const normalizeCloudParallelPhone = (value = '') => {
	const rawValue = String(value || '').replaceAll('.00', '').trim();
	if (!rawValue) {
		return '';
	}

	if (rawValue.includes('g.us')) {
		return rawValue;
	}

	let normalized = rawValue.replace(/\D/g, '');
	if (!normalized) {
		return '';
	}

	if (normalized.startsWith('55')) {
		const ddd = parseInt(normalized.substring(2, 4), 10);
		if (!Number.isNaN(ddd) && ddd >= 31 && normalized.length >= 13) {
			normalized = normalized.substring(0, 4) + normalized.substring(5);
		}
	}

	if (normalized.startsWith('52') && normalized.length === 12 && normalized.substring(2, 3) !== '1') {
		normalized = normalized.substring(0, 2) + '1' + normalized.substring(2);
	}

	return normalized;
};

const executeDb = (sql, params = []) => {
	return new Promise((resolve) => {
		Common.db_connect.query(sql, params, (err, res) => {
			if (err) {
				console.error('[CLOUD PARALLEL][DB] Query failed', err.message, { sql });
			}
			resolve(res);
		});
	});
};
const cleanup_autoresponder_dedupe = () => {
	if (autoresponder_recent_messages.size === 0) return;

	const now = Date.now();
	for (const [key, expires_at] of autoresponder_recent_messages.entries()) {
		if (expires_at <= now) {
			autoresponder_recent_messages.delete(key);
		}
	}

	if (autoresponder_recent_messages.size <= AUTORESPONDER_DEDUPE_MAX_ITEMS) return;

	const overflow = autoresponder_recent_messages.size - AUTORESPONDER_DEDUPE_MAX_ITEMS;
	let removed = 0;
	for (const key of autoresponder_recent_messages.keys()) {
		autoresponder_recent_messages.delete(key);
		removed++;
		if (removed >= overflow) break;
	}
};


var CHATBOT_RESET_TIME = config.time_to_reset ?? 120;
let chatbot_latest_receive = moment().add(CHATBOT_RESET_TIME * 4, 'm');

const io = new Server(server, {
	cors: {
		origin: config.frontend,
	}
});

app.use(bodyParser.urlencoded({
	extended: true,
	limit: '50mb'
}));

app.use(bodyParser.json({
	verify: (req, res, buf, encoding) => {
		try {
			req.rawBody = buf ? buf.toString(encoding || 'utf8') : '';
		} catch (error) {
			req.rawBody = '';
		}
	}
}));


io.on('connection', (socket) => {
	console.log('new connection');
});

const Device = (os.platform() === 'win32') ? 'Windows' : (os.platform() === 'darwin') ? 'MacOS' : 'Linux'

const {
	default: makeWASocket,
	areJidsSameUser,
	BufferJSON,
	useMultiFileAuthState,
	DisconnectReason,
	fetchLatestWaWebVersion,
	WAMessageStubType,
	Browsers,
	makeInMemoryStore,


	AnyMessageContent,
	BufferedEventData,

	CacheStore,
	Chat,
	ConnectionState,
	Contact,
	delay,

	downloadMediaMessage,

	generateWAMessageFromContent,
	getAggregateVotesInPollMessage,
	getContentType,
	getDevice,
	GroupMetadata,
	isJidBroadcast,
	isJidGroup,
	isJidUser,
	jidNormalizedUser,
	makeCacheableSignalKeyStore,
	MessageUpsertType,
	MiscMessageGenerationOptions,
	ParticipantAction,
	PHONENUMBER_MCC,
	prepareWAMessageMedia,
	proto,

	UserFacingSocketConfig,
	WABrowserDescription,
	WAMediaUpload,
	WAMessage,
	WAMessageUpdate,
	WAPresence,
	WASocket,
} = require('@itsukichan/baileys')

const CALLRESPONDER_DEBUG_ENABLED = process.env.CALLRESPONDER_DEBUG === "1";
const CALLRESPONDER_TRACE_INSTANCE = String(process.env.CALLRESPONDER_TRACE_INSTANCE || "").trim();
const CALLRESPONDER_TRACE_ALL_FRAMES = process.env.CALLRESPONDER_TRACE_ALL_FRAMES === "1";
const callResponderRuntime = createCallResponderRuntime({
	Common,
	fs,
	path,
	session_dir,
	areJidsSameUser,
	jidNormalizedUser,
});
const {
	parse_call_node,
	summarize_call_node,
	summarize_call_payload,
	get_callresponder_log_payload,
	log_callresponder_result,
	get_callresponder_item,
	get_callresponder_except_data,
	get_callresponder_state,
	insert_callresponder_history_event,
	is_callresponder_peer_same_as_session,
	is_callresponder_except_match,
	get_session_user_ids,
	is_same_jid_user,
	get_callcampaign_state,
	track_call_node_metadata,
	normalize_call_event,
} = callResponderRuntime;


var store = {};
var loggerBaileys = P({ timestamp: () => `,"time":"${new Date().toJSON()}"` }).child({})
loggerBaileys.level = "fatal";

var stores = {}

const isCallResponderTraceInstance = (instance_id) => {
	return CALLRESPONDER_DEBUG_ENABLED &&
		CALLRESPONDER_TRACE_INSTANCE !== "" &&
		CALLRESPONDER_TRACE_INSTANCE === String(instance_id || "").trim();
};

const normalizeCallResponderTraceValue = (value) => {
	if (value == null) {
		return "";
	}

	if (typeof value === "string") {
		return value;
	}

	if (typeof value === "number" || typeof value === "boolean") {
		return String(value);
	}

	return "";
};

const collectCallResponderFrameHints = (value, hints = new Set(), depth = 0) => {
	if (!value || depth > 3 || hints.size >= 16) {
		return hints;
	}

	if (Array.isArray(value)) {
		for (const item of value) {
			collectCallResponderFrameHints(item, hints, depth + 1);
			if (hints.size >= 16) break;
		}
		return hints;
	}

	if (typeof value === "object") {
		for (const [key, nestedValue] of Object.entries(value)) {
			collectCallResponderFrameHints(key, hints, depth + 1);
			collectCallResponderFrameHints(nestedValue, hints, depth + 1);
			if (hints.size >= 16) break;
		}
		return hints;
	}

	const normalized = normalizeCallResponderTraceValue(value).trim();
	if (!normalized) {
		return hints;
	}

	const lower = normalized.toLowerCase();
	const interestingPatterns = [
		"call",
		"offer",
		"reject",
		"accept",
		"terminate",
		"timeout",
		"relaylatency",
		"call-id",
		"call-creator",
		"group-jid",
		"video"
	];

	for (const pattern of interestingPatterns) {
		if (lower.includes(pattern)) {
			hints.add(normalized.slice(0, 120));
			break;
		}
	}

	return hints;
};

const summarizeCallResponderRawFrame = (frame) => {
	if (!frame || frame instanceof Uint8Array) {
		return {
			frameType: frame instanceof Uint8Array ? "binary" : "empty"
		};
	}

	const attrs = frame.attrs || {};
	const content = Array.isArray(frame.content) ? frame.content : [];
	const firstChild = content[0];
	const hints = Array.from(collectCallResponderFrameHints({
		tag: frame.tag,
		attrs,
		firstChildTag: firstChild?.tag,
		firstChildAttrs: firstChild?.attrs || {}
	}));

	return {
		frameType: "node",
		tag: frame.tag || "",
		attrs: {
			id: attrs.id || "",
			from: attrs.from || "",
			to: attrs.to || "",
			type: attrs.type || "",
			class: attrs.class || "",
			xmlns: attrs.xmlns || "",
			t: attrs.t || "",
			offline: attrs.offline || ""
		},
		firstChildTag: firstChild?.tag || "",
		firstChildAttrs: {
			from: firstChild?.attrs?.from || "",
			to: firstChild?.attrs?.to || "",
			type: firstChild?.attrs?.type || "",
			"call-id": firstChild?.attrs?.["call-id"] || "",
			"call-creator": firstChild?.attrs?.["call-creator"] || "",
			"group-jid": firstChild?.attrs?.["group-jid"] || ""
		},
		contentCount: content.length,
		callHints: hints
	};
};

const shouldTraceCallResponderRawFrame = (summary) => {
	if (!summary || summary.frameType !== "node") {
		return false;
	}

	if (CALLRESPONDER_TRACE_ALL_FRAMES) {
		return true;
	}

	if ((summary.callHints || []).length > 0) {
		return true;
	}

	return ["call", "notification", "receipt", "ack", "ib"].includes(summary.tag);
};

const isGroupJid = (jid) => {
	if (!jid) return false;
	const normalized = jid.toString().trim();

	if (isJidBroadcast?.(normalized)) return true;
	if (isJidGroup?.(normalized)) return true;

	const groupHints = ["@g.us", "@temp", "@broadcast", "-g.us"];
	const userHints = ["@s.whatsapp.net", "@c.us"];

	for (const suffix of userHints) {
		if (normalized.endsWith(suffix)) {
			return false;
		}
	}

	for (const suffix of groupHints) {
		if (normalized.includes(suffix)) {
			return true;
		}
	}

	return normalized.includes("-");
};

const APP_ROOT_PATH = path.resolve(__dirname, '..', '..');

const WAZIPER = {
	io: io,
	app: app,
	server: server,
	sessions: sessions,
	chatbot_latest_receive: chatbot_latest_receive,
	cors: cors(config.cors),

	resolve_baileys_media_path: function (media_url) {
		if (!media_url || typeof media_url !== "string") {
			return { resolvedUrl: media_url, isLocal: false, missingLocal: false };
		}

		const source = media_url.trim();
		if (!source) {
			return { resolvedUrl: media_url, isLocal: false, missingLocal: false };
		}

		const resolve_local_path = (pathname) => {
			let decoded_path = pathname || "/";
			try {
				decoded_path = decodeURIComponent(decoded_path);
			} catch (error) { }

			const absolute_path = path.resolve(APP_ROOT_PATH, "." + decoded_path);
			if (!absolute_path.startsWith(APP_ROOT_PATH + path.sep) && absolute_path !== APP_ROOT_PATH) {
				return { resolvedUrl: source, isLocal: false, missingLocal: false };
			}

			if (fs.existsSync(absolute_path)) {
				return { resolvedUrl: absolute_path, isLocal: true, missingLocal: false, localPath: absolute_path };
			}

			return { resolvedUrl: source, isLocal: false, missingLocal: true, localPath: absolute_path };
		};

		if (/^https?:\/\//i.test(source)) {
			try {
				const media_parsed = new URL(source);
				let frontend_host = null;
				try {
					frontend_host = new URL(config.frontend).host;
				} catch (error) { }

				if (frontend_host && media_parsed.host === frontend_host) {
					return resolve_local_path(media_parsed.pathname);
				}
			} catch (error) {
				return { resolvedUrl: source, isLocal: false, missingLocal: false };
			}

			return { resolvedUrl: source, isLocal: false, missingLocal: false };
		}

		if (source.startsWith("/")) {
			return resolve_local_path(source);
		}

		if (path.isAbsolute(source)) {
			if (fs.existsSync(source)) {
				return { resolvedUrl: source, isLocal: true, missingLocal: false, localPath: source };
			}

			return { resolvedUrl: source, isLocal: false, missingLocal: true, localPath: source };
		}

		return { resolvedUrl: source, isLocal: false, missingLocal: false };
	},

	check_remote_media_url: async function (media_url) {
		if (!media_url || typeof media_url !== "string" || !/^https?:\/\//i.test(media_url)) {
			return { ok: false, reason: "invalid_url" };
		}

		const accepted_prefixes = [
			"audio/",
			"video/",
			"image/",
			"application/octet-stream",
			"application/ogg"
		];

		const is_accepted_content_type = (content_type) => {
			if (!content_type || typeof content_type !== "string") {
				return false;
			}
			const normalized = content_type.toLowerCase();
			return accepted_prefixes.some((prefix) => normalized.startsWith(prefix));
		};

		const http_options = {
			timeout: 12000,
			maxRedirects: 5,
			validateStatus: () => true
		};

		try {
			const head_response = await axios.head(media_url, http_options);
			const head_status = head_response?.status || 0;
			const head_type = (head_response?.headers?.["content-type"] || "").toString();

			if (head_status >= 200 && head_status < 400 && is_accepted_content_type(head_type)) {
				return { ok: true, via: "head", status: head_status, contentType: head_type };
			}
		} catch (error) {
		}

		try {
			const get_response = await axios.get(media_url, {
				...http_options,
				responseType: "stream"
			});
			const get_status = get_response?.status || 0;
			const get_type = (get_response?.headers?.["content-type"] || "").toString();

			try {
				if (get_response?.data && typeof get_response.data.destroy === "function") {
					get_response.data.destroy();
				}
			} catch (error) {
			}

			if (get_status >= 200 && get_status < 400 && is_accepted_content_type(get_type)) {
				return { ok: true, via: "get", status: get_status, contentType: get_type };
			}

			return { ok: false, status: get_status, contentType: get_type };
		} catch (error) {
			return { ok: false, reason: error?.message || "remote_check_failed" };
		}
	},

		prepare_baileys_media_payload: async function (payload) {
			if (!payload || typeof payload !== "object") {
				return { ok: true, payload: payload };
			}

		const media_field = ["audio", "video", "image", "document"].find((field) => {
			return payload[field] && typeof payload[field] === "object" && typeof payload[field].url === "string";
		});

		if (!media_field) {
			return { ok: true, payload: payload };
		}

		const media_resolution = WAZIPER.resolve_baileys_media_path(payload[media_field].url);
		if (media_resolution.missingLocal) {
			const remote_check = await WAZIPER.check_remote_media_url(payload[media_field].url);
			if (remote_check.ok) {
				return {
					ok: true,
					payload: payload,
					usedRemoteUrl: payload[media_field].url,
					remoteInfo: remote_check
				};
			}

			const type_info = remote_check?.contentType ? ` (remote content-type: ${remote_check.contentType})` : "";
			const status_info = remote_check?.status ? ` (remote status: ${remote_check.status})` : "";
			return {
				ok: false,
				error: `Media file not found on server: ${media_resolution.localPath || payload[media_field].url}${status_info}${type_info}`
			};
		}

		if (media_resolution.isLocal) {
			return {
				ok: true,
				payload: {
					...payload,
					[media_field]: {
						...payload[media_field],
						url: media_resolution.resolvedUrl
					}
				},
				usedLocalPath: media_resolution.localPath
			};
		}

			return { ok: true, payload: payload };
		},

		normalize_baileys_button_entry: function (button, index = 0) {
			if (!button || typeof button !== "object") {
				return null;
			}

			const parseParams = (raw) => {
				if (!raw) return {};
				if (typeof raw === "object") return raw;
				try {
					return JSON.parse(String(raw));
				} catch (error) {
					return {};
				}
			};

			const limitText = (value, limit, fallback = "") => {
				const text = String(value || fallback || "").trim();
				return text.substring(0, limit);
			};

			const isDisabled = (value) => {
				if (value === true || value === 1) return true;
				if (typeof value === "string") {
					return ["1", "true", "sim", "yes"].includes(value.trim().toLowerCase());
				}
				return false;
			};

			let name = String(button.name || "").trim();
			let params = parseParams(button.buttonParamsJson);

			if (button.quickReplyButton) {
				name = "quick_reply";
				params = {
					id: button.quickReplyButton.id || button.quickReplyButton.displayText,
					display_text: button.quickReplyButton.displayText,
					disabled: false
				};
			} else if (button.urlButton) {
				name = "cta_url";
				params = {
					id: button.urlButton.id || button.urlButton.displayText,
					display_text: button.urlButton.displayText,
					url: button.urlButton.url,
					merchant_url: button.urlButton.url,
					disabled: false
				};
			} else if (button.callButton) {
				name = "cta_call";
				params = {
					id: button.callButton.id || button.callButton.displayText,
					display_text: button.callButton.displayText,
					phone_number: button.callButton.phoneNumber,
					disabled: false
				};
			} else if (button.reply || button.type === "reply" || button.type === 1) {
				const reply = button.reply || button;
				name = "quick_reply";
				params = {
					id: reply.id || reply.buttonId || reply.title || reply.display_text,
					display_text: reply.title || reply.display_text || reply.displayText || reply.buttonText?.displayText,
					disabled: false
				};
			} else if (button.type === "url" || button.type === "cta_url" || button.url) {
				const urlPayload = typeof button.url === "object" ? button.url : button;
				name = "cta_url";
				params = {
					id: urlPayload.id || urlPayload.displayText || urlPayload.display_text,
					display_text: urlPayload.displayText || urlPayload.display_text || urlPayload.buttonText?.displayText,
					url: urlPayload.url || button.url,
					merchant_url: urlPayload.url || button.url,
					disabled: false
				};
			} else if (button.type === "call" || button.type === "cta_call") {
				name = "cta_call";
				params = {
					id: button.id || button.displayText || button.display_text,
					display_text: button.displayText || button.display_text || button.call?.displayText || button.buttonText?.displayText,
					phone_number: button.phoneNumber || button.phone_number || button.call?.phoneNumber,
					disabled: false
				};
			}

			if (!name) {
				return null;
			}

			const displayText = limitText(
				params.display_text || params.displayText || params.title || params.text,
				20,
				`Opção ${index + 1}`
			);
			const id = limitText(params.id || params.button_id || params.buttonId || displayText || `bb_btn_${index + 1}`, 64, `bb_btn_${index + 1}`);

			if (name === "quick_reply") {
				return {
					button: {
						name: "quick_reply",
						buttonParamsJson: JSON.stringify({
							display_text: displayText,
							id: id,
							disabled: isDisabled(params.disabled)
						})
					},
					label: displayText,
					id: id,
					action: "reply"
				};
			}

			if (name === "cta_url") {
				const url = String(params.url || params.merchant_url || "").trim();
				if (!url) return null;
				return {
					button: {
						name: "cta_url",
						buttonParamsJson: JSON.stringify({
							display_text: displayText || "Abrir link",
							url: url,
							merchant_url: url,
							id: id,
							disabled: isDisabled(params.disabled)
						})
					},
					label: displayText || "Abrir link",
					id: id,
					action: "url",
					url: url
				};
			}

			if (name === "cta_call") {
				const phoneNumber = String(params.phone_number || params.phoneNumber || "").replace(/[^0-9+]/g, "");
				if (!phoneNumber) return null;
				return {
					button: {
						name: "cta_call",
						buttonParamsJson: JSON.stringify({
							display_text: displayText || "Ligar",
							phone_number: phoneNumber,
							id: id,
							disabled: isDisabled(params.disabled)
						})
					},
					label: displayText || "Ligar",
					id: id,
					action: "call",
					phone_number: phoneNumber
				};
			}

			if (name === "cta_copy") {
				const copyCode = String(params.copy_code || params.code || "").trim();
				if (!copyCode) return null;
				return {
					button: {
						name: "cta_copy",
						buttonParamsJson: JSON.stringify({
							display_text: displayText || "Copiar código",
							copy_code: copyCode,
							id: id,
							disabled: isDisabled(params.disabled)
						})
					},
					label: displayText || "Copiar código",
					id: id,
					action: "copy",
					copy_code: copyCode
				};
			}

			if (button.buttonParamsJson) {
				return {
					button: {
						name: name,
						buttonParamsJson: typeof button.buttonParamsJson === "string" ? button.buttonParamsJson : JSON.stringify(button.buttonParamsJson)
					},
					label: displayText || name,
					id: id,
					action: name
				};
			}

			return null;
		},

		build_baileys_button_text_fallback: function (bodyText, normalizedButtons) {
			const baseText = String(bodyText || "Escolha uma opção:").trim() || "Escolha uma opção:";
			const lines = (normalizedButtons || []).map((entry, index) => {
				let line = `${index + 1}. ${entry.label || entry.id || `Opção ${index + 1}`}`;
				if (entry.action === "url" && entry.url) line += ` - ${entry.url}`;
				if (entry.action === "call" && entry.phone_number) line += ` - ${entry.phone_number}`;
				if (entry.action === "copy" && entry.copy_code) line += ` - ${entry.copy_code}`;
				return line;
			}).filter(Boolean);

			if (!lines.length) {
				return baseText;
			}

			return `${baseText}\n\n${lines.join("\n")}\n\nResponda com o número ou com o nome da opção.`;
		},

		build_baileys_interactive_button_payload: function (payload) {
			if (!payload || typeof payload !== "object") {
				return null;
			}

			const buttonsSource = payload.interactiveButtons
				|| payload.templateButtons
				|| payload.buttons
				|| payload.interactive?.action?.buttons;

			if (!Array.isArray(buttonsSource) || buttonsSource.length === 0) {
				return null;
			}

			const normalizedButtons = buttonsSource
				.map((button, index) => WAZIPER.normalize_baileys_button_entry(button, index))
				.filter(Boolean)
				.slice(0, 10);

			if (!normalizedButtons.length) {
				return null;
			}

			const bodyText = String(payload.text || payload.caption || payload.interactive?.body?.text || "Escolha uma opção:").trim() || "Escolha uma opção:";
			const footerText = String(payload.footer || payload.interactive?.footer?.text || "").trim();
			const headerTitle = String(payload.title || payload.interactive?.header?.title || "").trim();
			const baileysPayload = {
				text: bodyText,
				interactiveButtons: normalizedButtons.map((entry) => entry.button)
			};

			if (footerText) {
				baileysPayload.footer = footerText;
			}

			if (headerTitle) {
				baileysPayload.title = headerTitle;
			}

			if (payload.subtitle) {
				baileysPayload.subtitle = String(payload.subtitle || "").trim();
			}

			const attachHeaderMedia = (field, messageField) => {
				const media = payload[field] || payload.interactive?.header?.[messageField];
				if (!media) return false;
				baileysPayload[field] = typeof media === "string" ? { url: media } : media;
				baileysPayload.caption = bodyText;
				baileysPayload.hasMediaAttachment = true;
				delete baileysPayload.text;
				return true;
			};

			attachHeaderMedia("image", "imageMessage")
				|| attachHeaderMedia("video", "videoMessage")
				|| attachHeaderMedia("document", "documentMessage");

			return {
				payload: baileysPayload,
				fallbackText: WAZIPER.build_baileys_button_text_fallback(bodyText, normalizedButtons),
				buttons: normalizedButtons
			};
		},

		upload_cloud_media: async function (phone_number_id, bearer, local_path, mime_type = "", filename = "") {
		if (!phone_number_id || !bearer) {
			return { ok: false, error: "Cloud media upload failed: missing phone_number_id or bearer token" };
		}

		if (!local_path || !fs.existsSync(local_path)) {
			return { ok: false, error: `Cloud media upload failed: file not found (${local_path})` };
		}

		const safe_filename = (filename && typeof filename === "string" && filename.trim() !== "")
			? filename.trim()
			: path.basename(local_path);

		const upload_url = `https://graph.facebook.com/v19.0/${phone_number_id}/media`;
		const form = new FormData();
		form.append("messaging_product", "whatsapp");
		form.append("file", fs.createReadStream(local_path), {
			filename: safe_filename,
			contentType: mime_type || undefined
		});

		try {
			const response = await axios.post(upload_url, form, {
				headers: {
					Authorization: `Bearer ${bearer}`,
					...form.getHeaders()
				},
				timeout: 60000,
				maxBodyLength: Infinity,
				maxContentLength: Infinity,
				validateStatus: () => true
			});

			const media_id = response?.data?.id || null;
			if (response.status >= 200 && response.status < 300 && media_id) {
				return { ok: true, mediaId: media_id };
			}

			return {
				ok: false,
				error: `Cloud media upload failed: status ${response?.status || 0}`,
				details: response?.data
			};
		} catch (error) {
			const meta_error = error?.response?.data;
			const meta_message = meta_error?.error?.message || error?.message || "cloud_media_upload_failed";
			return {
				ok: false,
				error: `Cloud media upload failed: ${meta_message}`,
				details: meta_error
			};
		}
	},

	resolve_cloud_media_reference: async function (media_url, phone_number_id, bearer, mime_type = "", filename = "") {
		if (!media_url || typeof media_url !== "string") {
			return { ok: false, error: "Cloud media URL is invalid" };
		}

		const source = media_url.trim();
		if (!source) {
			return { ok: false, error: "Cloud media URL is invalid" };
		}

		const upload_from_local = async (local_path) => {
			const upload_result = await WAZIPER.upload_cloud_media(phone_number_id, bearer, local_path, mime_type, filename);
			if (!upload_result.ok) {
				return upload_result;
			}

			return {
				ok: true,
				by: "id",
				value: upload_result.mediaId,
				debug: {
					mode: "uploaded_local",
					localPath: local_path
				}
			};
		};

		const media_resolution = WAZIPER.resolve_baileys_media_path(source);

		if (media_resolution.isLocal) {
			return upload_from_local(media_resolution.localPath || media_resolution.resolvedUrl);
		}

		if (media_resolution.missingLocal) {
			const remote_check = await WAZIPER.check_remote_media_url(source);
			if (remote_check.ok) {
				return {
					ok: true,
					by: "link",
					value: source,
					debug: {
						mode: "remote_link",
						remote: remote_check
					}
				};
			}

			const type_info = remote_check?.contentType ? ` (remote content-type: ${remote_check.contentType})` : "";
			const status_info = remote_check?.status ? ` (remote status: ${remote_check.status})` : "";
			return {
				ok: false,
				error: `Media file not found on server: ${media_resolution.localPath || source}${status_info}${type_info}`
			};
		}

		if (/^https?:\/\//i.test(source)) {
			const remote_check = await WAZIPER.check_remote_media_url(source);
			if (remote_check.ok) {
				return {
					ok: true,
					by: "link",
					value: source,
					debug: {
						mode: "remote_link",
						remote: remote_check
					}
				};
			}

			try {
				const parsed_url = new URL(source);
				let decoded_path = parsed_url.pathname || "/";
				try {
					decoded_path = decodeURIComponent(decoded_path);
				} catch (error) { }

				const fallback_local_path = path.resolve(APP_ROOT_PATH, "." + decoded_path);
				if (fallback_local_path.startsWith(APP_ROOT_PATH + path.sep) && fs.existsSync(fallback_local_path)) {
					return upload_from_local(fallback_local_path);
				}
			} catch (error) { }

			const type_info = remote_check?.contentType ? ` (remote content-type: ${remote_check.contentType})` : "";
			const status_info = remote_check?.status ? ` (remote status: ${remote_check.status})` : "";
			return {
				ok: false,
				error: `Cloud media URL is not accessible: ${source}${status_info}${type_info}`
			};
		}

		const relative_local_path = path.resolve(APP_ROOT_PATH, source);
		if (relative_local_path.startsWith(APP_ROOT_PATH + path.sep) && fs.existsSync(relative_local_path)) {
			return upload_from_local(relative_local_path);
		}

		if (path.isAbsolute(source) && fs.existsSync(source)) {
			return upload_from_local(source);
		}

		return { ok: false, error: `Cloud media path is invalid or missing: ${source}` };
	},

	makeWASocket: async function (instance_id) {
		if (connecting[instance_id]) {
			console.log(`Connection for instance ${instance_id} is already in progress. Waiting...`);
			return connecting[instance_id];
		}

		connecting[instance_id] = (async () => {
			try {
				console.error('creating socket', instance_id, session_dir)

				const { state, saveCreds } = await useMultiFileAuthState(session_dir + instance_id);

				if (!stores[instance_id]) {
					stores[instance_id] = makeInMemoryStore({ logger: loggerBaileys });
				}

				const { proxyString, agent: instanceProxyAgent } = await resolveProxyForInstance(instance_id);
				if (proxyString && !instanceProxyAgent) {
					console.warn('Proxy configurado, mas não foi possível criar agente. Instância:', instance_id, proxyString);
				}
				if (instanceProxyAgent) {
					console.log('Aplicando proxy para instância', instance_id, proxyString);
				}

				const WA = makeWASocket({
					auth: state,
					printQRInTerminal: false,
					version: [2, 3000, 1035418183],
					browser: [Device, 'Chrome', '96.0.4664.110'],
					logger: P({ level: 'silent' }),
					receivedPendingNotifications: true,
					retryRequestDelayMs: 10,
					connectTimeoutMs: 60000,
					qrTimeout: 40000,
					defaultQueryTimeoutMs: undefined,
					emitOwnEvents: false,
					generateHighQualityLinkPreview: true,
					msgRetryCounterCache: new NodeCache(),
					userDevicesCache: new NodeCache(),
					transactionOpts: { maxCommitRetries: 10, delayBetweenTriesMs: 10 },
					agent: instanceProxyAgent || undefined,
					/*patchMessageBeforeSending(message) {
						if (message.deviceSentMessage?.message?.listMessage?.listType === proto.Message.ListMessage.ListType.PRODUCT_LIST) {
							message = JSON.parse(JSON.stringify(message));
							//message.deviceSentMessage.message.listMessage.listType = proto.Message.ListMessage.ListType.SINGLE_SELECT;
							message.deviceSentMessage.message.listMessage = {
								title: message.listMessage.title,
								description: message.listMessage.description,
								buttonText: message.listMessage.buttonText,
								footerText: message.listMessage.footerText,
								sections: message.listMessage.sections,
								listType: proto.Message.ListMessage.ListType.SINGLE_SELECT
							}
						    
						}
						if (message.listMessage?.listType == proto.Message.ListMessage.ListType.PRODUCT_LIST) {
							message = JSON.parse(JSON.stringify(message));
							//message.listMessage.listType = proto.Message.ListMessage.ListType.SINGLE_SELECT;
							message.listMessage = {
								title: message.listMessage.title,
								description: message.listMessage.description,
								buttonText: message.listMessage.buttonText,
								footerText: message.listMessage.footerText,
								sections: message.listMessage.sections,
								listType: proto.Message.ListMessage.ListType.SINGLE_SELECT
							}
						    
						}
					    
					    
						console.log(message)
						return message;
					},*/
				});

				await WA.ev.on('connection.update', async ({ connection, lastDisconnect, isNewLogin, qr, receivedPendingNotifications }) => {
					/*
					* Get QR COde
					*/
					if (qr != undefined) {
						WA.qrcode = qr;
						if (new_sessions[instance_id] == undefined)
							new_sessions[instance_id] = new Date().getTime() / 1000 + 300;
					}

					/*
					* Login successful
					*/
					if (isNewLogin) {

						/*
						* Reload session after login successful
						*/
						await WAZIPER.makeWASocket(instance_id);

					}

					if (lastDisconnect != undefined && lastDisconnect.error != undefined) {
						var statusCode = lastDisconnect.error.output.statusCode;
						if (DisconnectReason.restartRequired == statusCode || DisconnectReason.connectionClosed == statusCode) {
							await WAZIPER.makeWASocket(instance_id);
						}
					}

					/*
					* Connection status
					*/
					switch (connection) {
						case "close":
							/*
							* 401 Unauthorized
							*/
							if (lastDisconnect.error != undefined) {
								var statusCode = lastDisconnect.error.output.statusCode;
								if (DisconnectReason.loggedOut == statusCode || 0 == statusCode) {
									var SESSION_PATH = session_dir + instance_id;
									if (fs.existsSync(SESSION_PATH)) {
										rimraf.sync(SESSION_PATH);
										delete sessions[instance_id];
										delete chatbots[instance_id];
										delete bulks[instance_id];
									}

									await WAZIPER.session(instance_id);
								}
							}
							break;

						case "open":
							// Reload WASocket
							if (WA.user.name == undefined) {
								WA.user.name = WA.user?.id ? Common.get_phone(WA.user?.id, "wid") : instance_id;
							}

							sessions[instance_id] = WA;

							// Remove QR code
							//if (sessions[instance_id].qrcode != undefined) {
							console.log('remove new session qr')
							delete sessions[instance_id].qrcode;
							delete new_sessions[instance_id];
							//}

							// Add account
							var session = await Common.db_get("sp_whatsapp_sessions", [{ instance_id: instance_id }, { status: 0 }]);
							if (session) {
								// Get avatar 
								WA.user.avatar = await WAZIPER.get_avatar(WA);

								var account = await Common.db_get("sp_accounts", [{ token: instance_id }]);
								if (!account) {
									account = await Common.db_get("sp_accounts", [{ pid: Common.get_phone(WA.user.id, "wid") }, { team_id: session.team_id }]);
								}

								await Common.update_status_instance(instance_id, WA.user);
								await WAZIPER.add_account(instance_id, session.team_id, WA.user, account);
								await Common.update_creds(WA, instance_id, WA.user);
							}

							WA.store = stores[instance_id];
							stores[instance_id].bind(WA.ev);
							WA.ev.flush();

							break;

						default:
						// code block
					}
				});

				await WA.ev.on('messages.upsert', async (messages) => {
					WAZIPER.webhook(instance_id, { event: "messages.upsert", data: messages });
					try {
						require('./capture_incoming.js').captureIncomingPayload(messages);
					} catch(e) {}

					//console.log(messages.type);
					//* LIVECHAT FUNCTION
					if ((config['extended_functions'] ?? true)) {
						Extend.chat.processChatMessages(WAZIPER, sessions, messages, instance_id);
					}
					//* END LIVECHAT FUNCTION
					if (messages.messages != undefined && messages.type == 'notify' || messages.messages != undefined && messages.type == 'append') {
						messages = messages.messages;

						if (messages.length > 0) {
							for (var i = 0; i < messages.length; i++) {
								var message = messages[i];
								var chat_id = message.key.remoteJid;

								Extend.attachAutomationContext(message, instance_id, { instance_id: instance_id }, {
									source: Extend.isLidJid(chat_id) ? 'baileys_lid' : 'baileys'
								});

								if (message.key.fromMe === true && message.key.remoteJid != "status@broadcast" && message.message != undefined) {
									var user_type = "user";

									if (isGroupJid(chat_id)) {
										user_type = "group";
									}

									try {
										await Extend.disableBotKeyword(WAZIPER, instance_id, user_type, message);
									} catch (error) {
										//console.error(error);
									}
								}


								if (message.key.fromMe === false && message.key.remoteJid != "status@broadcast" && message.message != undefined) {
									var user_type = "user";

									if (isGroupJid(chat_id)) {
										user_type = "group";
									} else {
										console.log('incomming message', user_type, instance_id, chat_id);
									}

										const botBuilderHandled = await WAZIPER.bot_builder_flow(instance_id, chat_id, message).catch((error) => {
											console.error(`[BOT_BUILDER] Erro ao processar mensagem Baileys: ${error.message}`);
											return false;
										});

										if (!botBuilderHandled) {
											WAZIPER.chatbot(instance_id, user_type, message);
											await Common.sleep(1000);
											WAZIPER.autoresponder(instance_id, user_type, message);
										}
								} else if (message.key.remoteJid != "status@broadcast" && message.messageStubType != undefined && message.messageStubType == WAMessageStubType.CIPHERTEXT) {
									//console.error({ key: message.key, params: message.messageStubParameters }, 'failure in decrypting message');

									var user_type = "user";

									if (isGroupJid(chat_id)) {
										user_type = "group";
									}

									var item = await Common.db_get("sp_whatsapp_fail_decode_message", [{ instance_id: instance_id }, { status: 1 }]);

									if (item) {
										if (user_type == "user") {
											await WAZIPER.auto_send(instance_id, chat_id, chat_id, "faildecode", item, false, message, false, function (result) { });
										}
									} else {
										//await WAZIPER.autoresponder(instance_id, user_type, message);
									}
								}
								//Add Groups for Export participants
								if (message.message != undefined) {
									if (chat_id.includes("@g.us")) {
										if (sessions[instance_id].groups == undefined) {
											sessions[instance_id].groups = [];
										}
									} else {
										//await WAZIPER.autoresponder(instance_id, user_type, message);
									}
								}

								//Add Groups for Export participants
								if (message.message != undefined) {
									if (chat_id.includes("@g.us")) {
										if (sessions[instance_id].groups == undefined) {
											sessions[instance_id].groups = [];
										}

										var newGroup = true;
										sessions[instance_id].groups.forEach(async (group) => {
											if (group.id == chat_id) {
												newGroup = false;
											}
										});

										if (newGroup) {
											await WA.groupMetadata(chat_id).then(async (group) => {
												sessions[instance_id].groups.push({ id: group.id, name: group.subject, size: group.size, desc: group.desc, participants: group.participants });
											}).catch((err) => { });
										}
									}
								}


							}
						}
					}
				});

				await WA.ev.on('contacts.update', async (contacts) => {
					WAZIPER.webhook(instance_id, { event: "contacts.update", data: contacts });
				});

				await WA.ev.on('contacts.upsert', async (contacts) => {
					WAZIPER.webhook(instance_id, { event: "contacts.upsert", data: contacts });
				});

					await WA.ev.on('messages.update', async (messages) => {
						WAZIPER.webhook(instance_id, { event: "messages.update", data: messages });
					});

					if (isCallResponderTraceInstance(instance_id)) {
						WA.ws.on('frame', (frame) => {
							try {
								const summary = summarizeCallResponderRawFrame(frame);
								if (shouldTraceCallResponderRawFrame(summary)) {
									console.log("[CALLFRAME] received", {
										instance_id,
										...summary
									});
								}
							} catch (error) {
								console.error("[CALLFRAME] trace failed", instance_id, error?.message || error);
							}
						});
					}

					WA.ws.on('CB:call', async (node) => {
						let trackedCallEvent = null;

						try {
							trackedCallEvent = await WAZIPER.track_call_node(instance_id, node);
							if (CALLRESPONDER_DEBUG_ENABLED) {
								console.log("[CALLWS] received", {
									instance_id,
									...summarize_call_node(node),
									...(trackedCallEvent ? get_callresponder_log_payload(instance_id, trackedCallEvent, { session: WA }) : {})
								});
							}
						} catch (error) {
							console.error("[CALLRESPONDER] track_call_node failed", instance_id, error?.message || error);
						}

						WAZIPER.fast_callresponder(instance_id, node, trackedCallEvent).catch((error) => {
							console.error("[CALLRESPONDER] fast reject failed", instance_id, error?.message || error);
						});
					});

					WA.ev.on('call', async (call) => {
						let debugCallEvent = null;

						try {
							if (CALLRESPONDER_DEBUG_ENABLED) {
								debugCallEvent = await normalize_call_event(WA, instance_id, call);
								console.log("[CALLEV] received", {
									instance_id,
									...summarize_call_payload(call),
									...(debugCallEvent ? get_callresponder_log_payload(instance_id, debugCallEvent, { session: WA }) : {})
								});
							}

							WAZIPER.webhook(instance_id, { event: "call", data: call });
							await WAZIPER.callcampaign_event(instance_id, call);
							await WAZIPER.callresponder(instance_id, call);
						} catch (error) {
							if (CALLRESPONDER_DEBUG_ENABLED) {
								console.error("[CALLEV] handler failed", {
									instance_id,
									error: error?.message || String(error),
									...summarize_call_payload(call),
									...(debugCallEvent ? get_callresponder_log_payload(instance_id, debugCallEvent, { session: WA }) : {})
								});
							} else {
								console.error("[CALLRESPONDER] call event handler failed", instance_id, error?.message || error);
							}
						}

				});

				await WA.ev.on('groups.update', async (group) => {
					WAZIPER.webhook(instance_id, { event: "groups.update", data: group });
				});

				await WA.ev.on('creds.update', saveCreds);

				return WA;
			} catch (error) {
				console.error(`Error in makeWASocket for ${instance_id}:`, error);
			} finally {
				delete connecting[instance_id];
			}
		})();

		return connecting[instance_id];
	},

	session: async function (instance_id, reset) {
		if (sessions[instance_id] == undefined || reset) {
			sessions[instance_id] = await WAZIPER.makeWASocket(instance_id);
		}

		return sessions[instance_id];
	},

	get_ws_status: function (instance_id) {
		const client = sessions[instance_id];
		const candidates = [
			client?.ws?.readyState,
			client?.ws?.socket?._readyState,
			client?.ws?._readyState
		];

		for (const status of candidates) {
			if (typeof status === "number") {
				return status;
			}
		}

		return undefined;
	},

	ensure_session_ready: async function (instance_id, force_reset = false) {
		if (!sessions[instance_id] || force_reset) {
			sessions[instance_id] = await WAZIPER.session(instance_id, true);
		}

		let wsstatus = WAZIPER.get_ws_status(instance_id);
		if (wsstatus === 1) {
			return true;
		}

		// Wait briefly for sockets that are connecting/opening.
		for (let i = 0; i < 8; i++) {
			await Common.sleep(500);
			wsstatus = WAZIPER.get_ws_status(instance_id);
			if (wsstatus === 1) {
				return true;
			}
		}

		// One controlled reconnect attempt before giving up.
		sessions[instance_id] = await WAZIPER.session(instance_id, true);
		for (let i = 0; i < 12; i++) {
			await Common.sleep(500);
			wsstatus = WAZIPER.get_ws_status(instance_id);
			if (wsstatus === 1) {
				return true;
			}
		}

		return false;
	},

	instance: async function (access_token, instance_id, res, callback, reset = false) {
		var time_now = Math.floor(new Date().getTime() / 1000);

		if (instance_id == undefined && res != undefined) {
			if (res) {
				return res.json({ status: 'error', message: "The Instance ID must be provided for the process to be completed" });
			} else {
				console.error(instance_id, "The Instance ID must be provided for the process to be completed");
				return callback(false);
			}
		}

		var team = await Common.db_get("sp_team", [{ ids: access_token }]);

		if (!team) {
			if (res) {
				return res.json({ status: 'error', message: "The authentication process has failed" });
			} else {
				console.error(instance_id, "The authentication process has failed");
				// return callback(false);
			}
		}

		var account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: team.id }]);

		if (account && account.login_type == 1) {
			return callback(account);
		}

		if (connecting[instance_id]) {
			console.log(`Waiting for ${instance_id} to finish connecting...`);
			await connecting[instance_id];
		}

		var session = await Common.db_get("sp_whatsapp_sessions", [{ instance_id: instance_id }, { team_id: team.id }]);
		if (!session) {
			console.log('no record found on sp_whatsapp_sessions', instance_id, team.id);
			// Common.db_update("sp_accounts", [{ status: 0 }, { token: instance_id }]);
			if (res) {
				return res.json({ status: 'error', message: "The Instance ID provided has been invalidated" });
			} else {
				console.error(instance_id, "The Instance ID provided has been invalidated");
				return callback(false);
			}
		}

		sessions[instance_id] = await WAZIPER.session(instance_id, reset);
		return callback(sessions[instance_id]);
	},

	webhook: async function (instance_id, data) {
		var tb_webhook = await Common.db_query("SHOW TABLES LIKE 'sp_whatsapp_webhook'");
		if (tb_webhook) {
			var webhook = await Common.db_query("SELECT * FROM sp_whatsapp_webhook WHERE status = 1 AND instance_id = '" + instance_id + "'");
			if (webhook) {
				webhook.allowed_events = webhook.allowed_events ?? '';
				if (webhook.allowed_events == '' || webhook.allowed_events.includes(data.event)) {
					//console.log('trigger webhook', instance_id, data.event);
					axios.post(webhook.webhook_url, { instance_id: instance_id, data: data }).then((res) => { }).catch((err) => { });
				}
			}
		}
	},

	get_qrcode: async function (instance_id, res) {
		var client = sessions[instance_id];
		if (client == undefined) {
			return res.json({ status: 'error', message: "The WhatsApp session could not be found in the system" });
		}

		if (client.qrcode != undefined && !client.qrcode) {
			return res.json({ status: 'error', message: "It seems that you have logged in successfully" });
		}

		//Check QR code exist
		for (var i = 0; i < 10; i++) {
			if (client.qrcode == undefined) {
				await Common.sleep(1000);
			}
		}

		if (client.qrcode == undefined || client.qrcode == false) {
			return res.json({ status: 'error', message: "The system cannot generate a WhatsApp QR code" });
		}

		var code = qrimg.imageSync(client.qrcode, { type: 'png' });
		return res.json({ status: 'success', message: 'Success', base64: 'data:image/png;base64,' + code.toString('base64') });
	},

	get_pairing: async function (instance_id, req, res) {
		var client = sessions[instance_id];
		if (client == undefined) {
			return res.json({ status: 'error', message: "The WhatsApp session could not be found in the system" });
		}

		if (client.authState.creds.registered) {
			return res.json({ status: 'error', message: "It seems that you have logged in successfully" });
		}

		if (!(client.authState.creds.registered && (client.user || {}).id) && client.qrcode != undefined) {
			let phoneNumber = req.query.phone;
			phoneNumber = phoneNumber.replace(/\D/g, '')
			client.paircode = await client.requestPairingCode(phoneNumber)
			console.log('Pairing code:', (client.paircode.match(/.{1,4}/g)).join('-'))
		}

		//Check QR code exist
		for (var i = 0; i < 10; i++) {
			if (client.paircode == undefined) {
				await Common.sleep(1000);
			}
		}

		if (client.paircode == undefined && client.qrcode == undefined || client.paircode == false && client.qrcode == false) {
			return res.json({ status: 'error', message: "The system cannot generate a WhatsApp Pairing code" });
		}

		return res.json({ status: 'success', message: 'Success', code: client.paircode.match(/.{1,4}/g).join('-') });
	},

	get_info: async function (instance_id, res) {
		var client = sessions[instance_id];
		if (client != undefined && client.user != undefined) {
			if (client.user.avatar == undefined) await Common.sleep(1500);
			client.user.avatar = await WAZIPER.get_avatar(client);
			return res.json({ status: 'success', message: "Success", data: client.user });
		} else {
			return res.json({ status: 'error', message: "Error", relogin: true });
		}
	},

	get_avatar: async function (client) {
		try {
			const ppUrl = await client.profilePictureUrl(client.user.id);
			return ppUrl;
		} catch (e) {
			return Common.get_avatar(client.user.name);
		}
	},

	relogin: async function (instance_id, res) {
		if (sessions[instance_id]) {
			var readyState = await WAZIPER.waitForOpenConnection(sessions[instance_id].ws);
			if (readyState === 1) {
				sessions[instance_id].end();
			}

			delete sessions[instance_id];
			delete chatbots[instance_id];
			delete bulks[instance_id];
		}

		await WAZIPER.session(instance_id, true);
	},

	logout: async function (instance_id, res) {
		Common.db_delete("sp_whatsapp_sessions", [{ instance_id: instance_id }]);
		Common.db_update("sp_accounts", [{ status: 0 }, { token: instance_id }]);

		if (sessions[instance_id]) {
			if (typeof sessions[instance_id].ws._events.close === "function") {
				sessions[instance_id].ws._events.close();
			}

			var SESSION_PATH = session_dir + instance_id;
			if (fs.existsSync(SESSION_PATH)) {
				rimraf.sync(SESSION_PATH);
			}
			delete sessions[instance_id];
			delete chatbots[instance_id];
			delete bulks[instance_id];

			if (res != undefined) {
				return res.json({ status: 'success', message: 'Success' });
			}
		} else {
			if (res != undefined) {
				return res.json({ status: 'error', message: 'This account seems to have logged out before.' });
			}
		}
	},

	// INICIO DA ATUALIZAÇÃO RERIVAN
	get_groups: async function (instance_id, res) {
		const client = sessions[instance_id];

		if (!client) {
			return res.json({ status: 'error', message: 'Client not found', data: [] });
		}

		try {
			const allGroups = await client.groupFetchAllParticipating();
			let totalGroups = 0;
			let totalCommunities = 0;
			let totalAnnouncements = 0;
			let totalWithPhotos = 0;
			let totalWithInviteLinks = 0;

			const result = await Promise.all(Object.values(allGroups).map(async (group) => {
				totalGroups++;
				if (group.isCommunity) totalCommunities++;
				if (group.announce) totalAnnouncements++;

				let hasPhoto = false;
				let hasInviteLink = false;

				try {
					const fullGroup = await client.groupMetadata(group.id).catch(() => group);
					
					const [profilePicUrl, inviteCode] = await Promise.all([
						client.profilePictureUrl(group.id, 'image').catch(() => 'NO PICTURE'),
						client.groupInviteCode(group.id).catch(() => null)
					]);

					hasPhoto = profilePicUrl !== 'NO PICTURE';
					hasInviteLink = !!inviteCode;

					if (hasPhoto) totalWithPhotos++;
					if (hasInviteLink) totalWithInviteLinks++;

					return {
						id: fullGroup.id || group.id,
						name: fullGroup.subject || group.subject,
						size: fullGroup.size || group.size || (fullGroup.participants ? fullGroup.participants.length : 0),
						creation: fullGroup.creation || group.creation,
						announce: fullGroup.announce !== undefined ? fullGroup.announce : group.announce,
						restrict: fullGroup.restrict !== undefined ? fullGroup.restrict : group.restrict,
						isCommunity: fullGroup.isCommunity !== undefined ? fullGroup.isCommunity : group.isCommunity,
						joinApprovalMode: fullGroup.joinApprovalMode !== undefined ? fullGroup.joinApprovalMode : group.joinApprovalMode,
						memberAddMode: fullGroup.memberAddMode !== undefined ? fullGroup.memberAddMode : group.memberAddMode,
						participants: fullGroup.participants || group.participants || [],
						owner: fullGroup.owner || group.owner,
						desc: fullGroup.desc || group.desc,
						hasPhoto,
						hasInviteLink,
						profilePicUrl: hasPhoto ? profilePicUrl : null,
						inviteCode: hasInviteLink ? `https://chat.whatsapp.com/${inviteCode}` : null
					};
				} catch (err) {
					console.error('Error fetching group details:', err);
					return {
						id: group.id,
						name: group.subject,
						size: group.size,
						creation: group.creation,
						announce: group.announce,
						restrict: group.restrict,
						isCommunity: group.isCommunity,
						joinApprovalMode: group.joinApprovalMode,
						memberAddMode: group.memberAddMode,
						participants: group.participants,
						owner: group.owner,
						desc: group.desc,
						hasPhoto,
						hasInviteLink
					};
				}
			}));

			const statistics = {
				totalGroups,
				totalCommunities,
				totalAnnouncements,
				totalWithPhotos,
				totalWithInviteLinks
			};

			return res.json({ status: 'success', message: result.length ? 'Success' : 'No groups found', data: result, statistics });
		} catch (error) {
			console.error('Error fetching groups:', error);
			return res.json({ status: 'error', message: error.message || 'Cannot get Groups', error });
		}
	},

	create_groups: async function (instance_id, req, res) {

		var client = sessions[instance_id];
		var name = req.body.name;
		var mem = req.body.participants;
		if (client != undefined) {
			try {
				if (name == undefined || name == "") {
					res.json({ status: 'error', message: 'Parameter "name" cannot be empty', data: [] });
				}
				if (mem == undefined || mem == "") {
					res.json({ status: 'error', message: 'Parameter "participans" cannot be empty', data: [] });
				}
				var group = await client.groupCreate(name, mem);
				if (group != undefined) {
					res.json({ status: 'success', message: 'Success', data: group });
				} else {
					res.json({ status: 'error', message: 'Cannot create group', data: [] });
				}
			} catch (e) {
				res.json({ status: 'error', message: 'Cannot create group', data: [] });
			}
		} else {
			res.json({ status: 'error', message: 'Please relogin', data: [] });
		}

	},

	add_participants: async function (instance_id, req, res) {
		var client = sessions[instance_id];
		var group_id = req.body.group_id;
		var mem = req.body.participants;
		var type = req.body.type;

		if (client != undefined) {
			try {
				if (group_id == undefined || group_id == "") {
					return res.json({ status: 'error', message: 'Parameter "group_id" cannot be empty', data: [] });
				}
				if (mem == undefined || mem == "") {
					return res.json({ status: 'error', message: 'Parameter "participants" cannot be empty', data: [] });
				}
				if (type == undefined || type == "") {
					return res.json({ status: 'error', message: 'Parameter "type" cannot be empty', data: [] });
				}
				var group = await client.groupParticipantsUpdate(group_id, mem, type);
				if (group != undefined) {
					return res.json({ status: 'success', message: 'Success', data: group });
				} else {
					return res.json({ status: 'error', message: 'Cannot Add Participants', data: [] });
				}
			} catch (e) {
				return res.json({ status: 'error', message: 'Cannot Add Participants', data: [] });
			}
		} else {
			return res.json({ status: 'error', message: 'Please relogin', data: [] });
		}
	},

	remove_participants: async function (instance_id, req, res) {
		var client = sessions[instance_id];
		var group_id = req.body.group_id;
		var mem = req.body.participants;
		var type = 'remove';  // Tipo fixo para remoção

		if (client != undefined) {
			try {
				if (group_id == undefined || group_id == "") {
					return res.json({ status: 'error', message: 'Parameter "group_id" cannot be empty', data: [] });
				}
				if (mem == undefined || mem == "") {
					return res.json({ status: 'error', message: 'Parameter "participants" cannot be empty', data: [] });
				}

				var group = await client.groupParticipantsUpdate(group_id, mem, type);
				if (group != undefined) {
					return res.json({ status: 'success', message: 'Success', data: group });
				} else {
					return res.json({ status: 'error', message: 'Cannot Remove Participants', data: [] });
				}
			} catch (e) {
				return res.json({ status: 'error', message: 'Cannot Remove Participants', data: [] });
			}
		} else {
			return res.json({ status: 'error', message: 'Please relogin', data: [] });
		}
	},


	edit_group: async function (instance_id, req, res) {
		var client = sessions[instance_id];
		var group_id = req.body.group_id;
		var new_name = req.body.new_name;
		var new_description = req.body.new_description;
		var new_picture = req.body.new_picture; // URL ou base64 da nova foto

		if (client != undefined) {
			try {
				if (!group_id) {
					return res.json({ status: 'error', message: 'Parameter "group_id" cannot be empty', data: [] });
				}

				if (!new_name && !new_description && !new_picture) {
					return res.json({ status: 'error', message: 'At least one of "new_name", "new_description", or "new_picture" must be provided', data: [] });
				}

				let responses = [];
				let success = false;

				// Atualiza o nome do grupo
				if (new_name) {
					try {
						let nameResult = await client.groupUpdateSubject(group_id, new_name);
						responses.push({ type: 'name', result: nameResult });
						if (nameResult && nameResult.status === 200) {
							success = true;
						}
					} catch (err) {
						console.error("Erro ao atualizar o nome do grupo:", err);
						responses.push({ type: 'name', result: 'Failed to update name', error: err.message });
					}
				}

				// Atualiza a descrição do grupo
				if (new_description) {
					try {
						let descriptionResult = await client.groupUpdateDescription(group_id, new_description);
						responses.push({ type: 'description', result: descriptionResult });
						if (descriptionResult && descriptionResult.status === 200) {
							success = true;
						}
					} catch (err) {
						console.error("Erro ao atualizar a descrição do grupo:", err);
						responses.push({ type: 'description', result: 'Failed to update description', error: err.message });
					}
				}

				// Atualiza a foto do grupo
				if (new_picture) {
					try {
						let mediaUpload;
						if (new_picture.startsWith('http') || new_picture.startsWith('https')) {
							// Baixa a imagem e converte para Buffer
							const axios = require('axios');
							const response = await axios.get(new_picture, { responseType: 'arraybuffer' });

							if (response.status !== 200) {
								throw new Error('Failed to download the image, status: ' + response.status);
							}

							mediaUpload = Buffer.from(response.data, 'binary');
						} else {
							// Caso seja um base64, converte para buffer
							const base64Data = new_picture.replace(/^data:image\/\w+;base64,/, '');
							mediaUpload = Buffer.from(base64Data, 'base64');
						}

						await client.updateProfilePicture(group_id, mediaUpload);
						responses.push({ type: 'picture', result: 'Profile picture updated successfully' });
						success = true; // Caso a atualização da foto seja bem-sucedida
					} catch (err) {
						console.error("Erro ao atualizar a foto do grupo:", err);
						responses.push({ type: 'picture', result: 'Failed to update picture', error: err.message });
					}
				}

				if (success) {
					return res.json({ status: 'success', message: 'Group updated successfully', data: responses });
				} else {
					return res.json({ status: 'error', message: 'Cannot Edit Group', data: responses });
				}
			} catch (e) {
				console.error('Erro ao editar o grupo:', e);
				return res.json({ status: 'error', message: 'Cannot Edit Group', data: [], error: e.message });
			}
		} else {
			return res.json({ status: 'error', message: 'Please relogin', data: [] });
		}
	},

	// FIM DA ATUALIZAÇÃO RERIVAN

	get_cloud_parallel_account_contexts: async function (item) {
		let accountIds = [];
		try {
			accountIds = JSON.parse(item.accounts || '[]');
		} catch (error) {
			accountIds = [];
		}

		accountIds = Array.isArray(accountIds)
			? accountIds.map((value) => parseInt(value, 10)).filter((value) => !Number.isNaN(value) && value > 0)
			: [];

		if (!accountIds.length) {
			return { all_cloud: false, accounts: [] };
		}

		const accountList = accountIds.join(',');
		const rows = await Common.db_query(`SELECT * FROM sp_accounts WHERE id IN (${accountList}) AND status = 1 ORDER BY FIELD(id, ${accountList})`, false);
		if (!rows || rows.length !== accountIds.length) {
			return { all_cloud: false, accounts: [] };
		}

		const accounts = [];
		for (const row of rows) {
			if (parseInt(row.login_type || 0, 10) !== 1) {
				return { all_cloud: false, accounts: [] };
			}

			let runtime = {};
			try {
				runtime = JSON.parse(row.data || row.tmp || '{}');
			} catch (error) {
				runtime = {};
			}

			accounts.push({
				id: parseInt(row.id, 10),
				ids: row.ids,
				name: row.name || row.username || `Cloud ${row.id}`,
				instance_id: row.token,
				access_token: String(runtime.token || runtime.access_token || '').trim(),
				phone_number_id: String(runtime.phone_number_id || row.username || row.pid || '').trim(),
				display_phone_number: String(runtime.display_phone_number || row.username || '').trim(),
				runtime: runtime,
				row: row
			});
		}

		return { all_cloud: true, accounts: accounts };
	},

	get_cloud_parallel_safe_cap: async function (accountContext) {
		const cacheKey = `cloud_parallel_cap:${accountContext.id}:${accountContext.phone_number_id}`;
		const cached = cloudParallelCapabilityCache.get(cacheKey);
		if (cached) {
			return cached;
		}

		let throughputLevel = '';
		let qualityRating = '';
		let displayPhoneNumber = accountContext.display_phone_number || '';
		let source = 'safe_fallback';

		if (accountContext.phone_number_id && accountContext.access_token) {
			try {
				const response = await axios.get(`https://graph.facebook.com/v22.0/${encodeURIComponent(accountContext.phone_number_id)}`, {
					headers: {
						Authorization: `Bearer ${accountContext.access_token}`
					},
					params: {
						fields: 'display_phone_number,quality_rating,throughput'
					},
					timeout: 15000
				});

				throughputLevel = String(response.data?.throughput?.level || '').trim();
				qualityRating = String(response.data?.quality_rating || '').trim();
				displayPhoneNumber = String(response.data?.display_phone_number || displayPhoneNumber).trim();
				source = throughputLevel ? 'live_throughput' : 'safe_fallback';
			} catch (error) {
				bulkMetaDebugLog({
					event: 'cloud_parallel_capability_error',
					account_id: accountContext.id,
					phone_number_id: accountContext.phone_number_id,
					error: error?.response?.data || error?.message || 'unknown_error'
				});
			}
		}

		const safeCap = throughputLevel && throughputLevel.toUpperCase() !== 'STANDARD'
			? CLOUD_PARALLEL_MAX_LEVEL
			: CLOUD_PARALLEL_SAFE_STANDARD_CAP;

		const resolved = {
			safe_cap: safeCap,
			throughput_level: throughputLevel,
			quality_rating: qualityRating,
			display_phone_number: displayPhoneNumber,
			source: source
		};

			cloudParallelCapabilityCache.set(cacheKey, resolved);
			return resolved;
		},

		get_bulk_schedule_hours: function (item) {
			if (!item || !item.schedule_time) {
				return [];
			}

			try {
				const parsed = JSON.parse(item.schedule_time);
				if (!Array.isArray(parsed)) {
					return [];
				}

				return [...new Set(parsed
					.map((value) => parseInt(value, 10))
					.filter((value) => !Number.isNaN(value) && value >= 0 && value <= 23)
				)].sort((a, b) => a - b);
			} catch (error) {
				console.error('[BULK SCHEDULE] Failed to parse schedule_time', error.message);
				return [];
			}
		},

		get_bulk_schedule_weekdays: function (item) {
			if (!item || !item.schedule_weekdays) {
				return [];
			}

			try {
				const parsed = JSON.parse(item.schedule_weekdays);
				if (!Array.isArray(parsed)) {
					return [];
				}

				return [...new Set(parsed
					.map((value) => parseInt(value, 10))
					.filter((value) => !Number.isNaN(value) && value >= 1 && value <= 7)
				)].sort((a, b) => a - b);
			} catch (error) {
				console.error('[BULK SCHEDULE] Failed to parse schedule_weekdays', error.message);
				return [];
			}
		},

		get_team_holiday_dates: async function (teamId) {
			return await new Promise((resolve) => {
				Common.db_connect.query(
					"SELECT DATE_FORMAT(holiday_date, '%Y-%m-%d') as holiday_date FROM sp_whatsapp_team_holidays WHERE team_id = ? ORDER BY holiday_date ASC",
					[parseInt(teamId || 0, 10)],
					(err, rows) => {
						if (err) {
							console.error('[BULK SCHEDULE] Failed to load team holidays', err.message);
							resolve(new Set());
							return;
						}

						resolve(new Set(
							(rows || [])
								.map((row) => String(row?.holiday_date || '').trim())
								.filter(Boolean)
						));
					}
				);
			});
		},

		resolveNextAllowedBulkRun: async function (item, now) {
			const timezone = String(item?.timezone || '').trim();
			const scheduleHours = WAZIPER.get_bulk_schedule_hours(item);
			const scheduleWeekdays = WAZIPER.get_bulk_schedule_weekdays(item);
			const skipTeamHolidays = parseInt(item?.skip_team_holidays || 0, 10) === 1;

			if (!timezone || !moment.tz.zone(timezone)) {
				return {
					allowed: true,
					nextTime: 0,
					reason: 'none',
					localDate: '',
					localHour: -1,
					timezone: timezone,
					scheduleHours: scheduleHours,
					scheduleWeekdays: scheduleWeekdays,
					skipTeamHolidays: skipTeamHolidays
				};
			}

			const localNow = moment.unix(parseInt(now, 10)).tz(timezone);
			const currentDate = localNow.format('YYYY-MM-DD');
			const currentHour = parseInt(localNow.format('H'), 10);
			const currentWeekday = parseInt(localNow.isoWeekday(), 10);
			const holidayDates = skipTeamHolidays
				? await WAZIPER.get_team_holiday_dates(item?.team_id)
				: new Set();

			let reason = 'none';
			if (skipTeamHolidays && holidayDates.has(currentDate)) {
				reason = 'holiday';
			} else if (scheduleWeekdays.length > 0 && !scheduleWeekdays.includes(currentWeekday)) {
				reason = 'weekday';
			} else if (scheduleHours.length > 0 && !scheduleHours.includes(currentHour)) {
				reason = 'hour';
			}

			if (reason === 'none') {
				return {
					allowed: true,
					nextTime: 0,
					reason: 'none',
					localDate: currentDate,
					localHour: currentHour,
					timezone: timezone,
					scheduleHours: scheduleHours,
					scheduleWeekdays: scheduleWeekdays,
					skipTeamHolidays: skipTeamHolidays
				};
			}

			for (let dayOffset = 0; dayOffset <= BULK_SCHEDULE_LOOKAHEAD_DAYS; dayOffset++) {
				const dayMoment = localNow.clone().startOf('day').add(dayOffset, 'days');
				const dateKey = dayMoment.format('YYYY-MM-DD');
				const weekday = parseInt(dayMoment.isoWeekday(), 10);

				if (skipTeamHolidays && holidayDates.has(dateKey)) {
					continue;
				}

				if (scheduleWeekdays.length > 0 && !scheduleWeekdays.includes(weekday)) {
					continue;
				}

				if (scheduleHours.length > 0) {
					const availableHours = dayOffset === 0
						? scheduleHours.filter((hour) => hour > currentHour)
						: scheduleHours;

					if (!availableHours.length) {
						continue;
					}

					const candidate = dayMoment.clone().hour(availableHours[0]).minute(0).second(0);
					const candidateUnix = parseInt(candidate.unix(), 10);
					if (Number.isFinite(candidateUnix) && candidateUnix > parseInt(now, 10)) {
						return {
							allowed: false,
							nextTime: candidateUnix,
							reason: reason,
							localDate: currentDate,
							localHour: currentHour,
							timezone: timezone,
							scheduleHours: scheduleHours,
							scheduleWeekdays: scheduleWeekdays,
							skipTeamHolidays: skipTeamHolidays
						};
					}
					continue;
				}

				if (dayOffset === 0) {
					continue;
				}

				const candidate = dayMoment.clone().hour(0).minute(0).second(0);
				const candidateUnix = parseInt(candidate.unix(), 10);
				if (!Number.isFinite(candidateUnix) || candidateUnix <= parseInt(now, 10)) {
					continue;
				}

				return {
					allowed: false,
					nextTime: candidateUnix,
					reason: reason,
					localDate: currentDate,
					localHour: currentHour,
					timezone: timezone,
					scheduleHours: scheduleHours,
					scheduleWeekdays: scheduleWeekdays,
					skipTeamHolidays: skipTeamHolidays
				};
			}

			return {
				allowed: false,
				nextTime: 0,
				reason: reason,
				localDate: currentDate,
				localHour: currentHour,
				timezone: timezone,
				scheduleHours: scheduleHours,
				scheduleWeekdays: scheduleWeekdays,
				skipTeamHolidays: skipTeamHolidays
			};
		},

		build_cloud_parallel_runtime: async function (item) {
			const contexts = await WAZIPER.get_cloud_parallel_account_contexts(item);
		if (!contexts.all_cloud || !contexts.accounts.length) {
			return { all_cloud: false, accounts: [], aggregate_cap: 0, effective_level: 0 };
		}

		const accounts = [];
		let aggregateCap = 0;
		for (const accountContext of contexts.accounts) {
			const capability = await WAZIPER.get_cloud_parallel_safe_cap(accountContext);
			aggregateCap += parseInt(capability.safe_cap || 0, 10);
			accounts.push({ ...accountContext, ...capability });
		}

		aggregateCap = Math.min(CLOUD_PARALLEL_MAX_LEVEL, aggregateCap);
		const savedLevel = parseInt(item.cloud_parallel_level || 0, 10);
		const effectiveLevel = Math.max(0, Math.min(savedLevel || 0, aggregateCap, CLOUD_PARALLEL_MAX_LEVEL));

		if (savedLevel !== effectiveLevel) {
			bulkMetaDebugLog({
				event: 'cloud_parallel_level_clamped',
				schedule_id: item.id,
				saved_level: savedLevel,
				aggregate_cap: aggregateCap,
				effective_level: effectiveLevel
			});
		}

		return {
			all_cloud: true,
			accounts: accounts,
			aggregate_cap: aggregateCap,
			effective_level: effectiveLevel
		};
	},

	seed_cloud_parallel_dispatches: async function (item) {
		const contacts = await Common.get_contact_phone_numbers(item.contact_id);
		if (!contacts || !contacts.length) {
			return 0;
		}

		const now = Common.time();
		const rows = [];
		for (const contact of contacts) {
			const rawPhone = String(contact.phone || '').replaceAll('.00', '').trim();
			const normalizedPhone = normalizeCloudParallelPhone(rawPhone) || rawPhone;
			if (!normalizedPhone) {
				continue;
			}

			rows.push([
				parseInt(item.id, 10),
				parseInt(item.team_id, 10),
				String(contact.ids || contact.id || ''),
				parseInt(contact.id, 10) || null,
				rawPhone || normalizedPhone,
				normalizedPhone,
				now,
				now
			]);
		}

		for (let index = 0; index < rows.length; index += 500) {
			const chunk = rows.slice(index, index + 500);
			if (!chunk.length) {
				continue;
			}

			const placeholders = chunk.map(() => '(?,?,?,?,?,?,?,?)').join(',');
			await executeDb(
				`INSERT INTO sp_whatsapp_cloud_dispatches (` +
				`schedule_id, team_id, phone_number_id, contact_phone_id, raw_phone, normalized_phone, created, updated` +
				`) VALUES ${placeholders} ` +
				`ON DUPLICATE KEY UPDATE ` +
				`raw_phone = VALUES(raw_phone), ` +
				`phone_number_id = VALUES(phone_number_id), ` +
				`contact_phone_id = VALUES(contact_phone_id), ` +
				`updated = VALUES(updated)`,
				chunk.flat()
			);
		}

		return rows.length;
	},

	reset_stale_cloud_parallel_dispatches: async function (item, now) {
		await executeDb(
			`UPDATE sp_whatsapp_cloud_dispatches ` +
			`SET status = 'retry_wait', error_message = ?, next_attempt_at = ?, updated = ? ` +
			`WHERE schedule_id = ? AND status = 'processing' AND IFNULL(last_attempt_at, 0) < ?`,
			[
				'Recovered from stale processing state',
				now + CLOUD_PARALLEL_BACKOFF_SECONDS,
				now,
				parseInt(item.id, 10),
				now - CLOUD_PARALLEL_STALE_PROCESSING_SECONDS
			]
		);
	},

	get_cloud_parallel_wave_number: async function (scheduleId) {
		const row = await Common.db_query('SELECT COALESCE(MAX(batch_no), 0) as max_batch FROM sp_whatsapp_cloud_dispatches WHERE schedule_id = ?', [scheduleId], true);
		return parseInt(row?.max_batch || 0, 10) + 1;
	},

	get_cloud_parallel_due_dispatches: async function (item, limit, now) {
		return await Common.db_query(
			`SELECT * FROM sp_whatsapp_cloud_dispatches ` +
			`WHERE schedule_id = ? ` +
			`AND (status = 'queued' OR (status = 'retry_wait' AND (next_attempt_at IS NULL OR next_attempt_at <= ?))) ` +
			`ORDER BY id ASC LIMIT ?`,
			[parseInt(item.id, 10), now, limit],
			false
		) || [];
	},

	get_cloud_parallel_status_summary: async function (item) {
		const rows = await Common.db_query(
			`SELECT status, COUNT(*) as total, MIN(next_attempt_at) as min_next_attempt_at ` +
			`FROM sp_whatsapp_cloud_dispatches WHERE schedule_id = ? GROUP BY status`,
			[parseInt(item.id, 10)],
			false
		) || [];

		const summary = {
			sent: 0,
			failed: 0,
			queued: 0,
			processing: 0,
			retry_wait: 0,
			next_retry_at: 0,
			total: 0
		};

		for (const row of rows) {
			const status = String(row.status || '');
			const total = parseInt(row.total || 0, 10);
			summary.total += total;
			if (summary[status] !== undefined) {
				summary[status] = total;
			}
			if (status === 'retry_wait') {
				summary.next_retry_at = parseInt(row.min_next_attempt_at || 0, 10) || 0;
			}
		}

		return summary;
	},

	update_cloud_parallel_schedule_progress: async function (item, nextTime = 0, complete = false) {
		const summary = await WAZIPER.get_cloud_parallel_status_summary(item);
		const updateData = {
			sent: summary.sent,
			failed: summary.failed,
			run: 0
		};

		if (nextTime > 0) {
			updateData.time_post = nextTime;
		}

		if (complete) {
			updateData.status = 2;
		}

		await Common.db_update('sp_whatsapp_schedules', [updateData, { id: item.id }]);

		const totalPhoneNumbers = await Common.get_total_phone_number(item.contact_id);
		if (complete) {
			WAZIPER.io.emit('end_campaign_' + item.team_id, {
				id: item.id,
				status: 2
			});
		} else {
			WAZIPER.io.emit('update_campaign_' + item.team_id, {
				id: item.id,
				sent: summary.sent,
				failed: summary.failed,
				next: nextTime,
				total_phone_numbers: totalPhoneNumbers?.count || 0
			});
		}

		return summary;
	},

	process_cloud_parallel_dispatch_result: async function (item, dispatch, accountContext, result, batchNo, now) {
		if (result?.action === 'pause' || result?.stats === false) {
			await executeDb(
				`UPDATE sp_whatsapp_cloud_dispatches SET status = 'queued', error_message = ?, updated = ? WHERE id = ?`,
				[String(result?.message || 'Cloud parallel dispatch paused'), now, parseInt(dispatch.id, 10)]
			);

			await Common.db_update('sp_whatsapp_schedules', [{ run: 0, status: 0 }, { id: item.id }]);
			WAZIPER.io.emit('pause_campaign_' + item.team_id, {
				id: item.id,
				status: 0
			});

			return { paused: true, status: 'queued' };
		}

		const isSuccess = !!result?.status;
		const currentAttempt = parseInt(dispatch.attempt_count || 0, 10) + 1;
		const errorCode = parseInt(result?.error_code || 0, 10) || null;
		const errorMessage = String(result?.error_message || result?.message || '').trim();
		let dispatchStatus = isSuccess ? 'sent' : 'failed';
		let nextAttemptAt = null;

		if (!isSuccess && errorCode && [130429, 131056].includes(errorCode) && currentAttempt < 4) {
			dispatchStatus = 'retry_wait';
			nextAttemptAt = now + Math.min(900, CLOUD_PARALLEL_BACKOFF_SECONDS * currentAttempt);
		}

		await executeDb(
			`UPDATE sp_whatsapp_cloud_dispatches SET ` +
			`account_id = ?, batch_no = ?, status = ?, wa_message_id = ?, attempt_count = ?, ` +
			`error_code = ?, error_message = ?, last_attempt_at = ?, next_attempt_at = ?, updated = ? ` +
			`WHERE id = ?`,
			[
				accountContext.id,
				batchNo,
				dispatchStatus,
				result?.wa_message_id || null,
				currentAttempt,
				errorCode,
				errorMessage || null,
				now,
				nextAttemptAt,
				now,
				parseInt(dispatch.id, 10)
			]
		);

		return {
			paused: false,
			status: dispatchStatus,
			error_code: errorCode,
			wa_message_id: result?.wa_message_id || null
		};
	},

	bulk_messaging_cloud_parallel: async function (item, time_now) {
		const runtime = await WAZIPER.build_cloud_parallel_runtime(item);
		if (!runtime.all_cloud || !runtime.accounts.length) {
			bulkMetaDebugLog({
				event: 'cloud_parallel_fallback_legacy',
				schedule_id: item.id,
				reason: 'accounts_not_all_cloud'
			});
			return false;
		}

		if (runtime.effective_level <= 0) {
			const minDelay = Math.max(parseInt(item.min_delay || 60, 10), 1);
			await WAZIPER.update_cloud_parallel_schedule_progress(item, time_now + minDelay, false);
			return true;
		}

		await WAZIPER.seed_cloud_parallel_dispatches(item);
		await WAZIPER.reset_stale_cloud_parallel_dispatches(item, time_now);

		const dueDispatches = await WAZIPER.get_cloud_parallel_due_dispatches(item, runtime.effective_level, time_now);
		if (!dueDispatches.length) {
			const summary = await WAZIPER.get_cloud_parallel_status_summary(item);
			const remaining = summary.queued + summary.processing + summary.retry_wait;

			if (remaining === 0) {
				await WAZIPER.update_cloud_parallel_schedule_progress(item, time_now, true);
				return true;
			}

			const minDelay = Math.max(parseInt(item.min_delay || 60, 10), 1);
			const nextTime = summary.next_retry_at > 0 ? Math.max(time_now + minDelay, summary.next_retry_at) : time_now + minDelay;
			await WAZIPER.update_cloud_parallel_schedule_progress(item, nextTime, false);
			return true;
		}

		const batchNo = await WAZIPER.get_cloud_parallel_wave_number(item.id);
		const dispatchIds = dueDispatches.map((dispatch) => parseInt(dispatch.id, 10)).filter((value) => !Number.isNaN(value));
		if (dispatchIds.length) {
			await executeDb(
				`UPDATE sp_whatsapp_cloud_dispatches SET status = 'processing', batch_no = ?, last_attempt_at = ?, updated = ? ` +
				`WHERE id IN (${dispatchIds.join(',')})`,
				[batchNo, time_now, time_now]
			);
		}

		bulkMetaDebugLog({
			event: 'cloud_parallel_wave_start',
			schedule_id: item.id,
			batch_no: batchNo,
			effective_level: runtime.effective_level,
			dispatch_count: dueDispatches.length,
			account_ids: runtime.accounts.map((account) => account.id)
		});

		const waveResults = await Promise.all(dueDispatches.map(async (dispatch, index) => {
			const accountContext = runtime.accounts[index % runtime.accounts.length];
			const phoneNumberItem = dispatch.contact_phone_id ? await Common.get_phone_number_by_id(dispatch.contact_phone_id) : false;
			const phoneNumber = dispatch.normalized_phone || normalizeCloudParallelPhone(dispatch.raw_phone);

			return await new Promise(async (resolve) => {
				await WAZIPER.auto_send(
					accountContext.instance_id,
					phoneNumber,
					phoneNumber,
					'bulk',
					item,
					phoneNumberItem || false,
					false,
					false,
					async function (result) {
						const handled = await WAZIPER.process_cloud_parallel_dispatch_result(item, dispatch, accountContext, result || {}, batchNo, Common.time());
						resolve(handled);
					}
				);
			});
		}));

		if (waveResults.some((result) => result?.paused)) {
			return true;
		}

		const successCount = waveResults.filter((result) => result?.status === 'sent').length;
		const failedCount = waveResults.filter((result) => result?.status === 'failed').length;
		const retryCount = waveResults.filter((result) => result?.status === 'retry_wait').length;
		bulkMetaDebugLog({
			event: 'cloud_parallel_wave_finish',
			schedule_id: item.id,
			batch_no: batchNo,
			sent: successCount,
			failed: failedCount,
			retry_wait: retryCount
		});

		const summary = await WAZIPER.get_cloud_parallel_status_summary(item);
		const remainingQueued = summary.queued;
		const remainingRetry = summary.retry_wait;
		const remainingProcessing = summary.processing;
		const remaining = remainingQueued + remainingRetry + remainingProcessing;

		if (remaining === 0) {
			await WAZIPER.update_cloud_parallel_schedule_progress(item, Common.time(), true);
			return true;
		}

		const minDelay = Math.max(parseInt(item.min_delay || 60, 10), 1);
		let maxDelay = Math.max(parseInt(item.max_delay || minDelay, 10), minDelay);
		if (maxDelay < minDelay) {
			maxDelay = minDelay;
		}
		let nextTime = Common.time() + Common.randomIntFromInterval(minDelay, maxDelay);
		if (remainingQueued === 0 && summary.next_retry_at > 0) {
			nextTime = Math.max(nextTime, summary.next_retry_at);
		}

		await WAZIPER.update_cloud_parallel_schedule_progress(item, nextTime, false);
		return true;
	},


	bulk_messaging: async function () {

		const d = new Date();
		var time_now = parseInt(d.getTime() / 1000);

		console.log('bulk process check', time_now);

		var query = `SELECT * FROM sp_whatsapp_schedules WHERE status = 1 AND run <= '${time_now}' AND accounts != '' AND time_post <= '${time_now}' ORDER BY time_post ASC LIMIT 50`;
		items = await Common.db_query(query, false);


		if (items) {
			console.log('bulk process check items', items.length);

			for (const item of items) {
				const lock_until = time_now + 300;
				const lock_result = await new Promise((resolve) => {
					Common.db_connect.query(
						"UPDATE sp_whatsapp_schedules SET run = ? WHERE id = ? AND status = 1 AND run <= ? AND accounts != '' AND time_post <= ?",
						[lock_until, item.id, time_now, time_now],
						(err, res) => {
							if (err) console.error('[BULK LOCK] failed', item.id, err.message);
							resolve(res);
						}
					);
				});

				if (!lock_result || lock_result.affectedRows === 0) {
					console.log('[BULK LOCK] skip already locked', item.id);
					continue;
				}

				try {
						console.log('bulk process', item.id, 'next account', item.next_account);
						const scheduleResolution = await WAZIPER.resolveNextAllowedBulkRun(item, time_now);
						if (!scheduleResolution.allowed) {
							bulkMetaDebugLog({
								event: 'bulk_schedule_blocked',
								schedule_id: item.id,
								reason: scheduleResolution.reason,
								local_date: scheduleResolution.localDate,
								local_hour: scheduleResolution.localHour,
								timezone: scheduleResolution.timezone,
								schedule_hours: scheduleResolution.scheduleHours,
								schedule_weekdays: scheduleResolution.scheduleWeekdays,
								skip_team_holidays: scheduleResolution.skipTeamHolidays
							});

							if (scheduleResolution.nextTime > 0) {
								bulkMetaDebugLog({
									event: 'bulk_schedule_rescheduled',
									schedule_id: item.id,
									reason: scheduleResolution.reason,
									next_time: scheduleResolution.nextTime,
									local_date: scheduleResolution.localDate,
									local_hour: scheduleResolution.localHour,
									timezone: scheduleResolution.timezone
								});

								await Common.db_update("sp_whatsapp_schedules", [{ time_post: scheduleResolution.nextTime, run: 0 }, { id: item.id }]);
							} else {
								bulkMetaDebugLog({
									event: 'bulk_schedule_no_valid_slot',
									schedule_id: item.id,
									reason: scheduleResolution.reason,
									local_date: scheduleResolution.localDate,
									local_hour: scheduleResolution.localHour,
									timezone: scheduleResolution.timezone
								});

								await Common.db_update("sp_whatsapp_schedules", [{ status: 0, run: 0 }, { id: item.id }]);
								WAZIPER.io.emit('pause_campaign_' + item.team_id, {
									id: item.id,
									status: 0
								});
							}

							return false;
						}

						const cloudParallelEnabled = parseInt(item.cloud_parallel_enabled || 0, 10) === 1;
					const isCallCampaign = parseInt(item.type || 0, 10) === 7;
					if (cloudParallelEnabled && !isCallCampaign) {
						const handledByCloudParallel = await WAZIPER.bulk_messaging_cloud_parallel(item, time_now);
						if (handledByCloudParallel) {
							return false;
						}
					}


					if (!item.result) {
						console.log('restarting counters for ', item.id);
						var query_phone_data = '';
						if (!bulks[item.id]) {
							bulks[item.id] = {};
						}
						bulks[item.id]['bulk_sent'] = 0;
						bulks[item.id]['bulk_failed'] = 0;
					} else {
						result = JSON.parse(item.result);
						var query_phone_data = {
							ids: [],
							phones: []
						};
						for (var i = 0; i < result.length; i++) {
							if (result[i]?.phone_number_id != null) {
								query_phone_data.ids.push(result[i].phone_number_id);
							}

							if (result[i]?.phone_number != null) {
								query_phone_data.phones.push(result[i].phone_number.toString());
							}
						}
					}


					console.log('bulk continue bulk');
					var params = false;
					var normalized_candidate_phone = false;
					var phone_number_item = false;
					for (let candidateTry = 0; candidateTry < 20; candidateTry++) {
						phone_number_item = await Common.get_phone_number(item.contact_id, query_phone_data);
						if (!phone_number_item) {
							break;
						}

						const raw_candidate_phone = `${phone_number_item.phone}`.replaceAll('.00', '');
						const candidate_phone = raw_candidate_phone.indexOf("g.us") !== -1
							? raw_candidate_phone
							: await Common.check_especials(raw_candidate_phone, phone_number_item.id);

						if (query_phone_data?.phones?.includes(candidate_phone) || query_phone_data?.phones?.includes(raw_candidate_phone)) {
							if (!Array.isArray(query_phone_data.ids)) {
								query_phone_data.ids = [];
							}
							query_phone_data.ids.push(phone_number_item.id);
							continue;
						}

						normalized_candidate_phone = candidate_phone;
						break;
					}

					if (!phone_number_item) {
						//Complete
						await Common.db_update("sp_whatsapp_schedules", [{ status: 2, run: 0 }, { id: item.id }]);

						WAZIPER.io.emit('end_campaign_' + item.team_id, {
							id: item.id,
							status: 2
						});

						return false;
					} else {
						phone_number = normalized_candidate_phone || `${phone_number_item.phone}`.replaceAll('.00', '');
						params = phone_number_item.params;
					}

					//Random account
					var instance_id = false;
					var accounts = JSON.parse(item.accounts);
					var next_account = item.next_account;


					// TODO: estó se puede optimizar, primero se deberia traer solo las cuentas con status 1, y a partir de ahu contar, se podria eliminar el foreach

					if (next_account == null || next_account == "" || next_account > accounts.length - 1) next_account = 0;

					var check_account = await Common.get_accounts(accounts.join(","));
					if (check_account && check_account.count == 0) {
						console.log('no accounts');
						await Common.db_update("sp_whatsapp_schedules", [{ status: 0 }, { id: item.id }]);
					}

					await accounts.forEach(async (account, index) => {
						if (!instance_id && index == next_account) {
							var account_item = await Common.db_get("sp_accounts", [{ id: account }, { status: 1 }]);

							if (account_item) instance_id = account_item.token;



							phone_number = normalized_candidate_phone || `${phone_number_item.phone}`.replaceAll('.00', '');
							params = phone_number_item.params;
							if (phone_number.indexOf("g.us") !== -1) {
								var chat_id = phone_number;
							} else {
								phone_number = await Common.check_especials(phone_number, phone_number_item.id);
								var chat_id = String(phone_number).replace(/\D/g, '') + "@s.whatsapp.net";
							}

							if (account_item && account_item.team_id == phone_number_item.team_id) {
								console.log(`[DEBUG] Processing bulk item ${item.id}, account ${instance_id}, to ${phone_number}`);
								const isCallCampaign = parseInt(item.type || 0, 10) === 7;

								if (isCallCampaign && account_item.login_type != 2 && account_item.login_type != 3) {
									await WAZIPER.handle_bulk_schedule_result(item, next_account, {
										action: "pause",
										message: "Call campaigns require Baileys or Whatsmeow accounts"
									});
								} else if (!isCallCampaign && account_item.login_type != 1 && sessions[instance_id] == undefined) {
									console.log(`[DEBUG] Session undefined for Baileys account ${instance_id}, skipping/rescheduling`);
									Common.db_update("sp_whatsapp_schedules", [{ next_account: next_account + 1, run: 1 }, { id: item.id }]);
								} else {
									const bulkResultHandler = async function (result) {
										console.log(`[DEBUG] Bulk callback received for item ${item.id}`, result);
										if (result && result.phone_number_id == null && phone_number_item?.id != null) {
											result.phone_number_id = phone_number_item.id;
										}
										await WAZIPER.handle_bulk_schedule_result(item, next_account, result);
									};

									if (isCallCampaign) {
										console.log(`[DEBUG] Calling auto_call_campaign for item ${item.id}`);
										await WAZIPER.auto_call_campaign(instance_id, chat_id, phone_number, item, phone_number_item, bulkResultHandler);
									} else {
										console.log(`[DEBUG] Calling auto_send for item ${item.id}`);
										bulkMetaDebugLog({
											event: 'bulk_before_auto_send',
											schedule_id: item.id,
											team_id: item.team_id,
											schedule_type: item.type,
											schedule_template: item.template,
											schedule_media: item.media,
											schedule_caption_empty: !item.caption,
											schedule_accounts: item.accounts,
											contact_id: item.contact_id,
											instance_id: instance_id,
											phone_number: phone_number
										});
										await WAZIPER.auto_send(instance_id, chat_id, phone_number, "bulk", item, phone_number_item, false, false, bulkResultHandler);
									}
								}
							}
						}
					});
					// catch-all for loop end? No, loop is async.
				} catch (err_main) {
					console.error(`[CRITICAL] Error handling bulk item ${item.id}`, err_main);
					await Common.db_update("sp_whatsapp_schedules", [{ run: 0 }, { id: item.id }]);
				}
			}
		}
	},

	handle_bulk_schedule_result: async function (item, next_account, result) {
		if (result?.action === "pause") {
			await Common.db_update("sp_whatsapp_schedules", [{ run: 0, status: 0 }, { id: item.id }]);
			WAZIPER.io.emit('pause_campaign_' + item.team_id, {
				id: item.id,
				status: 0
			});
			return;
		}

		if (result?.action === "rotate") {
			await Common.db_update("sp_whatsapp_schedules", [{ next_account: next_account + 1, run: 1 }, { id: item.id }]);
			return;
		}

		if (result?.stats && ["bulk", "bulk_call"].includes(result.type)) {
			var status = !!result.status;
			var new_stats = {
				phone_number: result.phone_number,
				status: status,
			};

			const resultMessage = String(
				typeof result.message === 'string'
					? result.message
					: (result.error || result.reason || '')
			).trim();
			if (resultMessage) {
				new_stats.message = resultMessage;
			}

			const waMessageId = result.message?.key?.id || result.wa_message_id || result.message_id || '';
			if (waMessageId) {
				new_stats.wa_message_id = waMessageId;
			}

			const sentAt = result.message?.messageTimestamp?.low || result.message?.messageTimestamp || result.sent_at || 0;
			if (sentAt) {
				new_stats.sent_at = parseInt(sentAt, 10) || Math.floor(Date.now() / 1000);
			}

			if (result.error_code != null) {
				new_stats.error_code = result.error_code;
			}

			if (result.phone_number_id != null) {
				new_stats.phone_number_id = result.phone_number_id;
			}

			if (result.type === "bulk_call") {
				new_stats.call_id = result.call_id || null;
				new_stats.sent_at = result.sent_at || Math.floor(Date.now() / 1000);
				new_stats.event = result.event || (status ? "offer_sent" : "offer_failed");
				new_stats.call_mode = result.call_mode || "voice";
				new_stats.message = result.message || new_stats.message || "";
			}

			if (item.result == null || item.result == "") {
				var result_list = [new_stats];
			} else {
				try {
					var result_list = JSON.parse(item.result);
					if (!Array.isArray(result_list)) {
						result_list = [];
					}
				} catch (error) {
					console.error("Bulk result parse error", error);
					var result_list = [];
				}
				result_list.push(new_stats);
			}

			var total_sent = result_list.filter((entry) => !!entry?.status).length;
			var total_failed = result_list.filter((entry) => !entry?.status).length;

			if (bulks[item.id] == undefined) {
				bulks[item.id] = {};
			}
			bulks[item.id].bulk_sent = total_sent;
			bulks[item.id].bulk_failed = total_failed;

			var now = Math.floor(new Date().getTime() / 1000);
			var min_delay = parseInt(item.min_delay || 60);
			var max_delay = parseInt(item.max_delay || 300);
			if (max_delay < min_delay) max_delay = min_delay;

			var random_time = Math.floor(Math.random() * (max_delay - min_delay + 1)) + min_delay;
			var next_time = parseInt(item.time_post) + random_time;

			if (isNaN(next_time) || next_time < now) {
				next_time = now + random_time;
			}

			var data = {
				result: JSON.stringify(result_list),
				sent: total_sent,
				failed: total_failed,
				time_post: next_time,
				next_account: next_account + 1,
				run: 0,
			};

			try {
				await Common.db_update("sp_whatsapp_schedules", [data, { id: item.id }]);
				console.log("SCHEDULE UPDATED: run=0 next_time=", next_time);
			} catch (e) {
				console.error("DB UPDATE ERROR", e);
				await Common.db_update("sp_whatsapp_schedules", [{ run: 0 }, { id: item.id }]);
			}

			var total_phone_numbers = await Common.get_total_phone_number(item.contact_id);
			WAZIPER.io.emit('update_campaign_' + item.team_id, {
				id: item.id,
				sent: total_sent,
				failed: total_failed,
				next: next_time,
				total_phone_numbers: total_phone_numbers.count
			});
			return;
		}

		console.log("Bulk result invalid, resetting run");
		await Common.db_update("sp_whatsapp_schedules", [{ run: 0 }, { id: item.id }]);
	},

	auto_call_campaign: async function (instance_id, chat_id, phone_number, item, phone_number_item, callback) {
		var limit = await WAZIPER.limit(item, "bulk_call");
		if (!limit) {
			if (typeof callback === "function") {
				return callback({ status: 0, type: "bulk_call", stats: false, message: "Campaign limit validation failed" });
			}
			return false;
		}

		const session_ready = await WAZIPER.ensure_session_ready(instance_id);
		if (!session_ready || !sessions[instance_id]) {
			if (typeof callback === "function") {
				return callback({ action: "rotate", type: "bulk_call", message: "Baileys session is not connected" });
			}
			return false;
		}

		const session = sessions[instance_id];
		if (typeof session.offerCall !== "function") {
			console.error(`[CALLCAMPAIGN] capability unavailable for ${instance_id}`);
			if (typeof callback === "function") {
				return callback({ action: "pause", type: "bulk_call", message: "offerCall unavailable" });
			}
			return false;
		}

		if (isGroupJid(chat_id)) {
			const now = Math.floor(Date.now() / 1000);
			const fail_message = "Group calls are not supported in call campaigns";
			await Common.db_insert('sp_whatsapp_history', {
				instance_id: instance_id,
				team_id: item.team_id,
				phone: chat_id,
				type: "bulk_call",
				message: fail_message,
				status: 0,
				time_post: now,
			});

			if (typeof callback === "function") {
				return callback({
					status: 0,
					type: "bulk_call",
					phone_number: phone_number,
					stats: true,
					call_id: null,
					sent_at: now,
					event: "offer_failed",
					call_mode: "voice",
					message: fail_message,
				});
			}
			return false;
		}

		if (get_session_user_ids(session).some((jid) => is_same_jid_user(chat_id, jid))) {
			const now = Math.floor(Date.now() / 1000);
			const fail_message = "Cannot start a call to the same connected WhatsApp account";
			await Common.db_insert('sp_whatsapp_history', {
				instance_id: instance_id,
				team_id: item.team_id,
				phone: phone_number,
				type: "bulk_call",
				message: fail_message,
				status: 0,
				time_post: now,
			});

			if (typeof callback === "function") {
				return callback({
					status: 0,
					type: "bulk_call",
					phone_number: phone_number,
					stats: true,
					call_id: null,
					sent_at: now,
					event: "offer_failed",
					call_mode: "voice",
					message: fail_message,
				});
			}

			return false;
		}

		try {
			const callResponse = await session.offerCall(chat_id, false);
			const now = Math.floor(Date.now() / 1000);
			const state = get_callcampaign_state(session, now);
			state.outgoingByCallId[callResponse.id] = {
				scheduleId: item.id,
				team_id: item.team_id,
				phone: phone_number,
				chatId: chat_id,
				startedAt: now,
				updatedAt: now,
				loggedEvents: {}
			};
			state.metadataByCallId[callResponse.id] = {
				callCreator: session?.user?.id || "",
				chatId: chat_id,
				from: chat_id,
				isOutgoing: true,
				status: "offer",
				updatedAt: now
			};

			await Common.db_insert('sp_whatsapp_history', {
				instance_id: instance_id,
				team_id: item.team_id,
				phone: phone_number,
				type: "bulk_call",
				message: "CALL OFFER SENT",
				status: 1,
				time_post: now,
			});

			if (typeof callback === "function") {
				return callback({
					status: 1,
					type: "bulk_call",
					phone_number: phone_number,
					stats: true,
					call_id: callResponse.id,
					sent_at: now,
					event: "offer_sent",
					call_mode: "voice",
					message: "CALL OFFER SENT",
				});
			}

			return true;
		} catch (error) {
			const now = Math.floor(Date.now() / 1000);
			const fail_message = error?.message || "Failed to start call";
			console.error("[CALLCAMPAIGN] offerCall failed", instance_id, chat_id, fail_message);
			await Common.db_insert('sp_whatsapp_history', {
				instance_id: instance_id,
				team_id: item.team_id,
				phone: phone_number,
				type: "bulk_call",
				message: fail_message,
				status: 0,
				time_post: now,
			});

			if (typeof callback === "function") {
				return callback({
					status: 0,
					type: "bulk_call",
					phone_number: phone_number,
					stats: true,
					call_id: null,
					sent_at: now,
					event: "offer_failed",
					call_mode: "voice",
					message: fail_message,
				});
			}

			return false;
		}
		},

		bot_builder_extract_text: function (message) {
			if (!message || !message.message) return "";

			if (message.message?.ephemeralMessage?.message) {
				message = { ...message, message: message.message.ephemeralMessage.message };
			}
			if (message.message?.viewOnceMessage?.message) {
				message = { ...message, message: message.message.viewOnceMessage.message };
			}
			if (message.message?.viewOnceMessageV2?.message) {
				message = { ...message, message: message.message.viewOnceMessageV2.message };
			}
			if (message.message?.documentWithCaptionMessage?.message) {
				message = { ...message, message: message.message.documentWithCaptionMessage.message };
			}

			if (message.message?.buttonsResponseMessage) {
				return message.message.buttonsResponseMessage.selectedDisplayText
					|| message.message.buttonsResponseMessage.selectedButtonId
					|| "";
			}

			if (message.message?.listResponseMessage) {
				return message.message.listResponseMessage.title
					|| message.message.listResponseMessage.singleSelectReply?.selectedRowId
					|| "";
			}

			if (message.message?.templateButtonReplyMessage) {
				return message.message.templateButtonReplyMessage.selectedDisplayText
					|| message.message.templateButtonReplyMessage.selectedId
					|| "";
			}

			if (message.message?.interactiveResponseMessage) {
				try {
					const interactive = message.message.interactiveResponseMessage;
					let parsed = {};
					const rawParams = interactive.nativeFlowResponseMessage?.paramsJson
						|| interactive.nativeFlowResponseMessage?.params_json
						|| interactive.nativeFlowResponseMessage?.buttonParamsJson
						|| "{}";
					try { parsed = typeof rawParams === 'string' ? JSON.parse(rawParams) : rawParams; } catch (e) { parsed = {}; }
					return parsed.display_text
						|| parsed.displayText
						|| parsed.title
						|| parsed.id
						|| parsed.selectedId
						|| interactive.body?.text
						|| interactive.nativeFlowResponseMessage?.name
						|| "";
				} catch (error) {
					return message.message.interactiveResponseMessage?.body?.text || "";
				}
			}

			if (message.message?.templateButtonReplyMessage?.hydratedTemplateButton) {
				return message.message.templateButtonReplyMessage.hydratedTemplateButton.displayText || "";
			}

			return message.message?.conversation
				|| message.message?.extendedTextMessage?.text
				|| message.message?.imageMessage?.caption
				|| message.message?.videoMessage?.caption
				|| message.message?.documentMessage?.caption
				|| "";
		},

		bot_builder_identity_candidates: function (chat_id, message) {
			const candidates = [];
			const add = (value) => {
				if (value === undefined || value === null) return;
				const normalized = String(value).trim();
				if (!normalized || candidates.includes(normalized)) return;
				candidates.push(normalized);
			};

			const addNumberForms = (value) => {
				const raw = String(value || '').trim();
				if (!raw) return;
				add(raw);
				if (/^[0-9]+$/.test(raw)) {
					add(`${raw}@s.whatsapp.net`);
				}
			};

			const context = message?._automation_context || {};
			add(context.canonicalJid);
			addNumberForms(context.canonicalId);
			addNumberForms(context.canonicalNumber);
			add(context.replyJid);
			add(context.transportJid);
			addNumberForms(context.cloudTo);
			addNumberForms(message?._wa_id);
			add(message?._bsuid);
			if (message?._bsuid && !String(message._bsuid).includes('@')) {
				add(`${message._bsuid}@s.whatsapp.net`);
			}
			add(chat_id);

			return candidates;
		},

		bot_builder_flow: async function (instance_id, chat_id, message) {
			try {
				const integrations = await Common.db_query(
					"SELECT i.bot_id, b.trigger_keywords, b.enable_keyword, b.stop_keyword, b.bot_enabled, b.keyword_match_type, b.chat_type, b.status " +
					"FROM sp_bb_integrations i " +
					"JOIN sp_bot_builders b ON b.id = i.bot_id " +
					"JOIN sp_accounts a ON a.id = i.instance_id " +
					"WHERE a.token = ? AND i.status = 1 AND b.status = 1",
					[instance_id],
					false
				);

				if (!integrations || integrations.length === 0) {
					return false;
				}

				const text = WAZIPER.bot_builder_extract_text(message).trim();
				console.log('[BOT_BUILDER_REPLY_DEBUG]', JSON.stringify({
					instance_id,
					chat_id,
					extracted_text: text,
					message_keys: Object.keys(message.message || {}),
					message: message.message || null
				}));
				if (!text) {
					console.log(`[BOT_BUILDER] Mensagem sem texto roteável para ${instance_id}`);
					return false;
				}

				const phoneCandidates = WAZIPER.bot_builder_identity_candidates(chat_id, message);
				if (phoneCandidates.length === 0) {
					return false;
				}

				const activeSessions = await Common.db_query(
					"SELECT s.id, s.bot_id FROM sp_bb_sessions s " +
					"JOIN sp_bb_integrations i ON i.bot_id = s.bot_id " +
					"JOIN sp_accounts a ON a.id = i.instance_id " +
					`WHERE s.phone IN (${phoneCandidates.map(() => '?').join(',')}) AND a.token = ? AND i.status = 1 AND s.is_completed = 0`,
					[...phoneCandidates, instance_id],
					false
				);

				let shouldProcess = activeSessions && activeSessions.length > 0;
				if (!shouldProcess) {
					const msg = text.toLowerCase();
					const isGroup = String(chat_id || '').includes('@g.us');

					for (const bot of integrations) {
						if (bot.bot_enabled == 0) continue;

						const chatType = bot.chat_type || 'all';
						if (chatType === 'individual' && isGroup) continue;
						if (chatType === 'groups' && !isGroup) continue;

						const matchType = bot.keyword_match_type || 'contains';
						const matches = (keyword) => {
							const kw = String(keyword || '').trim().toLowerCase();
							if (!kw) return false;
							return matchType === 'exact' ? kw === msg : (kw === msg || msg.includes(kw));
						};

						const primaryKeywords = String(bot.enable_keyword || '')
							.split(',')
							.map((keyword) => keyword.trim())
							.filter(Boolean);
						const fallbackKeywords = String(bot.trigger_keywords || '')
							.split(',')
							.map((keyword) => keyword.trim())
							.filter(Boolean);

						if ([...primaryKeywords, ...fallbackKeywords].some(matches)) {
							shouldProcess = true;
							break;
						}
					}
				}

				if (!shouldProcess) {
					return false;
				}

				const websiteUrl = (config.website_url || config.frontend || '').replace(/\/$/, '');
				if (!websiteUrl) {
					console.error('[BOT_BUILDER] frontend/website_url não configurado no config.js');
					return false;
				}

				const payloadMessage = {
					key: message.key,
					message: message.message,
					messageTimestamp: message.messageTimestamp,
					pushName: message.pushName || '',
					official_api: message.official_api === true,
					wamid: message.wamid || null,
					_wa_id: message._wa_id || null,
					_bsuid: message._bsuid || null,
					_automation_context: message._automation_context || null,
				};

				const response = await axios.post(`${websiteUrl}/bot-builder/webhook`, {
					instance_id: instance_id,
					data: { messages: [payloadMessage] }
				}, {
					timeout: 30000,
					headers: { 'Content-Type': 'application/json' }
				}).catch((error) => {
					console.error(`[BOT_BUILDER] Falha ao chamar webhook PHP: ${error.message}`);
					return null;
				});

				if (response && response.status === 200 && response.data && response.data.status === 'success' && response.data.handled !== false) {
					console.log(`[BOT_BUILDER] Fluxo processado para ${chat_id} em ${instance_id}`);
					return true;
				}

				return false;
			} catch (error) {
				console.error(`[BOT_BUILDER] Erro no fluxo: ${error.message}`);
				return false;
			}
		},

		autoresponder: async function (instance_id, user_type, message) {
		console.log(`[DEBUG] WAZIPER.autoresponder called for ${instance_id}`);
		var chat_id = message.key.remoteJid;
		var incoming_message_id = message?.wamid || message?.id || message?.key?.id || null;
		if (incoming_message_id) {
			cleanup_autoresponder_dedupe();
			var dedupe_key = `${instance_id}:${chat_id}:${incoming_message_id}`;
			var dedupe_now = Date.now();
			var dedupe_expires_at = autoresponder_recent_messages.get(dedupe_key);

			if (dedupe_expires_at && dedupe_expires_at > dedupe_now) {
				console.log("[AUTORESPONDER] Duplicate inbound ignored:", dedupe_key);
				return false;
			}

			autoresponder_recent_messages.set(dedupe_key, dedupe_now + AUTORESPONDER_DEDUPE_TTL_MS);
		}
		var now = new Date().getTime() / 1000;
		var item = await Common.db_get("sp_whatsapp_autoresponder", [{ instance_id: instance_id }, { status: 1 }]);
		// console.log("MENSAGEM RECEBIDA:", message);
		//    console.log("Conte do de contextInfo:", message.contextInfo);


		//item.delay = 1; // TESTE APENAS - Ajuste o valor para o n mero desejado em minutos

		if (!item) {
			return false;
		}

		//Accept sent to all/group/user
		switch (item.send_to) {
			case 2:
				if (user_type == "group") return false;
				break;
			case 3:
				if (user_type == "user") return false;
				break;
		}

		//Except contacts
		var except_data = [];
		if (item.except != null) {
			var except_data = item.except.split(",");;
		}

		//console.log("Contatos Exclu dos:", except_data);

		if (except_data.length > 0) {
			for (var i = 0; i < except_data.length; i++) {
				if (except_data[i] != "" && chat_id.indexOf(except_data[i]) != -1) {
					console.log("Contato exclu do. N o enviando resposta autom tica.");
					return false;
				}
			}
		}

		//EDITED G3
		var idConversa = message.key.id;
		var participanteGrupo = message.key.participant;

		//nextAction, inputName e saveData
		var saveData = '';
		var inputName = '';
		var nextAction = '';

		//EDITED G3 - Acessar o texto da mensagem
		var msgConversa = '';
		if (message.message?.conversation) {
			msgConversa = message.message.conversation;
		} else if (message.message?.extendedTextMessage?.text) {
			msgConversa = message.message.extendedTextMessage.text;
		} else if (message.text?.body) {
			// Cloud API format
			msgConversa = message.text.body;
		}

		//Definir a mensagem recebida
		const msgRecebida = msgConversa;
		//EDITED G3 - Obter o nome definido pelo contato no WhatsApp
		const waName = message.pushName || '';
		var cleanedWaName = waName.replace(/[&<>"']/g, '');
		const automationContext = getAutomationContextForMessage(message, instance_id);
		const autoresponderIdentity = automationContext.canonicalId || Extend.getIdentifierPrefix(chat_id);
		const autoresponderNumber = automationContext.canonicalNumber || autoresponderIdentity;
		const autoresponderReplyJid = automationContext.replyJid || chat_id;
		const responseLookupIds = buildAutomationIdentifierCandidates(automationContext, chat_id);

		// Obter o n mero de telefone do contato
		var userPhone = autoresponderNumber;
		console.log("DADOS RR:", {
			instance_id: instance_id,
			identity: autoresponderIdentity,
			number: autoresponderNumber,
			replyJid: autoresponderReplyJid,
			aliases: responseLookupIds
		});
		// Verificar se h  um registro na tabela para este n mero

		// Substituir as vari veis no corpo da requisi  o
		item.caption = item.caption.replace('%msg_recebida%', msgRecebida)
			.replace('%wa_nome%', cleanedWaName)
			.replace('%wa_numero%', userPhone);
		//console.log("nome wa:", message.pushName);
		//console.log("Dados MSG:", item.caption);

		var responseRecord = await loadAutoresponderResponseRecord(instance_id, responseLookupIds);

		if (responseRecord) {
			// Calcular o tempo decorrido desde a  ltima resposta
			var timeElapsed = now - new Date(responseRecord.last_response).getTime() / 1000;

			// Verificar se j  passou o tempo definido em delay
			if (timeElapsed < item.delay * 60) {
				//console.log("Proxima intera  o:", item.delay);
				console.log("Tempo ainda n o passou. Aguardando...");
				return false; // N o enviar a resposta autom tica se o tempo n o tiver passado
			}
		} else {
			console.log("Novo registro de delay será criado com identidade canônica após o envio.", {
				instance_id: instance_id,
				identity: autoresponderIdentity
			});
		}

		// Agrupa os parâmetros em um objeto
		const msg_info = {
			cleanedWaName: cleanedWaName,
			userPhone: userPhone,
			idConversa: idConversa,
			msgConversa: msgConversa,
			participanteGrupo: participanteGrupo,
			nextAction: nextAction,
			inputName: inputName,
			saveData: saveData
		};
		await touchAutoresponderResponseRecord(instance_id, autoresponderIdentity, responseLookupIds, new Date());

		//ENVIAR SE TIVER PASSADO TEMPO
		console.log("Tempo passou. Enviando a resposta automática...");

		// Send typing indicator for both Baileys and Cloud API
		try {
			var incoming_message_id = message?.wamid || message?.id || message?.key?.id || null;
			console.log('[AUTORESPONDER] Sending typing indicator. instance_id:', instance_id, 'message_id:', incoming_message_id);
			await Extend.sendPresence(sessions[instance_id], chat_id, item, instance_id, incoming_message_id);
		} catch (error) {
			console.error('[AUTORESPONDER] Error sending typing indicator:', error.message);
		}

		await WAZIPER.auto_send(instance_id, autoresponderReplyJid, autoresponderIdentity, "autoresponder", item, false, message, false, function (result) {
			//console.log('Resultado AR 3:', result);
			// Lógica de envio da resposta automática
		});

		// Stop typing indicator for Baileys
		if (sessions[instance_id] && typeof sessions[instance_id].sendPresenceUpdate === 'function') await sessions[instance_id].sendPresenceUpdate('available', autoresponderReplyJid);
		return false;
	},

	/*autoresponder: async function (instance_id, user_type, message) {
		var chat_id = message.key.remoteJid;
		var tz = await Extend.getAccountTimezone(instance_id);
		var now = new Date().getTime().toLocaleString('en-US', {
			timeZone: tz
		});
		now = now.split(",");
		now = now[0] + now[1] + now[2] + now[3];
		var item = await Common.db_get("sp_whatsapp_autoresponder", [{ instance_id: instance_id }, { status: 1 }]);
		if (!item) {
			return false;
		}
	
		//Accept sent to all/group/user
		switch (item.send_to) {
			case 2:
				if (user_type == "group") return false;
				break;
			case 3:
				if (user_type == "user") return false;
				break;
		}
	
		var check_autoresponder = await Extend.autoresponder_time(message, instance_id, chat_id);
	
		if (check_autoresponder && check_autoresponder + item.delay * 60 >= now) {
			return false;
		}
	
		//Except contacts
		var except_data = [];
		if (item.except != null) {
			var except_data = item.except.split(",");;
		}
	
		if (except_data.length > 0) {
			for (var i = 0; i < except_data.length; i++) {
				if (except_data[i] != "" && chat_id.indexOf(except_data[i]) != -1) {
					return false;
				}
			}
		}
	
		await WAZIPER.auto_send(instance_id, chat_id, chat_id, "autoresponder", item, false, message, false, function (result) { });
		return false;
	},*/

		track_call_node: async function (instance_id, node) {
			const session = sessions[instance_id];
			if (!session) {
				return null;
			}

			const callEvent = parse_call_node(node);
			if (!callEvent) {
				return null;
			}

			return track_call_node_metadata(session, instance_id, callEvent);
		},

		callcampaign_event: async function (instance_id, call) {
			const session = sessions[instance_id];
			if (!session) {
				return false;
			}

			const callEvent = await normalize_call_event(session, instance_id, call);
			if (!callEvent || !callEvent.id || !callEvent.isOutgoing) {
				return false;
			}

			if (!["accept", "reject", "timeout", "terminate"].includes(String(callEvent.status || ""))) {
				return false;
			}

			const now = Math.floor(Date.now() / 1000);
			const state = get_callcampaign_state(session, now);
			const trackedCall = state.outgoingByCallId[callEvent.id];
			if (!trackedCall) {
				return false;
			}

			if (!trackedCall.loggedEvents) {
				trackedCall.loggedEvents = {};
			}

			if (trackedCall.loggedEvents[callEvent.status]) {
				return false;
			}

			trackedCall.loggedEvents[callEvent.status] = now;
			trackedCall.updatedAt = now;

			await Common.db_insert('sp_whatsapp_history', {
				instance_id: instance_id,
				team_id: trackedCall.team_id,
				phone: trackedCall.phone,
				type: "bulk_call_event",
				message: `CALL ${String(callEvent.status).toUpperCase()}`,
				status: 1,
				time_post: now,
			});

			return true;
		},

		callresponder: async function (instance_id, call) {
			const session = sessions[instance_id];
			if (!session) {
				return false;
			}

			const callEvent = await normalize_call_event(session, instance_id, call);
			if (!callEvent || !callEvent.id || !callEvent.from || callEvent.isOutgoing) {
				return false;
			}

			const call_item = await get_callresponder_item(instance_id);
			if (!call_item) {
				return false;
			}

			const callStatus = String(callEvent.status || "");
			if (callStatus === "timeout" || callStatus === "terminate") {
				return false;
			}

			const chat_id = callEvent.resolvedReplyJid || callEvent.chatId || callEvent.from;
			const rejectJid = callEvent.resolvedRejectJid || chat_id;
			const now = Math.floor(Date.now() / 1000);
			const state = get_callresponder_state(session, now);
			const sameConnectedAccount = is_callresponder_peer_same_as_session(session, callEvent);
			callEvent.sameConnectedAccount = sameConnectedAccount;

			if (callStatus === "offer") {
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "offer_received",
					message: "CALL OFFER RECEIVED",
					status: 1,
					time_post: now
				});
			}

			if (!chat_id || sameConnectedAccount) {
				if (callStatus === "offer") {
					await insert_callresponder_history_event({
						instance_id,
						team_id: call_item.team_id,
						callEvent,
						state,
						eventKey: "reject_skipped_same_session",
						message: "CALL REJECT SKIPPED: same_connected_account",
						status: 1,
						time_post: now
					});
					log_callresponder_result("skip", instance_id, callEvent, { session, reason: "same_connected_account" });
				}
				return false;
			}

			const delayMinutes = Math.max(0, parseInt(call_item.delay, 10) || 0);
			const sendTo = parseInt(call_item.send_to, 10);
			const autoRejectEnabled = parseInt(call_item.auto_reject, 10) === 1;
			const exceptData = get_callresponder_except_data(call_item);

			if (is_callresponder_except_match(exceptData, callEvent)) {
				if (callStatus === "offer") {
					await insert_callresponder_history_event({
						instance_id,
						team_id: call_item.team_id,
						callEvent,
						state,
						eventKey: "reject_skipped_except_match",
						message: "CALL REJECT SKIPPED: except_match",
						status: 1,
						time_post: now
					});
					log_callresponder_result("skip", instance_id, callEvent, { session, reason: "except_match" });
				}
				return false;
			}

			const canSendReply = () => {
				const lastReplyAt = Number(state.lastReplyByChat[chat_id] || 0);
				return !(lastReplyAt && lastReplyAt + delayMinutes * 60 >= now);
			};

			const sendReply = async () => {
				if (state.repliedByCallId[callEvent.id] || !canSendReply()) {
					return false;
				}

				state.repliedByCallId[callEvent.id] = now;

				try {
					await WAZIPER.auto_send(instance_id, chat_id, chat_id, "callresponder", call_item, false, false, false, function (result) { });
					state.lastReplyByChat[chat_id] = now;
					return true;
				} catch (error) {
					delete state.repliedByCallId[callEvent.id];
					console.error("[CALLRESPONDER] auto_send failed", instance_id, callEvent.id, error?.message || error);
					return false;
				}
			};

			if (autoRejectEnabled && callEvent.isGroup) {
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "reject_skipped_group_call",
					message: "CALL REJECT SKIPPED: group_call",
					status: 1,
					time_post: now
				});
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "group_call" });
				return false;
			}

			if (autoRejectEnabled && callStatus === "offer") {
				const alreadyRejected = !!state.rejectedByCallId[callEvent.id];
				if (!alreadyRejected && typeof session.rejectCall !== "function") {
					await insert_callresponder_history_event({
						instance_id,
						team_id: call_item.team_id,
						callEvent,
						state,
						eventKey: "reject_skipped_unavailable",
						message: "CALL REJECT SKIPPED: reject_unavailable",
						status: 1,
						time_post: now
					});
					log_callresponder_result("skip", instance_id, callEvent, { session, reason: "reject_unavailable" });
					return false;
				}

				if (!alreadyRejected && !rejectJid) {
					await insert_callresponder_history_event({
						instance_id,
						team_id: call_item.team_id,
						callEvent,
						state,
						eventKey: "reject_skipped_unresolved_peer",
						message: "CALL REJECT SKIPPED: unresolved_peer",
						status: 1,
						time_post: now
					});
					log_callresponder_result("skip", instance_id, callEvent, { session, reason: "unresolved_peer" });
					return false;
				}

				if (!alreadyRejected) {
					try {
						await session.rejectCall(callEvent.id, rejectJid);
						state.rejectedByCallId[callEvent.id] = now;
						await insert_callresponder_history_event({
							instance_id,
							team_id: call_item.team_id,
							callEvent,
							state,
							eventKey: "reject_success",
							message: "CALL REJECT SUCCESS",
							status: 1,
							time_post: now
						});
						log_callresponder_result("success", instance_id, callEvent, { session, action: "reject" });
					} catch (error) {
						const rejectError = error?.message || String(error);
						await insert_callresponder_history_event({
							instance_id,
							team_id: call_item.team_id,
							callEvent,
							state,
							eventKey: `reject_failure:${rejectError}`,
							message: `CALL REJECT FAILURE: ${rejectError}`,
							status: 0,
							time_post: now
						});
						log_callresponder_result("failure", instance_id, callEvent, { session, action: "reject", error: rejectError });
						return false;
					}
				} else {
					log_callresponder_result("skip", instance_id, callEvent, { session, reason: "already_rejected" });
				}

				if (sendTo === 1 || sendTo === 3) {
					await sendReply();
				}

				return false;
			}

			if (state.repliedByCallId[callEvent.id]) {
				return false;
			}

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

			return false;
		},

		fast_callresponder: async function (instance_id, node, preTrackedCallEvent = null) {
			const session = sessions[instance_id];
			const parsedCallEvent = preTrackedCallEvent ? null : parse_call_node(node);
			const callEvent = preTrackedCallEvent || await track_call_node_metadata(session, instance_id, parsedCallEvent);
			if (!callEvent || callEvent.status !== "offer" || !callEvent.id || !callEvent.from) {
				return false;
			}
			callEvent.sameConnectedAccount = is_callresponder_peer_same_as_session(session, callEvent);

			if (!session || typeof session.rejectCall !== "function") {
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "reject_unavailable" });
				return false;
			}

			const call_item = await get_callresponder_item(instance_id);
			if (!call_item || parseInt(call_item.auto_reject, 10) !== 1) {
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "callresponder_disabled" });
				return false;
			}

			const now = Math.floor(Date.now() / 1000);
			const state = get_callresponder_state(session, now);
			await insert_callresponder_history_event({
				instance_id,
				team_id: call_item.team_id,
				callEvent,
				state,
				eventKey: "offer_received",
				message: "CALL OFFER RECEIVED",
				status: 1,
				time_post: now
			});

			if (callEvent.isGroup) {
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "reject_skipped_group_call",
					message: "CALL REJECT SKIPPED: group_call",
					status: 1,
					time_post: now
				});
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "group_call" });
				return false;
			}

			if (callEvent.isOutgoing) {
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "outgoing_call" });
				return false;
			}

			const chat_id = callEvent.resolvedReplyJid || callEvent.chatId || callEvent.from;
			const rejectJid = callEvent.resolvedRejectJid || chat_id;
			if (!chat_id || callEvent.sameConnectedAccount) {
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "reject_skipped_same_session",
					message: "CALL REJECT SKIPPED: same_connected_account",
					status: 1,
					time_post: now
				});
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "same_connected_account" });
				return false;
			}

			const exceptData = get_callresponder_except_data(call_item);
			if (is_callresponder_except_match(exceptData, callEvent)) {
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "reject_skipped_except_match",
					message: "CALL REJECT SKIPPED: except_match",
					status: 1,
					time_post: now
				});
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "except_match" });
				return false;
			}

			if (state.rejectedByCallId[callEvent.id]) {
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "already_rejected" });
				return false;
			}

			if (!rejectJid) {
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "reject_skipped_unresolved_peer",
					message: "CALL REJECT SKIPPED: unresolved_peer",
					status: 1,
					time_post: now
				});
				log_callresponder_result("skip", instance_id, callEvent, { session, reason: "unresolved_peer" });
				return false;
			}

			try {
				await session.rejectCall(callEvent.id, rejectJid);
				state.rejectedByCallId[callEvent.id] = now;
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: "reject_success",
					message: "CALL REJECT SUCCESS",
					status: 1,
					time_post: now
				});
				log_callresponder_result("success", instance_id, callEvent, { session, action: "reject" });
				return true;
			} catch (error) {
				const rejectError = error?.message || String(error);
				await insert_callresponder_history_event({
					instance_id,
					team_id: call_item.team_id,
					callEvent,
					state,
					eventKey: `reject_failure:${rejectError}`,
					message: `CALL REJECT FAILURE: ${rejectError}`,
					status: 0,
					time_post: now
				});
				log_callresponder_result("failure", instance_id, callEvent, { session, action: "reject", error: rejectError });
				return false;
			}
		},

		chatbot: async function (instance_id, user_type, message) {
			console.log(`[DEBUG] WAZIPER.chatbot called for ${instance_id}, user_type: ${user_type}`);
		var chat_id = message.key.remoteJid;
		const normalizeKeyword = (value = '') => String(value).toLowerCase().trim();
		const getKeywords = (item) => String(item.keywords || '')
			.split(',')
			.map(normalizeKeyword)
			.filter(keyword => keyword !== '');
		const canRunForUserType = (item) => {
			switch (Number(item.send_to)) {
				case 2:
					return user_type !== "group";
				case 3:
					return user_type !== "user";
				default:
					return true;
			}
		};
		const getBestChatbotMatch = (rows, content) => {
			if (!content) {
				return null;
			}

			const normalizedMessage = String(content).toLowerCase();
			const normalizedMessageTrimmed = normalizedMessage.trim();
			let bestMatch = null;

			for (const item of rows) {
				if (!canRunForUserType(item)) {
					continue;
				}

				const keywords = getKeywords(item);
				if (!keywords.length) {
					continue;
				}

				let matchedKeyword = null;
				if (Number(item.type_search) === 2) {
					matchedKeyword = keywords.find(keyword => normalizedMessageTrimmed === keyword) || null;
				} else {
					matchedKeyword = keywords.find(keyword => normalizedMessage.indexOf(keyword) !== -1) || null;
				}

				if (!matchedKeyword) {
					continue;
				}

				const candidate = {
					item,
					matchedKeyword,
					priority: Number(item.type_search) === 2 ? 2 : 1,
					keywordLength: matchedKeyword.length,
				};

				if (
					!bestMatch ||
					candidate.priority > bestMatch.priority ||
					(
						candidate.priority === bestMatch.priority &&
						candidate.keywordLength > bestMatch.keywordLength
					) ||
					(
						candidate.priority === bestMatch.priority &&
						candidate.keywordLength === bestMatch.keywordLength &&
						Number(candidate.item.id) < Number(bestMatch.item.id)
					)
				) {
					bestMatch = candidate;
				}
			}

			return bestMatch;
		};

		counter = 0;
		var sent = false;
		var content = false;

		var body_message = {};

		if (message.message?.ephemeralMessage) {
			message.message = message.message.ephemeralMessage.message;
		}

		if (message.message?.buttonsResponseMessage != undefined) {
			content = message.message?.buttonsResponseMessage.selectedDisplayText;
			body_message['content'] = content;
			body_message['type'] = 'buttonsResponseMessage';
		} else if (message.message?.templateButtonReplyMessage != undefined) {
			content = message.message.templateButtonReplyMessage.selectedDisplayText;
			body_message['content'] = message.message.templateButtonReplyMessage.selectedDisplayText;
			body_message['type'] = 'templateButtonReplyMessage';
		} else if (message.message?.listResponseMessage != undefined) {
			content = message.message.listResponseMessage.title + " " + message.message.listResponseMessage.description;
			body_message['content'] = message.message.listResponseMessage.title + " " + message.message.listResponseMessage.description;
			body_message['type'] = 'listResponseMessage';
		} else if (typeof message.message?.extendedTextMessage != "undefined" && message.message.extendedTextMessage != null) {
			content = message.message.extendedTextMessage.text;
			body_message['content'] = message.message.extendedTextMessage.text;
			body_message['type'] = 'textMessage';
		} else if (typeof message.message?.imageMessage != "undefined" && message.message.imageMessage != null) {
			content = message.message.imageMessage.caption;
			body_message['content'] = message.message.imageMessage.caption;
			body_message['type'] = 'imageMessage';
			if (!content) {
				content = '📷';
				body_message['content'] = '📷';
			}
		}
		else if (typeof message.message?.stickerMessage != "undefined" && message.message.stickerMessage != null) {

			content = message.message?.stickerMessage?.caption;
			body_message['content'] = message.message?.stickerMessage?.caption;
			body_message['type'] = 'stickerMessage';
			if (!content) {
				content = '📷';
				body_message['content'] = '📷';
			}
		} else if (typeof message.message?.videoMessage != "undefined" && message.message.videoMessage != null) {
			content = message.message?.videoMessage?.caption;
			body_message['content'] = message.message?.videoMessage?.caption;
			body_message['type'] = 'videoMessage';
			if (!content) {
				content = '📹';
				body_message['content'] = '📹';
			}
		}
		else if (typeof message.message?.audioMessage != "undefined" && message.message.audioMessage != null) {
			content = message.message?.audioMessage?.caption;
			body_message['content'] = message.message?.audioMessage?.caption;
			body_message['type'] = 'audioMessage';
			if (!content) {
				content = '🎧';
				body_message['content'] = '🎧';
			}
		} else if (typeof message.message?.conversation != "undefined") {
			content = message.message.conversation;
			body_message['content'] = message.message.conversation;
			body_message['type'] = 'textMessage';
		}

		if (!content) {
			message['message'] = {};
			message['message']['conversation'] = '👋';
			content = '👋';

			body_message['content'] = '';
			body_message['type'] = 'emptyMessage';
		}
		body_message['messages'] = message.message;


		WAZIPER.webhook(instance_id, {
			event: "received_message", message: {
				'body_message': body_message,
				'message_key': message?.key,
				'push_name': message?.pushName ?? '',
				'from_contact': Common.get_phone(chat_id, null)
			}
		});

		if (body_message['type'] == 'emptyMessage') return false;

		var subscriptor_ = await Extend.getSubscriber(WAZIPER, message, instance_id);
		if (subscriptor_.enabled_chatbot == '0') {
			console.error('chatbot cant continue', instance_id, subscriptor_.chatid, subscriptor_.enabled_chatbot)
			return false;
		}

		var items = await Common.db_fetch("sp_whatsapp_chatbot", [{ instance_id: instance_id }, { status: 1 }, { run: 1 }]);
		if (!items) {
			return false;
		}

		var allow_continue = await Extend.updateSubscriber(WAZIPER, subscriptor_, content, instance_id, user_type, message, null);

		if (allow_continue) {
			for (const item of items) {
				console.log(`[DEBUG] Chatbot Rule Check. Item ID: ${item.id}, Type: ${item.type_search}, Keywords: ${JSON.stringify(getKeywords(item))}, Content: "${content}"`);
			}

			const bestMatch = getBestChatbotMatch(items, content);
			if (bestMatch) {
				const item = bestMatch.item;
				console.log(`[DEBUG] Chatbot Match (${Number(item.type_search) === 2 ? 'Exact' : 'Contains'})! Msg: "${String(content).toLowerCase()}", Key: "${bestMatch.matchedKeyword}"`);
				sent = true;
				var ct = counter++;

				var allow_continue_cb = await Extend.updateSubscriber(WAZIPER, subscriptor_, content, instance_id, user_type, message, item);
				if (allow_continue_cb) {
					setTimeout(function () {
						//WAZIPER.chatbot_latest_receive = moment().add(CHATBOT_RESET_TIME, 'm');
						WAZIPER.auto_send(instance_id, chat_id, chat_id, "chatbot", item, false, message, content, function (result) {
							if (item.nextBot != null && item.nextBot != '' && Number(item.save_data) !== 2) {
								Extend.nextBot(result, item, message, instance_id, user_type, WAZIPER);
							}
						});
					}, ct * chatbot_delay);
				}
			}
		}


		if (!sent) {
			console.log(`[DEBUG] No chatbot keywords matched for ${instance_id}. Checking default.`);
			var item = await Common.db_get("sp_whatsapp_chatbot", [{ instance_id: instance_id }, { status: 1 }, { is_default: 1 }]);

			if (item) {
				var run = true;

				switch (item.send_to) {
					case 2:
						if (user_type == "group") run = false;;
						break;
					case 3:
						if (user_type == "user") run = false;
						break;
				}
				if (run) {
					WAZIPER.auto_send(instance_id, chat_id, chat_id, "chatbot", item, false, message, content, function (result) {
						Extend.nextBot(result, item, message, instance_id, user_type, WAZIPER);
					});
				}
			}
		}

	},

		resetAi: async function (instance_id, res) {
			Extend.resetAi(instance_id);
			console.log(`${instance_id} OpenAi history restarted`);
			res.json({ status: 'success', message: `${instance_id} OpenAi history restarted`, data: { "instance_id": instance_id } });
		},

		bot_builder_send: async function (instance_id, access_token, req, res) {
			try {
				const team = await Common.db_get("sp_team", [{ ids: access_token }]);
				if (!team) {
					return res.json({ status: 'error', message: 'Falha na autenticação da equipe' });
				}

				const account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: team.id }]);
				if (!account) {
					return res.json({ status: 'error', message: 'Conta WhatsApp não encontrada' });
				}

				let chat_id = String(req.body.chat_id || '').trim();
				const messageType = String(req.body.message_type || 'text').trim().toLowerCase();
				let payload = req.body.payload || {};
				if (typeof payload === 'string') {
					try {
						payload = JSON.parse(payload || '{}');
					} catch (error) {
						payload = {};
					}
				}

				if (!chat_id) {
					return res.json({ status: 'error', message: 'Destino do Bot Builder não informado' });
				}

				if (account.login_type != 1 && !chat_id.includes('@')) {
					chat_id = `${chat_id}@s.whatsapp.net`;
				}

				const item = {
					id: parseInt(req.body.bot_id || payload.bot_id || 0, 10) || 0,
					team_id: team.id,
					type: 1,
					caption: '',
					media: '',
					filename: '',
					name: 'Bot Builder',
					presenceType: 1,
					presenceTime: Math.max(1, Math.min(8, parseInt(req.body.presence_time || payload.presence_time || payload.presenceTime || 2, 10) || 2))
				};

				const quotaAllowed = await WAZIPER.limit(item, 'api');
				if (!quotaAllowed) {
					return res.json({ status: 'error', message: 'Limite mensal de mensagens atingido ou plano expirado' });
				}

				let typeMedia = '';
				let data = { text: String(payload.text || payload.caption || '') };

				const makeButtonId = (value, index) => {
					const safe = String(value || '').trim();
					if (safe) return safe.substring(0, 64);
					return `bb_btn_${Date.now()}_${index}`;
				};

				switch (messageType) {
					case 'image':
						item.media = String(payload.url || payload.media || '').trim();
						item.caption = String(payload.caption || payload.text || '').trim();
						data = { image: { url: item.media }, caption: item.caption };
						typeMedia = 'imageMessage';
						break;

					case 'video':
						item.media = String(payload.url || payload.media || '').trim();
						item.caption = String(payload.caption || payload.text || '').trim();
						data = { video: { url: item.media }, caption: item.caption };
						typeMedia = 'videoMessage';
						break;

					case 'audio':
						item.media = String(payload.url || payload.media || '').trim();
						data = { audio: { url: item.media }, ptt: true };
						typeMedia = 'audioMessage';
						break;

					case 'buttons': {
						const rawButtons = Array.isArray(payload.buttons) ? payload.buttons.slice(0, 3) : [];
						const text = String(payload.text || payload.caption || 'Escolha uma opção:').trim();
						const templateButtons = Array.isArray(payload.templateButtons) ? payload.templateButtons.slice(0, 3) : [];
						const interactiveButtons = Array.isArray(payload.interactiveButtons) ? payload.interactiveButtons.slice(0, 3) : [];

						rawButtons.forEach((button, index) => {
							const label = String(button.label || button.text || button.title || `Opção ${index + 1}`).trim().substring(0, 20);
							const id = makeButtonId(button.id || button.value || label, index);
							templateButtons.push({
								quickReplyButton: {
									id,
									displayText: label
								}
							});
							interactiveButtons.push({
								name: 'quick_reply',
								buttonParamsJson: JSON.stringify({
									id,
									display_text: label,
									disabled: false
								})
							});
						});

						if (interactiveButtons.length === 0 && templateButtons.length > 0) {
							templateButtons.forEach((button, index) => {
								const quick = button.quickReplyButton || null;
								if (!quick) return;
								const label = String(quick.displayText || quick.id || `Opção ${index + 1}`).trim().substring(0, 20);
								const id = makeButtonId(quick.id || label, index);
								interactiveButtons.push({
									name: 'quick_reply',
									buttonParamsJson: JSON.stringify({ id, display_text: label, disabled: false })
								});
							});
						}

						item.caption = text;
						data = {
							_isInteractiveButtons: true,
							text,
							footer: String(payload.footer || ' ').substring(0, 60),
							templateButtons,
							interactiveButtons
						};
						typeMedia = 'button';
						break;
					}

					case 'list': {
						const sections = Array.isArray(payload.sections) ? payload.sections : [];
						item.caption = String(payload.text || payload.caption || 'Selecione uma opção:').trim();
						data = {
							title: String(payload.title || 'Menu').substring(0, 60),
							text: item.caption,
							footer: String(payload.footer || '').substring(0, 60),
							buttonText: String(payload.buttonText || payload.button_text || 'Selecionar').substring(0, 20),
							sections
						};
						typeMedia = 'list';
						break;
					}

					case 'carousel': {
						const cards = Array.isArray(payload.cards) ? payload.cards : [];
						item.caption = String(payload.text || payload.caption || 'Escolha uma opção:').trim();
						data = {
							text: item.caption,
							title: payload.title || '',
							subtitle: payload.subtitle || '',
							footer: payload.footer || '',
							cards: cards.slice(0, 10).map((card, index) => {
								let imageUrl = '';
								if (typeof card.media === 'string') imageUrl = card.media;
								if (!imageUrl && card.media && typeof card.media === 'object') imageUrl = card.media.url || '';
								if (!imageUrl && typeof card.image === 'string') imageUrl = card.image;
								if (!imageUrl && card.image && typeof card.image === 'object') imageUrl = card.image.url || '';

								const normalizedCard = {
									buttons: Array.isArray(card.buttons) ? card.buttons.slice(0, 3) : [],
									title: String(card.title || `Card ${index + 1}`).substring(0, 60),
									body: String(card.body || card.description || ' ').substring(0, 1024),
									footer: String(card.footer || ' ').substring(0, 60)
								};

								if (imageUrl) normalizedCard.image = { url: String(imageUrl).trim() };
								return normalizedCard;
							})
						};
						typeMedia = 'carousel';
						break;
					}

					default:
						item.caption = String(payload.text || payload.caption || '').trim();
						data = { text: item.caption };
						break;
				}

				if (['image', 'video', 'audio'].includes(messageType) && !item.media) {
					return res.json({ status: 'error', message: 'URL da mídia não informada no Bot Builder' });
				}

				if (messageType === 'buttons' && (!data.interactiveButtons || data.interactiveButtons.length === 0) && (!data.templateButtons || data.templateButtons.length === 0)) {
					return res.json({ status: 'error', message: 'Nenhum botão válido informado no Bot Builder' });
				}

				if (messageType === 'carousel' && (!data.cards || data.cards.length === 0)) {
					return res.json({ status: 'error', message: 'Nenhum card válido informado no Bot Builder' });
				}

				if (!typeMedia && (!item.caption || item.caption.trim() === '')) {
					return res.json({ status: 'error', message: 'Mensagem vazia no Bot Builder' });
				}

				const result = await new Promise((resolve) => {
					let resolved = false;
					const finish = (payloadResult) => {
						if (resolved) return;
						resolved = true;
						resolve(payloadResult || { status: 0, message: 'Sem retorno do envio' });
					};

					setTimeout(() => finish({ status: 0, message: 'Tempo limite do envio Bot Builder' }), 60000);

					(async () => {
						try {
							await Extend.sendPresence(sessions[instance_id], chat_id, item, instance_id, req.body.message_id || payload.message_id || null);
						} catch (error) {
							console.error('[BOT_BUILDER_SEND] Falha ao enviar presença digitando:', error.message);
						}

						WAZIPER.process_send_message(
							chat_id,
							data,
							'api',
							instance_id,
							chat_id,
							item,
							finish,
							typeMedia
						);
					})();
				});

				if (result && (result.status === 1 || result.status === true || result.status === 'success')) {
					return res.json({ status: 'success', message: 'Mensagem enviada pelo Bot Builder', data: result });
				}

				return res.json({ status: 'error', message: result?.message || 'Falha ao enviar pelo Bot Builder', data: result });
			} catch (error) {
				console.error('[BOT_BUILDER_SEND] Erro:', error);
				return res.json({ status: 'error', message: error.message || 'Erro interno no envio do Bot Builder' });
			}
		},

		send_cloud_template: async function (instance_id, access_token, req, res) {
		var chat_id = req.body.chat_id;
		var language_code = req.body.language_code;
		var template_name = req.body.template_name;
		var components = req.body.components;
		var team = await Common.db_get("sp_team", [{ ids: access_token }]);
		var type = "api"

		if (!team) {
			return res.json({ status: 'error', message: "The authentication process has failed" });
		}

		var account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: team.id }]);

		if (account && account.login_type == 1) {

			let item = {
				team_id: team.id
			}

			var limit = await WAZIPER.limit(item, type);
			if (!limit) {
				//return callback({ status: 0, stats: false, message: "The number of messages you have sent per month has exceeded the maximum limit" });
				return res.json({ status: 'error', message: "The number of messages you have sent per month has exceeded the maximum limit" });
			}

			let tmpData = {};
			try {
				let rawData = account.data || account.tmp;
				tmpData = rawData ? JSON.parse(rawData) : {};
			} catch (e) {
				console.error("Error parsing account data for instance " + instance_id, e);
			}

			const bearer = tmpData.token || tmpData.access_token || "";
			const phoneNumberId = tmpData.phone_number_id || account.username;
			const whatsappAPIURL = `https://graph.facebook.com/v19.0/${phoneNumberId}/messages`;

			let data = JSON.stringify({
				"messaging_product": "whatsapp",
				"recipient_type": "individual",
				"to": chat_id,
				"type": "template",
				"template": {
					"name": template_name,
					"language": {
						"code": language_code
					},
					"components": components
				}
			});

			let config = {
				method: 'post',
				maxBodyLength: Infinity,
				url: whatsappAPIURL,
				headers: {
					'Content-Type': 'application/json',
					'Authorization': `Bearer ${bearer}`
				},
				data: data
			};

			axios.request(config)
				.then(async (response) => {

					try {

						const message = response.data.messages[0];
						const pushname = response.data.contacts[0]?.profile?.name ?? instance_id;

						let message___ = await Extend.process_official_sent_message(JSON.parse(data), chat_id, message?.id ?? Common.makeid(), "", "template");

						//* LIVECHAT FUNCTION
						if ((config['extended_functions'] ?? true)) {
							Extend.chat.processChatMessages(WAZIPER, false, { messages: [message___] }, instance_id, true);
						}
						//* END LIVECHAT FUNCTION	
					} catch (error) {
						console.error(error)
					}


					console.log('Mensaje enviado: ', JSON.stringify(response.data, null, 4));
					WAZIPER.stats(instance_id, type, item, 1);
					return res.json({ status: 'success', message: "Success", "message": response.data });
				})
				.catch((error) => {
					console.error('Error al enviar mensaje: ', error);
					WAZIPER.stats(instance_id, type, item, 0);
					return res.json({ status: 'error', message: error });
				});
		}
	},

	send_message: async function (instance_id, access_token, req, res) {
		var type = req.query.type;
		var chat_id = req.body.chat_id;
		var media_url = req.body.media_url;
		var caption = req.body.caption ?? '';
		var filename = req.body.filename ?? '';
		var template = req.body.template ?? 0;
		var team = await Common.db_get("sp_team", [{ ids: access_token }]);

		if (!team) {
			return res.json({ status: 'error', message: "The authentication process has failed" });
		}

		if (typeof chat_id === "string" && chat_id.indexOf("@") === -1 && chat_id.indexOf("g.us") === -1) {
			chat_id = `${chat_id}@s.whatsapp.net`;
		}

		if (chat_id.indexOf("g.us") !== -1) {
			chat_id = chat_id;
		} else {
			chat_id = chat_id.split("@");
			chat_idd = await Common.check_especials(chat_id[0]);
			chat_id = chat_idd + "@" + chat_id[1];
		}

		item = {
			team_id: team.id,
			type: parseInt(type),
			template: type != 1 ? parseInt(template) : 0,
			caption: caption,
			media: media_url,
			filename: filename
		}

		await WAZIPER.auto_send(instance_id, chat_id, chat_id, "api", item, false, false, false, function (result) {
			console.log(result);
			if (result && (result.status === 1 || result.status === true || result.status === "success")) {
				if (result.message != undefined && typeof result.message === "object") {
					result.message.status = "SUCCESS";
				}
				return res.json({ status: 'success', message: "Success", "message": result.message });
			} else {
				return res.json({ status: 'error', message: result?.message || "Error" });
			}
		});
	},

	single_send_message: async function (instance_id, access_token, req, res) {
		var type = req.query.type;
		var chat_id = req.body.chat_id;
		var media_url = req.body.media_url;
		var caption = req.body.caption ?? '';
		var filename = req.body.filename ?? '';
		var template = req.body.template ?? 0;
		var team = await Common.db_get("sp_team", [{ ids: access_token }]);

		if (!team) {
			return res.json({ status: 'error', message: "The authentication process has failed" });
		}

		if (typeof chat_id === "string" && chat_id.indexOf("@") === -1 && chat_id.indexOf("g.us") === -1) {
			chat_id = `${chat_id}@s.whatsapp.net`;
		}

		if (chat_id.indexOf("g.us") !== -1) {
			chat_id = chat_id;
		} else {
			chat_id = chat_id;
		}

		item = {
			team_id: team.id,
			type: parseInt(type),
			template: type != 1 ? parseInt(template) : 0,
			caption: caption,
			media: media_url,
			filename: filename
		}

		await WAZIPER.auto_send(instance_id, chat_id, chat_id, "direct", item, false, false, false, function (result) {
			console.log(result);
			if (result && (result.status === 1 || result.status === true || result.status === "success")) {
				if (result.message != undefined && typeof result.message === "object") {
					result.message.status = "SUCCESS";
				}
				return res.json({ status: 'success', message: "Success", "message": result.message });
			} else {
				return res.json({ status: 'error', message: result?.message || "Error" });
			}
		});
	},

	retry_onfail: async function (instance_id) {
		sessions[instance_id] = await WAZIPER.session(instance_id, true);
	},

	webhook_handler: async function (instance_id, req, res) {
		console.log('Mensaje recibido post', JSON.stringify(req.body));
		var account = await Common.db_get("sp_accounts", [{ token: instance_id }]);
		try {
			if (account && account.login_type == 1) {

				if (req.body.entry[0].changes[0].value.messages) {
					const message = req.body.entry[0].changes[0].value.messages[0];
					const contactData = req.body.entry[0].changes[0].value.contacts?.[0] || {};
					const pushname = contactData?.profile?.name;

					// Meta 2026 BSUID: keep phone/wa_id isolated from non-numeric identifiers
					const inboundWaId = contactData?.wa_id || message.from || null;
					message._wa_id = inboundWaId && /^[0-9]+$/.test(String(inboundWaId)) ? String(inboundWaId) : null;
					message._bsuid = contactData?.user_id || message.from_user_id || null;
					message._username = contactData?.username || contactData?.profile?.username || message.from_username || null;
					message._parent_bsuid = contactData?.parent_user_id || message.from_parent_user_id || null;
					const inboundIdentity = message.from || message._bsuid || message._wa_id || null;

					if (inboundIdentity) {
						if (!message.from) {
							message.from = inboundIdentity;
						}

						try {
							Extend.mark_as_read(message, instance_id);
						} catch (error) {

						}

						message_to_script = await Extend.process_official_message(message, pushname, false, instance_id);
						await WAZIPER.recordOfficialFlowReplyEvent(instance_id, message, message_to_script);

						//* LIVECHAT FUNCTION
						if ((config['extended_functions'] ?? true)) {
							Extend.chat.processChatMessages(WAZIPER, false, { messages: [message_to_script] }, instance_id, true);
						}
						//* END LIVECHAT FUNCTION

								await Common.sleep(1000);
								const botBuilderHandled = await WAZIPER.bot_builder_flow(instance_id, message_to_script.key.remoteJid, message_to_script).catch((error) => {
									console.error(`[BOT_BUILDER] Erro ao processar mensagem Cloud API: ${error.message}`);
									return false;
								});

								if (!botBuilderHandled) {
									WAZIPER.chatbot(instance_id, "user", message_to_script);
									await Common.sleep(1000);
									WAZIPER.autoresponder(instance_id, "user", message_to_script);
								}
					}
				}
			} else {
				res.status(400).send(`Instance ${instance_id} not exist`);
			}
		} catch (error) {

		} finally {
			res.status(200).send('OK');
		}

	},

	recordOfficialFlowReplyEvent: async function (instance_id, officialMessage, message_to_script) {
		try {
			const flowReply = message_to_script?.flow_reply || null;
			if (!flowReply) {
				return;
			}

			const responseData = flowReply.response_json && typeof flowReply.response_json === 'object'
				? flowReply.response_json
				: {};

			let flow = null;
			const flowId = parseInt(responseData.flow_id || 0, 10);
			if (flowId > 0) {
				flow = await Common.db_get('sp_whatsapp_flows', [{ id: flowId }]);
			}

			if (!flow && responseData.flow_slug) {
				flow = await Common.db_query(
					'SELECT * FROM sp_whatsapp_flows WHERE slug = ? ORDER BY changed DESC LIMIT 1',
					[String(responseData.flow_slug)]
				);
			}

			if (!flow && responseData.meta_flow_id) {
				flow = await Common.db_query(
					'SELECT * FROM sp_whatsapp_flows WHERE meta_flow_id = ? ORDER BY changed DESC LIMIT 1',
					[String(responseData.meta_flow_id)]
				);
			}

			const account = await Common.db_get('sp_accounts', [{ token: instance_id }]);
			const contactId = Common.get_phone(message_to_script?.key?.remoteJid || officialMessage?.from || '');

			await Common.db_insert('sp_whatsapp_flow_events', {
				team_id: account?.team_id || flow?.team_id || null,
				flow_id: flow?.id || (flowId > 0 ? flowId : null),
				endpoint_id: flow?.endpoint_id || null,
				account_id: account?.id || flow?.account_id || null,
				account_ids: account?.ids || flow?.account_ids || null,
				instance_id: instance_id || '',
				event_type: 'flow_reply',
				direction: 'inbound',
				contact_id: contactId,
				chat_id: contactId,
				flow_token: String(responseData.flow_token || ''),
				message_id: officialMessage?.id || message_to_script?.wamid || null,
				status: 'success',
				payload: JSON.stringify(officialMessage?.interactive?.nfm_reply || flowReply || {}, null, 0),
				response: JSON.stringify(responseData, null, 0),
				error_message: '',
				created: Common.time()
			});
		} catch (error) {
			console.error('Failed to record Flow reply event', error);
		}
	},

	process_send_message: async function (chat_id, data, type, instance_id, phone_number, item, callback, type_media = '') {
		console.log('[STATUS-LOG] process_send_message called:', {
			type: type,
			instance_id: instance_id,
			hasItem: !!item,
			itemId: item?.id,
			itemTeamId: item?.team_id,
			itemType: item?.type,
			chat_id: chat_id
		});

		if (!item || !item.team_id) {
			console.error('[STATUS-LOG] process_send_message: item or item.team_id missing!', { item });
		}

		var account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: item?.team_id }]);
		console.log("[DEBUG] process_send_message account found:", account ? account.id : "null", "type:", account ? account.login_type : "N/A");

		if (account && account.login_type == 1) {

			let tmpData = {};
			try {
				let rawData = account.data || account.tmp;
				tmpData = rawData ? JSON.parse(rawData) : {};
			} catch (e) {
				console.error("Error parsing account data for instance " + instance_id, e);
			}
			const bearer = tmpData.token || tmpData.access_token || "";
			console.log(`[DEBUG] Cloud API Bearer Token (first 10 chars): ${bearer.substring(0, 10)}...`);

			const phoneNumberId = tmpData.phone_number_id || account.username;
			const whatsappAPIURL = `https://graph.facebook.com/v19.0/${phoneNumberId}/messages`;
			const rawChatId = chat_id;
			const syntheticCloudMessage = {
				key: { remoteJid: rawChatId },
				_wa_id: Extend.isNumericIdentifier(phone_number) ? String(phone_number).trim() : '',
				_bsuid: Extend.isNumericIdentifier(phone_number) ? '' : Extend.getIdentifierPrefix(phone_number),
			};
			const resolvedCloudChatId = Extend.resolveCloudDestination(rawChatId, syntheticCloudMessage, phone_number, {
				number: Extend.isNumericIdentifier(phone_number) ? String(phone_number).trim() : '',
				instance_id: instance_id
			});

			console.log('[CLOUD SEND] target resolution', {
				instance_id: instance_id,
				type: type,
				rawChatId: rawChatId,
				phone_number: phone_number,
				resolvedCloudChatId: resolvedCloudChatId
			});
			bulkMetaDebugLog({
				event: 'cloud_target_resolution',
				instance_id: instance_id,
				type: type,
				raw_chat_id: rawChatId,
				phone_number: phone_number,
				resolved_to: resolvedCloudChatId
			});

			if (!resolvedCloudChatId) {
				const fail_message = "Cloud API destination could not be resolved to a numeric wa_id";
				console.error('[ERROR] ' + fail_message, {
					instance_id: instance_id,
					rawChatId: rawChatId,
					phone_number: phone_number,
					itemId: item?.id,
					type: type
				});
				if (typeof callback === 'function') callback({ status: 0, type: type, phone_number: phone_number, stats: true, message: fail_message });
				WAZIPER.stats(instance_id, type, item, 0);
				return;
			}

			chat_id = resolvedCloudChatId;

			var messageBody = {};


			if (item.media != "" && item.media && type_media !== 'template') {
				const build_cloud_media_payload = async (cloud_type, media_url, extra_fields = {}) => {
					const mime_type = (data.mimetype || data.mimeType || "").toString();
					const suggested_filename = (data.fileName || data.filename || Common.get_file_name(media_url || item.media) || "").toString();
					const media_reference = await WAZIPER.resolve_cloud_media_reference(media_url, phoneNumberId, bearer, mime_type, suggested_filename);

					if (!media_reference.ok) {
						return { ok: false, error: media_reference.error || "Cloud media payload could not be prepared" };
					}

					const media_body = (media_reference.by === "id")
						? { id: media_reference.value }
						: { link: media_reference.value };

					return {
						ok: true,
						body: {
							messaging_product: "whatsapp",
							to: chat_id,
							type: cloud_type,
							[cloud_type]: {
								...media_body,
								...extra_fields
							}
						},
						mediaRef: media_reference
					};
				};

				const detect_cloud_media_kind = (media_url, mime_hint = "") => {
					const mime_value = (mime_hint || "").toString().toLowerCase();
					if (mime_value.startsWith("audio/")) return "audio";
					if (mime_value.startsWith("video/")) return "video";
					if (mime_value.startsWith("image/")) return "image";
					if (mime_value.startsWith("application/")) return "document";

					const safe_url = (media_url || "").toString();
					const clean_url = safe_url.split("?")[0].split("#")[0];
					const ext = clean_url.includes(".") ? clean_url.substring(clean_url.lastIndexOf(".") + 1).toLowerCase() : "";

					if (["ogg", "opus", "mp3", "m4a", "aac", "amr", "wav", "weba"].includes(ext)) return "audio";
					if (["mp4", "3gp", "mov", "webm", "mkv"].includes(ext)) return "video";
					if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) return "image";
					if (["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "csv", "zip", "rar"].includes(ext)) return "document";

					return null;
				};

				const media_hint_url = data?.audio?.url || data?.video?.url || data?.image?.url || data?.document?.url || item.media;
				const inferred_cloud_kind = detect_cloud_media_kind(media_hint_url, data?.mimetype || data?.mimeType || "");

				let normalized_type_media = type_media;
				if (inferred_cloud_kind === "audio") normalized_type_media = "audioMessage";
				if (inferred_cloud_kind === "video") normalized_type_media = "videoMessage";
				if (inferred_cloud_kind === "image") normalized_type_media = "imageMessage";
				if (inferred_cloud_kind === "document") normalized_type_media = "documentMessage";

				if (normalized_type_media !== type_media) {
					console.log(`[DEBUG] Cloud media type normalized from ${type_media} to ${normalized_type_media} for ${media_hint_url}`);
				}

				let media_payload_result = null;
				switch (normalized_type_media) {
					case "videoMessage":
						media_payload_result = await build_cloud_media_payload(
							"video",
							data?.video?.url || data?.image?.url || data?.document?.url || item.media,
							data?.caption ? { caption: data.caption } : {}
						);
						break;

					case "imageMessage":
						media_payload_result = await build_cloud_media_payload(
							"image",
							data?.image?.url || data?.document?.url || item.media,
							data?.caption ? { caption: data.caption } : {}
						);
						break;

					case "audioMessage":
						var audio_extra = {};
						if (typeof data?.ptt === "boolean") {
							audio_extra.voice = data.ptt;
						} else {
							// Keep Cloud behavior aligned with Baileys voice notes.
							audio_extra.voice = true;
						}
						media_payload_result = await build_cloud_media_payload(
							"audio",
							data?.audio?.url || data?.image?.url || data?.document?.url || item.media,
							audio_extra
						);
						break;

					default:
						var document_extra = {};
						if (data?.caption) document_extra.caption = data.caption;
						if (data?.fileName) document_extra.filename = data.fileName;
						media_payload_result = await build_cloud_media_payload("document", data?.document?.url || item.media, document_extra);
						break;
				}

				if (!media_payload_result || !media_payload_result.ok) {
					const fail_message = media_payload_result?.error || "Cloud media payload could not be prepared";
					console.error('[ERROR] ' + fail_message, { instance_id: instance_id, chat_id: chat_id, type_media: type_media });
					if (typeof callback === 'function') callback({ status: 0, type: type, phone_number: phone_number, stats: true, message: fail_message });
					WAZIPER.stats(instance_id, type, item, 0);
					return;
				}

				messageBody = media_payload_result.body;
				if (media_payload_result.mediaRef?.debug?.mode === "uploaded_local") {
					console.log('[DEBUG] Cloud media uploaded from local path:', media_payload_result.mediaRef.debug.localPath);
				}
				if (media_payload_result.mediaRef?.debug?.mode === "remote_link") {
					console.log('[DEBUG] Cloud media using remote link:', media_payload_result.mediaRef.value, media_payload_result.mediaRef.debug.remote || {});
				}
			} else {

				switch (type_media) {
					case 'button':
						//console.log(JSON.stringify(data))
						messageBody = {
							messaging_product: "whatsapp",
							to: chat_id,
							type: "interactive",
							interactive: {
								type: "button",
								body: {
									text: (data.text ?? (data.caption ?? 'press button')).substring(0, 1024) // Cloud API limit: 1024 chars
								},
								action: {
									buttons: []
								}
							}
						}

						if (data.footer) {
							messageBody.interactive.footer = {
								text: data.footer.substring(0, 60) // Cloud API limit: 60 chars
							}
						}

						if (data.image) {

							messageBody.interactive.header = {
								type: "image",
								image: {
									link: data.image.url
								}
							}
						}

						// Handle both templateButtons (Single Message) and interactiveButtons (Chatbot)
						var buttonsToProcess = data.templateButtons || data.interactiveButtons;

						if (buttonsToProcess && Array.isArray(buttonsToProcess)) {
							// If there is ONLY ONE button and it is a URL, use the native cta_url interactive type
							if (buttonsToProcess.length === 1) {
								let element = buttonsToProcess[0];
								let isUrlButton = false;
								let isCallButton = false;
								let url = '';
								let displayText = '';
								let phoneNumber = '';

								if (element.urlButton) {
									isUrlButton = true;
									url = element.urlButton.url;
									displayText = element.urlButton.displayText;
								} else if (element.callButton) {
									isCallButton = true;
									phoneNumber = element.callButton.phoneNumber;
									displayText = element.callButton.displayText;
								} else if (element.buttonParamsJson) {
									try {
										let params = typeof element.buttonParamsJson === 'string'
											? JSON.parse(element.buttonParamsJson)
											: element.buttonParamsJson;
										if (params.url) {
											isUrlButton = true;
											url = params.url;
											displayText = params.display_text || params.displayText || params.title;
										} else if (params.phone_number) {
											isCallButton = true;
											phoneNumber = params.phone_number;
											displayText = params.display_text || params.displayText || params.title;
										}
									} catch (e) { }
								}

								if (isUrlButton) {
									messageBody.interactive.type = "cta_url";
									messageBody.interactive.action = {
										name: "cta_url",
										parameters: {
											display_text: (displayText || 'Link').substring(0, 20),
											url: url
										}
									};
									// Empty buttonsToProcess so the next logic (reply buttons) is skipped
									buttonsToProcess = [];
								} else if (isCallButton) {
									// Use cta_url with tel: protocol for phone calls
									messageBody.interactive.type = "cta_url";
									messageBody.interactive.action = {
										name: "cta_url",
										parameters: {
											display_text: (displayText || 'Ligar').substring(0, 20),
											url: 'tel:' + phoneNumber.replace(/[^0-9+]/g, '')
										}
									};
									// Empty buttonsToProcess so the next logic (reply buttons) is skipped
									buttonsToProcess = [];
								}
							}

							buttonsToProcess.forEach(element => {
								// Check if it's templateButtons format (quickReplyButton)
								if (element.quickReplyButton) {
									messageBody.interactive.action.buttons.push({
										type: "reply",
										reply: {
											id: element.quickReplyButton.id,
											title: element.quickReplyButton.displayText
										}
									});
								}
								// Check if it's interactiveButtons format (buttonParamsJson)
								else if (element.buttonParamsJson) {
									try {
										let params = typeof element.buttonParamsJson === 'string'
											? JSON.parse(element.buttonParamsJson)
											: element.buttonParamsJson;

										// Cloud API only supports quick_reply buttons in interactive buttons
										// cta_url buttons need to be converted to quick_reply with displayText
										messageBody.interactive.action.buttons.push({
											type: "reply",
											reply: {
												id: params.id || Math.random().toString(36).substr(2, 9),
												title: (params.display_text || params.displayText || 'Button').substring(0, 20)
											}
										});
									} catch (e) {
										console.error('[ERROR] Failed to parse button params:', e.message);
									}
								}
								// Handle urlButton from templates (convert to reply for Cloud API)
								else if (element.urlButton) {
									messageBody.interactive.action.buttons.push({
										type: "reply",
										reply: {
											id: 'url_' + Math.random().toString(36).substr(2, 9),
											title: (element.urlButton.displayText || 'Link').substring(0, 20)
										}
									});
								}
								// Handle callButton from templates (convert to reply for Cloud API)
								else if (element.callButton) {
									messageBody.interactive.action.buttons.push({
										type: "reply",
										reply: {
											id: 'call_' + Math.random().toString(36).substr(2, 9),
											title: (element.callButton.displayText || 'Ligar').substring(0, 20)
										}
									});
								}
								// Handle catalogButton from templates (convert to reply for Cloud API)
								else if (element.catalogButton) {
									messageBody.interactive.action.buttons.push({
										type: "reply",
										reply: {
											id: 'catalog_' + Math.random().toString(36).substr(2, 9),
											title: (element.catalogButton.displayText || 'Catálogo').substring(0, 20)
										}
									});
								}
							});
						}
						break;
					case 'list':
						//console.log(JSON.stringify(data))
						messageBody = {
							messaging_product: "whatsapp",
							recipient_type: "individual",
							to: chat_id,
							type: "interactive",
							interactive: {
								type: "list",
								header: {
									type: "text",
									text: (data.title || 'Menu').substring(0, 60) // Cloud API limit: 60 chars
								},
								body: {
									text: (data.text || ' ').substring(0, 1024) // Cloud API limit: 1024 chars
								},
								footer: {
									text: (data.footer || '').substring(0, 60) // Cloud API limit: 60 chars
								},
								action: {
									button: (data.buttonText || 'Select').substring(0, 20), // Cloud API limit: 20 chars
									sections: []
								}
							}
						};

						(data.sections || []).forEach(section => {
							Common.special_log(section, 'section');
							let rows_to_add = [];
							(section.rows || []).forEach(row => {
								Common.special_log(row, 'row');
								rows_to_add.push({
									title: (row.title || 'Item').substring(0, 24), // Cloud API limit: 24 chars
									id: row.rowId,
									description: (row.description || '').substring(0, 72) // Cloud API limit: 72 chars
								});
							});

							let section_to_add = {
								title: (section.title || 'Section').substring(0, 24), // Cloud API limit: 24 chars
								rows: rows_to_add.slice(0, 10) // Cloud API limit: 10 rows per section
							};
							messageBody.interactive.action.sections.push(section_to_add);
						});
						// Cloud API limit: 10 sections max
						messageBody.interactive.action.sections = messageBody.interactive.action.sections.slice(0, 10);
						break;

					case 'template':
						{
							const langCode = normalizeLanguageCode(data.language);
							const structural = Array.isArray(data.components) ? data.components : [];
							const bodyExampleValues = parseBodyExampleValues(data.body_example_values);
							const flowButtonDefaults = Array.isArray(data.flow_button_defaults) ? data.flow_button_defaults : [];
							let headerFormat = null;
							let headerExampleHandle = null;
							let maxBodyIndex = 0;
							let hasBodyPlaceholders = false;

							structural.forEach((c) => {
								if (!c || typeof c !== 'object') return;
								const t = String(c.type || '').toUpperCase();
								if (t === 'HEADER') {
									headerFormat = String(c.format || '').toUpperCase();
									if (c.example && Array.isArray(c.example.header_handle) && c.example.header_handle.length > 0) {
										headerExampleHandle = String(c.example.header_handle[0]);
									} else if (c.example && c.example.header_handle && typeof c.example.header_handle === 'string') {
										headerExampleHandle = c.example.header_handle;
									}
								}
								if (t === 'BODY') {
									const text = String(c.text || '');
									const m = text.match(/\{\{(\d+)\}\}/g);
									if (m && m.length) {
										hasBodyPlaceholders = true;
										const matches = [...text.matchAll(/\{\{(\d+)\}\}/g)];
										matches.forEach(mm => {
											const idx = parseInt(mm[1] || '0', 10);
											if (idx > maxBodyIndex) maxBodyIndex = idx;
										});
									}
								}
							});

							const sendingComponents = [];
							if (headerFormat && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(headerFormat)) {
								const mediaType = headerFormat.toLowerCase();
								let dhm = (data.default_header_media && typeof data.default_header_media === 'object') ? data.default_header_media : null;

								let template_saved_media = null;
								if (data.image && data.image.url) {
									template_saved_media = data.image.url;
								} else if (data.media) {
									template_saved_media = data.media;
								}

								// NEW LOGIC FOR INJECTING CUSTOM MEDIA ATTACHED TO AUTO RESPONDER / CHATBOT
								if (item && item.media && item.media != "") {
									template_saved_media = item.media;
								}

								if (template_saved_media) {
									try {
										const mime_type = (data.mimetype || data.mimeType || "").toString();
										const suggested_filename = (data.fileName || data.filename || Common.get_file_name(template_saved_media) || "").toString();
										const media_reference = await WAZIPER.resolve_cloud_media_reference(template_saved_media, phoneNumberId, bearer, mime_type, suggested_filename);

										if (media_reference && media_reference.ok) {
											dhm = (media_reference.by === "id") ? { id: media_reference.value } : { link: media_reference.value };
										}
									} catch (e) {
										console.error('[ERROR] template custom media resolution failed:', e);
									}
								}

								// FALLBACK SE NENHUMA MÍDIA FOI CONFIGURADA, USAR A MÍDIA PADRÃO DA META (header_handle)
								if (!dhm && headerExampleHandle) {
									if (headerExampleHandle.startsWith('http')) {
										dhm = { link: headerExampleHandle };
									} else {
										dhm = { id: headerExampleHandle };
									}
								}

								if (dhm && (dhm.id || dhm.link)) {
									const param = { type: mediaType };
									param[mediaType] = dhm.id ? { id: String(dhm.id) } : { link: String(dhm.link) };
									sendingComponents.push({ type: 'header', parameters: [param] });
								}
							}
							if (hasBodyPlaceholders && maxBodyIndex > 0) {
								const params = [];
								for (let i = 1; i <= maxBodyIndex; i++) {
									let val = String(bodyExampleValues[i - 1] || '');

										// Resolve dynamic variables from contact/subscriber data [field]
										// and spreadsheet columns %param% — enables per-contact personalization
										try {
											if (val.match(/\[.*?\]/) || val.match(/%.*?%/)) {
												const sess = (typeof sessions !== 'undefined' && sessions[instance_id]) ? sessions[instance_id] : (WAZIPER.sessions ? WAZIPER.sessions[instance_id] : null);
												const placeholderMessage = data.placeholder_message || null;
												val = await Extend.common_data(WAZIPER, sess, instance_id, item, placeholderMessage, val);
												// spreadsheet_params is injected by auto_send into the data/tpl object
												const spreadsheetParams = data.spreadsheet_params || null;
												if (spreadsheetParams) {
													val = Common.params(spreadsheetParams, val);
												}
										}
									} catch (e) {
										console.error('[TEMPLATE-VAR] Error resolving variable for body param ' + i + ':', e.message);
									}

									params.push({ type: 'text', text: String(val || '') });
								}
								sendingComponents.push({ type: 'body', parameters: params });
							}

							for (const c of structural) {
								if (!c || typeof c !== 'object' || String(c.type || '').toUpperCase() !== 'BUTTONS') {
									continue;
								}

								const buttons = Array.isArray(c.buttons) ? c.buttons : [];
								for (let buttonIndex = 0; buttonIndex < buttons.length; buttonIndex++) {
									const button = buttons[buttonIndex];
									if (!button || typeof button !== 'object') continue;

									if (String(button.type || '').toUpperCase() !== 'FLOW') {
										continue;
									}

									const resolvedIndex = String(button.index != null ? button.index : buttonIndex);
									const defaults = flowButtonDefaults.find(entry => String(entry?.index ?? '') === resolvedIndex) || {};
									const action = {
										flow_token: String(defaults.flow_token || buildTemplateFlowToken(data.name, chat_id, resolvedIndex))
									};
									const flowActionData = await resolveTemplateFlowActionData(
										defaults.flow_action_data ?? defaults.flowActionData ?? null,
										{ instance_id, item, data }
									);

									if (flowActionData && Object.keys(flowActionData).length > 0) {
										action.flow_action_data = flowActionData;
									}

									sendingComponents.push({
										type: 'button',
										sub_type: 'flow',
										index: resolvedIndex,
										parameters: [
											{
												type: 'action',
												action
											}
										]
									});
								}
							}

							messageBody = {
								messaging_product: "whatsapp",
								to: chat_id,
								type: "template",
								template: {
									name: data.name,
									language: { code: langCode },
									components: sendingComponents
								}
							};
						}
						break;

					case 'carousel':
						messageBody = {
							messaging_product: "whatsapp",
							to: chat_id,
							type: "interactive",
							interactive: {
								type: "carousel",
								body: {
									text: data.text || " "
								},
								action: {
									cards: []
								}
							}
						};

						if (data.cards && Array.isArray(data.cards)) {
							data.cards.forEach((card, cardIndex) => {
								let cardObj = {
									card_index: cardIndex, // Required by Cloud API
									type: 'BUTTON', // Required by Cloud API (from PHP implementation)
									body: {
										text: card.body || " "
									}
								};

								const cardMediaUrl = (typeof card.media === 'string' ? card.media : (card.media && card.media.url ? card.media.url : '')) || (typeof card.image === 'string' ? card.image : (card.image && card.image.url ? card.image.url : ''));
								if (cardMediaUrl) {
									cardObj.header = {
										type: "image",
										image: {
											link: cardMediaUrl
										}
									};
								} else if (card.video && card.video.url) {
									cardObj.header = {
										type: "video",
										video: {
											link: card.video.url
										}
									};
								}

								if (card.buttons && card.buttons.length > 0) {
									cardObj.action = {
										buttons: []
									};
									card.buttons.forEach(btn => {
										try {
											let btnParams = typeof btn.buttonParamsJson === 'string' ? JSON.parse(btn.buttonParamsJson) : btn.buttonParamsJson;
											cardObj.action.buttons.push({
												type: "quick_reply",
												quick_reply: {
													id: btnParams.id || 'btn_' + Math.random().toString(36).substr(2, 9),
													title: (btnParams.display_text || btnParams.title || btn.name || 'Button').substring(0, 20)
												}
											});
										} catch (e) {
											console.error('Error parsing button params for carousel:', e);
										}
									});
									// Limit to 2 buttons per card (Cloud API limit)
									cardObj.action.buttons = cardObj.action.buttons.slice(0, 2);
								} else {
									// CAROUSEL CARDS MUST HAVE AT LEAST ONE BUTTON (from PHP implementation)
									cardObj.action = {
										buttons: [
											{
												type: "quick_reply",
												quick_reply: {
													id: 'btn_' + Math.random().toString(36).substr(2, 9),
													title: "OK"
												}
											}
										]
									};
								}
								messageBody.interactive.action.cards.push(cardObj);
							});
						}
						break;

					default:
						messageBody = {
							messaging_product: "whatsapp",
							to: chat_id,
							text: { body: data.text }
						}
						break;
				}

			}

			console.log("Meta API URL:", whatsappAPIURL);
			console.log("Meta Payload:", JSON.stringify(messageBody));

			axios.post(whatsappAPIURL, messageBody, {
				headers: { Authorization: `Bearer ${bearer}` },
				timeout: 30000 // 30s timeout for Cloud API
			}).then(async response => {

				try {
					const message = response.data?.messages?.[0];
					const wa_message_id = message?.id || Common.makeid(22);

					// Log de status inicial (sent) para campanhas Bulk Cloud API
					console.log('[STATUS-LOG] Attempting to log status:', {
						hasItem: !!item,
						itemId: item?.id,
						itemTeamId: item?.team_id,
						hasAccount: !!account,
						accountId: account?.id,
						type: type,
						wa_message_id: wa_message_id,
						chat_id: chat_id
					});

					// Só grava se for bulk e tiver todos os dados necessários
					if (type === 'bulk' && item && item.id && item.team_id && account && account.id) {
						try {
							const toNum = (typeof chat_id === 'string' && chat_id.includes('@')) ? chat_id.split('@')[0] : String(chat_id || '');
							const logData = {
								team_id: parseInt(item.team_id),
								schedule_id: parseInt(item.id),
								account_id: parseInt(account.id),
								campaign_name: item.name || null,
								to_number: String(toNum || chat_id).substring(0, 128),
								wa_message_id: String(wa_message_id || '').substring(0, 255),
								status: 'sent',
								last_status_at: Common.time(),
								created: Common.time()
							};
							console.log('[STATUS-LOG] Inserting logData:', JSON.stringify(logData));

							// Usar Promise para garantir que o erro seja capturado
							await new Promise((resolve, reject) => {
								Common.db_connect.query("INSERT INTO sp_whatsapp_message_status SET ?", logData, (err, res) => {
									if (err) {
										console.error('[STATUS-LOG] Insert failed:', err.message, err);
										try {
											const fs = require('fs');
											const path = require('path');
											const logPath = path.join(__dirname, '../../writable/logs/status_log_errors.log');
											const logLine = new Date().toISOString() + ' schedule_id=' + item.id + ' err=' + err.message + ' code=' + (err.code || '') + ' sqlState=' + (err.sqlState || '') + ' stack=' + (err.stack || '') + '\n';
											fs.appendFileSync(logPath, logLine);
										} catch (e) {
											console.error('[STATUS-LOG] Failed to write error log:', e);
										}
										reject(err);
									} else {
										console.log('[STATUS-LOG] Successfully inserted status log:', {
											schedule_id: item.id,
											wa_message_id: wa_message_id,
											insertId: res?.insertId,
											affectedRows: res?.affectedRows
										});
										resolve(res);
									}
								});
							});
						} catch (logErr) {
							console.error('[STATUS-LOG] Failed to insert initial status log:', logErr?.message || logErr, logErr?.stack);
						}
					} else {
						console.warn('[STATUS-LOG] Skipping insert - conditions not met:', {
							type: type,
							isBulk: type === 'bulk',
							hasItem: !!item,
							itemId: item?.id,
							itemTeamId: item?.team_id,
							hasAccount: !!account,
							accountId: account?.id
						});
					}

					let message___ = await Extend.process_official_sent_message(messageBody, chat_id, wa_message_id, "", instance_id);

					//* LIVECHAT FUNCTION
					if ((config['extended_functions'] ?? true)) {
						Extend.chat.processChatMessages(WAZIPER, false, { messages: [message___] }, instance_id, true);
					}
					//* END LIVECHAT FUNCTION	
				} catch (error) {
					console.error(error)
				}
				if (typeof callback === 'function') {
					callback({
						status: 1,
						type: type,
						phone_number: phone_number,
						stats: true,
						message: response.data,
						wa_message_id: response.data?.messages?.[0]?.id || null
					});
				}
				WAZIPER.stats(instance_id, type, item, 1);

			}).catch(error => {
				console.error('Error al enviar mensaje: ', error.response ? JSON.stringify(error.response.data) : error.message);
				const cloud_error_message = error?.response?.data?.error?.message || error?.message || "Cloud API send failed";
				if (typeof callback === 'function') {
					callback({
						status: 0,
						type: type,
						phone_number: phone_number,
						stats: true,
						message: cloud_error_message,
						error_code: parseInt(error?.response?.data?.error?.code || 0, 10) || null,
						error_message: cloud_error_message
					});
				}
				WAZIPER.stats(instance_id, type, item, 0);
			})


		} else {
			var check_evo_acc = await Common.db_get("sp_accounts", [{ pid: account.pid }, { social_network: "whatsapp_evo" }]);
			var tp = ["button", "list"];
			if (check_evo_acc && tp.includes(type_media)) {
				console.log(check_evo_acc)
				var evo_sess = await Common.db_get("sp_whatsapp_sessions_evo", [{ instance_id: check_evo_acc.token }, { team_id: item.team_id }]);

				if (evo_sess && evo_sess.status == 1) {
					const tokens = JSON.parse(evo_sess.data);
					console.log(tokens.hash.jwt)
					var opt = {};
					if (type_media == "list") {
						opt = {
							number: chat_id,
							options: {
								delay: 1200,
								presence: "composing"
							},
							listMessage: {
								title: data.title,
								description: data.text,
								footerText: data.footer,
								buttonText: data.buttonText,
								sections: data.sections
							}
						}
					}

					console.log(opt)

					axios.post(config.evo_server + 'message/sendList/' + evo_sess.instance_id, opt, {
						headers: {
							'Content-Type': 'application/json',
							'Authorization': 'Bearer ' + tokens.hash.jwt
						}
					}).then(async (response) => {
						console.log(response.data)
						hist = {
							instance_id: instance_id,
							team_id: item.team_id,
							phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
							type: type,
							message: "LIST MESSAGE TEMPLATE",
							status: 1,
							time_post: parseInt(Math.floor(new Date().getTime() / 1000)),
						};
						await Common.db_insert('sp_whatsapp_history', hist);
						callback({ status: 1, type: type, phone_number: phone_number, stats: true, message: response.data });
						WAZIPER.stats(instance_id, type, item, 1);


					}).catch(async (error) => {
						console.log(error)
						callback({ status: 0, type: type, phone_number: phone_number, stats: true });
						WAZIPER.stats(instance_id, type, item, 0);
						hist = {
							instance_id: instance_id,
							team_id: item.team_id,
							phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
							type: type,
							message: "LIST MESSAGE TEMPLATE",
							status: 0,
							time_post: parseInt(Math.floor(new Date().getTime() / 1000)),
						};
						await Common.db_insert('sp_whatsapp_history', hist);

					})
				}
			} else {
				let baileys_button_fallback_text = null;
				if (type_media != '' || type_media == "button" || type_media == "list" || type_media == "poll") {
					var cont = "TEMPLATE MESSAGE CONTENT"
				} else {
					cont = item.caption
				}
				if (type_media == "list") {
					datas = data
				} else if (type_media == "button") {
					const interactive_payload = WAZIPER.build_baileys_interactive_button_payload(data);
					if (interactive_payload) {
						console.log('[BOT_BUILDER_BUTTONS] Payload interativo Baileys preparado:', JSON.stringify({ buttons: interactive_payload.buttons?.length || 0, text: data?.text || data?.caption || '' }));
						datas = interactive_payload.payload;
						baileys_button_fallback_text = interactive_payload.fallbackText;
						cont = "BOTÃO INTERATIVO";
					} else {
						console.warn('[BOT_BUILDER_BUTTONS] Botão Baileys sem estrutura interativa válida; usando payload original.');
						datas = data;
					}
				} else {
					datas = data
				}
				const media_preparation = await WAZIPER.prepare_baileys_media_payload(datas);
				if (!media_preparation.ok) {
					const fail_message = media_preparation.error || "Media payload could not be prepared";
					console.error('[ERROR] ' + fail_message, { instance_id: instance_id, chat_id: chat_id, type_media: type_media });

					var hist = {
						instance_id: instance_id,
						team_id: item.team_id,
						phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
						type: type,
						message: cont,
						status: 0,
						time_post: parseInt(Math.floor(new Date().getTime() / 1000)),
					};
					await Common.db_insert('sp_whatsapp_history', hist);

					if (typeof callback === 'function') callback({ status: 0, type: type, phone_number: phone_number, stats: true, message: fail_message });
					WAZIPER.stats(instance_id, type, item, 0);
					return;
				}

				datas = media_preparation.payload;
				if (media_preparation.usedLocalPath) {
					console.log('[DEBUG] Using local media path for Baileys:', media_preparation.usedLocalPath);
				}
				if (media_preparation.usedRemoteUrl) {
					console.log('[DEBUG] Using remote media URL for Baileys:', media_preparation.usedRemoteUrl, media_preparation.remoteInfo || {});
				}
				const session_ready = await WAZIPER.ensure_session_ready(instance_id);
				if (!session_ready || !sessions[instance_id] || typeof sessions[instance_id].sendMessage !== "function") {
					const fail_message = "Baileys session is not connected";
					console.error('[ERROR] ' + fail_message, { instance_id: instance_id, chat_id: chat_id, type_media: type_media });
					var hist = {
						instance_id: instance_id,
						team_id: item.team_id,
						phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
						type: type,
						message: cont,
						status: 0,
						time_post: parseInt(Math.floor(new Date().getTime() / 1000)),
					};
					await Common.db_insert('sp_whatsapp_history', hist);
					if (typeof callback === 'function') callback({ status: 0, type: type, phone_number: phone_number, stats: true, message: fail_message });
					WAZIPER.stats(instance_id, type, item, 0);
					return;
				}
				console.log(datas)
				const sendPromise = sessions[instance_id].sendMessage(chat_id, datas, { backgroundColor: '' });
				const timeoutPromise = new Promise((_, reject) => setTimeout(() => reject(new Error('SendMessage Timeout')), 30000));

				await Promise.race([sendPromise, timeoutPromise]).then(async (message) => {
					console.log(message.message)
					if (typeof callback === 'function') callback({ status: 1, type: type, phone_number: phone_number, stats: true, message: message });
					WAZIPER.stats(instance_id, type, item, 1);

					var hist = {
						instance_id: instance_id,
						team_id: item.team_id,
						phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
						type: type,
						message: cont,
						status: 1,
						time_post: parseInt(message.messageTimestamp),
					};
					var res = Common.db_insert('sp_whatsapp_history', hist);
					}).catch(async (err) => {
						console.log('Error or Timeout sending message:', err.message);
						if (baileys_button_fallback_text) {
							try {
								console.warn('[BOT_BUILDER_BUTTONS] Envio interativo falhou; enviando menu em texto como fallback:', err.message);
								const fallbackPromise = sessions[instance_id].sendMessage(chat_id, { text: baileys_button_fallback_text }, { backgroundColor: '' });
								const fallbackTimeout = new Promise((_, reject) => setTimeout(() => reject(new Error('Fallback SendMessage Timeout')), 30000));
								const fallbackMessage = await Promise.race([fallbackPromise, fallbackTimeout]);
								if (typeof callback === 'function') callback({ status: 1, type: type, phone_number: phone_number, stats: true, message: fallbackMessage, fallback: true });
								WAZIPER.stats(instance_id, type, item, 1);

								var fallbackHist = {
									instance_id: instance_id,
									team_id: item.team_id,
									phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
									type: type,
									message: "BOTÃO INTERATIVO (FALLBACK TEXTO)",
									status: 1,
									time_post: parseInt(fallbackMessage?.messageTimestamp || Math.floor(new Date().getTime() / 1000)),
								};
								await Common.db_insert('sp_whatsapp_history', fallbackHist);
								return;
							} catch (fallbackErr) {
								console.log('[BOT_BUILDER_BUTTONS] Fallback de texto também falhou:', fallbackErr.message);
							}
						}
						var hist = {
							instance_id: instance_id,
							team_id: item.team_id,
						phone: chat_id.includes("g.us") == true ? chat_id : Common.get_phone(chat_id),
						type: type,
						message: cont,
						status: 0,
						time_post: parseInt(Math.floor(new Date().getTime() / 1000)),
					};
					var res = Common.db_insert('sp_whatsapp_history', hist);
					// WAZIPER.retry_onfail(instance_id); // Removed potentially aggressive retry
					if (typeof callback === 'function') callback({ status: 0, type: type, phone_number: phone_number, stats: true, message: err.message });
					WAZIPER.stats(instance_id, type, item, 0);
				});
			}
		}
	},

	auto_send: async function (instance_id, chat_id, phone_number, type, item, params, message, content, callback, retry = false) {
		console.log(`[DEBUG] auto_send start for ${phone_number}`);
		var limit = await WAZIPER.limit(item, type);
		console.log(`[DEBUG] limit result for ${phone_number}: ${limit}`);
		if (!limit) {
			return callback({ status: 0, stats: false, message: "The number of messages you have sent per month has exceeded the maximum limit" });
		}


		var { new_caption, can_continue } = await Extend.process_message(instance_id, item, chat_id, type, content, (err) => {
			if (sessions[instance_id]) {
				sessions[instance_id].sendMessage(sessions[instance_id].user.id, { text: `The message could not be sent to ${chat_id} with AI due to the following error:\n${err}` })
					.then()
					.catch()
			} else {
				console.log(`AI Process Error for ${chat_id}: ${err}`);
			}
		});
		item.caption = new_caption;
		const outboundAccount = await Common.db_get("sp_accounts", [{ token: instance_id }]);
		const isOfficialAccount = outboundAccount?.login_type == 1;
		const automationContext = getAutomationContextForMessage(message, instance_id);

		if (automationContext && Object.keys(automationContext).length > 0) {
			if (automationContext.canonicalId) {
				phone_number = automationContext.canonicalId;
			}

			if (isOfficialAccount) {
				const resolvedCloudDestination = Extend.resolveCloudDestination(chat_id, message, phone_number, { instance_id: instance_id });
				if (resolvedCloudDestination) {
					chat_id = resolvedCloudDestination;
				} else if (message?.official_api === true) {
					bulkMetaDebugLog({
						event: 'auto_send_cloud_destination_missing',
						type: type,
						instance_id: instance_id,
						raw_chat_id: chat_id,
						phone_number: phone_number,
						canonical_id: automationContext.canonicalId || '',
						canonical_number: automationContext.canonicalNumber || '',
						aliases: automationContext.aliases || []
					});
				}
			} else if (automationContext.replyJid) {
				chat_id = automationContext.replyJid;
			}
		}

		var params_org = params;

		if (type == 'bulk') {
			try {
				console.log(`[DEBUG] inside bulk block. params type: ${typeof params}`);
				console.log(`[DEBUG] bulk item structure:`, {
					id: item?.id,
					team_id: item?.team_id,
					type: item?.type,
					template: item?.template,
					accounts: item?.accounts
				});
				var account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: item.team_id }]);
				console.log(`[DEBUG] bulk account found:`, {
					id: account?.id,
					login_type: account?.login_type,
					name: account?.name
				});

				console.log('Bulk check phone:', phone_number, 'Is Cloud:', account?.login_type == 1);
				var isValid = await Extend.check_phone(sessions[instance_id], phone_number, (params ? params.is_valid : 0), account.login_type == 1);
				console.log('Bulk phone result:', isValid);

				if (!isValid) {
					callback({ status: 0, type: type, phone_number: phone_number, stats: true });
					WAZIPER.stats(instance_id, type, item, 0);
					can_continue = false;
				} else {
					if (params && params.params) {
						try {
							params = params.params;
						} catch (err_params) {
							console.error('[ERROR] accessing params.params', err_params);
						}
					}

					// Bulk sends do not carry an inbound WhatsApp message object, so [wa_name]
					// needs a safe synthetic context built from the latest known subscriber data.
					if (!message) {
						message = await Extend.buildBulkPlaceholderMessage(instance_id, item.team_id, chat_id, phone_number);
					}
				}
			} catch (err_bulk) {
				console.error('[CRITICAL] Error in bulk block', err_bulk);
			}
		}


		console.log(`[DEBUG] can_continue value: ${can_continue}`);

		if (can_continue) {
			console.log(`[DEBUG] calling sendPresence. Session exists? ${!!sessions[instance_id]}`);
			try {
				// Extract message_id for Cloud API (message.wamid is full wamid, message.id) or Baileys (message.key.id)
				var incoming_message_id = message?.wamid || message?.id || message?.key?.id || null;
				await Extend.sendPresence(sessions[instance_id], chat_id, item, instance_id, incoming_message_id);
			} catch (error) {

			}

			console.log(`[DEBUG] auto_send item type: ${item.type}`);
			try {
				bulkMetaDebugLog({
					event: 'auto_send_enter',
					type: type,
					schedule_id: item?.id,
					team_id: item?.team_id,
					item_type: item?.type,
					item_template: item?.template,
					instance_id: instance_id,
					phone_number: phone_number
				});
			} catch (e) { }

			switch (item.type) {

				//Button 
				case 2:
					{
						let internal_template = null;
						try {
							internal_template = await Common.db_get("sp_whatsapp_template", [{ id: item.template }]);
						} catch (e) { }
						let payloadInternal = null;
						if (internal_template && internal_template.data) {
							try { payloadInternal = JSON.parse(internal_template.data); } catch (e) { }
						}
						const metaEnabled = isMetaOfficialEnabled(payloadInternal);
						try {
							bulkMetaDebugLog({
								event: 'case2_meta_enabled_eval',
								schedule_id: item?.id,
								template_id: item?.template,
								enabled_raw: payloadInternal?.meta_official?.enabled,
								metaEnabled: metaEnabled
							});
						} catch (e) { }

						if (metaEnabled) {
							const account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: item.team_id }]);
							const typeCandidates = [];
							if (item?.type != null) typeCandidates.push(String(item.type));
							if (internal_template?.type != null) typeCandidates.push(String(internal_template.type));
							const uniqueTypes = [...new Set(typeCandidates)].filter(t => t !== '' && t !== 'undefined' && t !== 'null');
							const idsCandidates = [];
							if (internal_template?.ids != null) idsCandidates.push(String(internal_template.ids));
							if (internal_template?.id != null) idsCandidates.push(String(internal_template.id));
							if (item?.template != null) idsCandidates.push(String(item.template));
							const uniqueIds = [...new Set(idsCandidates)].filter(v => v !== '' && v !== 'undefined' && v !== 'null');
							bulkMetaDebugLog({ event: 'try_lookup_case2', schedule_id: item.id, template_id: item.template, account_ids: String(account?.ids || ''), type_candidates: uniqueTypes, ids_candidates: uniqueIds });

							let approvedRow = null;
							let approvedData = null;
							let matchedType = null;
							let matchedIds = null;
							for (const t of uniqueTypes) {
								for (const ids of uniqueIds) {
									approvedRow = await getMetaApprovedTemplateRow({
										team_id: item.team_id,
										account_ids: account?.ids,
										source_template_type: t,
										source_template_ids: ids
									});
									try {
										if (approvedRow && approvedRow.id) {
											bulkMetaDebugLog({ event: 'getMetaApprovedTemplateRow_hit_case2', schedule_id: item.id, template_id: item.template, approved_template_id: approvedRow.id, source_template_type: t, source_template_ids: ids });
										}
									} catch (e) { }
									approvedData = parseApprovedTemplateData(approvedRow?.data);
									if (approvedData && approvedData.name) {
										matchedType = t;
										matchedIds = ids;
										break;
									}
								}
								if (approvedData && approvedData.name) break;
							}
							if (approvedData && approvedData.name) {
								bulkMetaDebugLog({ event: 'found_approved_case2', schedule_id: item.id, template_id: item.template, source_template_type: matchedType, source_template_ids: matchedIds, name: approvedData.name });
								const tpl = {
									name: approvedData.name,
									language: approvedData.language || payloadInternal?.meta_official?.languages || 'pt_BR',
									default_header_media: approvedData.default_header_media || null,
									components: approvedData.components || [],
									body_example_values: payloadInternal?.meta_official?.body_example || approvedData.body_example || '',
									flow_button_defaults: extractTemplateFlowButtonDefaults(payloadInternal, { chat_id, instance_id, template_name: approvedData.name }),
									image: payloadInternal?.image || null,
									media: payloadInternal?.media || null,
									spreadsheet_params: params || null,
									placeholder_message: message || null
								};
								WAZIPER.process_send_message(chat_id, tpl, type, instance_id, phone_number, item, callback, 'template');
								break;
							}
							console.warn('[META] Approved official template not found for bulk item', { schedule_id: item.id, template_id: item.template });
							bulkMetaDebugLog({ event: 'not_found_case2', schedule_id: item.id, template_id: item.template, type_candidates: uniqueTypes, ids_candidates: uniqueIds });
						}

						var template = await WAZIPER.button_template_handler(item, params, message, instance_id);
						if (template) {
							WAZIPER.process_send_message(chat_id, template, type, instance_id, phone_number, item, callback, 'button');
						} else {
							console.warn('button_template_handler returned empty payload for type 2, skipping send', { templateId: item.template, itemId: item.id });
						}
					}
					break;
				case 5:
					{
						let internal_template = null;
						try {
							internal_template = await Common.db_get("sp_whatsapp_template", [{ id: item.template }]);
						} catch (e) { }
						let payloadInternal = null;
						if (internal_template && internal_template.data) {
							try { payloadInternal = JSON.parse(internal_template.data); } catch (e) { }
						}
						const metaEnabled = isMetaOfficialEnabled(payloadInternal);

						if (metaEnabled) {
							const account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: item.team_id }]);
							const typeCandidates = [];
							if (item?.type != null) typeCandidates.push(String(item.type));
							if (internal_template?.type != null) typeCandidates.push(String(internal_template.type));
							const uniqueTypes = [...new Set(typeCandidates)].filter(t => t !== '' && t !== 'undefined' && t !== 'null');
							const idsCandidates = [];
							if (internal_template?.ids != null) idsCandidates.push(String(internal_template.ids));
							if (internal_template?.id != null) idsCandidates.push(String(internal_template.id));
							if (item?.template != null) idsCandidates.push(String(item.template));
							const uniqueIds = [...new Set(idsCandidates)].filter(v => v !== '' && v !== 'undefined' && v !== 'null');
							bulkMetaDebugLog({ event: 'try_lookup_case5', schedule_id: item.id, template_id: item.template, account_ids: String(account?.ids || ''), type_candidates: uniqueTypes, ids_candidates: uniqueIds });

							let approvedRow = null;
							let approvedData = null;
							let matchedType = null;
							let matchedIds = null;
							for (const t of uniqueTypes) {
								for (const ids of uniqueIds) {
									approvedRow = await getMetaApprovedTemplateRow({
										team_id: item.team_id,
										account_ids: account?.ids,
										source_template_type: t,
										source_template_ids: ids
									});
									approvedData = parseApprovedTemplateData(approvedRow?.data);
									if (approvedData && approvedData.name) {
										matchedType = t;
										matchedIds = ids;
										break;
									}
								}
								if (approvedData && approvedData.name) break;
							}
							if (approvedData && approvedData.name) {
								bulkMetaDebugLog({ event: 'found_approved_case5', schedule_id: item.id, template_id: item.template, source_template_type: matchedType, source_template_ids: matchedIds, name: approvedData.name });
								const tpl = {
									name: approvedData.name,
									language: approvedData.language || payloadInternal?.meta_official?.languages || 'pt_BR',
									default_header_media: approvedData.default_header_media || null,
									components: approvedData.components || [],
									body_example_values: payloadInternal?.meta_official?.body_example || approvedData.body_example || '',
									flow_button_defaults: extractTemplateFlowButtonDefaults(payloadInternal, { chat_id, instance_id, template_name: approvedData.name }),
									image: payloadInternal?.image || null,
									media: payloadInternal?.media || null,
									spreadsheet_params: params || null,
									placeholder_message: message || null
								};
								WAZIPER.process_send_message(chat_id, tpl, type, instance_id, phone_number, item, callback, 'template');
								break;
							}
							console.warn('[META] Approved official template not found for bulk item', { schedule_id: item.id, template_id: item.template });
							bulkMetaDebugLog({ event: 'not_found_case5', schedule_id: item.id, template_id: item.template, type_candidates: uniqueTypes, ids_candidates: uniqueIds });
						}

						var template = await WAZIPER.button_template_handler(item, params, message, instance_id);
						if (template) {
							WAZIPER.process_send_message(chat_id, template, type, instance_id, phone_number, item, callback, 'carousel');
						} else {
							console.warn('button_template_handler returned empty payload for type 5, skipping send', { templateId: item.template, itemId: item.id });
						}
					}
					break;
				//List Messages
				case 3:
					{
						let internal_template = null;
						try {
							internal_template = await Common.db_get("sp_whatsapp_template", [{ id: item.template }]);
						} catch (e) { }
						let payloadInternal = null;
						if (internal_template && internal_template.data) {
							try { payloadInternal = JSON.parse(internal_template.data); } catch (e) { }
						}
						const metaEnabled = isMetaOfficialEnabled(payloadInternal);

						if (metaEnabled) {
							const account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: item.team_id }]);
							const typeCandidates = [];
							if (item?.type != null) typeCandidates.push(String(item.type));
							if (internal_template?.type != null) typeCandidates.push(String(internal_template.type));
							const uniqueTypes = [...new Set(typeCandidates)].filter(t => t !== '' && t !== 'undefined' && t !== 'null');
							const idsCandidates = [];
							if (internal_template?.ids != null) idsCandidates.push(String(internal_template.ids));
							if (internal_template?.id != null) idsCandidates.push(String(internal_template.id));
							if (item?.template != null) idsCandidates.push(String(item.template));
							const uniqueIds = [...new Set(idsCandidates)].filter(v => v !== '' && v !== 'undefined' && v !== 'null');
							bulkMetaDebugLog({ event: 'try_lookup_case3', schedule_id: item.id, template_id: item.template, account_ids: String(account?.ids || ''), type_candidates: uniqueTypes, ids_candidates: uniqueIds });

							let approvedRow = null;
							let approvedData = null;
							let matchedType = null;
							let matchedIds = null;
							for (const t of uniqueTypes) {
								for (const ids of uniqueIds) {
									approvedRow = await getMetaApprovedTemplateRow({
										team_id: item.team_id,
										account_ids: account?.ids,
										source_template_type: t,
										source_template_ids: ids
									});
									approvedData = parseApprovedTemplateData(approvedRow?.data);
									if (approvedData && approvedData.name) {
										matchedType = t;
										matchedIds = ids;
										break;
									}
								}
								if (approvedData && approvedData.name) break;
							}
							if (approvedData && approvedData.name) {
								bulkMetaDebugLog({ event: 'found_approved_case3', schedule_id: item.id, template_id: item.template, source_template_type: matchedType, source_template_ids: matchedIds, name: approvedData.name });
								const tpl = {
									name: approvedData.name,
									language: approvedData.language || payloadInternal?.meta_official?.languages || 'pt_BR',
									default_header_media: approvedData.default_header_media || null,
									components: approvedData.components || [],
									body_example_values: payloadInternal?.meta_official?.body_example || approvedData.body_example || '',
									flow_button_defaults: extractTemplateFlowButtonDefaults(payloadInternal, { chat_id, instance_id, template_name: approvedData.name }),
									image: payloadInternal?.image || null,
									media: payloadInternal?.media || null,
									spreadsheet_params: params || null,
									placeholder_message: message || null
								};
								WAZIPER.process_send_message(chat_id, tpl, type, instance_id, phone_number, item, callback, 'template');
								break;
							}
							console.warn('[META] Approved official template not found for bulk item', { schedule_id: item.id, template_id: item.template });
							bulkMetaDebugLog({ event: 'not_found_case3', schedule_id: item.id, template_id: item.template, type_candidates: uniqueTypes, ids_candidates: uniqueIds });
						}

						var template = await WAZIPER.list_message_template_handler(item, params, message, instance_id);
						if (template) {
							WAZIPER.process_send_message(chat_id, template, type, instance_id, phone_number, item, callback, 'list');
						}
					}
					break;
				case 4:
					var template = await WAZIPER.poll_template_handler(item, params, message, instance_id);
					if (template) {
						WAZIPER.process_send_message(chat_id, template, type, instance_id, phone_number, item, callback, 'poll');
						return false;
					}
					break;
				//Official Template (Meta)
				case 6:
					var official_template_id = item.template;
					var official_template = await Common.db_get("sp_whatsapp_template", [{ id: official_template_id }]);
					if (official_template && official_template.type == 6) {
						var template_data = JSON.parse(official_template.data);
						// We pass the official_template data directly. 
						// type_media='template' will trigger the direct assignment in process_send_message
						WAZIPER.process_send_message(chat_id, template_data, type, instance_id, phone_number, item, callback, 'template');
					} else {
						console.error('[ERROR] Official template not found or type mismatch', { official_template_id });
						callback({ status: 0, type: type, phone_number: phone_number, stats: true });
					}
					break;
				//Media & Text
				default:
					console.log(`[DEBUG] auto_send entering default case`);
					// If this bulk schedule references an internal template with meta_official enabled,
					// force Cloud API send as an approved official template (type=66) to avoid 24h re-engagement blocks.
					try {
						if (type == 'bulk' && item && item.template) {
							const account = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: item.team_id }]);
							if (account && account.login_type == 1) {
								let internal_template = null;
								try {
									internal_template = await Common.db_get("sp_whatsapp_template", [{ id: item.template }]);
								} catch (e) { }
								let payloadInternal = null;
								if (internal_template && internal_template.data) {
									try { payloadInternal = JSON.parse(internal_template.data); } catch (e) { }
								}
								const metaEnabled = isMetaOfficialEnabled(payloadInternal);
								if (metaEnabled) {
									const idsCandidates = [];
									if (internal_template?.ids != null) idsCandidates.push(String(internal_template.ids));
									if (internal_template?.id != null) idsCandidates.push(String(internal_template.id));
									if (item?.template != null) idsCandidates.push(String(item.template));
									const uniqueIdsCandidates = [...new Set(idsCandidates)].filter(v => v !== '' && v !== 'undefined' && v !== 'null');
									const sourceTemplateIds = uniqueIdsCandidates[0] || '';
									const typeCandidates = [];
									if (internal_template && internal_template.type != null) typeCandidates.push(String(internal_template.type));
									if (item && item.type != null) typeCandidates.push(String(item.type));
									// de-dup + remove empties
									const uniqueCandidates = [...new Set(typeCandidates)].filter(t => t !== '' && t !== 'undefined' && t !== 'null');
									const debugInfo = {
										schedule_id: item.id,
										template_id: item.template,
										account_ids: String(account?.ids || ''),
										ids_candidates: uniqueIdsCandidates,
										type_candidates: uniqueCandidates
									};
									console.log('[META] default case meta_official enabled - trying approved lookup', debugInfo);
									bulkMetaDebugLog({ event: 'try_lookup', ...debugInfo });

									let approvedData = null;
									let matchedType = null;
									let matchedIds = null;
									for (const candidateType of uniqueCandidates) {
										for (const candidateIds of uniqueIdsCandidates) {
											const approvedRow = await getMetaApprovedTemplateRow({
												team_id: item.team_id,
												account_ids: account?.ids,
												source_template_type: candidateType,
												source_template_ids: candidateIds
											});
											approvedData = parseApprovedTemplateData(approvedRow?.data);
											if (approvedData && approvedData.name) {
												matchedType = candidateType;
												matchedIds = candidateIds;
												break;
											}
										}
										if (approvedData && approvedData.name) break;
									}

									if (approvedData && approvedData.name) {
										const foundInfo = {
											schedule_id: item.id,
											template_id: item.template,
											source_template_type: matchedType,
											source_template_ids: matchedIds,
											name: approvedData.name
										};
										console.log('[META] Approved template found in default case', foundInfo);
										bulkMetaDebugLog({ event: 'found_approved', ...foundInfo });
										const tpl = {
											name: approvedData.name,
											language: approvedData.language || payloadInternal?.meta_official?.languages || 'pt_BR',
											default_header_media: approvedData.default_header_media || null,
											components: approvedData.components || [],
											body_example_values: payloadInternal?.meta_official?.body_example || approvedData.body_example || '',
											flow_button_defaults: extractTemplateFlowButtonDefaults(payloadInternal, { chat_id, instance_id, template_name: approvedData.name }),
											image: payloadInternal?.image || null,
											media: payloadInternal?.media || null,
											spreadsheet_params: params || null,
											placeholder_message: message || null
										};
										WAZIPER.process_send_message(chat_id, tpl, type, instance_id, phone_number, item, callback, 'template');
										bulkMetaDebugLog({ event: 'send_template', schedule_id: item.id, to: phone_number, name: approvedData.name });
										break;
									}
									console.warn('[META] Approved official template not found for bulk item (default case)', { schedule_id: item.id, template_id: item.template });
									bulkMetaDebugLog({ event: 'not_found', schedule_id: item.id, template_id: item.template, type_candidates: uniqueCandidates, ids_candidates: uniqueIdsCandidates });
								}
							}
						}
					} catch (e) {
						console.error('[META] Error while attempting template fallback in default case', e);
						bulkMetaDebugLog({ event: 'error', message: String(e?.message || e), stack: String(e?.stack || '') });
					}

					var caption = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, spintax.unspin(item.caption));
					console.log(`[DEBUG] auto_send caption after common_data: ${caption ? 'set' : 'empty'}`);
					caption = Common.params(params, caption);
					console.log(`[DEBUG] auto_send caption after params: ${caption ? 'set' : 'empty'}`);
					if (item.media != "" && item.media) {
						var mime = Common.ext2mime(item.media);
						var post_type = Common.post_type(mime, 1);
						var filename = (item.filename != undefined) ? item.filename : Common.get_file_name(item.media);

						if (type == 'api' && item.filename && item.filename != '') {
							mime = Common.ext2mime(item.filename);
							post_type = Common.post_type(mime, 1);
						}

						switch (post_type) {
							case "videoMessage":
								var data = {
									video: { url: item.media },
									caption: caption
								}

								data['mimetype'] = mime;
								break;

							case "imageMessage":
								var data = {
									image: { url: item.media },
									caption: caption
								}
								break;

							case "audioMessage":
								var data = {
									audio: { url: item.media },
									ptt: true,
									caption: caption,
									mimetype: mime == "audio/ogg" ? "audio/ogg; codecs=opus" : mime
								}
								break;

							default:
								var data = {
									document: { url: item.media },
									fileName: filename,
									caption: caption,
									mimeType: mime
								}
								break;
						}

						console.log('send media message to ', chat_id);
						WAZIPER.process_send_message(chat_id, data, type, instance_id, phone_number, item, callback, post_type);
					} else {
						if (caption && caption.trim() !== '') {
							console.log('send message to ', chat_id);
							WAZIPER.process_send_message(chat_id, { text: caption }, type, instance_id, phone_number, item, callback);
						} else {
							console.warn('Skipped sending empty text message', { chat_id, itemId: item.id });
						}
					}

			}
		}
	},
	limit: async function (item, type) {
		var time_now = Math.floor(new Date().getTime() / 1000);
		const skipMessageQuota = type === "bulk_call";

		//
		var team = await Common.db_query(`SELECT owner FROM sp_team WHERE id = '` + item.team_id + `'`);
		if (!team) { return false }

		var user = await Common.db_query(`SELECT expiration_date FROM sp_users WHERE id = '` + team.owner + `'`);
		if (!user) { return false }

		if (user.expiration_date != 0 && user.expiration_date < time_now) {
			return false;
		}

		if (skipMessageQuota) {
			return true;
		}

		/*
		* Stats
		*/
		if (stats_history[item.team_id] == undefined) {
			stats_history[item.team_id] = {};
			var current_stats = await Common.db_get("sp_whatsapp_stats", [{ team_id: item.team_id }]);
			if (current_stats) {
				stats_history[item.team_id].wa_total_sent_by_month = current_stats.wa_total_sent_by_month;
				stats_history[item.team_id].wa_total_sent = current_stats.wa_total_sent;
				stats_history[item.team_id].wa_chatbot_count = current_stats.wa_chatbot_count;
				stats_history[item.team_id].wa_autoresponder_count = current_stats.wa_autoresponder_count;
				stats_history[item.team_id].wa_api_count = current_stats.wa_api_count;
				stats_history[item.team_id].wa_bulk_total_count = current_stats.wa_bulk_total_count;
				stats_history[item.team_id].wa_bulk_sent_count = current_stats.wa_bulk_sent_count;
				stats_history[item.team_id].wa_bulk_failed_count = current_stats.wa_bulk_failed_count;
				stats_history[item.team_id].wa_time_reset = current_stats.wa_time_reset;
				stats_history[item.team_id].next_update = current_stats.next_update;
			} else {
				return false;
			}
		}
		//End stats

		if (stats_history[item.team_id] != undefined) {
			if (stats_history[item.team_id].wa_time_reset < time_now) {
				stats_history[item.team_id].wa_total_sent_by_month = 0;
				stats_history[item.team_id].wa_time_reset = time_now + 30 * 60 * 60 * 24;
			}

			//if(stats_history[item.team_id].next_update < time_now){
			var current_stats = await Common.db_get("sp_whatsapp_stats", [{ team_id: item.team_id }]);
			if (current_stats) {
				stats_history[item.team_id].wa_time_reset = current_stats.wa_time_reset;
				if (current_stats.wa_time_reset == 0) {
					stats_history[item.team_id].wa_total_sent_by_month = 0;
					stats_history[item.team_id].wa_time_reset = time_now + 30 * 60 * 60 * 24;
				}
			}
			//}
		}

		/*
		* Limit by month
		*/
		if (limit_messages[item.team_id] == undefined) {
			limit_messages[item.team_id] = {};
			var team = await Common.db_get("sp_team", [{ id: item.team_id }]);
			if (team) {
				var permissioms = JSON.parse(team.permissions);
				limit_messages[item.team_id].whatsapp_message_per_month = parseInt(permissioms.whatsapp_message_per_month);
				limit_messages[item.team_id].next_update = 0;
			} else {
				return false;
			}
		}

		if (limit_messages[item.team_id].next_update < time_now) {
			var team = await Common.db_get("sp_team", [{ id: item.team_id }]);
			if (team) {
				var permissioms = JSON.parse(team.permissions);
				limit_messages[item.team_id].whatsapp_message_per_month = parseInt(permissioms.whatsapp_message_per_month);
				limit_messages[item.team_id].next_update = time_now + 30;
			}
		}
		//End limit by month

		/*
		* Stop all activity when over limit
		*/
		if (limit_messages[item.team_id] != undefined && stats_history[item.team_id] != undefined) {
			if (limit_messages[item.team_id].whatsapp_message_per_month <= stats_history[item.team_id].wa_total_sent_by_month) {

				//Stop bulk campaign
				switch (type) {
					case "bulk":
						await Common.db_update("sp_whatsapp_schedules", [{ run: 0, status: 0 }, { id: item.id }]);

						WAZIPER.io.emit('pause_campaign_' + item.team_id, {
							id: item.id,
							status: 0
						});

						break
				}

				return false;
			}
		}

		return true;
		//End stop all activity when over limit
	},

	stats: async function (instance_id, type, item, status) {
		var time_now = Math.floor(new Date().getTime() / 1000);

		if (stats_history[item.team_id].wa_time_reset < time_now) {
			stats_history[item.team_id].wa_total_sent_by_month = 0;
			stats_history[item.team_id].wa_time_reset = time_now + 30 * 60 * 60 * 24;
		}

		var sent = status ? 1 : 0;
		var failed = !status ? 1 : 0;

		stats_history[item.team_id].wa_total_sent_by_month += sent;
		stats_history[item.team_id].wa_total_sent += sent;

		switch (type) {
			case "chatbot":
				if (chatbots[item.id] == undefined) {
					chatbots[item.id] = {};
				}

				if (
					chatbots[item.id].chatbot_sent == undefined &&
					chatbots[item.id].chatbot_failed == undefined
				) {
					chatbots[item.id].chatbot_sent = item.sent;
					chatbots[item.id].chatbot_failed = item.failed;
				}

				chatbots[item.id].chatbot_sent += (status ? 1 : 0);
				chatbots[item.id].chatbot_failed += (!status ? 1 : 0);

				stats_history[item.team_id].wa_chatbot_count += sent;

				var total_sent = chatbots[item.id].chatbot_sent;
				var total_failed = chatbots[item.id].chatbot_failed;
				var data = {
					sent: total_sent,
					failed: total_failed,
				};

				await Common.db_update("sp_whatsapp_chatbot", [data, { id: item.id }]);
				break;

			case "autoresponder":
				if (!sessions[instance_id]) {
					sessions[instance_id] = {}
				}

				if (
					sessions[instance_id].autoresponder_sent == undefined &&
					sessions[instance_id].autoresponder_failed == undefined
				) {
					sessions[instance_id].autoresponder_sent = item.sent;
					sessions[instance_id].autoresponder_failed = item.sent;
				}

				sessions[instance_id].autoresponder_sent += (status ? 1 : 0);
				sessions[instance_id].autoresponder_failed += (!status ? 1 : 0);

				stats_history[item.team_id].wa_autoresponder_count += sent;

				var total_sent = sessions[instance_id].autoresponder_sent;
				var total_failed = sessions[instance_id].autoresponder_failed;
				var data = {
					sent: total_sent,
					failed: total_failed,
				};

				await Common.db_update("sp_whatsapp_autoresponder", [data, { id: item.id }]);
				break;

			case "bulk":
				stats_history[item.team_id].wa_bulk_total_count += 1;
				stats_history[item.team_id].wa_bulk_sent_count += sent;
				stats_history[item.team_id].wa_bulk_failed_count += failed;
				break;

			case "api":
				stats_history[item.team_id].wa_api_count += sent;
				break;
		}

		/*
		* Update stats
		*/
		if (stats_history[item.team_id].next_update < time_now) {
			stats_history[item.team_id].next_update = time_now + 30;
		}
		await Common.db_update("sp_whatsapp_stats", [stats_history[item.team_id], { team_id: item.team_id }]);
		//End update stats

	},


	createInteractiveButtonsFromButton: (buttons) => {
		const buttonsArray = [];
		buttons?.map((button) => {
			if (button.name === 'quick_reply') {
				buttonsArray.push({
					name: 'quick_reply',
					buttonParamsJson: JSON.stringify({
						display_text: button.display_text,
						id: button.id,
						disabled: false
					})
				});
			} else if (button.name === 'cta_url') {
				// Verifica se a URL contém o padrão de código OTP
				if (button.url.includes('https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=()')) {
					const code = new URLSearchParams(button.url.split('?')[1]).get('code'); // Obtém o código OTP da URL
					buttonsArray.push({
						name: 'cta_copy',
						buttonParamsJson: JSON.stringify({
							display_text: 'Copiar Código',
							id: button.id || 'unique_id',
							copy_code: code,
							disabled: false
						})
					});
				} else {
					buttonsArray.push({
						name: 'cta_url',
						buttonParamsJson: JSON.stringify({
							display_text: button.display_text,
							id: button.id,
							url: button.url,
							disabled: false
						})
					});
				}
			} else if (button.name === 'cta_call') {
				buttonsArray.push({
					name: 'cta_call',
					buttonParamsJson: JSON.stringify({
						display_text: button.display_text,
						id: button.id,
						phone_number: button.phone_number,
						disabled: false
					})
				});
			}
		});
		return buttonsArray;
	},


	// Main function
	button_template_handler: async function (item, params, message, instance_id) {
		var template_id = item.template;
		var template = await Common.db_get("sp_whatsapp_template", [{ id: template_id }]);

		if (!template && template_id) {
			template = await Common.db_get("sp_whatsapp_template", [{ ids: template_id }]);
			if (template) {
				console.log('button_template_handler template resolved by ids', template_id);
			}
		}

		if (template && template.type != item.type) {
			console.warn('button_template_handler: template type mismatch', { itemType: item.type, templateType: template.type, templateId: template_id });
			return false;
		}

		console.log('button_template_handler template', JSON.stringify(template))

		if (template) {
			var data = JSON.parse(template.data);
			var payload = {};

			var normalizeText = async function (value) {
				if (value === undefined || value === null) {
					return null;
				}

				var processed = spintax.unspin(String(value));
				processed = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, processed);
				processed = Common.params(params, processed);

				if (typeof processed === 'string') {
					processed = processed.trim();
				}

				return processed ? processed : null;
			};

			if (template.type == 5 && Array.isArray(data.cards) && data.cards.length) {
				(data.cards || []).forEach((card, idx) => {
					if (!card) {
						data.cards[idx] = {};
					}
				});

				var carouselCards = await Promise.all(data.cards.map(async (card) => {
					var title = await normalizeText(card.title);
					var body = await normalizeText(card.body);
					var footer = await normalizeText(card.footer);

					var media = card.media;
					var processedMedia = null;
					if (media) {
						processedMedia = typeof media === 'string' ? { url: media } : media;
					}

					var mappedButtons = [];
					var buttons = Array.isArray(card.buttons) ? card.buttons : [];
					for (var i = 0; i < buttons.length; i++) {
						var rawButton = buttons[i] || {};
						var buttonClone = {
							name: rawButton.name
						};

						if (rawButton.buttonParamsJson !== undefined) {
							if (typeof rawButton.buttonParamsJson === 'string') {
								try {
									buttonClone.buttonParamsJson = JSON.stringify(JSON.parse(rawButton.buttonParamsJson));
								} catch (error) {
									buttonClone.buttonParamsJson = JSON.stringify({});
								}
							} else if (typeof rawButton.buttonParamsJson === 'object' && rawButton.buttonParamsJson !== null) {
								buttonClone.buttonParamsJson = JSON.stringify(rawButton.buttonParamsJson);
							}
						}

						if (buttonClone.name && buttonClone.buttonParamsJson) {
							mappedButtons.push(buttonClone);
						}
					}

					var slide = {
						buttons: mappedButtons
					};

					if (title) {
						slide.title = title;
					}

					if (body) {
						slide.body = body;
					}

					if (footer) {
						slide.footer = footer;
					}

					if (processedMedia) {
						if (processedMedia.video) {
							slide.video = processedMedia.video;
						} else if (processedMedia.product) {
							slide.product = processedMedia.product;
						} else if (processedMedia.image || processedMedia.url || processedMedia.thumbnail) {
							slide.image = processedMedia;
						} else {
							slide.image = processedMedia;
						}
					}

					return slide;
				}));

				console.log('carousel cards raw', JSON.stringify(carouselCards));

				carouselCards = carouselCards.filter((card) => {
					return card && (card.title || card.body || card.buttons.length || card.image || card.video || card.product);
				});

				console.log('carousel cards filtered', JSON.stringify(carouselCards));

				if (!carouselCards.length) {
					console.warn('carousel discarded: no valid cards');
					return false;
				}

				var carouselTitle = await normalizeText(data.title);
				if (carouselTitle) {
					payload.title = carouselTitle;
				}

				var carouselSubtitle = await normalizeText(data.subtitle);
				if (carouselSubtitle) {
					payload.subtitle = carouselSubtitle;
				}

				var carouselText = await normalizeText(data.text);
				if (carouselText) {
					payload.text = carouselText;
				}

				var carouselFooter = await normalizeText(data.footer);
				if (carouselFooter) {
					payload.footer = carouselFooter;
				}

				payload.cards = carouselCards;
				console.log('carousel payload built', JSON.stringify(payload));
				return payload;
			}

			var interactiveButtons = [];

			var normalizedTitle = await normalizeText(data.title);
			if (normalizedTitle) {
				payload.title = normalizedTitle;
			}

			var normalizedSubtitle = await normalizeText(data.subtitle);
			if (normalizedSubtitle) {
				payload.subtitle = normalizedSubtitle;
			}

			var mediaImage = data.image ? (typeof data.image === 'string' ? { url: data.image } : data.image) : null;
			var mediaVideo = data.video ? (typeof data.video === 'string' ? { url: data.video } : data.video) : null;
			var mediaDocument = null;
			if (data.document) {
				mediaDocument = typeof data.document === 'string' ? { url: data.document } : data.document;
				if (mediaDocument && !mediaDocument.mimetype && data.document_mimetype) {
					mediaDocument.mimetype = data.document_mimetype;
				}
			}

			var hasMediaAttachment = Boolean(mediaImage || mediaVideo || mediaDocument);

			if (data.hasMediaAttachment !== undefined) {
				payload.hasMediaAttachment = data.hasMediaAttachment;
			} else if (hasMediaAttachment) {
				payload.hasMediaAttachment = true;
			}

			if (mediaImage) {
				payload.image = mediaImage;
			}

			if (mediaVideo) {
				payload.video = mediaVideo;
			}

			if (mediaDocument) {
				payload.document = mediaDocument;
			}

			var normalizedFooter = await normalizeText(data.footer);
			if (normalizedFooter) {
				payload.footer = normalizedFooter;
			}

			var normalizedCaption = await normalizeText(data.caption);
			var normalizedBody = await normalizeText(data.text);

			var bodyForMessage;
			if (hasMediaAttachment) {
				bodyForMessage = normalizedCaption || normalizedBody || normalizedTitle || normalizedSubtitle;
				if (bodyForMessage) {
					payload.caption = bodyForMessage;
				}
			} else {
				bodyForMessage = normalizedBody || normalizedCaption || normalizedTitle || normalizedSubtitle;
				if (bodyForMessage) {
					payload.text = bodyForMessage;
				}
			}

			if (data.templateButtons && data.templateButtons.length > 0) {
				for (var i = 0; i < data.templateButtons.length; i++) {
					var buttonData = data.templateButtons[i];
					var button = null;

					if (buttonData.quickReplyButton !== undefined) {
						var displayText = spintax.unspin(buttonData.quickReplyButton.displayText);
						displayText = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, displayText);
						displayText = Common.params(params, displayText);
						button = {
							name: 'quick_reply',
							buttonParamsJson: JSON.stringify({
								display_text: displayText,
								id: buttonData.quickReplyButton.id || buttonData.quickReplyButton.displayText,
								disabled: false
							})
						};
					}

					if (buttonData.urlButton !== undefined) {
						var displayText = spintax.unspin(buttonData.urlButton.displayText);
						displayText = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, displayText);
						displayText = Common.params(params, displayText);

						if (buttonData.urlButton.url.includes('https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=()')) {
							const code = new URLSearchParams(buttonData.urlButton.url.split('?')[1]).get('code');
							button = {
								name: 'cta_copy',
								buttonParamsJson: JSON.stringify({
									display_text: 'Copiar Código',
									id: buttonData.urlButton.id || buttonData.urlButton.displayText,
									copy_code: code,
									disabled: false
								})
							};
						} else {
							button = {
								name: 'cta_url',
								buttonParamsJson: JSON.stringify({
									display_text: displayText,
									id: buttonData.urlButton.id || buttonData.urlButton.displayText,
									url: buttonData.urlButton.url,
									disabled: false
								})
							};
						}
					}

					if (buttonData.callButton !== undefined) {
						var displayText = spintax.unspin(buttonData.callButton.displayText);
						displayText = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, displayText);
						displayText = Common.params(params, displayText);

						button = {
							name: 'cta_call',
							buttonParamsJson: JSON.stringify({
								display_text: displayText,
								id: buttonData.callButton.id || buttonData.callButton.displayText,
								phone_number: buttonData.callButton.phoneNumber,
								disabled: false
							})
						};
					}

					if (button) {
						interactiveButtons.push(button);
					}
				}
			}

			if (!interactiveButtons.length) {
				console.warn('button payload discarded: no interactive buttons');
				return false;
			}

			if (!bodyForMessage) {
				bodyForMessage = normalizedTitle || normalizedSubtitle || normalizedFooter;
			}

			if (!bodyForMessage) {
				console.warn('button payload discarded: no text or caption available');
				return false;
			}

			if (!payload.text && !payload.caption) {
				if (hasMediaAttachment) {
					payload.caption = bodyForMessage;
				} else {
					payload.text = bodyForMessage;
				}
			}

			payload.interactiveButtons = interactiveButtons;
			console.log('button payload built', JSON.stringify(payload));

			return payload;
		}

		if (!template) {
			console.warn('button_template_handler: template not found for id/ids', template_id);
		}

		return false;
	}
	,

	list_message_template_handler: async function (item, params, message, instance_id) {
		var template_id = item.template;
		var template = await Common.db_get("sp_whatsapp_template", [{ id: template_id }, { type: 1 }]);
		if (template) {

			var data = JSON.parse(template.data);

			//console.log(WAZIPER.sessions);
			if (data.text != undefined) {
				data.text = spintax.unspin(data.text);
				data.text = await Extend.common_data(
					WAZIPER
					, WAZIPER.sessions[instance_id]
					, instance_id
					, item, message
					, data.text
				);
				data.text = Common.params(params, data.text);
				//delete data.text;
			}

			if (data.footer != undefined) {
				data.footer = spintax.unspin(data.footer);
				data.footer = await Extend.common_data(WAZIPER, WAZIPER.sessions[instance_id], instance_id, item, message, data.footer);
				data.footer = Common.params(params, data.footer);
				//delete data.footer;
			}

			if (data.title != undefined) {
				data.title = spintax.unspin(data.title);
				data.title = await Extend.common_data(WAZIPER, WAZIPER.sessions[instance_id], instance_id, item, message, data.title);
				data.title = Common.params(params, data.title);
			}

			if (data.buttonText != undefined) {
				data.buttonText = spintax.unspin(data.buttonText);
				data.buttonText = await Extend.common_data(WAZIPER, WAZIPER.sessions[instance_id], instance_id, item, message, data.buttonText);
				data.buttonText = Common.params(params, data.buttonText);
			}

			for (var i = 0; i < data.sections.length; i++) {
				var sessions = data.sections;
				if (data.sections[i]) {
					if (data.sections[i].title != undefined) {
						data.sections[i].title = spintax.unspin(data.sections[i].title);
						data.sections[i].title = await Extend.common_data(WAZIPER, WAZIPER.sessions[instance_id], instance_id, item, message, data.sections[i].title);
						data.sections[i].title = Common.params(params, data.sections[i].title);
					}

					for (var j = 0; j < data.sections[i].rows.length; j++) {
						if (data.sections[i].rows[j].title != undefined) {
							data.sections[i].rows[j].title = spintax.unspin(data.sections[i].rows[j].title);
							data.sections[i].rows[j].title = await Extend.common_data(WAZIPER, WAZIPER.sessions[instance_id], instance_id, item, message, data.sections[i].rows[j].title);
							data.sections[i].rows[j].title = Common.params(params, data.sections[i].rows[j].title);
						}

						if (data.sections[i].rows[j].description != undefined) {
							data.sections[i].rows[j].description = spintax.unspin(data.sections[i].rows[j].description);
							data.sections[i].rows[j].description = await Extend.common_data(WAZIPER, WAZIPER.sessions[instance_id], instance_id, item, message, data.sections[i].rows[j].description);
							data.sections[i].rows[j].description = Common.params(params, data.sections[i].rows[j].description);
						}
						data.sections[i].rows[j].rowId = data.sections[i].rows[j].rowId;
						console.log(data.sections[i].rows[j])
					}
				}
			}

			data.listType = 2;
			var lm = {
				title: data.title,
				text: data.text,
				buttonText: data.buttonText,
				footer: data.footer,
				sections: data.sections,
				listType: 2
			};
			var msg = {
				listMessage: lm
			}
			console.log(msg);
			return lm;
		}

		return false;
	},

	poll_template_handler: async function (item, params, message, instance_id) {
		var template_id = item.template;
		var template = await Common.db_get("sp_whatsapp_template", [{ id: template_id }, { type: 3 }]);
		if (template) {
			var data = JSON.parse(template.data);
			if (data.name != undefined) {
				data.name = spintax.unspin(data.name);
				data.name = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, data.name);
				data.name = Common.params(params, data.name);
			}

			for (i = 0; i < data.values.length; i++) {
				data.values[i] = spintax.unspin(data.values[i]);
				data.values[i] = await Extend.common_data(WAZIPER, sessions[instance_id], instance_id, item, message, data.values[i]);
				data.values[i] = Common.params(params, data.values[i]);
			}

			var pollOpt = {
				name: data.name,
				values: data.values,
				selectableCount: data.selectableCount == 0 ? null : 1
			}

			pollOpt = { poll: pollOpt };

			return pollOpt;
		}
	},

	live_back: async function () {
		var account = await Common.db_query(`
			SELECT a.changed, a.token as instance_id, a.id, b.ids as access_token 
			FROM sp_accounts as a 
			INNER JOIN sp_team as b ON a.team_id=b.id 
			WHERE a.social_network = 'whatsapp' AND a.login_type = '2' AND a.status = 1 
			ORDER BY a.changed ASC 
			LIMIT 1
		`);

		if (account) {

			let wsstatus = WAZIPER.get_ws_status(account.instance_id);
			const should_restart = wsstatus !== 1;

			if (should_restart) {
				console.error('checking', account.instance_id, 'ws status:', wsstatus, 'restarting');
			} else {
				console.log('checking', account.instance_id, 'ws status:', wsstatus, 'keep');
			}

			var now = new Date().getTime() / 1000;
			await Common.db_update("sp_accounts", [{ changed: now }, { id: account.id }]);
			await WAZIPER.instance(account.access_token, account.instance_id, false, async (client) => {


				if (client.user && client.user.name) {
					await Common.db_update("sp_accounts", [{ name: client.user.name }, { id: account.id }]);
				}

				if (client.qrcode != undefined && client.qrcode != "") {
					await WAZIPER.logout(account.instance_id);
				}
			}, should_restart);
		}

		//Close new session after 2 minutes
		if (Object.keys(new_sessions).length) {
			Object.keys(new_sessions).forEach(async (instance_id) => {
				var now = new Date().getTime() / 1000;
				if (now > new_sessions[instance_id] && sessions[instance_id] && sessions[instance_id].qrcode != undefined) {
					delete new_sessions[instance_id];
					await WAZIPER.logout(instance_id);
				}
			});
		}

		console.log("Total sessions: ", Object.keys(sessions).length);
		console.log("Total queue sessions: ", Object.keys(new_sessions).length);
	},

	add_account: async function (instance_id, team_id, wa_info, account) {
		if (!account) {
			await Common.db_insert_account(instance_id, team_id, wa_info);
		} else {
			var old_instance_id = account.token;

			await Common.db_update_account(instance_id, team_id, wa_info, account.id);

			//Update old session
			if (instance_id != old_instance_id) {
				await Common.db_delete("sp_whatsapp_sessions", [{ instance_id: old_instance_id }]);
				await Common.db_update("sp_whatsapp_autoresponder", [{ instance_id: instance_id }, { instance_id: old_instance_id }]);
				await Common.db_update("sp_whatsapp_chatbot", [{ instance_id: instance_id }, { instance_id: old_instance_id }]);
				await Common.db_update("sp_whatsapp_webhook", [{ instance_id: instance_id }, { instance_id: old_instance_id }]);
				WAZIPER.logout(old_instance_id);
			}

			var pid = Common.get_phone(wa_info.id, 'wid');
			var account_other = await Common.db_query(`SELECT id FROM sp_accounts WHERE pid = '` + pid + `' AND team_id = '` + team_id + `' AND id != '` + account.id + `'`);
			if (account_other) {
				await Common.db_delete("sp_accounts", [{ id: account_other.id }]);
			}
		}

		/*Create WhatsApp stats for user*/
		var wa_stats = await Common.db_get("sp_whatsapp_stats", [{ team_id: team_id }]);
		if (!wa_stats) await Common.db_insert_stats(team_id);
	}
}



module.exports = WAZIPER;

if (process.env.WAZIPER_DISABLE_CRON !== '1') {
	cron.schedule('*/30 * * * * *', function () {
		WAZIPER.live_back();
	});

	cron.schedule('*/2 * * * * *', function () {
		//console.log('bulk init cron')
		WAZIPER.bulk_messaging();
	});

	cron.schedule('*/30 * * * * *', function () {
		Extend.validatePhones(WAZIPER, sessions);
	});
}
