const mysql = require('mysql');
const config = require('../config.js');

const connection = mysql.createConnection(config.database);
connection.connect();

const data = {
  templateButtons: [
    { index: 0, quickReplyButton: { displayText: 'Botão 1 - Teste', id: 'btn1' } },
    { index: 1, quickReplyButton: { displayText: 'Botão 2 - Smart TV', id: 'btn2' } },
    { index: 2, quickReplyButton: { displayText: 'Botão 3 - TV Box', id: 'btn3' } },
    { index: 3, quickReplyButton: { displayText: 'Botão 4 - Celular', id: 'btn4' } },
    { index: 4, quickReplyButton: { displayText: 'Botão 5 - PC', id: 'btn5' } },
    { index: 5, quickReplyButton: { displayText: 'Botão 6 - Automático', id: 'btn6' } }
  ],
  footer: 'Zapmatic V4',
  title: 'Botão',
  text: 'TESTE DE 6 BOTÕES DO SISTEMA',
  local_variables: [],
  meta_official: {
    enabled: false,
    base_name: '',
    category: 'MARKETING',
    languages: '',
    header_format: 'TEXT',
    body_example: ''
  }
};

const ids = 'teste_6_botoes_' + Date.now().toString(36);
const now = Math.floor(Date.now() / 1000);

connection.query(
  `UPDATE sp_whatsapp_template
   SET ids = IF(ids IS NULL OR ids = '', ?, ids),
       team_id = 245,
       type = 2,
       name = 'TESTE_6_BOTOES',
       data = ?,
       changed = ?,
       created = IF(created IS NULL OR created = 0, ?, created)
   WHERE id = 1151`,
  [ids, JSON.stringify(data), now, now],
  function(e) {
    if (e) {
      console.error(e);
    } else {
      console.log('Template 1151 corrigido com 6 botões reais no editor. ids:', ids);
    }
    connection.end();
  }
);
