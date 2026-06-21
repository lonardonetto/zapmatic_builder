const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_history 
    WHERE message LIKE '%TESTE AUTOMATICO%' OR message LIKE '%Smart TV%' OR message LIKE '%Fazer um Teste Gratuito%' OR message LIKE '%Celular%'
    ORDER BY id DESC LIMIT 50
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("HISTORY ROWS WITH TARGET TEXTS:");
        results.forEach(r => {
            console.log(`\n--- ID: ${r.id} | phone: ${r.phone} | time_post: ${r.time_post} | type: ${r.type} ---`);
            console.log(r.message);
        });
    } else {
        console.error(error);
    }
    connection.end();
});
