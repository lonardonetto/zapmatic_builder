# Spec: Webhook Cloud API independente por sistema (app único, callback por WABA)

> feature: cloud-api-webhook-independente
> status: rascunho
> data: 2026-08-18
> afetados: todos os servidores (main, MetaSenderPro, Kivozap, AgenciaMCW, Chatbut, IaClicks, Elite, PlusZap)

---

## 1. Contexto

Todos os sistemas usam o **mesmo app Meta** (`ELITEZAP`, `app_id = 763786439394524`)
como provider de Embedded Signup e Cloud API. O app tem **uma callback URL única**
configurada no painel da Meta, hoje apontando para:

```
https://zapmatic.tec.br/whatsapp_webhook/index   (o main)
```

Por isso, **todo** webhook de **todo** número conectado em qualquer sistema é
entregue ao **main**. O main processa os números que existem no banco dele e
descarta os demais (`Forwarding DISABLED — loop prevention`, commit `42a818f5`).

**Consequência:** sistemas filhos (Chatbut, Kivozap, etc.) têm o Bot Builder /
autoresponder **quebrado** para números Cloud API — o webhook nunca chega a eles.
O disparo via Single Message funciona porque envia direto pela Graph API (token
do próprio número), sem depender de webhook de entrada.

### Objetivo

Cada sistema processa os webhooks dos **seus próprios números** no **seu próprio
endpoint** (domínio), **sem reencaminhamento entre servidores** e **sem depender
do domínio do main** estar no ar. Continuamos usando o **mesmo app ELITEZAP**
(infraestrutura de provider) para todos — só a **callback por WABA** passa a ser
individual.

---

## 2. Decisão de arquitetura (comprovada empiricamente)

A Graph API da Meta aceita o parâmetro **`override_callback_uri`** no endpoint
`POST /{waba_id}/subscribed_apps`. Ele cria uma **callback por WABA** que
sobrescreve a callback padrão do app, sem alterar o app e sem afetar os demais
WABAs do mesmo app.

### Prova (executada em 2026-08-18)

Comando:
```
POST https://graph.facebook.com/v22.0/{waba_id}/subscribed_apps
  ?override_callback_uri={url_encode(domínio_local + '/whatsapp_webhook')}
  &verify_token={verify_token_da_conta}
  &access_token={token_do_numero}
```

Resultado (campo `webhook_configuration` do número):

| Momento | `webhook_configuration` |
|---|---|
| Antes | `{"application":"https://zapmatic.tec.br/whatsapp_webhook/index"}` |
| Depois | `{"whatsapp_business_account":"https://chatbut.com.br/index.php/whatsapp_webhook","application":"https://zapmatic.tec.br/whatsapp_webhook/index"}` |

> `whatsapp_business_account` passa a ser o callback efetivo por WABA.
> `application` continua sendo o fallback do app (main) — mantido por
> compatibilidade, mas o callback por WABA tem precedência.

### Por que esta solução é a correta

1. **Independência real:** cada sistema aponta para o próprio domínio. Se o main
   cair, os filhos continuam recebendo webhooks (o callback é por WABA, não
   passa pelo main).
2. **Sem reencaminhamento:** elimina a necessidade de o main reencaminhar para os
   filhos (que causava loop e foi removido no `42a818f5`).
3. **App único mantido:** continuamos usando o ELITEZAP como provider — os
   clientes sem app completo dependem da nossa infraestrutura, e quem tem BM
   própria mantém os templates sob o mesmo app.
4. **Sem mudança de credencial:** o token de cada número (que já está no banco de
   cada sistema) é suficiente para fazer o subscribe com override.

---

## 3. Histórias

### US-001 — Callback por WABA automática na conexão

Como operador, quero que ao conectar um número Cloud API (embedded signup **ou**
manual), o sistema configure automaticamente a callback do WABA para o **domínio
do próprio sistema**, para que o webhook chegue direto no endpoint local.

#### AC-001 — Embedded Signup configura callback local

- **Dado** que um usuário conecta um número via Embedded Signup
- **Quando** o `saveOneEmbeddedProfile()` salva o perfil
- **Então** o sistema chama `POST /{waba_id}/subscribed_apps` com
  `override_callback_uri = base_url() . '/whatsapp_webhook'` e
  `verify_token` do perfil
- **E** o campo `webhook_configuration.whatsapp_business_account` do número
  passa a apontar para o domínio local

#### AC-002 — Fluxo manual (save_official) configura callback local

- **Dado** que um usuário conecta via fluxo manual (`save_official`)
- **Quando** o perfil é salvo
- **Então** o sistema também configura o `override_callback_uri` para o domínio
  local (mesmo endpoint, mesmo verify_token informado)

#### AC-003 — Verify token corresponde ao endpoint local

- **Dado** que o sistema configura a callback por WABA
- **Quando** a Meta faz a verificação (`GET /whatsapp_webhook?hub.mode=subscribe&hub.verify_token=...`)
- **Então** o endpoint local responde com o challenge (HTTP 200)
- **E** o verify_token usado é o mesmo salvo no `data.verify_token` da conta

### US-002 — Correção retroativa dos números já conectados

Como operador, quero que todos os números Cloud API já existentes nos sistemas
filhos tenham a callback corrigida para o domínio local, sem reconectar.

#### AC-004 — Script de correção por sistema

- **Dado** que um sistema filho tem N contas Cloud API (`login_type = 1`)
- **Quando** rodo o script de correção
- **Então** para cada conta, o sistema chama `subscribed_apps` com
  `override_callback_uri` do domínio local
- **E** o `webhook_configuration.whatsapp_business_account` de cada número passa
  a apontar para o domínio local

#### AC-005 — Idempotência

- **Dado** que o script é rodado mais de uma vez
- **Quando** a callback já está correta
- **Então** o script não gera erro nem efeito colateral (re-subscribe é seguro)

### US-003 — Endpoint local robusto

Como operador, quero que o endpoint `whatsapp_webhook` de cada sistema processe
seus próprios números sem depender de outros servidores.

#### AC-006 — Processamento local apenas

- **Dado** que um webhook chega no domínio local com `phone_number_id` local
- **Quando** o `Whatsapp_webhook.php` processa
- **Então** encontra a conta local e encaminha ao Bot Builder (já funciona)

#### AC-007 — Número não-local apenas logado

- **Dado** que chega um webhook com `phone_number_id` que não é deste sistema
- **Quando** o `Whatsapp_webhook.php` processa
- **Então** loga `No account found ... locally. Forwarding DISABLED` e não
  reencaminha (comportamento atual mantido — sem loop)

---

## 4. Plano de implementação

### 4.1 Mudança de código (única, replicada a todos os sistemas)

**Arquivo:** `inc/core/Whatsapp_profiles/Controllers/Whatsapp_profiles.php`

No `saveOneEmbeddedProfile()` (após o `subscribed_apps` atual, ~linha 2887) e no
`save_official()` (após validar credenciais, ~linha 2689), adicionar o subscribe
com override:

```php
// Configurar callback do WABA para o DOMINIO LOCAL (independencia de sistema)
$local_callback = rtrim(base_url(), '/') . '/whatsapp_webhook';
$override_url = "https://graph.facebook.com/{$graph_version}/{$waba_id}/subscribed_apps";
$ch = curl_init($override_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'override_callback_uri' => $local_callback,
    'verify_token' => $verify_token,
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$override_resp = curl_exec($ch);
curl_close($ch);
$this->embedded_log("Callback override WABA={$waba_id} -> {$local_callback}: " . substr($override_resp, 0, 200));
```

> **Observação importante:** `verify_token` deve ser o mesmo usado pelo endpoint
> local. Hoje o `saveOneEmbeddedProfile` gera `uniqid('zapmatic_')` e salva em
> `data.verify_token`; o endpoint `Whatsapp_webhook.php` valida qualquer
> `hub.verify_token` contra as contas locais — verificar se a validação bate
> (ver AC-008 abaixo).

### 4.2 Verificação da validação de verify_token (AC-008)

**Arquivo:** `inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php` (linhas 178-203)

**JÁ CORRETO.** A validação GET já usa `hub.verify_token` contra o
`data.verify_token` de qualquer conta Cloud API local (`login_type = 1`):

```php
$sql = "SELECT id FROM sp_accounts WHERE social_network = 'whatsapp'
        AND login_type = 1
        AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.verify_token')) = ?";
```

Portanto, ao configurar o `override_callback_uri` com o `verify_token` da conta,
o endpoint local valida corretamente (prova: o teste no Chatbut retornou o
challenge `TESTE123` com o token `zapmatic_6a7f3ca16a128`). Nenhuma mudança
necessária aqui — apenas garantir que o `verify_token` usado no subscribe seja
exatamente o salvo em `data.verify_token` da conta.

### 4.3 Script de correção retroativa

Criar um endpoint/script (ex.: `whatsapp_profiles/fix_webhook_callbacks`) que:

1. Busca todas as contas `login_type = 1` ativas.
2. Para cada uma, lê `waba_id`, `verify_token` e `token` do `data`.
3. Chama `POST /{waba_id}/subscribed_apps` com `override_callback_uri` do domínio
   local e o `verify_token` da conta.
4. Loga o resultado.

Executável via CLI (`php spark`) ou rota protegida.

---

## 5. Matriz de servidores (escopo)

| Servidor | Domínio | Callback desejada | Números Cloud API |
|---|---|---|---|
| main | zapmatic.tec.br | `https://zapmatic.tec.br/whatsapp_webhook` | 2 (186, 200) |
| MetaSenderPro | sender.metanivelpro.com | `https://sender.metanivelpro.com/whatsapp_webhook` | 12 |
| Kivozap | kivozap.com.br | `https://kivozap.com.br/whatsapp_webhook` | (auditar) |
| AgenciaMCW | chatbot.agenciamcw.com.br | `https://chatbot.agenciamcw.com.br/whatsapp_webhook` | (auditar) |
| Chatbut | chatbut.com.br | `https://chatbut.com.br/whatsapp_webhook` | 9 |
| IaClicks | iaclicks.com | `https://iaclicks.com/whatsapp_webhook` | (auditar) |
| Elite | elitecomunicacao.zapmatic.tec.br | `https://elitecomunicacao.zapmatic.tec.br/whatsapp_webhook` | (auditar) |
| PlusZap | pluszap.com | `https://pluszap.com/whatsapp_webhook` | (auditar) |

---

## 6. Riscos e mitigações

| Risco | Mitigação |
|---|---|
| `verify_token` do endpoint local não bate com o salvo | AC-008: alinhar a validação para aceitar o token individual de cada conta |
| Meta rejeitar `override_callback_uri` em alguma versão da API | Testado OK na v22.0; manter fallback para o fluxo atual |
| WABA com `subscribed_apps` de outro app | O override é por WABA, independente do app inscrito — validar em todos |
| Endpoint local atrás de HTTPS inválido | Meta exige HTTPS válido; todos os domínios já têm SSL |

---

## 7. Verificação (Definition of Done)

- [ ] Conectar número novo via Embedded Signup → `webhook_configuration.whatsapp_business_account` aponta para o domínio local
- [ ] Conectar número novo via manual → idem
- [ ] Rodar script retroativo em um sistema filho → todos os números passam a apontar para o domínio local
- [ ] Enviar mensagem ao número → webhook chega no domínio local → Bot Builder/autoresponder dispara
- [ ] Main fora do ar → sistema filho continua recebendo webhooks (teste de independência)
- [ ] Número de outro sistema chegando no domínio errado → apenas loga `Forwarding DISABLED` (sem loop)

---

## 8. Referências

- Commit `42a818f5` — desativou reencaminhamento entre plataformas (loop prevention)
- Spec `fix-cloud-api-flows` — diagnóstico do deadlock cURL interno (já corrigido)
- Spec `sync-metasenderpro` — template de sincronização multi-servidor
- Graph API: `POST /{waba_id}/subscribed_apps` com `override_callback_uri`
  (comprovado empiricamente em 2026-08-18)
