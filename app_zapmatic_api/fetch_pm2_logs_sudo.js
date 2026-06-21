const { execSync } = require('child_process');
try {
    const logs = execSync('sudo -S pm2 logs waziper --lines 500 --nostream', { stdio: 'pipe' }).toString();
    const lines = logs.split('\n');
    let found = false;
    lines.forEach(l => {
        if(l.includes('21970402529') || l.includes('21968666544') || l.includes('TEMPLATE MESSAGE CONTENT')) {
            console.log(l);
            found = true;
        }
    });
    if(!found) console.log("Target strings not found in recent pm2 logs.");
} catch(e) {
    console.log("Error running pm2 logs:", e.message);
}
