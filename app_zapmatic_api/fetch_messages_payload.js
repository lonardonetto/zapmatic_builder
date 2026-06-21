const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_messages 
    WHERE body LIKE '%teste%' OR body LIKE '%dispositivo%' OR from_phone LIKE '%21970402529%' OR from_phone LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 10
`;

connection.query(query, function (error, results, fields) {
    if (error) {
        console.error("Error querying sp_whatsapp_messages:", error);
    } else {
        console.log("MESSAGES ROWS:");
        console.log(JSON.stringify(results, null, 2));
    }
    
    // Let's check sp_whatsapp_livechat which might contain the raw incoming messages
    const query2 = `
        SELECT * FROM sp_whatsapp_livechat 
        WHERE from_phone LIKE '%21970402529%' OR from_phone LIKE '%21968666544%'
        ORDER BY id DESC LIMIT 10
    `;
    
    connection.query(query2, function (err, results2) {
        if (!err) {
            console.log("\nLIVECHAT ROWS:");
            console.log(JSON.stringify(results2, null, 2));
        } else {
            console.error("Error querying livechat:", err);
        }
        
        // Check columns of sp_whatsapp_history
        connection.query("DESCRIBE sp_whatsapp_history", function(e, cols) {
            console.log("\n sp_whatsapp_history COLS:");
            console.log(cols);
            connection.end();
        });
    });
});
