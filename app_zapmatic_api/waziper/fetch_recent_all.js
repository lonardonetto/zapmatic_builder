const fs = require('fs');
const path = require('path');
const file = path.join(__dirname, 'incoming_payloads.log');

if (fs.existsSync(file)) {
    const data = fs.readFileSync(file, 'utf8');
    const payloads = data.split('[2026-06-17T');
    
    // Check the last 150 payloads
    const recent = payloads.slice(-150);
    
    let found = false;
    for (let p of recent) {
        if (p.includes('messageContextInfo') && !p.includes('imageMessage') && !p.includes('extendedTextMessage') && !p.includes('Invalid PreKey') && !p.includes('No session found')) {
            console.log("\n--- FOUND POTENTIAL UNUSUAL PAYLOAD ---");
            console.log(p.substring(0, 500) + "...");
            found = true;
        }
    }
}
