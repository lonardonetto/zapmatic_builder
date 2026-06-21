const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_livechat 
    WHERE remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 500
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("LIVECHAT ROWS (last 500, looking for texts):");
        results.forEach(r => {
            if(r.text && (r.text.includes("Fazer um Teste Gratuito") || r.text.includes("Smart TV") || r.text.includes("Celular") || r.text.includes("TESTE AUTOMATICO") || r.text.includes("Falar com Atendente") || r.text.includes("NOME DA SUA EMPRESA"))) {
                console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} | type: ${r.message_type} ---`);
                console.log(r.text);
            }
        });
    } else {
        console.error(error);
    }
    
    // Also let's check ALL tables for those texts if they aren't here
    connection.query("SELECT * FROM sp_whatsapp_template WHERE data LIKE '%Smart TV%' OR data LIKE '%Fazer um Teste Gratuito%'", function(err, tpl) {
        if(!err) {
            console.log("\nTEMPLATES FOUND:");
            tpl.forEach(t => {
                console.log(`\n--- ID: ${t.id} | Name: ${t.name} ---`);
                console.log(t.data);
            });
        }
        connection.end();
    });
});
