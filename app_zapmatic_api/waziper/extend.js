const mysql = require('mysql');
const config = require("./../config.js");
const common = require("./common.js");
const moment = require('moment-timezone');
const Queue = require('bull');
const axios = require('axios');
const fs = require('fs');
const path = require('path');
const util = require('util');
const ioredis = require('ioredis');

const writeFileAsync = util.promisify(fs.writeFile);
const { join } = path;
const {
    WAMessageStubType,
    getContentType,
    jidNormalizedUser,
    downloadContentFromMessage
} = require('@itsukichan/baileys')

var redis_ = new ioredis(config.redis);

var cacheLayer = {
    set: async (key, value, option, optionValue) => {

        const setPromisefy = util.promisify(redis_.set).bind(redis_);
        if (option !== undefined && optionValue !== undefined) {
            return setPromisefy(key, value, option, optionValue);
        }
        return setPromisefy(key, value);

    },
    get: (key) => {
        const getPromisefy = util.promisify(redis_.get).bind(redis_);
        return getPromisefy(key);
    }
}

const { OpenAI } = require('openai');

// Proxy configuration
const proxyUrl = config.proxy_openai ?? '';

if (proxyUrl != '') {
    var { HttpsProxyAgent } = require('https-proxy-agent');
    var proxyAgent = new HttpsProxyAgent(proxyUrl);
} else {
    var proxyAgent = null;
}



let OpenAi_History_Chat = {}
let OpenAi_Chats_Ids = {}

var lang = [
    'af', 'ar', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'az',
    'be', 'bg', 'bm', 'bn', 'bn-bd', 'bo', 'br', 'bs',
    'ca', 'cs', 'cv', 'cy',
    'da', 'de', 'de-at', 'de-ch', 'dv', 'el',
    'en-au', 'en-ca', 'en-gb', 'en-ie', 'en-il', 'en-in', 'en-nz', 'en-sg', 'eo', 'es', 'es-do', 'es-mx', 'es-us', 'et', 'eu',
    'fa', 'fi', 'fil', 'fo', 'fr', 'fr-ca', 'fr-ch', 'fy',
    'ga', 'gd', 'gl', 'gom-deva', 'gom-latn', 'gu',
    'he', 'hi', 'hr', 'hu', 'hy-am',
    'id', 'is', 'it', 'it-ch',
    'ja', 'jv',
    'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'ky',
    'lb', 'lo', 'lt', 'lv',
    'me', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'ms-my', 'mt', 'my',
    'nb', 'ne', 'nl', 'nl-be', 'nn',
    'oc-lnc',
    'pa-in', 'pl', 'pt', 'pt-br',
    'ro', 'ru',
    'sd', 'se', 'si', 'sk', 'sl', 'sq', 'sr', 'sr-cyrl', 'ss', 'sv', 'sw',
    'ta', 'te', 'tet', 'tg', 'th', 'tk', 'tl-ph', 'tlh', 'tr', 'tzl', 'tzm', 'tzm-latn',
    'ug-cn', 'uk', 'ur', 'uz', 'uz-latn',
    'vi',
    'x-pseudo',
    'yo',
    'zh-cn', 'zh-hk', 'zh-mo', 'zh-tw'
];

lang.forEach(loc => {
    require(`./../node_modules/moment/locale/${loc}.js`);
});
moment.locale('en');

const db = common.db_connect;// mysql.createPool(config.database);
const session_dir = path.join(__dirname, '..', 'sessions');




const Extend = {

    getDescendantProp: (obj, desc) => {
        var arr = desc.split(".");
        while (arr.length && obj) {
            var comp = arr.shift();
            var match = new RegExp("(.+)\\[([0-9]*)\\]").exec(comp);
            if ((match !== null) && (match.length == 3)) {
                var arrayData = { arrName: match[1], arrIndex: match[2] };
                if (obj[arrayData.arrName] != undefined) {
                    obj = obj[arrayData.arrName][arrayData.arrIndex];
                } else {
                    obj = undefined;
                    break;
                }
            } else {
                obj = obj[comp]
            }
        }
        return obj;
    },

    normalizeBulkPhone: (value = '') => {
        return String(value || '').replace(/\D/g, '');
    },

    sanitizeDisplayName: (value = '', fallbackNumber = '') => {
        const safeValue = String(value || '').trim();
        const safeNumber = String(fallbackNumber || '').trim();

        if (!safeValue) {
            return '';
        }

        if (safeValue === '.' || safeValue === '-' || safeValue === '_') {
            return '';
        }

        if (safeNumber && safeValue === safeNumber) {
            return '';
        }

        if (/^[0-9]+$/.test(safeValue)) {
            return '';
        }

        return safeValue;
    },

    extractSubscriberDisplayName: (subscriber = {}) => {
        const contactData = Extend.safeParseJson(subscriber.contact_data, {});
        const subscriberData = Extend.safeParseJson(subscriber.data, {});
        const fallbackNumber = String(contactData.number || '').trim();
        const aliases = Array.isArray(contactData.aliases) ? contactData.aliases : [];
        const candidates = [
            contactData.name,
            subscriberData.push_name,
            subscriberData.pushName,
            contactData.username,
            aliases[0]
        ];

        for (const candidate of candidates) {
            const displayName = Extend.sanitizeDisplayName(candidate, fallbackNumber);
            if (displayName) {
                return displayName;
            }
        }

        return '';
    },

    findBulkSubscriberContext: async (instance_id, team_id, chat_id = '', phone_number = '') => {
        const normalizedPhone = Extend.normalizeBulkPhone(phone_number || chat_id);
        const normalizedChatId = String(chat_id || '').trim();
        const defaultWaChatId = normalizedPhone ? `${normalizedPhone}@s.whatsapp.net` : '';
        const clauses = [];
        const bindings = [team_id, instance_id];

        if (normalizedChatId) {
            clauses.push("chatid = ?");
            bindings.push(normalizedChatId);
        }

        if (defaultWaChatId && defaultWaChatId !== normalizedChatId) {
            clauses.push("chatid = ?");
            bindings.push(defaultWaChatId);
        }

        if (normalizedPhone) {
            clauses.push("JSON_UNQUOTE(JSON_EXTRACT(contact_data, '$.number')) = ?");
            bindings.push(normalizedPhone);
        }

        if (!clauses.length) {
            return null;
        }

        const query =
            "SELECT * FROM `sp_whatsapp_subscriber` WHERE team_id = ? AND instance_id = ? AND (" +
            clauses.join(" OR ") +
            ") ORDER BY id DESC LIMIT 1";

        const row = await common.db_query(query, bindings, true);
        if (!row) {
            return null;
        }

        const subscriber = Extend.hydrateSubscriberRow(row);

        return {
            subscriber: subscriber,
            wa_name: Extend.extractSubscriberDisplayName(subscriber),
            chatid: subscriber.chatid || normalizedChatId || defaultWaChatId
        };
    },

    buildBulkPlaceholderMessage: async (instance_id, team_id, chat_id = '', phone_number = '') => {
        const subscriberContext = await Extend.findBulkSubscriberContext(instance_id, team_id, chat_id, phone_number);
        const normalizedPhone = Extend.normalizeBulkPhone(phone_number || chat_id);
        const fallbackChatId = String(chat_id || '').trim() || (normalizedPhone ? `${normalizedPhone}@s.whatsapp.net` : '');

        if (!subscriberContext && !fallbackChatId) {
            return false;
        }

        return {
            key: {
                remoteJid: subscriberContext?.chatid || fallbackChatId
            },
            pushName: subscriberContext?.wa_name || '',
            messageTimestamp: Math.floor(Date.now() / 1000),
            _bulk_context: true
        };
    },

    safeParseJson: (rawValue, fallback = {}) => {
        if (rawValue === undefined || rawValue === null || rawValue === '') {
            return fallback;
        }

        if (typeof rawValue === 'object') {
            return rawValue;
        }

        try {
            const parsedValue = JSON.parse(rawValue);
            return parsedValue && typeof parsedValue === 'object' ? parsedValue : fallback;
        } catch (error) {
            return fallback;
        }
    },

    getIdentifierPrefix: (jid = '') => {
        const safeJid = String(jid || '');
        return safeJid.split(':')[0].split('@')[0];
    },

    isNumericIdentifier: (value = '') => {
        return /^[0-9]+$/.test(String(value || '').trim());
    },

    normalizeOfficialJid: (value = '') => {
        const prefix = Extend.getIdentifierPrefix(value);
        return prefix ? `${prefix}@s.whatsapp.net` : '';
    },

    normalizeTransportJid: (value = '', fallbackDomain = 's.whatsapp.net') => {
        const safeValue = String(value || '').trim();
        if (!safeValue) {
            return '';
        }

        const prefix = Extend.getIdentifierPrefix(safeValue);
        if (!prefix) {
            return '';
        }

        if (safeValue.includes('@')) {
            const domain = safeValue.split('@')[1]?.trim();
            return domain ? `${prefix}@${domain}` : `${prefix}@${fallbackDomain}`;
        }

        return `${prefix}@${fallbackDomain}`;
    },

    stripUndefinedSuffix: (value = '') => {
        return String(value || '').replace(/@undefined$/i, '').trim();
    },

    isLidJid: (value = '') => {
        return String(value || '').trim().endsWith('@lid');
    },

    isGroupLikeJid: (value = '') => {
        const safeValue = String(value || '').trim();
        return safeValue.endsWith('@g.us') || safeValue.endsWith('@broadcast') || safeValue.endsWith('@newsletter');
    },

    readSessionMappingValue: (filePath = '') => {
        try {
            if (!filePath || !fs.existsSync(filePath)) {
                return '';
            }

            const raw = fs.readFileSync(filePath, 'utf8').trim();
            if (!raw) {
                return '';
            }

            try {
                const parsed = JSON.parse(raw);
                if (typeof parsed === 'string') {
                    return parsed;
                }

                if (parsed && typeof parsed === 'object') {
                    return parsed.jid || parsed.phone || parsed.number || parsed.value || '';
                }
            } catch (error) {
                return raw;
            }

            return '';
        } catch (error) {
            return '';
        }
    },

    resolvePhoneFromLidMapping: (instance_id = '', value = '') => {
        const prefix = Extend.getIdentifierPrefix(value);
        if (!instance_id || !prefix || !Extend.isNumericIdentifier(prefix)) {
            return '';
        }

        const mappingPath = path.join(session_dir, String(instance_id), `lid-mapping-${prefix}_reverse.json`);
        const mappedValue = Extend.readSessionMappingValue(mappingPath);
        const mappedPrefix = Extend.getIdentifierPrefix(mappedValue);

        return Extend.isNumericIdentifier(mappedPrefix) ? mappedPrefix : '';
    },

    buildAutomationContext: (receiber, instance_id = '', contact_data = {}, options = {}) => {
        const incomingData = Extend.safeParseJson(contact_data, {});
        const existingContext = receiber?._automation_context && typeof receiber._automation_context === 'object'
            ? receiber._automation_context
            : {};

        const rawRemoteJid = Extend.stripUndefinedSuffix(
            receiber?.key?.remoteJid ||
            receiber?.chatid ||
            incomingData.transport_jid ||
            incomingData.identity_jid ||
            ''
        );
        const rawIdentifier = Extend.getIdentifierPrefix(rawRemoteJid);
        const explicitUsername = String(
            receiber?._username ||
            incomingData.username ||
            existingContext.username ||
            ''
        ).trim();
        const explicitParentBsuid = String(
            receiber?._parent_bsuid ||
            incomingData.parentBsuid ||
            incomingData.parent_bsuid ||
            existingContext.parentBsuid ||
            ''
        ).trim();
        const explicitWaCandidates = [
            receiber?._wa_id,
            incomingData.number,
            incomingData.waId,
            existingContext.canonicalNumber,
            existingContext.cloudTo,
        ].map((value) => String(value || '').trim()).filter(Boolean);
        const explicitBsuidCandidates = [
            receiber?._bsuid,
            incomingData.bsuid,
            incomingData.identity_key,
            existingContext.bsuid,
        ].map((value) => String(value || '').trim()).filter(Boolean);
        const mappedPhone = Extend.isLidJid(rawRemoteJid)
            ? Extend.resolvePhoneFromLidMapping(instance_id, rawRemoteJid)
            : '';

        let canonicalNumber = explicitWaCandidates.find((value) => Extend.isNumericIdentifier(value)) || '';
        if (!canonicalNumber && mappedPhone) {
            canonicalNumber = mappedPhone;
        }
        if (!canonicalNumber && rawIdentifier && Extend.isNumericIdentifier(rawIdentifier) && !Extend.isLidJid(rawRemoteJid) && !Extend.isGroupLikeJid(rawRemoteJid)) {
            canonicalNumber = rawIdentifier;
        }

        let bsuid = explicitBsuidCandidates.find((value) => value && !Extend.isNumericIdentifier(value)) || '';
        if (!bsuid && rawIdentifier && !Extend.isNumericIdentifier(rawIdentifier) && !Extend.isGroupLikeJid(rawRemoteJid)) {
            bsuid = rawIdentifier;
        }

        let canonicalId = canonicalNumber || bsuid || rawIdentifier || '';
        let canonicalJid = '';
        let identityType = 'jid';

        if (Extend.isGroupLikeJid(rawRemoteJid)) {
            canonicalId = rawRemoteJid || canonicalId;
            canonicalJid = rawRemoteJid;
            identityType = rawRemoteJid.endsWith('@g.us') ? 'group' : 'channel';
        } else if (canonicalNumber) {
            canonicalJid = Extend.normalizeOfficialJid(canonicalNumber);
            identityType = Extend.isLidJid(rawRemoteJid) ? 'phone_from_lid' : 'phone';
        } else if (bsuid) {
            canonicalJid = Extend.normalizeOfficialJid(bsuid);
            identityType = 'bsuid';
        } else if (rawRemoteJid) {
            canonicalJid = Extend.normalizeOfficialJid(rawIdentifier);
        }

        const transportJid = rawRemoteJid || (canonicalNumber ? Extend.normalizeOfficialJid(canonicalNumber) : canonicalJid);
        const replyJid = rawRemoteJid || transportJid || canonicalJid;
        const numberJid = canonicalNumber ? Extend.normalizeOfficialJid(canonicalNumber) : '';
        const bsuidJid = bsuid ? Extend.normalizeOfficialJid(bsuid) : '';
        const aliasCandidates = [
            rawRemoteJid,
            rawIdentifier,
            canonicalId,
            canonicalNumber,
            canonicalJid,
            transportJid,
            replyJid,
            numberJid,
            bsuid,
            bsuidJid,
            incomingData.transport_jid,
            incomingData.identity_jid,
            incomingData.number_jid,
            incomingData.bsuid_jid,
            incomingData.identity_key,
            ...(Array.isArray(incomingData.aliases) ? incomingData.aliases : []),
            ...(Array.isArray(existingContext.aliases) ? existingContext.aliases : []),
        ].map((value) => Extend.stripUndefinedSuffix(value)).filter(Boolean);
        const aliases = [...new Set(aliasCandidates)];

        return {
            canonicalId,
            canonicalNumber,
            canonicalJid,
            transportJid,
            replyJid,
            cloudTo: canonicalNumber,
            identityType,
            aliases,
            source: options.source || (receiber?.official_api ? 'cloud_api' : (Extend.isLidJid(rawRemoteJid) ? 'baileys_lid' : 'baileys')),
            rawRemoteJid,
            rawIdentifier,
            bsuid,
            numberJid,
            bsuidJid,
            username: explicitUsername,
            parentBsuid: explicitParentBsuid,
            instance_id: instance_id || existingContext.instance_id || '',
        };
    },

    attachAutomationContext: (receiber, instance_id = '', contact_data = {}, options = {}) => {
        if (!receiber || typeof receiber !== 'object') {
            return receiber;
        }

        const automationContext = Extend.buildAutomationContext(receiber, instance_id, contact_data, options);
        receiber._automation_context = automationContext;

        if (!receiber._wa_id && automationContext.canonicalNumber) {
            receiber._wa_id = automationContext.canonicalNumber;
        }

        if (!receiber._bsuid && automationContext.bsuid) {
            receiber._bsuid = automationContext.bsuid;
        }

        return receiber;
    },

    getAutomationContext: (receiber, instance_id = '', contact_data = {}, options = {}) => {
        if (receiber?._automation_context && typeof receiber._automation_context === 'object') {
            return receiber._automation_context;
        }

        Extend.attachAutomationContext(receiber, instance_id, contact_data, options);
        return receiber?._automation_context || {};
    },

    resolveCloudDestination: (chat_id = '', message = null, phone_number = '', contact_data = {}) => {
        const automationContext = Extend.getAutomationContext(message || { key: { remoteJid: chat_id } }, message?._automation_context?.instance_id || '', contact_data);
        const numericPhone = Extend.normalizeBulkPhone(phone_number);
        const rawTarget = Extend.stripUndefinedSuffix(chat_id);
        const rawPrefix = Extend.getIdentifierPrefix(rawTarget);

        if (automationContext.cloudTo) {
            return automationContext.cloudTo;
        }

        if (numericPhone) {
            return numericPhone;
        }

        if (Extend.isNumericIdentifier(rawPrefix) && !Extend.isLidJid(rawTarget) && !Extend.isGroupLikeJid(rawTarget)) {
            return rawPrefix;
        }

        return '';
    },

    buildAutomationContactData: (receiber, contact_data = {}, currentContactData = {}) => {
        const current = Extend.safeParseJson(currentContactData, {});
        const incoming = Extend.safeParseJson(contact_data, {});
        const contextInstanceId = incoming.instance_id || current.instance_id || receiber?._automation_context?.instance_id || '';
        const identityContext = Extend.getAutomationContext(receiber, contextInstanceId, { ...current, ...incoming }, { official_api: receiber?.official_api === true });
        const mergedAliases = [...new Set([
            ...(Array.isArray(current.aliases) ? current.aliases : []),
            ...(Array.isArray(incoming.aliases) ? incoming.aliases : []),
            ...identityContext.aliases
        ].filter(Boolean))];

        const nextContactData = {
            ...current,
            ...incoming,
            name: incoming.name || current.name || identityContext.username || identityContext.canonicalId || identityContext.rawIdentifier || '',
            number: incoming.number || current.number || identityContext.canonicalNumber || '',
            profilePicUrl: incoming.profilePicUrl ?? current.profilePicUrl ?? '',
            isGroup: incoming.isGroup ?? current.isGroup ?? false,
            extraInfo: incoming.extraInfo ?? current.extraInfo ?? [],
            bsuid: incoming.bsuid || current.bsuid || identityContext.bsuid || '',
            username: incoming.username || current.username || identityContext.username || '',
            parentBsuid: incoming.parentBsuid || current.parentBsuid || identityContext.parentBsuid || '',
            identity_key: identityContext.canonicalId || incoming.identity_key || current.identity_key || '',
            identity_type: identityContext.identityType || incoming.identity_type || current.identity_type || '',
            transport_jid: identityContext.transportJid || incoming.transport_jid || current.transport_jid || '',
            identity_jid: identityContext.canonicalJid || incoming.identity_jid || current.identity_jid || '',
            number_jid: identityContext.numberJid || incoming.number_jid || current.number_jid || '',
            bsuid_jid: identityContext.bsuidJid || incoming.bsuid_jid || current.bsuid_jid || '',
            aliases: mergedAliases
        };

        const shouldPromoteCanonicalNumber = Boolean(identityContext.canonicalNumber) && (
            !nextContactData.number ||
            nextContactData.number === identityContext.rawIdentifier ||
            (identityContext.transportJid && Extend.isLidJid(identityContext.transportJid))
        );

        if (shouldPromoteCanonicalNumber) {
            nextContactData.number = identityContext.canonicalNumber;
        }

        if (!nextContactData.bsuid && identityContext.bsuid) {
            nextContactData.bsuid = identityContext.bsuid;
        }

        if (!nextContactData.name) {
            nextContactData.name = identityContext.canonicalId || identityContext.rawIdentifier || '';
        }

        return nextContactData;
    },

    buildOfficialIdentityContext: (receiber, contact_data = {}) => {
        const identityContext = Extend.getAutomationContext(receiber, receiber?._automation_context?.instance_id || '', contact_data, { official_api: true });

        return {
            transportJid: identityContext.transportJid,
            transportId: identityContext.rawIdentifier,
            bsuid: identityContext.bsuid,
            waId: identityContext.canonicalNumber,
            username: identityContext.username,
            parentBsuid: identityContext.parentBsuid,
            identityKey: identityContext.canonicalId,
            identityType: identityContext.identityType,
            identityJid: identityContext.canonicalJid,
            numberJid: identityContext.numberJid,
            bsuidJid: identityContext.bsuidJid,
            aliases: identityContext.aliases
        };
    },

    buildOfficialContactData: (receiber, contact_data = {}, currentContactData = {}) => {
        return Extend.buildAutomationContactData(receiber, contact_data, currentContactData);
    },

    hydrateSubscriberRow: (row) => {
        const subscriber = row || {};

        return {
            id: subscriber.id,
            team_id: subscriber.team_id,
            chatid: subscriber.chatid,
            last_chatbot_id: subscriber.last_chatbot_id,
            status: subscriber.status,
            data: Extend.safeParseJson(subscriber.data, {}),
            last_response: subscriber.last_response,
            instance_id: subscriber.instance_id,
            last_response_time: subscriber.last_response_time,
            tags: subscriber.tags,
            kanban_group: subscriber.kanban_group,
            enabled_chatbot: subscriber.enabled_chatbot,
            contact_data: Extend.safeParseJson(subscriber.contact_data, {}),
            unreadMessages: subscriber.unreadMessages,
            lastMessage: subscriber.lastMessage,
            lastMessageTime: subscriber.lastMessageTime
        };
    },

    getSubscriber: async function (waziper, receiber, instance_id = '', contact_data = { name: '', number: '', profilePicUrl: '', isGroup: false, extraInfo: [] }, official_api = false) {
        official_api = official_api || (receiber?.official_api === true);
        const normalizedContactData = Extend.safeParseJson(contact_data, {});
        Extend.attachAutomationContext(receiber, instance_id, normalizedContactData, { official_api });
        const automationContext = Extend.getAutomationContext(receiber, instance_id, normalizedContactData, { official_api });

        if (!official_api) {
            var instance = await common.get_instance(instance_id);

            if (!instance) {
                return false;
            }
            var team_id = instance.team_id;
        } else {
            var account = await common.db_get("sp_accounts", [{ token: instance_id }]);
            if (!account) {
                return false;
            }
            var team_id = account.team_id;
        }

        var chat_id = automationContext.canonicalJid || receiber?.key?.remoteJid;
        var officialIdentity = official_api ? Extend.buildOfficialIdentityContext(receiber, normalizedContactData) : null;

        var objSubscriber = await new Promise(async (resolve, reject) => {
            const searchClauses = [];
            const orderClauses = [];
            const pushClause = (clause) => {
                if (clause && !searchClauses.includes(clause)) {
                    searchClauses.push(clause);
                }
            };
            const pushOrderClause = (clause) => {
                if (clause && !orderClauses.includes(clause)) {
                    orderClauses.push(clause);
                }
            };
            const pushChatCandidate = (value) => {
                const safeValue = Extend.stripUndefinedSuffix(value);
                if (!safeValue) {
                    return;
                }

                pushClause("chatid = " + db.escape(safeValue));

                if (!safeValue.includes('@') && Extend.isNumericIdentifier(safeValue)) {
                    pushClause("chatid = " + db.escape(Extend.normalizeOfficialJid(safeValue)));
                }
            };
            const pushJsonEq = (jsonPath, value) => {
                const safeValue = Extend.stripUndefinedSuffix(value);
                if (!safeValue) {
                    return;
                }

                pushClause(`JSON_UNQUOTE(JSON_EXTRACT(contact_data, '${jsonPath}')) = ${db.escape(safeValue)}`);
            };
            const pushAliasLookup = (value) => {
                const safeValue = Extend.stripUndefinedSuffix(value);
                if (!safeValue) {
                    return;
                }

                pushClause(`JSON_SEARCH(contact_data, 'one', ${db.escape(safeValue)}, NULL, '$.aliases[*]') IS NOT NULL`);
            };

            const chatCandidates = [
                receiber?.key?.remoteJid,
                chat_id,
                automationContext.transportJid,
                automationContext.replyJid,
                automationContext.canonicalJid,
                automationContext.rawIdentifier,
                automationContext.canonicalId,
                automationContext.canonicalNumber,
                ...(Array.isArray(automationContext.aliases) ? automationContext.aliases : []),
            ];

            chatCandidates.forEach(pushChatCandidate);
            pushJsonEq('$.number', automationContext.canonicalNumber);
            pushJsonEq('$.bsuid', automationContext.bsuid || officialIdentity?.bsuid || normalizedContactData.bsuid);
            pushJsonEq('$.identity_key', automationContext.canonicalId || officialIdentity?.identityKey || normalizedContactData.identity_key);

            [
                automationContext.transportJid,
                automationContext.canonicalJid,
                automationContext.numberJid,
                automationContext.bsuidJid,
            ].forEach((value) => {
                pushJsonEq('$.transport_jid', value);
                pushJsonEq('$.identity_jid', value);
                pushJsonEq('$.number_jid', value);
                pushJsonEq('$.bsuid_jid', value);
            });

            (Array.isArray(automationContext.aliases) ? automationContext.aliases : []).forEach(pushAliasLookup);

            if (searchClauses.length === 0) {
                pushClause("chatid = " + db.escape(receiber?.key?.remoteJid || ''));
            }

            if (!Extend.isGroupLikeJid(receiber?.key?.remoteJid || '')) {
                const preferredCanonicalNumber = Extend.stripUndefinedSuffix(automationContext.canonicalNumber || '');
                const preferredCanonicalId = Extend.stripUndefinedSuffix(automationContext.canonicalId || officialIdentity?.identityKey || normalizedContactData.identity_key || '');
                const preferredCanonicalJid = Extend.stripUndefinedSuffix(automationContext.canonicalJid || '');
                const preferredNumberJid = Extend.stripUndefinedSuffix(automationContext.numberJid || preferredCanonicalJid || '');

                if (preferredCanonicalNumber) {
                    pushOrderClause(`CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(contact_data, '$.number')) = ${db.escape(preferredCanonicalNumber)} THEN 0 ELSE 1 END`);
                    pushOrderClause(`CASE WHEN chatid = ${db.escape(Extend.normalizeOfficialJid(preferredCanonicalNumber))} THEN 0 ELSE 1 END`);
                }

                if (preferredCanonicalId) {
                    pushOrderClause(`CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(contact_data, '$.identity_key')) = ${db.escape(preferredCanonicalId)} THEN 0 ELSE 1 END`);
                }

                if (preferredCanonicalJid) {
                    pushOrderClause(`CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(contact_data, '$.identity_jid')) = ${db.escape(preferredCanonicalJid)} THEN 0 ELSE 1 END`);
                }

                if (preferredNumberJid) {
                    pushOrderClause(`CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(contact_data, '$.number_jid')) = ${db.escape(preferredNumberJid)} THEN 0 ELSE 1 END`);
                }
            }

            const nameQuery =
                "SELECT * FROM `sp_whatsapp_subscriber` WHERE team_id = " + db.escape(team_id) +
                " AND instance_id = " + db.escape(instance_id) +
                " AND (" + searchClauses.join(" OR ") + ") " +
                (orderClauses.length > 0 ? ("ORDER BY " + orderClauses.join(", ") + ", id DESC LIMIT 1") : "ORDER BY id DESC LIMIT 1");

            db.query(nameQuery, (a, subscriber_res) => {
                if (subscriber_res && subscriber_res.length > 0) {

                    subscriber_res = Extend.hydrateSubscriberRow(subscriber_res[0]);
                    if (subscriber_res.status == 0) {
                        common.db_update('sp_whatsapp_subscriber', [{ status: 1, kanban_group: '' }, { id: subscriber_res.id }]);
                        subscriber_res.status = 1;
                    }

                    if (official_api) {
                        const nextContactData = Extend.buildOfficialContactData(receiber, normalizedContactData, subscriber_res.contact_data);
                        const nextChatId = nextContactData.identity_jid || chat_id || subscriber_res.chatid;
                        const updateData = {};

                        if (nextChatId && subscriber_res.chatid !== nextChatId) {
                            updateData.chatid = nextChatId;
                            subscriber_res.chatid = nextChatId;
                        }

                        if (JSON.stringify(nextContactData) !== JSON.stringify(subscriber_res.contact_data)) {
                            updateData.contact_data = JSON.stringify(nextContactData);
                            subscriber_res.contact_data = nextContactData;
                        }

                        if (Object.keys(updateData).length > 0) {
                            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriber_res.id + "'", updateData, async () => {
                                resolve(subscriber_res);
                            });
                            return;
                        }
                    } else {
                        const nextContactData = Extend.buildAutomationContactData(receiber, normalizedContactData, subscriber_res.contact_data);
                        const nextChatId = Extend.isGroupLikeJid(receiber?.key?.remoteJid || '')
                            ? (receiber?.key?.remoteJid || subscriber_res.chatid)
                            : (nextContactData.identity_jid || chat_id || subscriber_res.chatid);
                        const updateData = {};

                        if (nextChatId && subscriber_res.chatid !== nextChatId) {
                            updateData.chatid = nextChatId;
                            subscriber_res.chatid = nextChatId;
                        }

                        if (JSON.stringify(nextContactData) !== JSON.stringify(subscriber_res.contact_data)) {
                            updateData.contact_data = JSON.stringify(nextContactData);
                            subscriber_res.contact_data = nextContactData;
                        }

                        if (Object.keys(updateData).length > 0) {
                            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriber_res.id + "'", updateData, async () => {
                                resolve(subscriber_res);
                            });
                            return;
                        }
                    }

                    resolve(subscriber_res);

                } else {

                    var createdData = moment();
                    var nextContactData = official_api
                        ? Extend.buildOfficialContactData(receiber, normalizedContactData)
                        : Extend.buildAutomationContactData(receiber, normalizedContactData);
                    const nextChatId = Extend.isGroupLikeJid(receiber?.key?.remoteJid || '')
                        ? (receiber?.key?.remoteJid || chat_id)
                        : (nextContactData.identity_jid || chat_id);

                    var newSubscriberData = {
                        team_id: team_id,
                        chatid: nextChatId,
                        data: JSON.stringify({ created: createdData }),
                        status: 1,
                        instance_id: instance_id,
                        last_response_time: receiber["messageTimestamp"],
                        tags: '',
                        kanban_group: '',
                        enabled_chatbot: 1,
                        contact_data: JSON.stringify(nextContactData),
                        unreadMessages: 0,
                        lastMessage: '',
                        lastMessageTime: 0
                    }
                    db.query("INSERT INTO sp_whatsapp_subscriber SET ?", newSubscriberData, async (a, newSubscriberSuccess) => {
                        if (a) { console.error(a) }
                        try {
                            if (newSubscriberSuccess) {
                                var webhookData = {
                                    suscriptorId: newSubscriberSuccess.insertId,
                                    chatid: nextChatId,
                                    instance_id: instance_id,
                                    newData: {
                                        inputName: 'created',
                                        value: createdData
                                    },
                                    data: { created: createdData }
                                }

                                await waziper.webhook(instance_id, { event: "new subscriber", data: webhookData });
                            }
                        } catch (error) {
                            console.error('chk phone webhook error:', error);
                        }

                        resolve({
                            id: newSubscriberSuccess.insertId,
                            team_id: team_id,
                            chatid: nextChatId,
                            data: { created: createdData },
                            status: 1,
                            instance_id: instance_id,
                            last_response_time: receiber["messageTimestamp"],
                            tags: '',
                            kanban_group: '',
                            enabled_chatbot: 1,
                            contact_data: nextContactData,
                            unreadMessages: 0,
                            lastMessage: '',
                            lastMessageTime: 0
                        })
                    });

                }
            })

        });

        return objSubscriber;
    },

    updateSubscriberContactData: async function (subscriptor, contact_data = { name: '', number: '', profilePicUrl: '', isGroup: false, extraInfo: [] }) {
        return new Promise((resolve, reject) => {
            const previousContactData = Extend.safeParseJson(subscriptor.contact_data, {});
            const incomingContactData = Extend.safeParseJson(contact_data, {});
            const mergedAliases = [...new Set([
                ...(Array.isArray(previousContactData.aliases) ? previousContactData.aliases : []),
                ...(Array.isArray(incomingContactData.aliases) ? incomingContactData.aliases : [])
            ].filter(Boolean))];

            const mergedContactData = {
                ...previousContactData,
                ...incomingContactData,
                aliases: mergedAliases
            };

            ['name', 'number', 'profilePicUrl', 'bsuid', 'username', 'parentBsuid', 'identity_key', 'identity_type', 'transport_jid', 'identity_jid', 'number_jid', 'bsuid_jid'].forEach((field) => {
                if ((mergedContactData[field] === undefined || mergedContactData[field] === null || mergedContactData[field] === '') && previousContactData[field]) {
                    mergedContactData[field] = previousContactData[field];
                }
            });

            if ((!Array.isArray(mergedContactData.extraInfo) || mergedContactData.extraInfo.length === 0) && Array.isArray(previousContactData.extraInfo)) {
                mergedContactData.extraInfo = previousContactData.extraInfo;
            }

            var data = {
                contact_data: JSON.stringify(mergedContactData)
            }
            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                subscriptor.contact_data = mergedContactData;
                resolve(subscriptor);
            });
        })
    },

    updateSubscriberMessages: async function (subscriptor, unreadMessages, lastMessage, lastMessageTime) {
        return new Promise((resolve, reject) => {
            var data = {
                unreadMessages: unreadMessages,
                lastMessage: lastMessage,
                lastMessageTime: lastMessageTime
            }
            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                subscriptor.unreadMessages = unreadMessages;
                subscriptor.lastMessage = lastMessage;
                subscriptor.lastMessageTime = lastMessageTime;
                resolve(subscriptor);
            });
        })
    },

    updateSubscriber: async function (waziper, subscriptor, message_text, instance_id, user_type, message_obj, chatbot = null) {
        return new Promise((resolve, reject) => {
            if (true) {
                var sData = subscriptor.data;
                if (chatbot != null) {
                    if (chatbot.save_data == 2) {
                        var data = {
                            last_chatbot_id: chatbot.id,
                            last_response: message_text,
                            data: JSON.stringify(sData)
                        }
                        db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                            resolve(true);
                        });
                    } else {
                        resolve(true);
                    }
                } else {
                    db.query("SELECT * FROM sp_whatsapp_chatbot WHERE id = '" + subscriptor.last_chatbot_id + "'", function (a, bot) {
                        if (bot && bot.length > 0) {
                            bot = bot[0];
                            if (bot.save_data == 2) {
                                //console.log('save data',subscriptor.id, subscriptor.last_chatbot_id, instance_id, message_text);
                                sData[bot.inputname] = message_text;
                                var data = {
                                    last_chatbot_id: null,
                                    last_response: message_text,
                                    data: JSON.stringify(sData)
                                }
                                db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                                    if (a) console.error(a);
                                    var jid_ = subscriptor.chatid;

                                    var webhookData = {
                                        suscriptorId: subscriptor.id,
                                        chatid: subscriptor.chatid,
                                        newData: {
                                            inputName: bot.inputname,
                                            value: message_text
                                        },
                                        data: sData
                                    }

                                    waziper.webhook(instance_id, { event: "capturer", data: webhookData });

                                    if (bot.nextBot != null && bot.nextBot != '') {

                                        message_obj['message'] = {};
                                        message_obj['message']['conversation'] = bot.nextBot;

                                        //console.log('nextbot save data',subscriptor.id, subscriptor.last_chatbot_id, instance_id, message_obj);

                                        resolve(false);
                                        waziper.chatbot(instance_id, user_type, message_obj)
                                    } else {
                                        resolve(false);
                                    }

                                });
                            } else {
                                resolve(true);
                            }
                        } else {
                            resolve(true);
                        }
                    });
                }
            } else {
                resolve(false);
            }
        });
    },

    query: async function (query, row = false) {
        var res = await new Promise(async (resolve, reject) => {
            db.query(query, (err, res) => {
                return resolve(res, true);
            });
        });
        return Extend.row(res, row);
    },

    update: async function (table, data) {
        var res = await new Promise(async (resolve, reject) => {
            db.query("UPDATE " + table + " SET ? WHERE ?", data, (err, res) => {
                return resolve(res, true);
            });
        });

        return res;
    },

    row: async (res, row) => {
        if (res != undefined && res.length > 0) {
            if (row || row == undefined) {
                return res[0];
            } else {
                return res;
            }
        }
        return false;
    },

    getAccountTimezone: async (instance_id) => {
        var query = "SELECT u.timezone FROM sp_accounts a LEFT JOIN sp_team t on t.id = a.team_id LEFT JOIN sp_users u on u.id = t.owner where a.token = ?";
        var res = await new Promise(async (resolve, reject) => {
            db.query(query, [instance_id], (err, res) => {
                return resolve(res, true);
            });
        });
        return Extend.row(res);
    },

    getGreet: async (timezone, input) => {
        var current_hour = -1;
        if (timezone) {
            var now = moment(), greet = '', greets = input.split('|'), defaults = ['', 'good morning', 'good afternoon', 'good evening']
            for (let index = greets.length; index < 4; index++) { greets.push(defaults[index]); }
            current_hour = now.tz(timezone.timezone).format('HH');
            current_hour = parseInt(current_hour);
            switch (true) {
                case current_hour >= 12 && current_hour <= 18:
                    greet = greets[2];
                    break;
                case current_hour >= 19 && current_hour <= 23:
                    greet = greets[3];
                    break;
                default:
                    greet = greets[1];
                    break;
            }
            return greet;
        } else {
            return '';
        }
    },

    disableBotKeyword: async (waziper, instance_id, user_type, message) => {


        var ai_item = await common.db_get('sp_whatsapp_ai', [{ instance_id: instance_id }]);

        var subscriptor_ = await Extend.getSubscriber(waziper, message, instance_id);

        var content = false;

        if (message.message?.ephemeralMessage) {
            message.message = message.message.ephemeralMessage.message;
        }

        if (message.message?.buttonsResponseMessage != undefined) {
            content = message.message.buttonsResponseMessage.selectedDisplayText;
        } else if (message.message?.templateButtonReplyMessage != undefined) {
            content = message.message.templateButtonReplyMessage.selectedDisplayText;
        } else if (message.message?.listResponseMessage != undefined) {
            content = message.message.listResponseMessage.title + " " + message.message.listResponseMessage.description;
        } else if (typeof message.message?.extendedTextMessage != "undefined" && message.message.extendedTextMessage != null) {
            content = message.message.extendedTextMessage.text;
        } else if (typeof message.message?.imageMessage != "undefined" && message.message.imageMessage != null) {
            content = message.message.imageMessage.caption;
        } else if (typeof message.message?.videoMessage != "undefined" && message.message.videoMessage != null) {
            content = message.message.videoMessage.caption;
        } else if (typeof message.message?.conversation != "undefined") {
            content = message.message.conversation;
        }


        ai_item.key_disable = ai_item.key_disable != null && ai_item.key_disable != undefined && ai_item.key_disable != '' ? ai_item.key_disable : 'Disable';
        ai_item.key_enable = ai_item.key_enable != null && ai_item.key_enable != undefined && ai_item.key_enable != '' ? ai_item.key_enable : 'Enable';

        if (content == ai_item.key_disable || content == ai_item.key_enable) {
            var val = content == ai_item.key_disable ? '0' : '1';
            var data = {
                enabled_chatbot: val
            }
            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor_.id + "'", data, async (a, b) => { });
        }

    },

    getNowLocale: (prop, timeZone, defaultFormat = 'LLL', defaultLanguaje = 'en') => {
        var now = moment(), format = prop.split('|'), defaults = ['', defaultLanguaje, defaultFormat];
        for (let index = format.length; index < 3; index++) { format.push(defaults[index]); }
        now.locale(format[1]);
        return now.tz(timeZone).format(format[2])
    },

    sendPresence: async (instance, chat_id, item, instance_id = null, message_id = null) => {
        let cloudAccount = null;
        if (instance_id) {
            try {
                cloudAccount = await common.db_get("sp_accounts", [{ token: instance_id }]);
            } catch (error) {
                cloudAccount = null;
            }
        }

        // Cloud API typing indicator support
        if (cloudAccount && cloudAccount.login_type == 1) {
            try {
                // Use defaults if presenceType/presenceTime not configured (e.g., for autoresponder)
                var type = parseInt(item.presenceType || 1); // Default: 1 (composing)
                var time = parseInt(item.presenceTime || 2); // Default: 2 seconds

                if (message_id) {
                    const accountPayloadRaw = cloudAccount.data || cloudAccount.tmp || '';
                    if (accountPayloadRaw) {
                        var accountData = JSON.parse(accountPayloadRaw);
                        var phone_number_id = accountData.phone_number_id;
                        var access_token = accountData.token || accountData.access_token;

                        // Send typing indicator to Meta API
                        // Format: status=read, message_id (wamid), typing_indicator with type
                        var typingPayload = {
                            messaging_product: "whatsapp",
                            status: "read",
                            message_id: message_id,
                            typing_indicator: {
                                type: type == 1 ? "text" : "audio"
                            }
                        };

                        console.log('[DEBUG] Cloud API typing indicator:', message_id, type == 1 ? 'composing' : 'recording');

                        await axios.post(
                            `https://graph.facebook.com/v20.0/${phone_number_id}/messages`,
                            typingPayload,
                            {
                                headers: {
                                    'Authorization': `Bearer ${access_token}`,
                                    'Content-Type': 'application/json'
                                }
                            }
                        );

                        // Wait for the configured time
                        await new Promise(u => setTimeout(u, time * 1000));
                    }
                } else {
                    console.log('[DEBUG] Cloud API typing indicator skipped - no message_id available');
                }
            } catch (error) {
                console.error('[ERROR] Cloud API typing indicator failed:', error.response?.data?.error?.message || error.message);
            }
            return;
        }

        // Baileys typing indicator (original code)
        if (instance) {
            var type = parseInt(item.presenceType), time = parseInt(item.presenceTime);

            if (type != 0 && time > 0) {
                await instance.presenceSubscribe(chat_id)
                await new Promise(u => setTimeout(u, 500));
                await instance.sendPresenceUpdate(type == 1 ? 'composing' : 'recording', chat_id)
                await new Promise(u => setTimeout(u, time * 1000 - 500));
                await instance.sendPresenceUpdate('paused', chat_id)
            }
        }
    },

    nextBot: async (result, item, message, instance_id, user_type, WAZIPER) => {
        if (true) {
            if (item.nextBot != '') {
                message['message'] = {};
                message['message']['conversation'] = item.nextBot;
                WAZIPER.chatbot(instance_id, user_type, message);
            }
        }
    },

    toLowerKeys: function (obj) {
        return Object.keys(obj).reduce((pValue, cValue) => {
            pValue[cValue.toLowerCase()] = obj[cValue];
            return pValue;
        }, {});
    },

    convert_data: function (params, caption, isUrl = false) {

        var params = Extend.toLowerKeys(params);
        var regexExp = /\[(.*?)\]/;

        var oldValue;
        var counterLimit = 0;
        while (oldValue = caption["match"](regexExp)) {
            oldValue = oldValue[0];
            var prop = oldValue["substring"](1, oldValue.length - 1);
            var val = Extend.getDescendantProp(params, prop);

            if (val != undefined) {
                if (isUrl) {
                    caption = caption["replace"](oldValue, encodeURIComponent(val));
                } else {
                    caption = caption["replace"](oldValue, val);
                }
            } else {
                caption = caption["replace"](oldValue, '');
            }

            counterLimit++;
            if (counterLimit == 150) {
                break;
            }

        }
        return caption;
    },

    common_data: async (waziper, instance, instance_id, item, message, processText, withPresense = false, isUrl = false) => {

        var timezone = await Extend.getAccountTimezone(instance_id);

        var commonProps = {
            user_phone: common.get_phone(message?.key?.remoteJid ?? ''),
            wa_name: message?.pushName ?? '',
            me_phone: common.get_phone(instance?.user?.id ?? ''),
            me_wa_name: instance?.user?.name ?? '',
        }

        var regexExp = /\[(.*?)\]/;
        var oldValue;
        var counterLimit = 0;

        if (message) {
            var subscriber_ = await Extend.getSubscriber(waziper, message, instance_id);
            if (subscriber_) {
                var data = subscriber_.data;
                commonProps = { ...commonProps, ...data }
            }
        }


        if (item && item.get_api_data == 2 && item.api_url != '') {

            try {
                // obtengo los parametros y los reemplazo e la url
                var url = Extend.convert_data(commonProps, item.api_url, true);

                // obtengo el objeto de configuracion de la api
                var api_config = JSON.parse(item.api_config);
                var api_data = {};
                var api_headers = {};


                if (api_config.body && api_config.body?.length > 0) {
                    api_config.body.forEach(element => {
                        api_data[element.name] = Extend.convert_data(commonProps, element.value, false);
                    });
                }

                if (api_config.header && api_config.header?.length > 0) {
                    api_config.header.forEach(element => {
                        api_headers[element.name] = Extend.convert_data(commonProps, element.value, false);
                    });
                }

                var axios_config = {
                    method: api_config.method,
                    url: url,
                    timeout: 120000,
                    //data: api_data,
                    headers: api_headers
                };

                // Si el mÃ©todo es GET, agregar los datos como parÃ¡metros de la URL
                if (api_config.method === 'get') {
                    axios_config.params = api_data;
                    axios_config.data = api_data;
                } else {
                    axios_config.data = api_data;
                }

                var dt = await axios(axios_config);

                // Verificar si dt.data es un array
                if (Array.isArray(dt.data)) {
                    // Agregar una propiedad 'items' a commonProps con el array
                    commonProps = { ...commonProps, items: dt.data };
                } else {
                    // Si no es un array, agregar directamente a commonProps
                    commonProps = { ...commonProps, ...dt.data };
                }


            } catch (error) {
                console.error('fail apirest general', error)
            }

        }

        while (oldValue = processText["match"](regexExp)) {
            oldValue = oldValue[0];
            var prop = oldValue["substring"](1, oldValue.length - 1);
            if (prop.includes('greet')) {
                var val = await Extend.getGreet(timezone, prop);
            } else if (prop.includes('time')) {
                var val = Extend.getNowLocale(prop, timezone.timezone, 'LT');
            } else if (prop.includes('date')) {
                var val = Extend.getNowLocale(prop, timezone.timezone, 'll');
            } else if (prop.includes('now_format')) {
                var val = Extend.getNowLocale(prop, timezone.timezone);
            } else {
                var val = Extend.getDescendantProp(commonProps, prop)
            }


            if (val) {
                if (isUrl) {
                    processText = processText.replace(oldValue, encodeURIComponent(val));
                } else {
                    processText = processText.replace(oldValue, val);
                }
            } else {
                processText = processText.replace(oldValue, '');
            }


            counterLimit++;
            if (counterLimit == 150) {
                break;
            }
        }


        return processText;
    },

    /*check_phone: function (instance, contactToSend, phoneStatus = 0, cloud = true) {
    return new Promise(async (res, rej) => {
        if (instance || cloud) {
            if (`${contactToSend}`.includes("g.us") || `${contactToSend}`.includes("status") || phoneStatus === 1) {
                res(true);
            } else if (phoneStatus === 2) {
                res(false);
            } else {
                try {
                    if (!cloud) {
                        var validPhone = await new Promise((resolve, reject) => {
                            const timeoutId = setTimeout(() => {
                                resolve([true, true]);
                            }, 10000);
                            instance["onWhatsApp"](contactToSend).then(value => {
                                clearTimeout(timeoutId);
                                resolve(value);
                            }).catch(err => {
                                clearTimeout(timeoutId);
                                reject(err);
                            });
                        });
                    } else {
                        throw new Error('trying from cloud account');
                    }
                } catch (err) {
                    var validPhone = [true, true];
                }

                if (validPhone.length > 0) {
                    res(true);
                } else {
                    res(false);
                }
            }
        } else {
            res(false);
        }
    });
}*/

    check_phone: function (e, a, t = 0, isCloud = false) {
        return new Promise(async (s, n) => {
            console.log(`[DEBUG] check_phone called. Phone: ${a}, isCloud: ${isCloud}`);
            if (isCloud || ("" + a).includes("g.us") || ("" + a).includes("status") || 1 == t) {
                console.log('[DEBUG] check_phone resolving true (Cloud/Group/Status)');
                s(!0);
            }
            else if (2 == t) s(!1);
            else {
                try {
                    var r = await new Promise((t, s) => {
                        const n = setTimeout(() => {
                            t([!0, !0]);
                        }, 1e4);
                        if (e && typeof e.onWhatsApp === 'function') {
                            e.onWhatsApp(a).then((e) => {
                                clearTimeout(n), t(e);
                            }).catch(() => {
                                clearTimeout(n), t([!0, !0]);
                            });
                        } else {
                            clearTimeout(n);
                            t([!0, !0]); // Default to true if e.onWhatsApp is not a function (safety fallback)
                        }
                    });
                } catch (e) {
                    r = [!0, !0];
                }
                r.length > 0 ? s(!0) : s(!1);
            }
        });
    },

    resetAi: function (instance_id) {
        console.log('restarting openai history for', instance_id);

        delete OpenAi_History_Chat[instance_id];
        delete OpenAi_Chats_Ids[instance_id];

        OpenAi_History_Chat[instance_id] = {};
        OpenAi_Chats_Ids[instance_id] = {};
    },

    process_message: function (instance_id, item, chat_id, type, content, onFailGPTcallback = (error) => { }) {
        return new Promise(async (resolve, rejected) => {
            try {
                if (type == 'chatbot' && item.use_ai) {
                    var ai_item = await common.db_get('sp_whatsapp_ai', [{ instance_id: instance_id }]);
                    var messages_ia = [];

                    if (ai_item && ai_item.status == 1) {
                        var fix3_5 = false;
                        if (ai_item.model == 'gpt-3.5-turbo-0613') {
                            fix3_5 = true;
                        }

                        const openai = new OpenAI({
                            apiKey: ai_item.apikey,
                            httpAgent: proxyAgent,
                        });

                        messages_ia.push({ role: "system", content: ai_item.main_prompt });

                        if (!OpenAi_History_Chat[instance_id]) OpenAi_History_Chat[instance_id] = {};
                        if (!OpenAi_Chats_Ids[instance_id]) OpenAi_Chats_Ids[instance_id] = {};

                        if (!OpenAi_History_Chat[instance_id][chat_id]) {
                            OpenAi_History_Chat[instance_id][chat_id] = []
                        }

                        if (OpenAi_Chats_Ids[instance_id][chat_id]) {
                            var last_id = OpenAi_Chats_Ids[instance_id][chat_id];
                            var index = OpenAi_History_Chat[instance_id][chat_id].findIndex(x => x.id == last_id);
                            if (index != -1) {
                                var tmp = [];
                                for (var i = index; i < OpenAi_History_Chat[instance_id][chat_id].length; i++) {
                                    tmp.push(OpenAi_History_Chat[instance_id][chat_id][i]);
                                }
                                OpenAi_History_Chat[instance_id][chat_id] = tmp;
                            }
                        }

                        OpenAi_History_Chat[instance_id][chat_id].forEach(item => {
                            messages_ia.push(item);
                        });

                        messages_ia.push({ role: "user", content: content });

                        var resolve_obj = {};
                        var err = 'check your apikey or your openai account';

                        for (let intent = 0; intent <= 5; intent++) {
                            try {
                                console.log(`[DEBUG] Calling OpenAI. Intent: ${intent}, Chat: ${chat_id}`);
                                const completion = await openai.chat.completions.create({
                                    model: fix3_5 ? "gpt-3.5-turbo" : ai_item?.model,
                                    messages: messages_ia,
                                });

                                OpenAi_History_Chat[instance_id][chat_id].push({ role: 'user', content: content });
                                OpenAi_History_Chat[instance_id][chat_id].push(completion.choices[0].message);
                                OpenAi_Chats_Ids[instance_id][chat_id] = item.id;

                                const completion_text = completion.choices[0].message.content;
                                resolve_obj = { new_caption: completion_text, can_continue: true };
                                break;
                            } catch (error) {
                                console.error('[ERROR] AI Intent failed', intent, error);
                                err = error.message;
                            }

                            if (intent == 5) {
                                onFailGPTcallback(err);
                                delete OpenAi_History_Chat[instance_id][chat_id];
                                resolve_obj = { new_caption: '', can_continue: false };
                            }
                        }

                        resolve(resolve_obj);
                        return;

                    } else {
                        console.error('ai is disabled from settings for', instance_id)
                        resolve({ new_caption: '', can_continue: false });
                        return;
                    }
                }
            } catch (err) {
                console.error('[CRITICAL] process_message crash:', err);
            }
            resolve({ new_caption: item.caption, can_continue: true });
        });
    },

    validatePhones: async (waziper, sessions) => {


        try {
            if (true) {
                var set_progress = async function (id, val = 4) {
                    await common.db_query(`UPDATE sp_whatsapp_phone_numbers SET is_valid=${val}  WHERE id=${id}`)
                    /*db.query(`UPDATE sp_whatsapp_phone_numbers SET is_valid=${val}  WHERE id=${id}`, function (f, s) {
                        if (f) console.error(f);
                    });*/
                }
                var bulkQuery = `SELECT pn.id, pn.pid, pn.team_id, pn.phone, pn.is_valid, u.status, t.ids as team_ids FROM sp_whatsapp_phone_numbers as pn LEFT JOIN sp_team as t on t.id = pn.team_id LEFT JOIN sp_users as u on u.id = t.owner WHERE u.status = 2 AND(is_valid is null OR is_valid = 4) ORDER BY  is_valid LIMIT 50`;
                var toValidate = await Extend.query(bulkQuery);
                if (toValidate && toValidate.length > 0) {
                    for (let b_index = 0; b_index < toValidate.length; b_index++) {
                        const bulk = toValidate[b_index];
                        var bTeamIds = bulk["team_id"];
                        var bId = bulk["id"];
                        var bPhone = bulk["phone"];
                        var pId = bulk['pid'];
                        set_progress(bId, 3);
                        var queryAccount = `SELECT * FROM sp_accounts WHERE social_network = 'whatsapp' AND login_type = '2' AND status = '1' AND team_id= '${bTeamIds}'`;
                        var accounts = await Extend.query(queryAccount);
                        if (accounts && accounts.length > 0) {
                            var accounts_ids = accounts.map(u => u.id);
                            var account_id = accounts_ids[Math.floor(Math.random() * accounts_ids.length)];
                            var account = accounts.find(o => o.id == account_id);
                            var token = account.token;
                            if (sessions[token]) {
                                var newPhone = await common.check_especials(bPhone, bId);
                                var isValid = await Extend.check_phone(sessions[token], newPhone, 0);
                                set_progress(bId, isValid ? '1' : '2');
                            } else {
                                set_progress(bId, 4);
                            }
                        } else {
                            set_progress(bId, 0)
                        }
                    }
                    var s_toValidatePIDs = toValidate.reduce(function (acc, curr) {
                        if (!acc.includes(curr.pid)) acc.push(curr.pid);
                        return acc;
                    }, []);

                    for (let pid = 0; pid < s_toValidatePIDs.length; pid++) {
                        const element = s_toValidatePIDs[pid];
                        var item = toValidate.find(o => o.pid == element);
                        var bTeamIds = item["team_id"];
                        waziper.io.emit(`check_phone_update_${bTeamIds}`, {
                            id: element
                        })
                    }
                }
            }
        } catch (error) {

        }

    },

    handleMsgAck: async (waziper, instance_id, msg, ack = null) => {
        if (true) {
            await new Promise((r) => setTimeout(r, 500));
            try {

                var messageToUpdate = await common.db_get('sp_whatsapp_messages', [{ instance_id: instance_id }, { id: msg.key.id }]);
                if (!messageToUpdate) return;

                await Extend.update("sp_whatsapp_messages", [{ ack: ack }, { id: msg.key.id }]);

                messageToUpdate.ack = ack;

                waziper.io
                    //.to(`${instance_id}`)
                    .emit(`instance-${instance_id}-appMessage-update`, {
                        message: messageToUpdate
                    })

            } catch (err) {
                console.error(`Error handling message ack. Err: ${err}`);
            }
        }
    },

    autoresponder_time: async (message, instance_id, chat_id) => {

        const autoresponder_val = await cacheLayer.get(`autoresponder:${instance_id}:${chat_id}`);
        await cacheLayer.set(`autoresponder:${instance_id}:${chat_id}`, message.messageTimestamp);

        return Number.parseFloat(autoresponder_val);
    },

    process_official_sent_message: async function (messageBody, pid, message_id, pushname = "", instance_id = "") {

        // common.special_log(messageBody, 'procesing sent message body');
        switch (messageBody.type ?? '') {
            case "image":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.image?.caption ?? '',
                        link: messageBody.image?.link
                    }
                }
                break;
            case "audio":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.audio?.caption ?? '',
                        link: messageBody.audio?.link
                    }
                }
                break;
            case "document":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.document?.caption ?? '',
                        link: messageBody.document?.link
                    }
                }
                break;
            case "video":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.video?.caption ?? '',
                        link: messageBody.video?.link
                    }
                }
                break;
            case "template":
                message_to_script = {
                    message: {
                        conversation: `Template Name: ${messageBody.template?.name || ''}`
                    }
                }
                break;
            default:

                message_to_script = {
                    message: {
                        conversation: messageBody.text?.body || ''
                    }
                }

                break;
        }
        const normalizedPid = (typeof pid === 'string' && pid.includes('@')) ? pid : Extend.normalizeOfficialJid(pid);
        message_to_script.messageTimestamp = common.time();
        message_to_script.pushName = pushname;
        message_to_script.official_api = true;
        message_to_script.key = {
            remoteJid: normalizedPid || pid,
            id: message_id.slice(-15),
            fromMe: true
        };

        Extend.attachAutomationContext(message_to_script, instance_id, {
            number: Extend.isNumericIdentifier(Extend.getIdentifierPrefix(normalizedPid || pid)) ? Extend.getIdentifierPrefix(normalizedPid || pid) : '',
            identity_jid: normalizedPid || pid,
            instance_id: instance_id
        }, { official_api: true, source: 'cloud_api_outbound' });

        return message_to_script;
    },

    mark_as_read: async function (message, instance_id) {

        var account = await common.db_get("sp_accounts", [{ token: instance_id }]);

        if (account && account.login_type == 1) {

            let tmpData = {};
            try {
                let rawData = account.data || account.tmp;
                tmpData = rawData ? JSON.parse(rawData) : {};
            } catch (e) {
                console.error("Error parsing account data for instance " + instance_id, e);
                return;
            }
            const bearer = tmpData.token || tmpData.access_token || "";
            const phoneNumberId = tmpData.phone_number_id || account.username;
            const whatsappAPIURL = `https://graph.facebook.com/v19.0/${phoneNumberId}/messages`;

            let data = JSON.stringify({
                "messaging_product": "whatsapp",
                "status": "read",
                "message_id": message.id
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
                .then((response) => {
                    console.log('mark as read', message.id, JSON.stringify(response.data));
                })
                .catch((error) => {
                    console.log('fail mark as read', message.id, error);
                });
        }
    },

    process_official_message: async function (message, pushname, from_me = false, instance_id = "") {

        switch (message.type ?? '') {
            case 'interactive':
                // Detectar tipo de resposta interativa (button_reply ou list_reply)
                let interactiveText = '';
                if (message.interactive?.button_reply) {
                    interactiveText = message.interactive.button_reply.title || '';
                } else if (message.interactive?.list_reply) {
                    // Para lista, usar título + descrição (mesmo comportamento do Baileys)
                    interactiveText = message.interactive.list_reply.title || '';
                    if (message.interactive.list_reply.description) {
                        interactiveText += ' ' + message.interactive.list_reply.description;
                    }
                } else if (message.interactive?.nfm_reply) {
                    const flowReply = Extend.parseOfficialFlowReply(message.interactive.nfm_reply);
                    interactiveText = flowReply.summary_text || flowReply.body || '';

                    message_to_script = {
                        message: {
                            extendedTextMessage: {
                                text: interactiveText
                            }
                        },
                        flow_reply: flowReply
                    }

                    break;
                }

                message_to_script = {
                    message: {
                        buttonsResponseMessage: {
                            selectedDisplayText: interactiveText
                        }
                    }
                }

                break;
            case 'image':
                message_to_script = {
                    message: {
                        imageMessage: {
                            caption: message.image.caption ?? '',
                            mimetype: message.image.mime_type,
                            id: message.image.id ?? ''
                        }
                    }
                }
                break;
            case 'video':
                message_to_script = {
                    message: {
                        videoMessage: {
                            caption: message.video.caption ?? '',
                            mimetype: message.video.mime_type,
                            id: message.video.id ?? ''
                        }
                    }
                }
                break;
            case 'audio':
                message_to_script = {
                    message: {
                        audioMessage: {
                            caption: message.audio.caption ?? '',
                            mimetype: message.audio.mime_type,
                            id: message.audio.id ?? ''
                        }
                    }
                }
                break;
            case 'sticker':
                message_to_script = {
                    message: {
                        stickerMessage: {
                            caption: message.sticker.caption ?? '',
                            mimetype: message.sticker.mime_type,
                            id: message.sticker.id ?? ''
                        }
                    }
                }
                break;
            default:
                message_to_script = {
                    message: {
                        conversation: message.text?.body || ''
                    }
                }
                break;
        }

        const officialReceiver = {
            ...message,
            official_api: true,
            key: {
                remoteJid: message.from || message._bsuid || message._wa_id || ''
            }
        };
        Extend.attachAutomationContext(officialReceiver, instance_id, {
            number: String(message._wa_id || '').trim(),
            bsuid: String(message._bsuid || '').trim(),
            username: String(message._username || '').trim(),
            parentBsuid: String(message._parent_bsuid || '').trim(),
            instance_id: instance_id
        }, { official_api: true, source: 'cloud_api' });
        const officialIdentity = Extend.buildOfficialIdentityContext(officialReceiver);
        message_to_script.messageTimestamp = common.time();
        message_to_script.official_api = true;
        message_to_script.wamid = message.id; // Full wamid for Cloud API typing indicator
        message_to_script.key = {
            remoteJid: officialIdentity.identityJid || Extend.normalizeOfficialJid(message.from || message._bsuid || message._wa_id || ''),
            id: message.id.slice(-15),
            fromMe: from_me
        };
        message_to_script.pushName = pushname;

        // Meta 2026 BSUID: Propagate new identification fields
        if (message._bsuid) message_to_script._bsuid = message._bsuid;
        if (message._wa_id) message_to_script._wa_id = message._wa_id;
        if (message._username) message_to_script._username = message._username;
        if (message._parent_bsuid) message_to_script._parent_bsuid = message._parent_bsuid;
        if (officialReceiver._automation_context) {
            message_to_script._automation_context = officialReceiver._automation_context;
        }

        // common.special_log(message_to_script, "message_to_script");
        return message_to_script;
    },

    parseOfficialFlowReply: function (reply) {
        const rawResponseJson = reply?.response_json ?? reply?.responseJson ?? '';
        let parsedResponse = {};

        if (rawResponseJson && typeof rawResponseJson === 'object' && !Array.isArray(rawResponseJson)) {
            parsedResponse = rawResponseJson;
        } else if (typeof rawResponseJson === 'string' && rawResponseJson.trim() !== '') {
            try {
                const decoded = JSON.parse(rawResponseJson);
                if (decoded && typeof decoded === 'object' && !Array.isArray(decoded)) {
                    parsedResponse = decoded;
                }
            } catch (error) {
                parsedResponse = {};
            }
        }

        const summaryParts = [];
        const flowName = parsedResponse.flow_name || parsedResponse.flow_slug || reply?.name || '';
        const categoryTitle = parsedResponse.category_title || '';
        const optionTitle = parsedResponse.option_title || '';

        if (flowName) {
            summaryParts.push(`Flow: ${flowName}`);
        }

        if (categoryTitle) {
            summaryParts.push(`Categoria: ${categoryTitle}`);
        }

        if (optionTitle) {
            summaryParts.push(`Opcao: ${optionTitle}`);
        }

        for (const [key, value] of Object.entries(parsedResponse)) {
            if (['flow_name', 'flow_slug', 'category_title', 'option_title'].includes(key)) {
                continue;
            }

            if (value === null || value === undefined || value === '') {
                continue;
            }

            if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
                summaryParts.push(`${key}: ${value}`);
            }
        }

        return {
            name: reply?.name || '',
            body: reply?.body || '',
            response_json_raw: typeof rawResponseJson === 'string' ? rawResponseJson : JSON.stringify(rawResponseJson || {}),
            response_json: parsedResponse,
            summary_text: summaryParts.join(' | ')
        };
    },

    chat: {
        filterMessages: (msg) => {
            // common.special_log(msg, 'procesando mensaje', "-")

            if (msg.message?.protocolMessage) return false;

            if ([
                WAMessageStubType.REVOKE,
                WAMessageStubType.E2E_DEVICE_CHANGED,
                WAMessageStubType.E2E_IDENTITY_CHANGED,
                WAMessageStubType.CIPHERTEXT
            ].includes(msg.messageStubType)) return false;

            return true;
        },
        getTypeMessage: (msg) => {
            return getContentType(msg.message);
        },
        isValidMsg: (msg) => {
            if (msg.key.remoteJid === "status@broadcast") return false;
            try {
                const msgType = Extend.chat.getTypeMessage(msg);
                if (!msgType) {
                    return;
                }

                const ifType =
                    msgType === "conversation" ||
                    msgType === "extendedTextMessage" ||
                    msgType === "audioMessage" ||
                    msgType === "videoMessage" ||
                    msgType === "imageMessage" ||
                    msgType === "documentMessage" ||
                    msgType === "documentWithCaptionMessage" ||
                    msgType === "stickerMessage" ||
                    msgType === "buttonsResponseMessage" ||
                    msgType === "buttonsMessage" ||
                    msgType === "messageContextInfo" ||
                    msgType === "locationMessage" ||
                    msgType === "liveLocationMessage" ||
                    msgType === "contactMessage" ||
                    msgType === "voiceMessage" ||
                    msgType === "mediaMessage" ||
                    msgType === "contactsArrayMessage" ||
                    msgType === "reactionMessage" ||
                    msgType === "ephemeralMessage" ||
                    msgType === "protocolMessage" ||
                    msgType === "listResponseMessage" ||
                    msgType === "listMessage" ||
                    msgType === "viewOnceMessage"

                if (!ifType) {
                    //console.error(`>>> not isValidMsg: ${msgType} \n${JSON.stringify(msg?.message)}`);
                    return false;
                }

                return !!ifType;
            } catch (error) {
                return false;
            }
        },
        getBodyButton: (msg) => {
            if (msg.key.fromMe && msg?.message?.viewOnceMessage?.message?.buttonsMessage?.contentText) {
                let bodyMessage = `*${msg?.message?.viewOnceMessage?.message?.buttonsMessage?.contentText}*`;

                for (const buton of msg.message?.viewOnceMessage?.message?.buttonsMessage?.buttons) {
                    bodyMessage += `\n\n${buton.buttonText?.displayText}`;
                }
                return bodyMessage;
            }

            if (msg.key.fromMe && msg?.message?.viewOnceMessage?.message?.listMessage) {
                let bodyMessage = `*${msg?.message?.viewOnceMessage?.message?.listMessage?.description}*`;
                for (const buton of msg.message?.viewOnceMessage?.message?.listMessage?.sections) {
                    for (const rows of buton.rows) {
                        bodyMessage += `\n\n${rows.title}`;
                    }
                }

                return bodyMessage;
            }
        },
        msgLocation: (image, latitude, longitude) => {
            if (image) {
                var b64 = Buffer.from(image).toString("base64");

                let data = `data:image/png;base64, ${b64} | https://maps.google.com/maps?q=${latitude}%2C${longitude}&z=17&hl=pt-BR|${latitude}, ${longitude} `;
                return data;
            }
        },
        getBodyMessage: (msg) => {
            try {
                if (msg.message?.ephemeralMessage) {
                    msg.message = msg.message.ephemeralMessage.message;
                }
                let type = Extend.chat.getTypeMessage(msg);

                const types = {
                    conversation: msg?.message?.conversation,
                    imageMessage: msg.message?.imageMessage?.caption,
                    videoMessage: msg.message.videoMessage?.caption,
                    extendedTextMessage: msg.message.extendedTextMessage?.text,
                    buttonsResponseMessage: msg.message.buttonsResponseMessage?.selectedButtonId || msg.message.templateMessage?.hydratedTemplate?.hydratedContentText,
                    templateButtonReplyMessage: msg.message?.templateButtonReplyMessage?.selectedId,
                    messageContextInfo: msg.message.buttonsResponseMessage?.selectedButtonId || msg.message.listResponseMessage?.title,
                    buttonsMessage: Extend.chat.getBodyButton(msg) || msg.message.listResponseMessage?.singleSelectReply?.selectedRowId,
                    viewOnceMessage: Extend.chat.getBodyButton(msg) || msg.message?.listResponseMessage?.singleSelectReply?.selectedRowId,
                    stickerMessage: "sticker",
                    contactMessage: msg.message?.contactMessage?.vcard,
                    contactsArrayMessage: "varios contatos",
                    //locationMessage: `Latitude: ${msg.message.locationMessage?.degreesLatitude} - Longitude: ${msg.message.locationMessage?.degreesLongitude}`,
                    locationMessage: Extend.chat.msgLocation(
                        msg.message?.locationMessage?.jpegThumbnail,
                        msg.message?.locationMessage?.degreesLatitude,
                        msg.message?.locationMessage?.degreesLongitude
                    ),
                    liveLocationMessage: `Latitude: ${msg.message.liveLocationMessage?.degreesLatitude} - Longitude: ${msg.message.liveLocationMessage?.degreesLongitude}`,
                    documentMessage: msg.message?.documentMessage?.title,
                    audioMessage: "audio",
                    listMessage: Extend.chat.getBodyButton(msg) || msg.message.listResponseMessage?.title,
                    listResponseMessage: msg.message?.listResponseMessage?.singleSelectReply?.selectedRowId,
                    reactionMessage: msg.message.reactionMessage?.text || "reaction",
                    documentWithCaptionMessage: msg.message.documentMessage?.caption || 'document'
                };

                const objKey = Object.keys(types).find(key => key === type);

                if (!objKey) {
                    throw new Error(`no body message: ${type} \n ${JSON.stringify(msg)}`)
                }
                return types[type];

            } catch (error) {
                //console.error(error);
                return false;
            }
        },
        getSenderMessage: (session, msg) => {
            const me = {
                id: jidNormalizedUser(session.user.id),
                name: session.user.name
            }

            if (msg.key.fromMe) return me.id;
            const senderId = msg.participant || msg.key.participant || msg.key.remoteJid || undefined;
            return senderId && jidNormalizedUser(senderId);
        },
        getContactMessage: async (session, msg) => {
            const rawIdentifier = Extend.getIdentifierPrefix(msg.key.remoteJid);
            return { id: msg.key.remoteJid, name: msg.key.fromMe ? rawIdentifier : (msg.pushName || rawIdentifier) };
        },
        CreateOrUpdateContactService: async (waziper, message, instance_id, { name, number, profilePicUrl, isGroup, extraInfo = [] }) => {
            var subscriptor_ = await Extend.getSubscriber(waziper, message, instance_id, { name: name, number: number, profilePicUrl: profilePicUrl, isGroup: isGroup, extraInfo: extraInfo }, message.official_api ?? false);
            if (!message.key.fromMe) {
                var subscriptor_ = await Extend.updateSubscriberContactData(subscriptor_, { name: name, number: number, profilePicUrl: profilePicUrl, isGroup: isGroup, extraInfo: extraInfo })
            }
            return subscriptor_;
        },
        verifyContact: async (waziper, session, message, instance_id, msgContact) => {
            let profilePicUrl;
            try {
                if (message.official_api ?? false)
                    profilePicUrl = ''
                else
                    profilePicUrl = await session.profilePictureUrl(msgContact.id);
            } catch (e) {
                profilePicUrl = '';//join(__dirname, "..", "files", 'nopicture.png'); //`${process.env.FRONTEND_URL}/nopicture.png`;
            }

            let contactData = null;
            if (message.official_api ?? false) {
                const rawIdentifier = Extend.getIdentifierPrefix(msgContact.id);
                contactData = Extend.buildOfficialContactData(message, {
                    name: msgContact?.name || rawIdentifier,
                    number: String(message._wa_id || '').trim(),
                    profilePicUrl,
                    isGroup: msgContact.id.includes("g.us"),
                    instance_id: instance_id,
                    username: String(message._username || '').trim(),
                    parentBsuid: String(message._parent_bsuid || '').trim()
                });
            } else {
                contactData = Extend.buildAutomationContactData(message, {
                    name: msgContact?.name || msgContact.id.replace(/\D/g, ""),
                    number: msgContact.id.replace(/\D/g, ""),
                    profilePicUrl,
                    isGroup: msgContact.id.includes("g.us"),
                    instance_id: instance_id
                });
            }

            const contact = Extend.chat.CreateOrUpdateContactService(waziper, message, instance_id, contactData);

            return contact;
        },
        CreateMessageService: async ({ messageData, instance_id }, contact, waziper) => {

            let message_ = { ...messageData, instance_id, createdAt: common.time(), updatedAt: common.time() };

            if (message_.body == null || message_.body === '') {
                var fallbackBody = null;

                try {
                    var parsedData = message_.dataJson ? JSON.parse(message_.dataJson) : null;
                    if (parsedData && parsedData.message) {
                        var msg = parsedData.message;
                        if (msg.listMessage) {
                            fallbackBody = msg.listMessage.description || msg.listMessage.title || msg.listMessage.buttonText;
                        } else if (msg.interactiveMessage) {
                            var imsg = msg.interactiveMessage;
                            if (imsg.body && imsg.body.text) {
                                fallbackBody = imsg.body.text;
                            } else if (imsg.header && imsg.header.title) {
                                fallbackBody = imsg.header.title;
                            } else if (imsg.footer && imsg.footer.text) {
                                fallbackBody = imsg.footer.text;
                            }
                        } else if (msg.extendedTextMessage && msg.extendedTextMessage.text) {
                            fallbackBody = msg.extendedTextMessage.text;
                        }
                    }
                } catch (error) {
                    console.warn('CreateMessageService fallback parse error', error);
                }

                if (!fallbackBody) {
                    fallbackBody = (message_.caption || message_.title || message_.mediaType || '').toString();
                }

                if (!fallbackBody && message_.dataJson) {
                    fallbackBody = message_.dataJson;
                }

                if (!fallbackBody) {
                    fallbackBody = '-';
                }

                message_.body = fallbackBody;
            }

            var res = await common.db_insert('sp_whatsapp_messages', message_);

            waziper.io
                //.to(`${instance_id}`)
                //.to("notification")
                .emit(`instance-${instance_id}-appMessage-create`, {
                    message: message_,
                    subscriber: contact
                });

            return message_;

        },
        verifyMessage: async (msg, body, instance_id, contact, waziper) => {
            var plain_message = JSON.stringify(msg);


            if (!Number.isInteger(msg.status)) {
                msg.status = 3;
            }

            const messageData = {
                id: msg.key.id,
                instance_id: instance_id,
                contactId: msg.key.fromMe ? undefined : contact.id,
                body: body,
                fromMe: msg.key.fromMe,
                mediaType: Extend.chat.getTypeMessage(msg),
                read: msg.key.fromMe,
                ack: msg.status ?? 3,
                remoteJid: msg.key.remoteJid,
                participant: msg.key.participant ?? msg.key.remoteJid,
                dataJson: plain_message
            };
            return await Extend.chat.CreateMessageService({ messageData, instance_id: instance_id }, contact, waziper);

        },
        downloadMedia: async (msg, instance_id) => {
            try {

                const mineType =
                    msg.message?.imageMessage ||
                    msg.message?.audioMessage ||
                    msg.message?.videoMessage ||
                    msg.message?.stickerMessage ||
                    msg.message?.documentMessage ||
                    msg.message?.extendedTextMessage?.contextInfo?.quotedMessage?.imageMessage;




                const messageType = msg.message?.documentMessage
                    ? "document"
                    : mineType.mimetype.split("/")[0].replace("application", "document")
                        ? (mineType.mimetype.split("/")[0].replace("application", "document"))
                        : (mineType.mimetype.split("/")[0]);

                let stream;
                let contDownload = 0;

                while (contDownload < 3 && !stream) {
                    try {

                        var account = await common.db_get("sp_accounts", [{ token: instance_id }]);

                        if (account && account.login_type == 1) {
                            if (mineType.id) {
                                //common.special_log(mineType.id, 'mineType.id');

                                const { access_token: bearer } = JSON.parse(account.tmp);
                                const whatsappAPIURL = `https://graph.facebook.com/v19.0/${mineType.id}`;

                                var test = await axios.get(whatsappAPIURL, {
                                    headers: { Authorization: `Bearer ${bearer}` }
                                })

                                if (test.data?.url) {
                                    //common.special_log(test.data.url, "download media result", "-",);
                                    result = await axios.get(test.data?.url, {
                                        headers: { Authorization: `Bearer ${bearer}` }, responseType: 'stream'
                                    })

                                    if (result.data) {
                                        stream = result.data;
                                        //common.special_log(result.data, "download media stream")
                                    } else {
                                        throw new Error('fail to obtain media data')
                                    }


                                } else {
                                    throw new Error('fail to obtain url')
                                }


                            }
                            contDownload++;

                        } else {
                            stream = await downloadContentFromMessage(
                                msg.message.audioMessage ||
                                msg.message.videoMessage ||
                                msg.message.documentMessage ||
                                msg.message.imageMessage ||
                                msg.message.stickerMessage ||
                                msg.message.extendedTextMessage?.contextInfo.quotedMessage.imageMessage ||
                                msg.message?.buttonsMessage?.imageMessage ||
                                msg.message?.templateMessage?.fourRowTemplate?.imageMessage ||
                                msg.message?.templateMessage?.hydratedTemplate?.imageMessage ||
                                msg.message?.templateMessage?.hydratedFourRowTemplate?.imageMessage ||
                                msg.message?.interactiveMessage?.header?.imageMessage,
                                messageType
                            );
                        }
                    } catch (error) {
                        // common.special_log(error, "error download media result", "*", "error");
                        contDownload++;
                        await new Promise(resolve =>
                            setTimeout(resolve, 1000 * contDownload * 2)
                        );
                        console.error(
                            `>>>> error ${contDownload} al descargar el archivo ${msg?.key.id}`
                        );
                    }
                }

                let buffer = Buffer.from([]);

                try {
                    for await (const chunk of stream) {
                        buffer = Buffer.concat([buffer, chunk]);
                    }
                } catch (error) {
                    console.error('error download Media:', error)
                    return null;
                }

                if (!buffer) {
                    return null;
                }

                let filename = msg.message?.documentMessage?.fileName || "";

                if (!filename) {
                    const ext = mineType.mimetype.split("/")[1].split(";")[0];
                    var id = common.makeid(8);
                    filename = `${instance_id}_${id}.${ext}`;
                }

                const media_ = {
                    data: buffer,
                    mimetype: mineType.mimetype,
                    filename
                };

                return media_;
            } catch (error) {
                console.error('error download Media:', error)
                return null;
            }
        },
        verifyMediaMessage: async (msg, body, instance_id, contact, waziper) => {

            if (!msg.message.has_media) {
                //console.error('no has media on msg')
                var media = await Extend.chat.downloadMedia(msg, instance_id);

                if (!media) {
                    throw new Error("ERR_WAPP_DOWNLOAD_MEDIA");
                }

                const ext = media.mimetype.split("/")[1].split(";")[0];

                if (!media.filename) {
                    var id = common.makeid(8);
                    media.filename = `${instance_id}_${id}.${ext}`;
                }

                if (!['js', 'php', 'py', 'json'].includes(`${ext}`.toLowerCase()) && (config['save_files'] ?? true)) {

                    try {
                        await writeFileAsync(
                            join(__dirname, "..", "files", `${media.filename}`),
                            media.data,
                            "base64"
                        );
                    } catch (err) {
                        console.error(err);
                    }

                }
            } else {
                var media = {
                    mimetype: common.ext2mime(msg.message.link),
                    filename: msg.message.link
                }
            }

            const messageData = {
                id: msg.key.id,
                instance_id: instance_id,
                contactId: msg.key.fromMe ? undefined : contact.id,
                body: body ? body : media.filename,
                fromMe: msg.key.fromMe,
                read: msg.key.fromMe,
                mediaUrl: media.filename,
                mediaType: media.mimetype.split("/")[0],
                ack: msg.status,
                remoteJid: msg.key.remoteJid,
                participant: msg.key.participant,
                dataJson: JSON.stringify(msg),
            };
            // common.special_log(messageData, 'message_data', '+')

            return await Extend.chat.CreateMessageService({ messageData, instance_id: instance_id }, contact, waziper);


        },
        processChatMessages: async (waziper, sessions, messages, instance_id, official_api = false) => {
            if (true) {
                try {
                    const messages_filtered = messages.messages
                        .filter(Extend.chat.filterMessages)
                        .map(msg => msg);

                    if (messages_filtered) {
                        messages_filtered.forEach(async (originalMessage) => {
                            var msg_ = JSON.parse(JSON.stringify(originalMessage));

                            var messageExists = await common.db_get('sp_whatsapp_messages', [{ instance_id: instance_id }, { id: msg_.key.id }]);

                            if (!messageExists) {
                                if (Extend.chat.isValidMsg(msg_)) {

                                    const isGroup = msg_.key.remoteJid?.endsWith("@g.us");
                                    if (!isGroup) {
                                        const bodyMessage = Extend.chat.getBodyMessage(msg_);
                                        const msgType = Extend.chat.getTypeMessage(msg_);

                                        let hasMedia = false;
                                        hasMedia = msg_.message?.audioMessage || msg_.message?.imageMessage || msg_.message?.videoMessage || msg_.message?.documentMessage || msg_.message?.stickerMessage || msg_.message?.has_media;



                                        if (msg_.key.fromMe) {

                                            if (!hasMedia && msgType !== "conversation" && msgType !== "extendedTextMessage" && msgType !== "vcard") return;
                                        }

                                        var msgContact = await Extend.chat.getContactMessage(sessions[instance_id], msg_);
                                        const contact = await Extend.chat.verifyContact(waziper, sessions[instance_id], msg_, instance_id, msgContact);

                                        var unreadMessages = 0;
                                        if (msg_.key.fromMe) {
                                            await cacheLayer.set(`contacts:${contact.id}:unreads`, "0");
                                        } else {
                                            const unreads = await cacheLayer.get(`contacts:${contact.id}:unreads`);
                                            unreadMessages = +unreads + 1;
                                            await cacheLayer.set(`contacts:${contact.id}:unreads`, `${unreadMessages}`);
                                        }

                                        var contact_ = await Extend.updateSubscriberMessages(contact, unreadMessages, bodyMessage, common.time());

                                        if (unreadMessages > 0) { }

                                        if (hasMedia) {
                                            var u = await Extend.chat.verifyMediaMessage(msg_, bodyMessage, instance_id, contact, waziper);
                                        } else {
                                            var u = await Extend.chat.verifyMessage(msg_, bodyMessage, instance_id, contact, waziper);
                                        }
                                    }
                                } else {
                                    //console.error('msg invalid', msg);
                                }
                            }
                        });
                    }

                } catch (e) {
                    console.error(e);
                }
            }
        }
    }
}



module.exports = Extend; 
