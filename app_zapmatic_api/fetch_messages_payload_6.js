const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_messages 
    WHERE remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 5000
`;

connection.query(query, function (error, results) {
    if (!error) {
        let found = false;
        results.forEach(r => {
            try {
                if(r.dataJson && r.dataJson.includes("button") && r.dataJson.includes("Fazer um Teste Gratuito")) {
                    console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} ---`);
                    let data = JSON.parse(r.dataJson);
                    console.log(JSON.stringify(data, null, 2));
                    found = true;
                } else if(r.dataJson && r.dataJson.includes("button") && (r.dataJson.includes("Smart TV") || r.dataJson.includes("Celular"))) {
                    console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} ---`);
                    let data = JSON.parse(r.dataJson);
                    console.log(JSON.stringify(data, null, 2));
                    found = true;
                } else if(r.dataJson && r.dataJson.includes("button") && r.dataJson.includes("TESTE AUTOMATICO")) {
                    console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} ---`);
                    let data = JSON.parse(r.dataJson);
                    console.log(JSON.stringify(data, null, 2));
                    found = true;
                }
            } catch (e) {
            }
        });
        if(!found) {
            console.log("Not found in messages table.");
        }
    } else {
        console.error(error);
    }
    connection.end();
});
