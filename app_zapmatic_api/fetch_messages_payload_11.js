const mysql = require('mysql');
const config = require('./config.js');

const connection = mysql.createConnection(config.database);

connection.connect();

const query = `
    SELECT * FROM sp_whatsapp_livechat 
    WHERE messageTimestamp > (UNIX_TIMESTAMP() - 3600)
    AND (remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%')
    ORDER BY id DESC LIMIT 50
`;

connection.query(query, function (error, results) {
    if (!error) {
        console.log("LIVECHAT ROWS FROM LAST HOUR:");
        results.forEach(r => {
            console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} | type: ${r.message_type} | time: ${r.messageTimestamp} ---`);
            console.log(r.text);
            if(r.media) console.log("MEDIA:", r.media);
        });
    } else {
        console.error(error);
    }
    
    const query2 = `
        SELECT * FROM sp_whatsapp_messages 
        WHERE createdAt > (NOW() - INTERVAL 1 HOUR)
        AND (remoteJid LIKE '%21970402529%' OR remoteJid LIKE '%21968666544%')
        ORDER BY id DESC LIMIT 50
    `;
    
    connection.query(query2, function (err, res) {
        if (!err) {
            console.log("\n\nMESSAGES ROWS FROM LAST HOUR:");
            res.forEach(r => {
                console.log(`\n--- ID: ${r.id} | remoteJid: ${r.remoteJid} | body: ${r.body} ---`);
                try {
                    console.log(JSON.stringify(JSON.parse(r.dataJson), null, 2));
                } catch(e){}
            });
        }
        
        // Let's also check sp_whatsapp_history from the last hour
        const query3 = `
            SELECT * FROM sp_whatsapp_history 
            WHERE time_post > (UNIX_TIMESTAMP() - 3600)
            AND (phone LIKE '%21970402529%' OR phone LIKE '%21968666544%')
            ORDER BY id DESC LIMIT 50
        `;
        connection.query(query3, function(e, hist) {
             if (!e) {
                console.log("\n\nHISTORY ROWS FROM LAST HOUR:");
                hist.forEach(h => {
                    console.log(`\n--- ID: ${h.id} | phone: ${h.phone} | type: ${h.type} ---`);
                    console.log(h.message);
                });
             }
             connection.end();
        });
    });
});
