const fs = require('fs');
const path = require('path');
const file = path.join(__dirname, 'incoming_payloads.log');

if (fs.existsSync(file)) {
    const data = fs.readFileSync(file, 'utf8');
    const payloads = data.split('[2026-06-17T');
    
    // Check the last 30 payloads
    const recent = payloads.slice(-30);
    
    let found = false;
    for (let p of recent) {
        if (p.includes('button') || p.includes('interactive') || p.includes('template') || p.includes('Smart TV') || p.includes('TESTE AUTOMATICO') || p.includes('ola')) {
            console.log("\n--- FOUND POTENTIAL PAYLOAD ---");
            console.log(p);
            found = true;
        }
    }
} else {
    console.log("Log file not found");
}
