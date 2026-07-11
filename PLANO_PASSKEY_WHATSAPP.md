# Plano de Integração e Suporte a Passkey (WebAuthn) no WhatsApp

**Data de Criação:** Julho de 2026
**Contexto:** A Meta começou a exigir autenticação biométrica/Passkey (protocolo interno "Shortcake") para vincular novos dispositivos (principalmente contas WhatsApp Business). Em vez do QR Code tradicional, o WhatsApp solicita um desafio WebAuthn.

---

## 1. O que já foi feito (Frontend, PHP e Go/Whatsmeow) ✅

A estrutura base para suportar Passkey já foi implementada e corrigida. O seu sistema tem uma arquitetura excelente para lidar com isso, pois a biometria precisa vir do navegador do usuário.

*   **Frontend (`inc/core/Whatsapp/Assets/js/whatsapp.js`):**
    *   Quando o backend retorna `{"method": "passkey"}`, o JS aciona `start_passkey()`.
    *   Usa `navigator.credentials.get()` para pedir a biometria/PIN do usuário.
    *   Envia a resposta (assertion) via POST para o PHP.
*   **PHP (`inc/core/Whatsapp_profiles/Controllers/Whatsapp_profiles.php`):**
    *   Corrigido para aceitar a resposta do backend caso não venha o `qrcode`, repassando o método `passkey` corretamente para o JS.
    *   Possui rotas (`send_whatsmeow_passkey_response` e `confirm_whatsmeow_passkey`) que fazem a ponte entre o JS e a API em Go.
*   **Go API / Whatsmeow (`app_zapmatic_whatsmeow_api/internal/session/manager.go`):**
    *   A biblioteca `whatsmeow` já tem suporte nativo.
    *   Corrigimos um bug onde o desafio (challenge) perdia o formato `Base64Url` ao ser passado para o PHP.
    *   Fluxo pronto com os eventos `PairPasskeyRequest`, `PairPasskeyConfirmation` e `PairPasskeyError`.

---

## 2. O que falta fazer (Node.js / Baileys) ⏳

**Status atual do Baileys:** A biblioteca ainda *não* possui suporte oficial (Merge) para o protocolo de Passkey. Existe um rascunho promissor (PR #2689), mas não é recomendado mexer até que a comunidade estabilize o código oficial ou até que você realmente tenha uma conta bloqueada para testar.

Quando o Baileys lançar a atualização oficial com suporte a Passkey, você precisará aplicar a mesma lógica do Go no Node.js. Siga este roteiro:

### Passo A: Atualizar a Biblioteca
Atualizar o `@whiskeysockets/baileys` no `package.json` para a versão que incluir o merge do Passkey (provavelmente > 7.0.0).

### Passo B: Interceptar o Desafio no Baileys (Node.js)
No arquivo que inicializa a conexão (`app_zapmatic_api/waziper/waziper.js`), você precisará adicionar o gatilho/estado para o Passkey.
Baseado no PR atual do Baileys, a lógica será algo como:

```javascript
// Exemplo de como ficará o código no Baileys
sock.ev.on('connection.update', (update) => {
    const { connection, qr, passkeyRequired } = update;

    // Se o WhatsApp pedir Passkey no lugar do QR Code
    if (passkeyRequired) {
        // Você deve salvar as opções de request (challenge, rpId, etc)
        // para devolver no endpoint '/get_qrcode' do seu Express.
        WA.passkey_challenge = passkeyRequired.requestOptions;
        WA.method = "passkey";
    }
});
```

### Passo C: Ajustar a Rota `/get_qrcode` no Express (Node.js)
Hoje a rota retorna o PNG em base64. Você deve ajustá-la para verificar se um Passkey foi solicitado:

```javascript
// app.js ou rotas do Node.js
if (WA.method === "passkey") {
    return res.json({
        status: "success",
        method: "passkey",
        challenge: WA.passkey_challenge.challenge,
        rp_id: WA.passkey_challenge.rpId,
        timeout: WA.passkey_challenge.timeout
    });
}
```

### Passo D: Criar as rotas de Resposta e Confirmação no Express (Node.js)
Crie dois endpoints novos no seu Node.js (exatamente como existem no Go):

1.  **`POST /passkey/response`:** Vai receber o JSON com a biometria enviada pelo Frontend e injetar de volta na função específica que o Baileys criará (ex: `signPasskeyAssertion`).
2.  **`POST /passkey/confirm`:** Vai confirmar o código que o usuário validou no celular (se a UX exigir).

### Passo E: Integrar o PHP com o novo Node.js
No arquivo `Whatsapp_profiles.php`, assim como existem as funções:
*   `send_whatsmeow_passkey_response()`
*   `confirm_whatsmeow_passkey()`

Você precisará criar os análogos para o Baileys:
*   `send_baileys_passkey_response()`
*   `confirm_baileys_passkey()`

Que farão o `curl` não para o `127.0.0.1:8090` (Go), mas para o `127.0.0.1:8000` (Node.js). E então, basta atualizar o JS (`whatsapp.js`) para fazer a chamada certa dependendo se a instância for Whatsmeow ou Baileys.

---

**Resumo de Sobrevivência:**
O mais difícil já está feito: a coleta da biometria no navegador e a arquitetura multi-camadas (Frontend -> PHP -> Backend da API). Assim que o Baileys tiver os métodos prontos, a implementação do lado Node.js levará poucos minutos copiando a estrutura que já fizemos no Go.
