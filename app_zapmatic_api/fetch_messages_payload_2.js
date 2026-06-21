const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

connection.query("DESCRIBE sp_whatsapp_messages", function(e, cols) {
    if (!e) {
        console.log("\n sp_whatsapp_messages COLS:");
        console.log(cols.map(c => c.Field));
    }
    
    connection.query("DESCRIBE sp_whatsapp_livechat", function(e, cols) {
        if (!e) {
            console.log("\n sp_whatsapp_livechat COLS:");
            console.log(cols.map(c => c.Field));
        }
        
        connection.end();
    });
});
