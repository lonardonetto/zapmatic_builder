const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

// Query for recent messages involving those phone numbers
const query = `
    SELECT * FROM sp_whatsapp_history 
    WHERE phone LIKE '%21970402529%' OR phone LIKE '%21968666544%'
    ORDER BY id DESC LIMIT 10
`;

connection.query(query, function (error, results, fields) {
    if (error) {
        console.error("Error querying history:", error);
    } else {
        console.log("HISTORY ROWS:");
        console.log(JSON.stringify(results, null, 2));
    }
    
    // Also check if there's an incoming messages table or similar
    connection.query("SHOW TABLES LIKE '%whatsapp%'", function (err, tables) {
        if (!err) {
            console.log("\nTABLES:", tables);
        }
        connection.end();
    });
});
