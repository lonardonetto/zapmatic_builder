const fs = require('fs');
const pm2Out = '/root/.pm2/logs/waziper-out.log';
const logs = [
    '/root/.pm2/logs/waziper-out-0.log',
    '/root/.pm2/logs/waziper-error-0.log',
    '/root/.pm2/logs/waziper-error.log',
    '/www/wwwroot/app_zapmatic_app/app_zapmatic_api/zapmatic_debug.txt'
];

logs.forEach(f => {
    try {
        if(fs.existsSync(f)) {
            console.log("Checking " + f);
            const content = fs.readFileSync(f, 'utf8');
            const lines = content.split('\n').slice(-500);
            let found = false;
            lines.forEach(l => {
                 if(l.includes('TEMPLATE MESSAGE CONTENT') || l.includes('21970402529')) {
                     console.log(l);
                     found = true;
                 }
            });
            if(!found) console.log("Not found in " + f);
        }
    } catch(e) {}
});
