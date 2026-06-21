const config = require("./config.js");
const Common = require("./waziper/common.js");
const WAZIPER = require("./waziper/waziper.js");
const FlowEndpoint = require("./waziper/flow_endpoint.js");
const express = require('express');
const path = require('path')
const NodeCache = require("node-cache")
const axios = require('axios');

process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});

process.on('uncaughtException', (err) => {
    console.error('Uncaught Exception thrown:', err);
});

function parseProxyString(proxyRaw) {
    if (!proxyRaw || typeof proxyRaw !== 'string') {
        return null;
    }

    let raw = proxyRaw.trim();
    if (!raw) {
        return null;
    }

    let protocol = 'http';
    const protocolMatch = raw.match(/^[a-z0-9]+:\/\//i);
    if (protocolMatch) {
        protocol = protocolMatch[0].slice(0, -3).toLowerCase();
        raw = raw.slice(protocolMatch[0].length);
    }

    const authSplit = raw.split('@');
    let authPart = null;
    let hostPart = raw;
    if (authSplit.length === 2) {
        authPart = authSplit[0];
        hostPart = authSplit[1];
    }

    const hostPieces = hostPart.split(':');
    const host = hostPieces[0];
    const port = hostPieces[1] ? parseInt(hostPieces[1], 10) : (protocol === 'https' ? 443 : 80);
    if (!host || Number.isNaN(port)) {
        return null;
    }

    let username;
    let password;
    if (authPart) {
        const idx = authPart.indexOf(':');
        if (idx >= 0) {
            username = authPart.slice(0, idx);
            password = authPart.slice(idx + 1);
        } else {
            username = authPart;
            password = '';
        }
    }

    return {
        protocol,
        host,
        port,
        username,
        password
    };
}

//WAZIPER.app.use('/files', express.static('files'))
WAZIPER.app.use('/files', express.static(path.join(__dirname, 'files')));

WAZIPER.app.get('/instance', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        await WAZIPER.get_info(instance_id, res);
    });
});

WAZIPER.app.get('/get_qrcode', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        await WAZIPER.get_qrcode(instance_id, res);
    });
});

WAZIPER.app.get('/get_paircode', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        await WAZIPER.get_pairing(instance_id, req, res);
    });
});

WAZIPER.app.get('/get_groups', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        await WAZIPER.get_groups(instance_id, res);
    });
});

WAZIPER.app.get('/probe_ip_open', WAZIPER.cors, async (req, res) => {
    const instance_id = req.query.instance_id;

    if (!instance_id) {
        return res.status(400).json({ status: 'error', message: 'instance_id não informado' });
    }

    try {
        const account = await Common.db_get('sp_accounts', [{ token: instance_id }]);
        if (!account) {
            return res.status(404).json({ status: 'error', message: 'Conta não encontrada' });
        }

        let proxyAddress = null;
        let proxyRecord = null;

        if (account.proxy) {
            if (/^\d+$/.test(String(account.proxy))) {
                proxyRecord = await Common.db_get('sp_proxies', [{ id: account.proxy }]);
                proxyAddress = proxyRecord ? proxyRecord.proxy : null;
            } else {
                proxyAddress = account.proxy;
            }
        }

        if (!proxyAddress) {
            return res.json({
                status: 'success',
                instance_id,
                proxy: null,
                lookup: null,
                note: 'Conta não possui proxy atribuído.'
            });
        }

        const parsedProxy = parseProxyString(proxyAddress);
        if (!parsedProxy) {
            return res.status(400).json({ status: 'error', message: 'Proxy inválido ou não suportado.' });
        }

        if (parsedProxy.protocol && !['http', 'https'].includes(parsedProxy.protocol)) {
            return res.status(400).json({ status: 'error', message: `Protocolo de proxy não suportado: ${parsedProxy.protocol}` });
        }

        const axiosConfig = {
            timeout: 15000,
            proxy: {
                protocol: parsedProxy.protocol,
                host: parsedProxy.host,
                port: parsedProxy.port
            }
        };

        if (parsedProxy.username) {
            axiosConfig.proxy.auth = {
                username: parsedProxy.username,
                password: parsedProxy.password || ''
            };
        }

        const lookupUrl = 'http://ip-api.com/json/?fields=status,message,country,countryCode,regionName,city,lat,lon,query,isp,proxy,hosting,timezone';
        const lookupResponse = await axios.get(lookupUrl, axiosConfig);
        const lookupData = lookupResponse.data;

        if (!lookupData || lookupData.status !== 'success') {
            return res.status(502).json({
                status: 'error',
                message: lookupData?.message || 'Falha ao consultar serviço de geolocalização.',
                provider: 'ip-api.com',
                data: lookupData
            });
        }

        return res.json({
            status: 'success',
            instance_id,
            proxy: proxyRecord ? {
                id: proxyRecord.id,
                address: proxyRecord.proxy,
                location: proxyRecord.location,
                is_system: proxyRecord.is_system
            } : {
                id: null,
                address: proxyAddress,
                location: null
            },
            lookup: {
                provider: 'ip-api.com',
                country: lookupData.country,
                countryCode: lookupData.countryCode,
                region: lookupData.regionName,
                city: lookupData.city,
                latitude: lookupData.lat,
                longitude: lookupData.lon,
                ip: lookupData.query,
                isp: lookupData.isp,
                proxy: lookupData.proxy,
                hosting: lookupData.hosting,
                timezone: lookupData.timezone
            }
        });
    } catch (error) {
        return res.status(500).json({
            status: 'error',
            message: 'Erro ao consultar localização via proxy.',
            detail: error.message
        });
    }
});

WAZIPER.app.get('/logout', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;
    WAZIPER.logout(instance_id, res);
});

WAZIPER.app.post('/send_message', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        WAZIPER.send_message(instance_id, access_token, req, res);
    });
});

WAZIPER.app.post('/direct_send_message', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        WAZIPER.single_send_message(instance_id, access_token, req, res);
    });
});

WAZIPER.app.post('/bot_builder_send', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        WAZIPER.bot_builder_send(instance_id, access_token, req, res);
    });
});


WAZIPER.app.post('/send_template', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        WAZIPER.send_cloud_template(instance_id, access_token, req, res);
    });
});

WAZIPER.app.get('/flow_endpoint/:endpointIds', async function (req, res) {
    return FlowEndpoint.describe(req, res);
});

WAZIPER.app.post('/flow_endpoint/:endpointIds', async function (req, res) {
    return FlowEndpoint.handle(req, res);
});

WAZIPER.app.get('/reset', WAZIPER.cors, async function (req, res, next) {
    var api_key = await Common.db_query(`select value from sp_options where name = 'admin_api_key'`, true);
    if (api_key) {
        if (req.query.api_key == api_key.value) {
            res.json({
                status: 'success',
                message: 'Success'
            });

            process.exit();
        } else {
            res.json({
                status: 'error',
                message: 'not allowed',
                api: api_key
            });
        }
    }

});


WAZIPER.app.get('/clear_cache_ai', WAZIPER.cors, async function (req, res, next) {

    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;
    WAZIPER.resetAi(instance_id, res);
});

WAZIPER.app.post('/webhook/:accountId', async function (req, res) {
    WAZIPER.webhook_handler(req.params.accountId, req, res);
})

WAZIPER.app.get('/webhook/:accountId', async function (req, res) {
    const message = req.body;

    let VERIFY_TOKEN = await Common.db_get('sp_options', [{ name: 'wa_verify_token' }]);
    VERIFY_TOKEN = VERIFY_TOKEN.value;
    const accountId = req.params.accountId; // Obtienes accountId de la URL.

    // ParÃ¡metros de la solicitud de verificaciÃ³n de Facebook
    let mode = req.query['hub.mode'];
    let token = req.query['hub.verify_token'];
    let challenge = req.query['hub.challenge'];

    // Verifica si el token y el modo son correctos
    if (mode === 'subscribe' && token === VERIFY_TOKEN) {
        console.log(`Webhook verificado para la cuenta: ${accountId}`);
        res.send(challenge); // Responde el 'challenge' que Facebook enviÃ³
    } else {
        res.sendStatus(403); // EnvÃ­a un cÃ³digo de estado de prohibiciÃ³n si los tokens no coinciden
    }
})


WAZIPER.app.get('/', WAZIPER.cors, async (req, res) => {
    return res.json({ status: 'success', message: `BEM VINDO(A) AO ZAPMATIC - 🟢 O SERVIÇO ESTÁ ON SEU CURIOSO!!!` });
});

WAZIPER.server.listen(config.port, async () => {
    console.log(`OLÁ, O ZAPMATIC ESTÁ ON🟢`);
});
