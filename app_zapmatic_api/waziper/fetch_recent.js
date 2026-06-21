const fs = require('fs');
const path = require('path');
const file = path.join(__dirname, 'incoming_payloads.log');

if (fs.existsSync(file)) {
    const data = fs.readFileSync(file, 'utf8');
    const payloads = data.split('[2026-06-17T');
    
    // Check the last 10 payloads
    const recent = payloads.slice(-10);
    
    let found = false;
    for (let p of recent) {
        if (p.includes('button') || p.includes('interactive') || p.includes('template') || p.includes('Smart TV') || p.includes('TESTE AUTOMATICO')) {
            console.log("\n--- FOUND POTENTIAL PAYLOAD ---");
            console.log(p);
            found = true;
        }
    }
    
    if (!found) {
        console.log("No relevant payload found in the last 10 entries.");
        // print just the most recent one to see what's coming in
        if (recent.length > 0) {
             console.log("\n--- MOST RECENT PAYLOAD ---");
             console.log(recent[recent.length-1]);
        }
    }
} else {
    console.log("Log file not found");
}
