const fs = require('fs');
const path = require('path');

function captureIncomingPayload(data) {
    try {
        const payloadStr = JSON.stringify(data, null, 2);
        const logEntry = `\n[${new Date().toISOString()}] CAPTURED INCOMING PAYLOAD:\n${payloadStr}\n`;
        fs.appendFileSync(path.join(__dirname, 'incoming_payloads.log'), logEntry);
    } catch(e) {
        console.error("Error capturing payload", e);
    }
}
module.exports = { captureIncomingPayload };
