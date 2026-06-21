const fs = require('fs');
const mysql = require('mysql');
const config = require('../config.js');

const connection = mysql.createConnection(config.database);
connection.connect();

connection.query("SELECT * FROM sp_whatsapp_sessions WHERE status = 1", function(e, res) {
    if(!e && res.length > 0) {
        console.log("ALL MATCHES:");
        res.forEach(r => {
             console.log(`${r.instance_id} - ${r.id}`);
             try {
                let d = JSON.parse(r.data);
                if(d && d.user) {
                     console.log("  USER:", d.user.id);
                }
             } catch(e) {}
        });
    }
    connection.end();
});
