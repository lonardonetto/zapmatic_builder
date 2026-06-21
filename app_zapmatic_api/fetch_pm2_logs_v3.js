const { execSync } = require('child_process');
try {
    const logs = execSync('sudo pm2 logs waziper --lines 500 --nostream', { stdio: 'pipe' }).toString();
    console.log(logs.slice(-1000));
} catch(e) {
    console.log("pm2 error.");
}
