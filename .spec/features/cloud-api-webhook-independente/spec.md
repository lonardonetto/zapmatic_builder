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

### Por que esta solução é a correta

1. **Independência real:** cada sistema aponta para o próprio domínio.
2. **Sem reencaminhamento:** elimina o reencaminhamento entre plataformas (loop).
3. **App único mantido:** continuamos usando o ELITEZAP como provider.
4. **Sem mudança de credencial:** o token de cada número já salvo é suficiente.

---

## 3. Histórias

### US-022 — Callback por WABA automática na conexão

Como operador, quero que ao conectar um número Cloud API (embedded signup **ou**
manual), o sistema configure automaticamente a callback do WABA para o **domínio
do próprio sistema**, para que o webhook chegue direto no endpoint local.

#### AC-060 — Embedded Signup configura callback local

- **Dado** que um usuário conecta um número via Embedded Signup
- **Quando** o `saveOneEmbeddedProfile()`/`save_embedded()` salva o perfil
- **Então** o sistema chama `POST /{waba_id}/subscribed_apps` com
  `override_callback_uri = base_url() . '/whatsapp_webhook'` e
  `verify_token` do perfil
- **E** o campo `webhook_configuration.whatsapp_business_account` do número
  passa a apontar para o domínio local

#### AC-061 — Fluxo manual (save_official) configura callback local

- **Dado** que um usuário conecta via fluxo manual (`save_official`)
- **Quando** o perfil é salvo
- **Então** o sistema também configura o `override_callback_uri` para o domínio
  local (mesmo endpoint, mesmo verify_token informado)

#### AC-062 — Verify token corresponde ao endpoint local

- **Dado** que o sistema configura a callback por WABA
- **Quando** a Meta faz a verificação (`GET /whatsapp_webhook?hub.mode=subscribe&hub.verify_token=...`)
- **Então** o endpoint local responde com o challenge (HTTP 200)
- **E** o verify_token usado é o mesmo salvo no `data.verify_token` da conta

### US-023 — Correção retroativa dos números já conectados

Como operador, quero que todos os números Cloud API já existentes nos sistemas
filhos tenham a callback corrigida para o domínio local, sem reconectar.

#### AC-063 — Script de correção por sistema

- **Dado** que um sistema tem N contas Cloud API (`login_type = 1`)
- **Quando** rodo o script de correção
- **Então** para cada conta, o sistema chama `subscribed_apps` com
  `override_callback_uri` do domínio local
- **E** o `webhook_configuration.whatsapp_business_account` de cada número passa
  a apontar para o domínio local

#### AC-064 — Idempotência

- **Dado** que o script é rodado mais de uma vez
- **Quando** a callback já está correta
- **Então** o script não gera erro nem efeito colateral (re-subscribe é seguro)

### US-024 — Endpoint local robusto

Como operador, quero que o endpoint `whatsapp_webhook` de cada sistema processe
seus próprios números sem depender de outros servidores.

#### AC-065 — Processamento local apenas

- **Dado** que um webhook chega no domínio local com `phone_number_id` local
- **Quando** o `Whatsapp_webhook.php` processa
- **Então** encontra a conta local e encaminha ao Bot Builder (já funciona)

#### AC-066 — Número não-local apenas logado

- **Dado** que chega um webhook com `phone_number_id` que não é deste sistema
- **Quando** o `Whatsapp_webhook.php` processa
- **Então** loga `No account found ... locally. Forwarding DISABLED` e não
  reencaminha (comportamento atual mantido — sem loop)

#### AC-067 — Validação de verify_token por conta individual

- **Dado** que o endpoint local valida o `hub.verify_token`
- **Quando** chega a verificação GET
- **Então** o token é validado contra o `data.verify_token` de qualquer conta
  Cloud API local (`login_type = 1`) — comportamento já existente, sem mudança

---

## 4. Plano de implementação

### 4.1 Biblioteca pura (testável)

Criar `inc/core/Whatsapp_profiles/Libraries/MetaWebhookCallback.php` (padrão
`GroupCloner`/`MetaAppIdResolver`): monta a URL de callback local, a URL do
`subscribed_apps` e os parâmetros de override. Zero cURL, zero banco.

### 4.2 Integração no controller

No `saveOneEmbeddedProfile()`, no `save_embedded()` e no `save_official()`,
após gerar o `verify_token` (e após salvar), chamar o subscribe com
`override_callback_uri`. O `verify_token` usado é o mesmo salvo em
`data.verify_token`.

### 4.3 Script de correção retroativa

Criar comando CLI (`php spark whatsapp:fix-webhook-callbacks`) que itera as
contas `login_type = 1` ativas e chama o subscribe com override.

---

## 5. Matriz de servidores (escopo)

| Servidor | Domínio | Callback desejada |
|---|---|---|
| main | zapmatic.tec.br | `https://zapmatic.tec.br/whatsapp_webhook` |
| MetaSenderPro | sender.metanivelpro.com | `https://sender.metanivelpro.com/whatsapp_webhook` |
| Kivozap | kivozap.com.br | `https://kivozap.com.br/whatsapp_webhook` |
| AgenciaMCW | chatbot.agenciamcw.com.br | `https://chatbot.agenciamcw.com.br/whatsapp_webhook` |
| Chatbut | chatbut.com.br | `https://chatbut.com.br/whatsapp_webhook` |
| IaClicks | iaclicks.com | `https://iaclicks.com/whatsapp_webhook` |
| Elite | elitecomunicacao.zapmatic.tec.br | `https://elitecomunicacao.zapmatic.tec.br/whatsapp_webhook` |
| PlusZap | pluszap.com | `https://pluszap.com/whatsapp_webhook` |

> Implementado no main primeiro; replicado aos demais conforme ativação.

---

## 6. Fora de escopo

- Migrar os clientes para apps Meta próprios (continuam no app ELITEZAP).
- Alterar o endpoint `whatsapp_webhook` de validação (já correto — AC-067).

---

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-027 | `base_url()` em cada sistema retorna o domínio público correto (com HTTPS válido). | aberta | — |
| ASM-028 | O `override_callback_uri` continua aceito na v22.0 (provado em 2026-08-18); se a Meta mudar, mantemos fallback para o fluxo atual. | aberta | — |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-018 | A correção retroativa deve rodar automaticamente no deploy de cada sistema, ou manualmente sob demanda? | aberta | — |

---

## 9. Verificação (Definition of Done)

- [ ] Conectar número novo via Embedded Signup → `webhook_configuration.whatsapp_business_account` aponta para o domínio local
- [ ] Conectar número novo via manual → idem
- [ ] Rodar script retroativo em um sistema filho → números apontam para o domínio local
- [ ] Enviar mensagem → webhook chega no domínio local → Bot Builder dispara
- [ ] Main fora do ar → sistema filho continua recebendo webhooks
- [ ] Número de outro sistema → apenas loga `Forwarding DISABLED`

---

## 10. Referências

- Commit `42a818f5` — desativou reencaminhamento entre plataformas (loop prevention)
- Graph API: `POST /{waba_id}/subscribed_apps` com `override_callback_uri` (provado 2026-08-18)
