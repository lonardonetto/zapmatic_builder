const mysql = require('mysql');
const config = require('../config.js');

const connection = mysql.createConnection(config.database);
connection.connect();

connection.query("SELECT id, name, team_id FROM sp_whatsapp_template ORDER BY id DESC LIMIT 5", function(e, res) {
    if(!e) {
        console.log("Recent templates:");
        res.forEach(r => console.log(r));
    }
    
    // Find the team ID of the user you are logged in as usually
    connection.query("SELECT id, ids FROM sp_team LIMIT 5", function(e2, res2) {
         if(!e2) console.log("Teams:", res2);
         connection.end();
    });
});
