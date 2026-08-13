## WhatsApp Flows - Fase 5

### Objetivo

Habilitar `template + Flow button` na trilha Cloud API para permitir:

- envio manual via `Single Message` usando o módulo existente de `Modelo de botão`;
- reutilização do mesmo template aprovado no `Bulk`;
- geração correta do payload oficial da Meta para botão `FLOW`.

### Escopo entregue

1. `send_cloud_template()` passou a entender componentes estruturais `BUTTONS` com botão `FLOW`.
2. O payload oficial de envio agora monta:
   - `type = button`
   - `sub_type = flow`
   - `index`
   - `parameters[0].type = action`
   - `action.flow_token`
   - `action.flow_action_data` opcional
3. O `Single Message` passou a encaminhar metadados de Flow do template interno aprovado.
4. O runtime Node do bulk passou a montar o mesmo payload para templates aprovados com botão `FLOW`.
5. O módulo `Whatsapp_button_template` passou a permitir um novo tipo visual:
   - `Flow Button`
   - seleção de Flow publicado
   - JSON inicial opcional por botão
6. O `Single Message` teve o conflito de tipos corrigido:
   - `Flow` continua no tipo próprio do módulo
   - `Template Oficial` voltou a aparecer em aba separada
   - o backend voltou a aceitar template oficial aprovado sem conflitar com Flow
7. A submissão Meta do `Modelo de botão` agora aceita `FLOW` como tipo suportado, validando:
   - Flow existente
   - `meta_flow_id` publicado
   - mesma conta Cloud da submissão

### Arquivos principais

- `inc/core/Whatsapp/Helpers/Whatsapp_helper.php`
- `inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php`
- `inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php`
- `inc/core/Whatsapp_button_template/Views/update.php`
- `app_zapmatic_api/waziper/waziper.js`

### Testes internos

- `php -l inc/core/Whatsapp/Helpers/Whatsapp_helper.php`
- `php -l inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php`
- `php -l inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php`
- `php -l inc/core/Whatsapp_button_template/Views/update.php`
- `node --check app_zapmatic_api/waziper/waziper.js`
- teste de payload com `wa_build_cloud_template_payload()`:
  - `BODY` com variável `{{1}}`
  - botão `FLOW`
  - `flow_token`
  - `flow_action_data`
- teste backend com template oficial aprovado:
  - conta `ELITEZAP`
  - template `review_demo_apr2026_260407_093043`
  - destino `551231993269`
  - resultado final: `sent` e `delivered`

### Resultado do payload de teste

O payload interno foi formado com sucesso neste formato:

```json
{
  "type": "template",
  "template": {
    "name": "tmpl_flow_demo",
    "language": { "code": "pt_BR" },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "Leonardo" }
        ]
      },
      {
        "type": "button",
        "sub_type": "flow",
        "index": "0",
        "parameters": [
          {
            "type": "action",
            "action": {
              "flow_token": "tok_flow_123",
              "flow_action_data": {
                "origem": "bulk",
                "categoria": "financeiro"
              }
            }
          }
        ]
      }
    ]
  }
}
```

### Observações

- O caminho recomendado para usar Flow em outbound continua sendo:
  - criar o template no `Modelo de botão`
  - ativar `Oficial (Meta)`
  - usar `Flow Button`
  - submeter e aguardar `APPROVED`
- O bulk reutiliza o template aprovado vinculado ao template interno, sem precisar de um módulo paralelo.
- O problema relatado no `Single Message` não era ausência de template aprovado no banco; era conflito de UI/backend:
  - a aba oficial existia, mas deixou de ser renderizada após o `type=6` ser reaproveitado para Flow.
