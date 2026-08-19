# Tasks: Fix onboarding meta

> feature: fix-onboarding-meta

## T-022 — Criar resolvedor central de App ID/Secret da Meta [concluida]
- Refs: US-020, AC-053, AC-054, AC-055, AC-056, AC-058
- Arquivos: inc/core/Whatsapp_profiles/Libraries/MetaAppIdResolver.php
- Notas: Classe estática pura (zero CodeIgniter, zero banco), no padrão de
  `GroupCloner`. `resolve()` para App ID e `resolveSecret()` para App Secret,
  com a cadeia: valor específico válido > fallback legado válido > fallback fixo/vazio.

## T-023 — Usar o resolvedor no controller (save_embedded) [concluida]
- Refs: US-020, AC-056
- Arquivos: inc/core/Whatsapp_profiles/Controllers/Whatsapp_profiles.php
- Notas: Substituir `get_option('meta_app_id','') ?: get_option('facebook_login_app_id','')`
  + fallback manual por chamada a `MetaAppIdResolver::resolve(...)`. Manter `app_secret`
  e demais lógica intactos.

## T-024 — Usar o resolvedor na view (oauth) [concluida]
- Refs: US-020, AC-056
- Arquivos: inc/core/Whatsapp_profiles/Views/oauth.php
- Notas: Substituir a resolução de `$fb_app_id` pela chamada ao resolvedor. Garantir
  que o valor emitido em `var FB_APP_ID` seja sempre numérico.

## T-025 — Garantir que FB.login só rode após SDK inicializado [concluida]
- Refs: US-021, AC-057
- Arquivos: inc/core/Whatsapp_profiles/Views/oauth.php
- Notas: Ajustar `launchEmbeddedSignup`/`doEmbeddedLogin` para só chamar `FB.login`
  quando `window.FB` existir e o SDK estiver pronto, eliminando a corrida
  `FB.login() called before FB.init()`.

## T-026 — Testes PHPUnit do resolvedor e do gate de login [concluida]
- Refs: AC-053, AC-054, AC-055, AC-057, AC-058, AC-059
- Arquivos: tests/phpunit/MetaAppIdResolverTest.php, tests/phpunit/MetaLoginGateTest.php
- Notas: Testes anotados com `@spec:AC-xxx`. O gate de login extraído como função
  estática pura testável (ex.: `MetaLoginGate::canLogin(bool $fbReady)`).

## T-027 — Validar App ID/Secret no formulário de Configurações [concluida]
- Refs: US-020, AC-059
- Arquivos: inc/core/Social_network_settings/Controllers/Social_network_settings.php
- Notas: No `save()`, recusar `meta_app_id` não-numérico e `meta_app_secret` fora
  de 32 hex (vazio é permitido = usar fallback). Reusa `MetaAppIdResolver::isValid`
  e `isValidSecret`.
