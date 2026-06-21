const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_messages 
    WHERE remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 50
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("MESSAGES ROWS (last 50, looking for buttons):");
        results.forEach(r => {
            if(r.dataJson && r.dataJson.includes("button") || r.dataJson.includes("interactive") || r.dataJson.includes("list") || r.body && r.body.includes("TESTE AUTOMATICO")) {
                console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} | body: ${r.body} ---`);
                try {
                    let data = JSON.parse(r.dataJson);
                    console.log(JSON.stringify(data, null, 2));
                } catch (e) {
                    console.log("dataJson:", r.dataJson);
                }
            }
        });
    } else {
        console.error(error);
    }
    connection.end();
});
