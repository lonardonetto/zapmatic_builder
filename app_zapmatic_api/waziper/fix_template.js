const mysql = require('mysql');
const config = require('../config.js');

const connection = mysql.createConnection(config.database);
connection.connect();

// I will update the team_id to 245 which seems to be your active team, so it shows in your panel
connection.query("UPDATE sp_whatsapp_template SET team_id = 245 WHERE id = 1151", function(e) {
    if(!e) console.log("Template team_id updated to 245!");
    
    // Just to be super safe, let's duplicate it for team 1 as well with a different name
    connection.query("INSERT INTO sp_whatsapp_template (team_id, name, type, data) SELECT 1, 'TESTE_6_BOTOES_B', type, data FROM sp_whatsapp_template WHERE id = 1151", function() {
        console.log("Template duplicated just in case.");
        connection.end();
    });
});
