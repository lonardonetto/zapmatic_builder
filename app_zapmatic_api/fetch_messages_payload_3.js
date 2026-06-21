const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_messages 
    WHERE remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 5
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("MESSAGES ROWS (last 5):");
        results.forEach(r => {
            console.log(`\n--- remoteJid: ${r.remoteJid} | body: ${r.body} ---`);
            try {
                let data = JSON.parse(r.dataJson);
                console.log(JSON.stringify(data, null, 2));
            } catch (e) {
                console.log("dataJson:", r.dataJson);
            }
        });
    } else {
        console.error(error);
    }
    
    const query2 = `
        SELECT * FROM sp_whatsapp_livechat 
        WHERE remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%'
        ORDER BY id DESC LIMIT 5
    `;
    
    connection.query(query2, function (err, results2) {
        if (!err) {
            console.log("\nLIVECHAT ROWS (last 5):");
            results2.forEach(r => {
                console.log(`\n--- remoteJid: ${r.remoteJid} | type: ${r.message_type} ---`);
                console.log(r.text);
            });
        }
        
        connection.end();
    });
});
