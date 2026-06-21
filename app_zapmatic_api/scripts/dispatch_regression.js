#!/usr/bin/env node

process.env.WAZIPER_DISABLE_CRON = '1';

const assert = require('assert');
const axios = require('axios');
const moment = require('moment-timezone');
const Common = require('../waziper/common.js');
const Extend = require('../waziper/extend.js');
const WAZIPER = require('../waziper/waziper.js');

const results = [];

const formatError = (error) => error?.stack || error?.message || String(error);

const runTest = async (name, fn) => {
	try {
		const detail = await fn();
		results.push({ name, status: 'ok', detail });
		console.log(`[OK] ${name}: ${detail}`);
	} catch (error) {
		results.push({ name, status: 'fail', detail: formatError(error) });
		console.error(`[FAIL] ${name}`);
		console.error(formatError(error));
	}
};

const withPatched = async (patches, fn) => {
	const restores = [];

	try {
		for (const [target, key, replacement] of patches) {
			const original = target[key];
			target[key] = replacement;
			restores.push(() => {
				target[key] = original;
			});
		}

		return await fn();
	} finally {
		while (restores.length > 0) {
			const restore = restores.pop();
			restore();
		}
	}
};

const makeResponse = () => ({
	payload: null,
	json(data) {
		this.payload = data;
		return data;
	}
});

const wait = async (ms = 25) => new Promise((resolve) => setTimeout(resolve, ms));

const resetSession = (instanceId) => {
	delete WAZIPER.sessions[instanceId];
};

const main = async () => {
	WAZIPER.io.emit = () => {};
	WAZIPER.webhook = () => {};

	await runTest('autoresponder_baileys', async () => {
		const captures = { auto: null };
		const instanceId = 'test-baileys-ar';

		await withPatched([
			[Common, 'db_get', async (table) => {
				if (table === 'sp_whatsapp_autoresponder') {
					return {
						id: 11,
						team_id: 9,
						instance_id: instanceId,
						status: 1,
						send_to: 1,
						except: null,
						caption: 'Olá %wa_nome%',
						delay: 0,
						sent: 0,
						failed: 0,
					};
				}

				return false;
			}],
			[Common, 'db_query', async () => false],
			[Common, 'db_insert', async () => true],
			[Common, 'db_update', async () => true],
			[Extend, 'sendPresence', async () => true],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, item, params, message, content, callback) => {
				captures.auto = { instance_id, chat_id, phone_number, type, caption: item.caption };
				if (typeof callback === 'function') {
					await callback({ status: 1, type, phone_number, stats: true });
				}
				return true;
			}],
		], async () => {
			WAZIPER.sessions[instanceId] = {
				sendPresenceUpdate: async () => true,
			};

			await WAZIPER.autoresponder(instanceId, 'user', {
				key: { remoteJid: '5511999999999@s.whatsapp.net', id: 'msg-1' },
				pushName: 'João',
				message: { conversation: 'oi' },
			});
		});

		resetSession(instanceId);
		assert.ok(captures.auto, 'auto_send should be called');
		assert.strictEqual(captures.auto.type, 'autoresponder');
		assert.strictEqual(captures.auto.chat_id, '5511999999999@s.whatsapp.net');
		assert.strictEqual(captures.auto.phone_number, '5511999999999');
		return 'respondeu com identidade canônica Baileys';
	});

	await runTest('autoresponder_cloud', async () => {
		const captures = { auto: null, lookupParams: null };
		const instanceId = 'test-cloud-ar';

		await withPatched([
			[Common, 'db_get', async (table) => {
				if (table === 'sp_whatsapp_autoresponder') {
					return {
						id: 12,
						team_id: 9,
						instance_id: instanceId,
						status: 1,
						send_to: 1,
						except: null,
						caption: 'Olá %wa_nome%',
						delay: 0,
						sent: 0,
						failed: 0,
					};
				}

				return false;
			}],
			[Common, 'db_query', async (sql, params) => {
				if (String(sql).includes('sp_whatsapp_ar_responses')) {
					captures.lookupParams = params;
				}
				return false;
			}],
			[Common, 'db_insert', async () => true],
			[Common, 'db_update', async () => true],
			[Extend, 'sendPresence', async () => true],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, item, params, message, content, callback) => {
				captures.auto = { instance_id, chat_id, phone_number, type, caption: item.caption };
				if (typeof callback === 'function') {
					await callback({ status: 1, type, phone_number, stats: true });
				}
				return true;
			}],
		], async () => {
			WAZIPER.sessions[instanceId] = {
				sendPresenceUpdate: async () => true,
			};

			await WAZIPER.autoresponder(instanceId, 'user', {
				official_api: true,
				key: { remoteJid: 'BR.123456@s.whatsapp.net', id: 'wamid.1' },
				_wa_id: '5511999999999',
				_bsuid: 'BR.123456',
				pushName: 'Cloud User',
				text: { body: '1' },
				message: { conversation: '1' },
			});
		});

		resetSession(instanceId);
		assert.ok(captures.auto, 'auto_send should be called');
		assert.strictEqual(captures.auto.type, 'autoresponder');
		assert.strictEqual(captures.auto.phone_number, '5511999999999');
		assert.ok(Array.isArray(captures.lookupParams), 'legacy lookup aliases should be evaluated');
		assert.ok(captures.lookupParams.includes('5511999999999'), 'canonical number should be part of alias lookup');
		assert.ok(captures.lookupParams.includes('BR.123456'), 'legacy BSUID alias should be part of lookup');
		return 'respondeu com identidade canônica Cloud e lookup por aliases';
	});

	await runTest('chatbot_baileys', async () => {
		const captures = { itemId: null };
		const instanceId = 'test-baileys-bot';
		const originalSetTimeout = global.setTimeout;

		await withPatched([
			[Extend, 'getSubscriber', async () => ({ enabled_chatbot: '1', chatid: '5511999999999@s.whatsapp.net' })],
			[Extend, 'updateSubscriber', async () => true],
			[Extend, 'nextBot', async () => true],
			[Common, 'db_fetch', async (table) => {
				if (table === 'sp_whatsapp_chatbot') {
					return [
						{ id: 31, team_id: 9, send_to: 1, run: 1, status: 1, type_search: 1, keywords: '1', save_data: 0 },
						{ id: 30, team_id: 9, send_to: 1, run: 1, status: 1, type_search: 2, keywords: '1', save_data: 0 },
					];
				}
				return false;
			}],
			[Common, 'db_get', async () => false],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, item) => {
				captures.itemId = item.id;
				captures.type = type;
				return true;
			}],
		], async () => {
			global.setTimeout = (fn) => {
				fn();
				return 0;
			};

			await WAZIPER.chatbot(instanceId, 'user', {
				key: { remoteJid: '5511999999999@s.whatsapp.net', id: 'msg-2' },
				pushName: 'João',
				message: { conversation: '1' },
			});
		});

		global.setTimeout = originalSetTimeout;
		assert.strictEqual(captures.type, 'chatbot');
		assert.strictEqual(captures.itemId, 30);
		return 'selecionou a regra exata prioritária no Baileys';
	});

	await runTest('chatbot_cloud', async () => {
		const captures = { count: 0, type: null };
		const instanceId = 'test-cloud-bot';
		const originalSetTimeout = global.setTimeout;

		await withPatched([
			[Extend, 'getSubscriber', async () => ({ enabled_chatbot: '1', chatid: '5511999999999@s.whatsapp.net' })],
			[Extend, 'updateSubscriber', async () => true],
			[Extend, 'nextBot', async () => true],
			[Common, 'db_fetch', async (table) => {
				if (table === 'sp_whatsapp_chatbot') {
					return [
						{ id: 40, team_id: 9, send_to: 1, run: 1, status: 1, type_search: 2, keywords: '1', save_data: 0 },
					];
				}
				return false;
			}],
			[Common, 'db_get', async () => false],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type) => {
				captures.count += 1;
				captures.type = type;
				captures.chat_id = chat_id;
				captures.phone_number = phone_number;
				return true;
			}],
		], async () => {
			global.setTimeout = (fn) => {
				fn();
				return 0;
			};

			await WAZIPER.chatbot(instanceId, 'user', {
				official_api: true,
				key: { remoteJid: 'BR.999001@s.whatsapp.net', id: 'wamid.2' },
				_wa_id: '5511988887777',
				_bsuid: 'BR.999001',
				pushName: 'Cloud User',
				message: { conversation: '1' },
			});
		});

		global.setTimeout = originalSetTimeout;
		assert.strictEqual(captures.count, 1);
		assert.strictEqual(captures.type, 'chatbot');
		return 'executou a regra do chatbot para entrada Cloud';
	});

	await runTest('single_message_baileys_wrapper', async () => {
		const captures = { call: null };
		const res = makeResponse();

		await withPatched([
			[Common, 'db_get', async (table) => {
				if (table === 'sp_team') {
					return { id: 9 };
				}
				return false;
			}],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, item, params, message, content, callback) => {
				captures.call = { instance_id, chat_id, phone_number, type };
				await callback({ status: 1, message: { id: 'msg-direct-b' } });
				return true;
			}],
		], async () => {
			await WAZIPER.single_send_message('baileys-direct', 'team-token', {
				query: { type: '1' },
				body: {
					chat_id: '5511990000111',
					caption: 'Teste',
					media_url: '',
					filename: '',
				}
			}, res);
		});

		assert.ok(captures.call);
		assert.strictEqual(captures.call.type, 'direct');
		assert.strictEqual(captures.call.chat_id, '5511990000111@s.whatsapp.net');
		assert.strictEqual(res.payload?.status, 'success');
		return 'normalizou o destino e respondeu sucesso no wrapper Baileys';
	});

	await runTest('single_message_cloud_send_path', async () => {
		const captures = { payload: null, callback: null, stats: [] };

		await withPatched([
			[Common, 'db_get', async (table, data) => {
				if (table === 'sp_accounts') {
					return {
						id: 69,
						team_id: 9,
						login_type: 1,
						username: '546422435230521',
						data: JSON.stringify({
							token: 'cloud-access-token',
							phone_number_id: '546422435230521'
						})
					};
				}
				return false;
			}],
			[Common.db_connect, 'query', (sql, data, cb) => {
				if (typeof data === 'function') {
					cb = data;
				}
				if (typeof cb === 'function') {
					cb(null, { insertId: 1, affectedRows: 1 });
				}
			}],
			[axios, 'post', async (url, body) => {
				captures.payload = { url, body };
				return {
					data: {
						messages: [{ id: 'wamid.cloud.1' }],
						contacts: [{ profile: { name: 'Cloud' } }]
					}
				};
			}],
			[Extend, 'process_official_sent_message', async () => ({ id: 'wamid.cloud.1' })],
			[WAZIPER, 'stats', async (instance_id, type, item, status) => {
				captures.stats.push({ instance_id, type, status });
			}],
			[Extend.chat, 'processChatMessages', () => true],
		], async () => {
			await new Promise((resolve, reject) => {
				WAZIPER.process_send_message(
					'BR.123456@s.whatsapp.net',
					{ text: 'Olá Cloud' },
					'direct',
					'cloud-direct',
					'5511991234567',
					{ id: 88, team_id: 9, type: 1, caption: 'Olá Cloud' },
					(result) => {
						captures.callback = result;
						resolve();
					}
				);
			});
		});

		assert.ok(captures.payload, 'axios.post should be called');
		assert.strictEqual(captures.payload.body.to, '5511991234567');
		assert.strictEqual(captures.callback?.wa_message_id, 'wamid.cloud.1');
		assert.strictEqual(captures.stats[0]?.status, 1);
		return 'resolveu destino numérico e propagou wa_message_id no envio Cloud';
	});

	await runTest('bulk_baileys_legacy', async () => {
		const updates = [];
		const instanceId = 'legacy-bulk-baileys';

		await withPatched([
			[Common, 'db_query', async (sql) => {
				if (String(sql).includes('FROM sp_whatsapp_schedules')) {
					return [{
						id: 501,
						team_id: 9,
						status: 1,
						run: 0,
						time_post: 1000,
						accounts: JSON.stringify([77]),
						contact_id: 333,
						min_delay: 1,
						max_delay: 1,
						next_account: 0,
						result: '',
						type: 1,
						timezone: '',
						cloud_parallel_enabled: 0,
					}];
				}

				return false;
			}],
			[Common, 'db_update', async (table, data) => {
				updates.push({ table, data });
				return true;
			}],
			[Common, 'get_phone_number', async () => ({ id: 700, phone: '5511992222333', params: {}, team_id: 9 })],
			[Common, 'check_especials', async (phone) => phone],
			[Common, 'get_accounts', async () => ({ count: 1 })],
			[Common, 'db_get', async (table) => {
				if (table === 'sp_accounts') {
					return { id: 77, login_type: 2, token: instanceId, team_id: 9, name: 'Baileys' };
				}
				return false;
			}],
			[Common, 'get_total_phone_number', async () => ({ count: 1 })],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, item, phone_number_item, message, content, callback) => {
				await callback({ status: 1, type: 'bulk', stats: true, phone_number, phone_number_id: phone_number_item.id });
				return true;
			}],
		], async () => {
			WAZIPER.sessions[instanceId] = { user: { id: instanceId } };
			await WAZIPER.bulk_messaging();
			await wait();
		});

		resetSession(instanceId);

		const resultUpdate = updates.find((entry) => entry.table === 'sp_whatsapp_schedules' && entry.data?.[0]?.result);
		assert.ok(resultUpdate, 'legacy bulk should update schedule result');
		assert.strictEqual(resultUpdate.data[0].sent, 1);
		assert.strictEqual(resultUpdate.data[0].failed, 0);
		return 'manteve o fluxo legado do bulk Baileys e consolidou o resultado';
	});

	await runTest('bulk_schedule_weekday_reschedule', async () => {
		const item = {
			id: 701,
			team_id: 9,
			timezone: 'America/Sao_Paulo',
			schedule_weekdays: JSON.stringify(['1', '2', '3', '4', '5']),
			skip_team_holidays: 0,
			schedule_time: '',
		};
		const now = moment.tz('2026-06-06 10:15:00', item.timezone).unix();
		const expected = moment.tz('2026-06-08 00:00:00', item.timezone).unix();

		const result = await WAZIPER.resolveNextAllowedBulkRun(item, now);

		assert.strictEqual(result.allowed, false);
		assert.strictEqual(result.reason, 'weekday');
		assert.strictEqual(result.nextTime, expected);
		return 'pulou do sábado para segunda-feira 00:00 no fuso da campanha';
	});

	await runTest('bulk_schedule_hour_reschedule', async () => {
		const item = {
			id: 702,
			team_id: 9,
			timezone: 'America/Sao_Paulo',
			schedule_weekdays: JSON.stringify(['1', '2', '3', '4', '5']),
			skip_team_holidays: 0,
			schedule_time: JSON.stringify(['9', '10', '11']),
		};
		const now = moment.tz('2026-06-08 12:30:00', item.timezone).unix();
		const expected = moment.tz('2026-06-09 09:00:00', item.timezone).unix();

		const result = await WAZIPER.resolveNextAllowedBulkRun(item, now);

		assert.strictEqual(result.allowed, false);
		assert.strictEqual(result.reason, 'hour');
		assert.strictEqual(result.nextTime, expected);
		return 'pulou da janela encerrada para a primeira hora válida do próximo dia útil';
	});

	await runTest('bulk_schedule_holiday_skip', async () => {
		const item = {
			id: 703,
			team_id: 9,
			timezone: 'America/Sao_Paulo',
			schedule_weekdays: JSON.stringify(['1', '2', '3', '4', '5']),
			skip_team_holidays: 1,
			schedule_time: '',
		};
		const now = moment.tz('2026-06-08 08:00:00', item.timezone).unix();
		const expected = moment.tz('2026-06-09 00:00:00', item.timezone).unix();

		const result = await withPatched([
			[WAZIPER, 'get_team_holiday_dates', async () => new Set(['2026-06-08'])],
		], async () => {
			return await WAZIPER.resolveNextAllowedBulkRun(item, now);
		});

		assert.strictEqual(result.allowed, false);
		assert.strictEqual(result.reason, 'holiday');
		assert.strictEqual(result.nextTime, expected);
		return 'ignorou o feriado da equipe e reagendou para o próximo dia útil';
	});

	await runTest('bulk_schedule_gate_reschedules_before_send', async () => {
		const updates = [];
		const autoCalls = [];

		await withPatched([
			[Common, 'db_query', async (sql) => {
				if (String(sql).includes('FROM sp_whatsapp_schedules')) {
					return [{
						id: 704,
						team_id: 9,
						status: 1,
						run: 0,
						time_post: 1000,
						accounts: JSON.stringify([77]),
						contact_id: 333,
						min_delay: 1,
						max_delay: 1,
						next_account: 0,
						result: '',
						type: 1,
						timezone: 'America/Sao_Paulo',
						cloud_parallel_enabled: 0,
					}];
				}

				return false;
			}],
			[Common, 'db_update', async (table, data) => {
				updates.push({ table, data });
				return true;
			}],
			[WAZIPER, 'resolveNextAllowedBulkRun', async () => ({
				allowed: false,
				nextTime: 3600,
				reason: 'weekday',
				localDate: '2026-06-06',
				localHour: 10,
				timezone: 'America/Sao_Paulo',
				scheduleHours: [],
				scheduleWeekdays: [1, 2, 3, 4, 5],
				skipTeamHolidays: false,
			})],
			[WAZIPER, 'auto_send', async () => {
				autoCalls.push(true);
				return true;
			}],
		], async () => {
			await WAZIPER.bulk_messaging();
			await wait();
		});

		assert.strictEqual(autoCalls.length, 0, 'bulk should not send before honoring the schedule gate');
		assert.ok(updates.some((entry) => entry.table === 'sp_whatsapp_schedules' && entry.data?.[0]?.time_post === 3600), 'bulk should reschedule the next valid run');
		return 'bloqueou o envio e remarcou a campanha antes de tocar no motor de disparo';
	});

	await runTest('bulk_cloud_parallel', async () => {
		const autoCalls = [];
		const dbQueries = [];
		let progress = null;
		const item = {
			id: 601,
			team_id: 9,
			contact_id: 444,
			min_delay: 10,
			max_delay: 30,
			cloud_parallel_level: 20,
		};

		await withPatched([
			[WAZIPER, 'build_cloud_parallel_runtime', async () => ({
				all_cloud: true,
				aggregate_cap: 100,
				effective_level: 2,
				accounts: [
					{ id: 1, instance_id: 'cloud-a' },
					{ id: 2, instance_id: 'cloud-b' },
				]
			})],
			[WAZIPER, 'seed_cloud_parallel_dispatches', async () => 2],
			[WAZIPER, 'reset_stale_cloud_parallel_dispatches', async () => true],
			[WAZIPER, 'get_cloud_parallel_due_dispatches', async () => ([
				{ id: 1, contact_phone_id: 9001, raw_phone: '5511991111111', normalized_phone: '5511991111111', attempt_count: 0 },
				{ id: 2, contact_phone_id: 9002, raw_phone: '5511992222222', normalized_phone: '5511992222222', attempt_count: 0 },
			])],
			[WAZIPER, 'get_cloud_parallel_wave_number', async () => 1],
			[Common, 'get_phone_number_by_id', async (id) => ({ id, params: {} })],
			[Common.db_connect, 'query', (sql, params, cb) => {
				dbQueries.push(String(sql));
				if (typeof params === 'function') {
					cb = params;
				}
				if (typeof cb === 'function') {
					cb(null, []);
				}
			}],
			[WAZIPER, 'process_cloud_parallel_dispatch_result', async (schedule, dispatch, accountContext, result) => {
				return dispatch.id === 1
					? { paused: false, status: 'sent' }
					: { paused: false, status: 'retry_wait' };
			}],
			[WAZIPER, 'get_cloud_parallel_status_summary', async () => ({
				sent: 1,
				failed: 0,
				queued: 0,
				processing: 0,
				retry_wait: 1,
				next_retry_at: 1120,
				total: 2,
			})],
			[WAZIPER, 'update_cloud_parallel_schedule_progress', async (schedule, nextTime, complete) => {
				progress = { nextTime, complete };
				return true;
			}],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, schedule, params, message, content, callback) => {
				autoCalls.push({ instance_id, chat_id, phone_number, type });
				if (instance_id === 'cloud-a') {
					await callback({ status: 1, wa_message_id: 'wamid.wave.1' });
				} else {
					await callback({ status: 0, error_code: 131056, error_message: 'retry later' });
				}
				return true;
			}],
			[Common, 'time', () => 1000],
			[Common, 'randomIntFromInterval', () => 30],
		], async () => {
			await WAZIPER.bulk_messaging_cloud_parallel(item, 1000);
		});

		assert.deepStrictEqual(autoCalls.map((entry) => entry.instance_id), ['cloud-a', 'cloud-b']);
		assert.ok(dbQueries.some((sql) => sql.includes('sp_whatsapp_cloud_dispatches')), 'cloud dispatch table should be touched');
		assert.deepStrictEqual(progress, { nextTime: 1120, complete: false });
		return 'executou onda paralela Cloud com round-robin e reagendamento por retry';
	});

	await runTest('bulk_cloud_parallel_80_simulated', async () => {
		const autoCalls = [];
		const dbQueries = [];
		let progress = null;
		const item = {
			id: 602,
			team_id: 9,
			contact_id: 445,
			min_delay: 10,
			max_delay: 30,
			cloud_parallel_level: 80,
		};
		const dueDispatches = Array.from({ length: 80 }, (_, index) => {
			const suffix = String(index + 1).padStart(2, '0');
			return {
				id: index + 1,
				contact_phone_id: 9100 + index,
				raw_phone: `5511999900${suffix}`,
				normalized_phone: `5511999900${suffix}`,
				attempt_count: 0,
			};
		});

		await withPatched([
			[WAZIPER, 'build_cloud_parallel_runtime', async () => ({
				all_cloud: true,
				aggregate_cap: 100,
				effective_level: 80,
				accounts: [
					{ id: 1, instance_id: 'cloud-a' },
					{ id: 2, instance_id: 'cloud-b' },
				]
			})],
			[WAZIPER, 'seed_cloud_parallel_dispatches', async () => 80],
			[WAZIPER, 'reset_stale_cloud_parallel_dispatches', async () => true],
			[WAZIPER, 'get_cloud_parallel_due_dispatches', async () => dueDispatches],
			[WAZIPER, 'get_cloud_parallel_wave_number', async () => 1],
			[Common, 'get_phone_number_by_id', async (id) => ({ id, params: {} })],
			[Common.db_connect, 'query', (sql, params, cb) => {
				dbQueries.push(String(sql));
				if (typeof params === 'function') {
					cb = params;
				}
				if (typeof cb === 'function') {
					cb(null, []);
				}
			}],
			[WAZIPER, 'process_cloud_parallel_dispatch_result', async () => ({ paused: false, status: 'sent' })],
			[WAZIPER, 'get_cloud_parallel_status_summary', async () => ({
				sent: 80,
				failed: 0,
				queued: 0,
				processing: 0,
				retry_wait: 0,
				next_retry_at: 0,
				total: 80,
			})],
			[WAZIPER, 'update_cloud_parallel_schedule_progress', async (schedule, nextTime, complete) => {
				progress = { nextTime, complete };
				return true;
			}],
			[WAZIPER, 'auto_send', async (instance_id, chat_id, phone_number, type, schedule, params, message, content, callback) => {
				autoCalls.push({ instance_id, chat_id, phone_number, type });
				await callback({ status: 1, wa_message_id: `wamid.${phone_number}` });
				return true;
			}],
			[Common, 'time', () => 1000],
			[Common, 'randomIntFromInterval', () => 30],
		], async () => {
			await WAZIPER.bulk_messaging_cloud_parallel(item, 1000);
		});

		const cloudACount = autoCalls.filter((entry) => entry.instance_id === 'cloud-a').length;
		const cloudBCount = autoCalls.filter((entry) => entry.instance_id === 'cloud-b').length;

		assert.strictEqual(autoCalls.length, 80, 'should dispatch exactly 80 cloud sends in one wave');
		assert.strictEqual(cloudACount, 40, 'round-robin should distribute 40 sends to cloud-a');
		assert.strictEqual(cloudBCount, 40, 'round-robin should distribute 40 sends to cloud-b');
		assert.ok(dbQueries.some((sql) => sql.includes('sp_whatsapp_cloud_dispatches')), 'cloud dispatch table should be touched for the 80-send wave');
		assert.deepStrictEqual(progress, { nextTime: 1000, complete: true });
		return 'simulou 80 envios Cloud na mesma onda com distribuicao 40/40 e conclusao da campanha';
	});

	const failed = results.filter((result) => result.status !== 'ok');
	const passed = results.length - failed.length;

	console.log('\nResumo dos testes internos');
	console.log(`- Total: ${results.length}`);
	console.log(`- Sucesso: ${passed}`);
	console.log(`- Falha: ${failed.length}`);

	if (failed.length > 0) {
		process.exitCode = 1;
	}

	process.exit(process.exitCode || 0);
};

main().catch((error) => {
	console.error('dispatch regression failed');
	console.error(formatError(error));
	process.exit(1);
});
