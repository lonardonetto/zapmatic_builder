const fs = require('fs');
const mysql = require('mysql');
const config = require('../config.js');

const connection = mysql.createConnection(config.database);
connection.connect();

connection.query("DESCRIBE sp_whatsapp_sessions", function(e, res) {
    if(!e) {
        let cols = res.map(r => r.Field);
        console.log("Columns:", cols);
    }
    
    // Attempting to just grab status = 1 if phone doesn't exist
    connection.query("SELECT instance_id, status FROM sp_whatsapp_sessions WHERE status = 1 LIMIT 5", function(e2, res2) {
        console.log("\nACTIVE SESSIONS:");
        res2.forEach(r => {
             console.log(r.instance_id);
             // check if folder exists
             if(fs.existsSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/sessions/' + r.instance_id)) {
                 console.log("-> folder exists for", r.instance_id);
             }
        });
        connection.end();
    });
});
