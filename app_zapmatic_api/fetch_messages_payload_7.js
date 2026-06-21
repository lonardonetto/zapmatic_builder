const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_history 
    WHERE phone LIKE '%21970402529%' OR phone LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 500
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("HISTORY ROWS (last 500, looking for buttons/interactive):");
        results.forEach(r => {
            if(r.message && (r.message.includes("Fazer um Teste Gratuito") || r.message.includes("Smart TV") || r.message.includes("Celular") || r.message.includes("TESTE AUTOMATICO") || r.message.includes("Falar com Atendente") || r.message.includes("NOME DA SUA EMPRESA"))) {
                console.log(`\n--- ID: ${r.id} | phone: ${r.phone} | time_post: ${r.time_post} | type: ${r.type} ---`);
                console.log(r.message);
            }
        });
    } else {
        console.error(error);
    }
    connection.end();
});
