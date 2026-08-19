# Tasks: Webhook Cloud API independente por sistema

> feature: cloud-api-webhook-independente

## T-028 — Criar biblioteca pura MetaWebhookCallback [concluida]
- Refs: US-022, US-023, AC-060, AC-061, AC-063, AC-064
- Arquivos: inc/core/Whatsapp_profiles/Libraries/MetaWebhookCallback.php
- Notas: Monta `buildLocalCallbackUrl(baseUrl)`, `buildOverrideUrl(graphVersion, wabaId)`,
  `buildOverrideParams(localCallback, verifyToken)` e `isCorrect(webhookConfig, localCallback)`.
  Zero cURL, zero banco. Padrão de `MetaAppIdResolver`.

## T-029 — Integrar override no save_embedded/saveOneEmbeddedProfile [concluida]
- Refs: US-022, AC-060, AC-062
- Arquivos: inc/core/Whatsapp_profiles/Controllers/Whatsapp_profiles.php
- Notas: Após gerar `verify_token`, chamar `subscribed_apps` com `override_callback_uri`
  usando o MESMO `verify_token` salvo em `data.verify_token`. Logar resultado.

## T-030 — Integrar override no save_official [concluida]
- Refs: US-022, AC-061, AC-062
- Arquivos: inc/core/Whatsapp_profiles/Controllers/Whatsapp_profiles.php
- Notas: Após validar credenciais e antes/logo após salvar, chamar o subscribe com
  override usando o `verify_token` informado.

## T-031 — Script de correção retroativa (CLI) [concluida]
- Refs: US-023, AC-063, AC-064
- Arquivos: app/Commands/WhatsappFixWebhookCallbacks.php
- Notas: Comando `whatsapp:fix-webhook-callbacks`. Itera contas `login_type=1` ativas,
  lê `waba_id`/`verify_token`/`token` do `data` e chama subscribe com override.
  Idempotente (re-subscribe é seguro).

## T-032 — Testes PHPUnit do MetaWebhookCallback [concluida]
- Refs: AC-060, AC-061, AC-063, AC-064
- Arquivos: tests/phpunit/MetaWebhookCallbackTest.php
- Notas: Testes anotados com `@spec:AC-xxx` cobrindo montagem de URL, parâmetros e
  checagem `isCorrect`.

## T-033 — Extrair política de webhook para MetaWebhookPolicy [concluida]
- Refs: US-024, AC-065, AC-066, AC-067
- Arquivos: inc/core/Whatsapp_profiles/Libraries/MetaWebhookPolicy.php, inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php
- Notas: `decideAction(foundLocally)` (process/log_disabled) e `verifyTokenMatches(tokens, incoming)`.
  Controller passa a usar a classe sem mudar comportamento observável.

## T-034 — Testes PHPUnit do MetaWebhookPolicy + verify_token [concluida]
- Refs: AC-062, AC-065, AC-066, AC-067
- Arquivos: tests/phpunit/MetaWebhookPolicyTest.php
- Notas: Testes anotados cobrindo decisão local/não-local e validação de verify_token.
