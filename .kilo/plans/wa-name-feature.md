# Plano: Captura e Uso Automático do Nome do WhatsApp (wa_name)

## Contexto e Estratégia
O objetivo é capturar o "pushName" (nome do perfil do WhatsApp) de forma invisível no back-end e injetá-lo automaticamente nas variáveis da sessão do Flow Builder, criando uma variável nativa `{{wa_name}}`.

O Waziper (Baileys) e o Whatsmeow (Go) **já enviam** o campo `pushName` dentro do payload do webhook de recebimento de mensagem.

Como o motor do Bot Builder (no PHP) já substitui automaticamente qualquer tag `{{variavel}}` pelo que está salvo no `context` (contexto da sessão em JSON), a **única coisa que precisamos fazer** é inserir o `pushName` dentro desse contexto no momento em que a sessão é criada.

Isso significa que **nenhuma modificação no Frontend (JS) ou nos motores (Waziper/Go)** é necessária. Faremos tudo puramente no motor central em PHP.

---

## 1. Arquitetura da Solução

1. **Extração Unificada:** O PHP lerá o campo `$message['pushName']` logo no início do processamento do webhook.
2. **Injeção na Criação:** Quando uma pessoa enviar a primeira palavra-chave e não tiver um fluxo ativo, o sistema criará a sessão (`sp_bb_sessions`) já inicializando a coluna `context` com `{"wa_name": "Nome da Pessoa"}`.
3. **Injeção em Sessão Ativa:** Se a pessoa já estiver no meio do fluxo, o sistema verificará se o `wa_name` existe no contexto; se não existir (ou o nome do perfil tiver mudado), ele atualiza o `context` invisivelmente e segue o fluxo.
4. **Substituição (Replace):** A função `replace_vars()` nativa do Flow Builder fará o resto. Onde tiver `{{wa_name}}` no balão de texto, sairá o nome real.

---

## 2. Como usar isso na prática (Estratégia Profissional de Fluxo)

Como construtor de bots, o usuário poderá montar a seguinte lógica nativamente:

- **Bloco de Texto Inicial:** "Olá, {{wa_name}}! Tudo bem? O seu nome de contato é esse mesmo?"
- **Bloco de Botões (Escolha):**
  - Botão 1: "Sim, é esse" -> *(Liga para o Menu Principal)*. Como o `wa_name` já está na memória, a tag continuará funcionando perfeitamente.
  - Botão 2: "Não, é outro" -> *(Liga para um Bloco Input)*.
- **Bloco de Input de Texto (Perguntar Nome):**
  - Mensagem: "Sem problemas! Como você gostaria de ser chamado?"
  - Variável para salvar: **`wa_name`**. (O pulo do gato! O cliente digita o nome correto, o bloco Input **sobrescreve** a variável padrão).
- **Consequência:** A partir dali, usar `{{wa_name}}` sempre usará o nome correto escolhido (ou mantido) pelo cliente. Sem nenhum trabalho complexo.

---

## 3. Alterações Exatas de Código (Apenas PHP)

### Arquivo: `inc/core/Bot_builder/Models/Bot_builderModel.php`
Alterar a assinatura da função `create_session` para aceitar um JSON inicial.
```php
public function create_session($bot_id, $phone, $instance_id = null, $initial_context = '{}') {
    // ... código existente de encerrar sessões
    $this->db->table('sp_bb_sessions')->insert([
        'bot_id' => $bot_id,
        'phone' => $phone,
        'instance_id' => $instance_id,
        'context' => $initial_context, // <-- USA O NOVO PARÂMETRO
        'is_completed' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    return $this->db->insertID();
}
```

### Arquivo: `inc/core/Bot_builder/Controllers/Bot_builder.php`
No método `process_webhook()`:

1. **Extrair o nome:**
Dentro de `foreach ($messages as $message) {`
Adicionar:
```php
$push_name = trim($message['pushName'] ?? '');
// Alguns provedores enviam em profile->name, então fazemos fallback
if (empty($push_name) && isset($message['profile']['name'])) {
    $push_name = trim($message['profile']['name']);
}
```

2. **Sessão já existente (Atualizar nome se necessário):**
Onde o código acha a `$session` ativa (logo antes de executar o bloco), adicionar:
```php
$ctx_array = json_decode($session->context ?? '{}', true);
if (!empty($push_name) && ($ctx_array['wa_name'] ?? '') !== $push_name) {
    $ctx_array['wa_name'] = $push_name;
    $session->context = json_encode($context);
    $this->model->update_session($session->id, ['context' => $session->context]);
}
```

3. **Criar novas sessões (Para todos os triggers: Keyword, Command, Reply, Autorespond):**
Substituir o contexto inicial vazio pelo novo.
```php
$init_ctx = json_encode(['wa_name' => $push_name]);
$session_id = $this->model->create_session($bot->id, $phone, $instance_id_for_lookup, $init_ctx);

$session = (object)[
    'id' => $session_id,
    'bot_id' => $bot->id,
    'phone' => $reply_phone,
    'reply_phone' => $reply_phone,
    'canonical_phone' => $phone,
    'context' => $init_ctx, // <-- USA O INIT_CTX EM VEZ DE '{}'
    'current_block_id' => $bot->start_block_id
];
```
*(Fazer isso nos 4 blocos de código onde as sessões são criadas no `process_webhook()` e também no `run_flow()` quando o bot é redirecionado via bloco Jump to Bot)*.

---

## Conclusão

Essa abordagem atende perfeitamente aos requisitos:
1. **Nenhuma alteração no Waziper (Baileys) nem no Go.**
2. Tudo é feito dentro do motor central do Builder.
3. Traz a funcionalidade de uso de `{{wa_name}}` instantaneamente no momento da ativação do fluxo.
4. Permite a validação natural de "Esse é seu nome?", permitindo sobrescrever a variável nativamente se o usuário responder num bloco Input.
