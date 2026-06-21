const fs = require('fs');
const logFile = '/root/.pm2/logs/waziper-out.log'; // Try default pm2 log location
if(fs.existsSync(logFile)) {
    const logs = fs.readFileSync(logFile, 'utf8');
    const lines = logs.split('\n').slice(-1000);
    console.log("LAST 1000 LINES OF PM2 OUT LOG:");
    let found = false;
    lines.forEach(l => {
        if(l.includes('21970402529') || l.includes('21968666544') || l.includes('TEMPLATE MESSAGE CONTENT')) {
            console.log(l);
            found = true;
        }
    });
    if(!found) console.log("Target strings not found in logs.");
} else {
    console.log("Log file not found at " + logFile);
}
