const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_livechat 
    WHERE text LIKE '%TESTE AUTOMATICO%' OR text LIKE '%Smart TV%' OR text LIKE '%Fazer um Teste Gratuito%' OR text LIKE '%Celular%'
    ORDER BY id DESC LIMIT 50
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("LIVECHAT ROWS WITH TARGET TEXTS:");
        results.forEach(r => {
            console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} | type: ${r.message_type} ---`);
            console.log(r.text);
        });
    } else {
        console.error(error);
    }
    
    connection.query("SELECT * FROM sp_whatsapp_messages WHERE body LIKE '%TESTE AUTOMATICO%' OR body LIKE '%Smart TV%' OR body LIKE '%Fazer um Teste Gratuito%' OR body LIKE '%Celular%' ORDER BY id DESC LIMIT 50", function (err, res) {
        if (!err) {
            console.log("\n\nMESSAGES ROWS WITH TARGET TEXTS:");
            res.forEach(r => {
                console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} | body: ${r.body} ---`);
            });
        }
        connection.end();
    });
});
