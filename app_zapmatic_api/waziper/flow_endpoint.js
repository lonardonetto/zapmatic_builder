const fs = require('fs');
const crypto = require('crypto');
const Common = require('./common.js');

class FlowEndpointException extends Error {
	constructor(statusCode, message) {
		super(message);
		this.name = 'FlowEndpointException';
		this.statusCode = statusCode;
	}
}

const FlowEndpoint = {
	async describe(req, res) {
		try {
			const context = await FlowEndpoint.resolveEndpointContext(req.params.endpointIds);
			return res.json({
				status: 'success',
				endpoint_id: context.endpoint.ids,
				endpoint_status: context.endpoint.endpoint_status || 'not_configured',
				account_id: context.endpoint.account_id,
				account_ids: context.endpoint.account_ids,
				flow_id: context.flow ? context.flow.id : null,
				flow_name: context.flow ? context.flow.name : null
			});
		} catch (error) {
			return res.status(404).json({
				status: 'error',
				message: error.message
			});
		}
	},

	async handle(req, res) {
		let context = null;
		let decryptedRequest = null;

		try {
			context = await FlowEndpoint.resolveEndpointContext(req.params.endpointIds);

			const appSecret = await Common.get_setting('meta_app_secret', '') || await Common.get_setting('facebook_login_app_secret', '');
			if (!FlowEndpoint.isRequestSignatureValid(req, appSecret)) {
				await FlowEndpoint.logEndpointEvent(context, {
					eventType: 'flow_endpoint_invalid_signature',
					status: 'error',
					payload: FlowEndpoint.safeJson(req.body),
					error: 'Request signature did not match the configured app secret'
				});
				return res.status(432).send();
			}

			const privatePem = FlowEndpoint.readPrivateKey(context.endpoint.private_key_path);
			decryptedRequest = FlowEndpoint.decryptRequest(req.body, privatePem);

			const enrichedContext = await FlowEndpoint.enrichContextWithFlow(context, decryptedRequest.decryptedBody.flow_token);
			const responsePayload = await FlowEndpoint.getNextScreen(enrichedContext, decryptedRequest.decryptedBody);
			const encryptedResponse = FlowEndpoint.encryptResponse(
				responsePayload,
				decryptedRequest.aesKeyBuffer,
				decryptedRequest.initialVectorBuffer
			);

			await FlowEndpoint.touchEndpoint(enrichedContext.endpoint.id);
			await FlowEndpoint.logEndpointEvent(enrichedContext, {
				eventType: FlowEndpoint.getEventTypeForAction(decryptedRequest.decryptedBody.action),
				status: 'success',
				payload: decryptedRequest.decryptedBody,
				response: responsePayload
			});

			return res.send(encryptedResponse);
		} catch (error) {
			const statusCode = error instanceof FlowEndpointException ? error.statusCode : 500;
			await FlowEndpoint.logEndpointEvent(context, {
				eventType: 'flow_endpoint_error',
				status: 'error',
				payload: decryptedRequest ? decryptedRequest.decryptedBody : FlowEndpoint.safeJson(req.body),
				error: error.message || 'Unhandled Flow endpoint error'
			});

			if (statusCode === 421 || statusCode === 432) {
				return res.status(statusCode).send();
			}

			return res.status(statusCode).send();
		}
	},

	async resolveEndpointContext(endpointIds) {
		const endpointKey = String(endpointIds || '').trim();
		if (!endpointKey) {
			throw new Error('Flow endpoint identifier is missing');
		}

		const endpoint = await Common.db_get('sp_whatsapp_flow_endpoints', [{ ids: endpointKey }]);
		if (!endpoint) {
			throw new Error('Flow endpoint was not found');
		}

		const account = endpoint.account_id
			? await Common.db_get('sp_accounts', [{ id: endpoint.account_id }])
			: null;

		const flow = await FlowEndpoint.findFlowForEndpoint(endpoint, null);

		return { endpoint, account, flow };
	},

	async enrichContextWithFlow(context, flowToken) {
		if (context.flow) {
			return context;
		}

		const flow = await FlowEndpoint.findFlowForEndpoint(context.endpoint, flowToken);
		return {
			...context,
			flow: flow || null
		};
	},

	async findFlowForEndpoint(endpoint, flowToken) {
		const tokenInfo = FlowEndpoint.parseFlowToken(flowToken);
		if (tokenInfo) {
			const flow = await Common.db_get('sp_whatsapp_flows', [
				{ id: tokenInfo.flow_id },
				{ team_id: tokenInfo.team_id }
			]);

			if (flow) {
				const sameEndpoint = !flow.endpoint_id || parseInt(flow.endpoint_id, 10) === parseInt(endpoint.id, 10);
				const sameAccount = parseInt(flow.account_id || 0, 10) === parseInt(endpoint.account_id || 0, 10);
				if (sameEndpoint || sameAccount) {
					return flow;
				}
			}
		}

		return await Common.db_query(
			'SELECT * FROM sp_whatsapp_flows WHERE team_id = ? AND (endpoint_id = ? OR account_id = ?) ORDER BY changed DESC LIMIT 1',
			[
				parseInt(endpoint.team_id || 0, 10),
				parseInt(endpoint.id || 0, 10),
				parseInt(endpoint.account_id || 0, 10)
			]
		);
	},

	parseFlowToken(flowToken) {
		const match = String(flowToken || '').match(/^wa_flow_(\d+)_(\d+)_/);
		if (!match) {
			return null;
		}

		return {
			team_id: parseInt(match[1], 10),
			flow_id: parseInt(match[2], 10)
		};
	},

	readPrivateKey(privateKeyPath) {
		const normalizedPath = String(privateKeyPath || '').trim();
		if (!normalizedPath || !fs.existsSync(normalizedPath)) {
			throw new FlowEndpointException(421, 'Private key file not found for this Flow endpoint');
		}

		return fs.readFileSync(normalizedPath, 'utf8');
	},

	isRequestSignatureValid(req, appSecret) {
		if (!appSecret) {
			return true;
		}

		const signatureHeader = req.get('x-hub-signature-256');
		if (!signatureHeader || !signatureHeader.startsWith('sha256=')) {
			return false;
		}

		const expected = Buffer.from(signatureHeader.replace('sha256=', ''), 'utf8');
		const hmac = crypto.createHmac('sha256', appSecret);
		const digest = Buffer.from(hmac.update(req.rawBody || '').digest('hex'), 'utf8');

		if (expected.length !== digest.length) {
			return false;
		}

		return crypto.timingSafeEqual(expected, digest);
	},

	decryptRequest(body, privatePem) {
		const { encrypted_aes_key, encrypted_flow_data, initial_vector } = body || {};
		if (!encrypted_aes_key || !encrypted_flow_data || !initial_vector) {
			throw new FlowEndpointException(400, 'Flow request payload is incomplete');
		}

		let decryptedAesKey = null;
		try {
			decryptedAesKey = crypto.privateDecrypt(
				{
					key: privatePem,
					padding: crypto.constants.RSA_PKCS1_OAEP_PADDING,
					oaepHash: 'sha256',
				},
				Buffer.from(encrypted_aes_key, 'base64')
			);
		} catch (error) {
			throw new FlowEndpointException(421, 'Failed to decrypt the request. Please verify your private key.');
		}

		const flowDataBuffer = Buffer.from(encrypted_flow_data, 'base64');
		const initialVectorBuffer = Buffer.from(initial_vector, 'base64');
		const encryptedFlowDataBody = flowDataBuffer.subarray(0, -16);
		const encryptedFlowDataTag = flowDataBuffer.subarray(-16);

		const decipher = crypto.createDecipheriv('aes-128-gcm', decryptedAesKey, initialVectorBuffer);
		decipher.setAuthTag(encryptedFlowDataTag);

		const decryptedJSONString = Buffer.concat([
			decipher.update(encryptedFlowDataBody),
			decipher.final()
		]).toString('utf8');

		return {
			decryptedBody: JSON.parse(decryptedJSONString),
			aesKeyBuffer: decryptedAesKey,
			initialVectorBuffer
		};
	},

	encryptResponse(response, aesKeyBuffer, initialVectorBuffer) {
		const flippedIv = [];
		for (const pair of initialVectorBuffer.entries()) {
			flippedIv.push(~pair[1]);
		}

		const cipher = crypto.createCipheriv('aes-128-gcm', aesKeyBuffer, Buffer.from(flippedIv));
		return Buffer.concat([
			cipher.update(JSON.stringify(response), 'utf8'),
			cipher.final(),
			cipher.getAuthTag()
		]).toString('base64');
	},

	async getNextScreen(context, decryptedBody) {
		const { action, data, screen, flow_token } = decryptedBody;
		const normalizedAction = String(action || '').toUpperCase();

		if (normalizedAction === 'PING') {
			return {
				data: {
					status: 'active'
				}
			};
		}

		if (data && data.error) {
			return {
				data: {
					acknowledged: true
				}
			};
		}

		const flow = context.flow;
		const runtime = FlowEndpoint.parseFlowRuntime(flow);
		const previewData = runtime.previewData;
		const firstScreenId = runtime.firstScreenId || screen || 'WELCOME';

		if (normalizedAction === 'INIT') {
			return {
				screen: firstScreenId,
				data: FlowEndpoint.buildScreenData(runtime, flow, data, firstScreenId)
			};
		}

		if (normalizedAction === 'BACK') {
			const previousScreenId = FlowEndpoint.findPreviousScreenId(runtime, screen) || firstScreenId;
			return {
				screen: previousScreenId,
				data: FlowEndpoint.buildScreenData(runtime, flow, data, previousScreenId)
			};
		}

		if (normalizedAction === 'DATA_EXCHANGE') {
			if (FlowEndpoint.shouldCompleteExchange(runtime, screen, data)) {
				return FlowEndpoint.buildSuccessResponse(runtime, flow, flow_token, screen, data);
			}

			const nextScreenId = FlowEndpoint.resolveNextScreenId(runtime, screen, data) || screen || firstScreenId;
			return {
				screen: nextScreenId,
				data: FlowEndpoint.buildScreenData(runtime, flow, data, nextScreenId)
			};
		}

		return {
			data: {
				acknowledged: true
			}
		};
	},

	parsePreviewData(rawPreviewData) {
		return FlowEndpoint.parseJsonObject(rawPreviewData);
	},

	extractFirstScreenId(rawFlowJson) {
		const parsed = FlowEndpoint.parseJsonObject(rawFlowJson);
		return parsed?.screens?.[0]?.id || null;
	},

	parseJsonObject(rawValue) {
		if (!rawValue) {
			return {};
		}

		if (typeof rawValue === 'object' && !Array.isArray(rawValue)) {
			return rawValue;
		}

		try {
			const parsed = JSON.parse(String(rawValue));
			if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
				return parsed;
			}
		} catch (error) {
			return {};
		}

		return {};
	},

	parseFlowRuntime(flow) {
		const flowJson = FlowEndpoint.parseJsonObject(flow ? flow.flow_json : null);
		const builderState = FlowEndpoint.parseJsonObject(flow ? flow.builder_state : null);
		const previewData = FlowEndpoint.parsePreviewData(flow ? flow.preview_data : null);
		const screens = Array.isArray(flowJson.screens) ? flowJson.screens : [];
		const routingModel = flowJson.routing_model && typeof flowJson.routing_model === 'object'
			? flowJson.routing_model
			: {};
		const screensById = {};
		const terminalScreens = {};
		const previousScreenIds = {};
		const navigation = {};

		for (const screen of screens) {
			if (!screen || !screen.id) {
				continue;
			}

			screensById[screen.id] = screen;
			navigation[screen.id] = FlowEndpoint.buildNavigationRuntime(screen);
			if (screen.terminal === true) {
				terminalScreens[screen.id] = true;
			}
		}

		for (const [screenId, nextScreens] of Object.entries(routingModel)) {
			if (!Array.isArray(nextScreens)) {
				continue;
			}

			for (const nextScreenId of nextScreens) {
				if (!nextScreenId || previousScreenIds[nextScreenId]) {
					continue;
				}

				previousScreenIds[nextScreenId] = screenId;
			}
		}

		return {
			flowJson,
			builderState,
			previewData,
			screens,
			screensById,
			navigation,
			routingModel,
			terminalScreens,
			previousScreenIds,
			firstScreenId: screens[0]?.id || null,
			guidedMenu: FlowEndpoint.buildGuidedMenuRuntime(builderState, navigation),
		};
	},

	buildNavigationRuntime(screen) {
		const runtime = {
			byId: {},
			byTitle: {},
		};

		FlowEndpoint.walkLayoutNode(screen?.layout || null, (node) => {
			if (!node || node.type !== 'NavigationList' || !Array.isArray(node['list-items'])) {
				return;
			}

			for (const item of node['list-items']) {
				const itemId = String(item?.id || '').trim();
				const title = String(item?.['main-content']?.title || '').trim();
				const description = String(item?.['main-content']?.description || '').trim();
				const metadata = String(item?.['main-content']?.metadata || '').trim();
				const targetScreenId = String(item?.['on-click-action']?.next?.name || '').trim();
				if (!itemId && !title) {
					continue;
				}

				const itemRuntime = {
					id: itemId,
					title,
					description,
					metadata,
					targetScreenId,
				};

				if (itemId) {
					runtime.byId[itemId] = itemRuntime;
				}

				if (title) {
					runtime.byTitle[FlowEndpoint.normalizeLookup(title)] = itemRuntime;
				}
			}
		});

		return runtime;
	},

	buildGuidedMenuRuntime(builderState, navigation) {
		const guidedMenu = builderState?.guided_menu;
		const menuRuntime = {
			menuScreenId: String(guidedMenu?.menu?.screen_id || '').trim(),
			categoriesById: {},
			categoriesByTitle: {},
			optionsById: {},
			optionsByTitle: {},
			terminalByScreenId: {},
		};

		const sections = Array.isArray(guidedMenu?.sections) ? guidedMenu.sections : [];
		for (const section of sections) {
			const categoryId = String(section?.id || '').trim();
			const categoryTitle = String(section?.title || '').trim();
			const categoryScreenId = navigation?.[menuRuntime.menuScreenId]?.byId?.[categoryId]?.targetScreenId || '';

			const categoryRuntime = {
				id: categoryId,
				title: categoryTitle,
				screenId: categoryScreenId,
				description: String(section?.description || '').trim(),
			};

			if (categoryId) {
				menuRuntime.categoriesById[categoryId] = categoryRuntime;
			}

			if (categoryTitle) {
				menuRuntime.categoriesByTitle[FlowEndpoint.normalizeLookup(categoryTitle)] = categoryRuntime;
			}

			const items = Array.isArray(section?.items) ? section.items : [];
			for (const item of items) {
				const optionId = String(item?.id || '').trim();
				const optionTitle = String(item?.title || '').trim();
				const optionScreenId = categoryScreenId && navigation?.[categoryScreenId]?.byId?.[optionId]?.targetScreenId
					? navigation[categoryScreenId].byId[optionId].targetScreenId
					: '';

				const optionRuntime = {
					id: optionId,
					title: optionTitle,
					screenId: optionScreenId,
					description: String(item?.description || '').trim(),
					categoryId,
					categoryTitle,
					categoryScreenId,
				};

				if (optionId) {
					menuRuntime.optionsById[optionId] = optionRuntime;
				}

				if (optionTitle) {
					menuRuntime.optionsByTitle[FlowEndpoint.normalizeLookup(optionTitle)] = optionRuntime;
				}

				if (optionScreenId) {
					menuRuntime.terminalByScreenId[optionScreenId] = optionRuntime;
				}
			}
		}

		return menuRuntime;
	},

	walkLayoutNode(node, callback) {
		if (!node || typeof node !== 'object') {
			return;
		}

		callback(node);

		for (const key of Object.keys(node)) {
			const value = node[key];
			if (Array.isArray(value)) {
				for (const child of value) {
					FlowEndpoint.walkLayoutNode(child, callback);
				}
			} else if (value && typeof value === 'object') {
				FlowEndpoint.walkLayoutNode(value, callback);
			}
		}
	},

	resolveNextScreenId(runtime, currentScreenId, data) {
		const screenId = String(currentScreenId || '').trim();
		if (!screenId) {
			return runtime.firstScreenId || null;
		}

		const currentNavigation = runtime.navigation?.[screenId] || { byId: {}, byTitle: {} };
		const candidates = FlowEndpoint.extractStringCandidates(data);

		for (const candidate of candidates) {
			if (currentNavigation.byId[candidate.raw]?.targetScreenId) {
				return currentNavigation.byId[candidate.raw].targetScreenId;
			}

			if (currentNavigation.byTitle[candidate.normalized]?.targetScreenId) {
				return currentNavigation.byTitle[candidate.normalized].targetScreenId;
			}
		}

		return null;
	},

	extractStringCandidates(value, candidates = []) {
		if (value === null || value === undefined) {
			return candidates;
		}

		if (Array.isArray(value)) {
			for (const item of value) {
				FlowEndpoint.extractStringCandidates(item, candidates);
			}
			return candidates;
		}

		if (typeof value === 'object') {
			for (const [key, item] of Object.entries(value)) {
				if (key) {
					candidates.push({
						raw: String(key).trim(),
						normalized: FlowEndpoint.normalizeLookup(key),
					});
				}
				FlowEndpoint.extractStringCandidates(item, candidates);
			}
			return candidates;
		}

		const raw = String(value).trim();
		if (raw !== '') {
			candidates.push({
				raw,
				normalized: FlowEndpoint.normalizeLookup(raw),
			});
		}

		return candidates;
	},

	normalizeLookup(value) {
		return String(value || '').trim().toLowerCase();
	},

	buildScreenData(runtime, flow, data, screenId) {
		const responseData = {
			...runtime.previewData,
		};
		const selectionContext = FlowEndpoint.extractSelectionContext(runtime, screenId, data);
		const flowReference = FlowEndpoint.buildFlowReferenceParams(flow);

		return {
			...responseData,
			...flowReference,
			...selectionContext,
		};
	},

	extractSelectionContext(runtime, screenId, data) {
		const context = {};
		const candidates = FlowEndpoint.extractStringCandidates(data);
		const guidedMenu = runtime.guidedMenu || {};
		const optionFromScreen = guidedMenu.terminalByScreenId?.[screenId] || null;

		if (optionFromScreen) {
			context.category_id = optionFromScreen.categoryId;
			context.category_title = optionFromScreen.categoryTitle;
			context.option_id = optionFromScreen.id;
			context.option_title = optionFromScreen.title;
		}

		for (const candidate of candidates) {
			const categoryById = guidedMenu.categoriesById?.[candidate.raw];
			const categoryByTitle = guidedMenu.categoriesByTitle?.[candidate.normalized];
			const optionById = guidedMenu.optionsById?.[candidate.raw];
			const optionByTitle = guidedMenu.optionsByTitle?.[candidate.normalized];

			const category = categoryById || categoryByTitle || null;
			const option = optionById || optionByTitle || null;

			if (category) {
				context.category_id = category.id;
				context.category_title = category.title;
			}

			if (option) {
				context.category_id = option.categoryId;
				context.category_title = option.categoryTitle;
				context.option_id = option.id;
				context.option_title = option.title;
			}
		}

		return context;
	},

	shouldCompleteExchange(runtime, screenId, data) {
		if (!data || typeof data !== 'object') {
			return false;
		}

		if (data.complete === true || data.action === 'complete' || data.trigger === 'complete') {
			return true;
		}

		const currentScreenId = String(screenId || '').trim();
		if (!currentScreenId || !runtime.terminalScreens?.[currentScreenId]) {
			return false;
		}

		return Object.keys(FlowEndpoint.sanitizeCompletionParams(data)).length > 0;
	},

	buildSuccessResponse(runtime, flow, flowToken, screenId, data) {
		const params = {
			flow_token: String(flowToken || ''),
			...FlowEndpoint.buildFlowReferenceParams(flow),
			...FlowEndpoint.extractSelectionContext(runtime, screenId, data),
			...FlowEndpoint.sanitizeCompletionParams(data),
		};

		return {
			screen: 'SUCCESS',
			data: {
				extension_message_response: {
					params,
				},
			},
		};
	},

	buildFlowReferenceParams(flow) {
		return {
			flow_id: flow?.id ? String(flow.id) : '',
			flow_name: String(flow?.name || ''),
			flow_slug: String(flow?.slug || ''),
			meta_flow_id: String(flow?.meta_flow_id || ''),
		};
	},

	sanitizeCompletionParams(data) {
		if (!data || typeof data !== 'object' || Array.isArray(data)) {
			return {};
		}

		const params = {};
		const ignoredKeys = new Set(['complete', 'trigger', 'action', 'error']);

		for (const [key, value] of Object.entries(data)) {
			if (!key || ignoredKeys.has(key)) {
				continue;
			}

			if (value === null || value === undefined) {
				continue;
			}

			if (typeof value === 'string') {
				params[key] = value;
				continue;
			}

			if (typeof value === 'number' || typeof value === 'boolean') {
				params[key] = value;
				continue;
			}

			try {
				params[key] = JSON.stringify(value);
			} catch (error) {
				params[key] = String(value);
			}
		}

		return params;
	},

	findPreviousScreenId(runtime, screenId) {
		const currentScreenId = String(screenId || '').trim();
		return runtime.previousScreenIds?.[currentScreenId] || null;
	},

	getEventTypeForAction(action) {
		switch (String(action || '').toUpperCase()) {
			case 'PING':
				return 'flow_endpoint_ping';

			case 'INIT':
				return 'flow_endpoint_init';

			case 'BACK':
				return 'flow_endpoint_back';

			case 'DATA_EXCHANGE':
				return 'flow_endpoint_data_exchange';

			default:
				return 'flow_endpoint_request';
		}
	},

	async touchEndpoint(endpointId) {
		if (!endpointId) {
			return;
		}

		const now = Math.floor(Date.now() / 1000);
		await Common.db_update('sp_whatsapp_flow_endpoints', [
			{ last_sync_at: now, changed: now },
			{ id: endpointId }
		]);
	},

	async logEndpointEvent(context, details) {
		try {
			if (!context || !context.endpoint) {
				return;
			}

			await Common.db_insert('sp_whatsapp_flow_events', {
				team_id: context.endpoint.team_id || null,
				flow_id: context.flow ? context.flow.id : null,
				endpoint_id: context.endpoint.id || null,
				account_id: context.endpoint.account_id || null,
				account_ids: context.endpoint.account_ids || null,
				instance_id: context.account ? context.account.token : null,
				event_type: details.eventType || 'flow_endpoint_event',
				direction: 'inbound',
				contact_id: null,
				chat_id: null,
				flow_token: details.payload?.flow_token || '',
				message_id: null,
				status: details.status || 'unknown',
				payload: JSON.stringify(FlowEndpoint.safeJson(details.payload), null, 0),
				response: details.response ? JSON.stringify(FlowEndpoint.safeJson(details.response), null, 0) : null,
				error_message: details.error || '',
				created: Math.floor(Date.now() / 1000)
			});
		} catch (error) {
			console.error('Failed to log Flow endpoint event', error);
		}
	},

	safeJson(value) {
		if (value === undefined) {
			return null;
		}

		try {
			return JSON.parse(JSON.stringify(value));
		} catch (error) {
			return {
				raw: String(value)
			};
		}
	}
};

module.exports = FlowEndpoint;
