# Spec: Fix onboarding meta

> feature: fix-onboarding-meta
> status: rascunho

## Contexto

O onboarding automático da Meta (Embedded Signup / Facebook SDK) não abre. No
console do navegador aparecem dois erros: `✅ Facebook SDK inicializado com App
ID: admind` e `FB.login() called before FB.init()`.

A causa raiz está no **App ID inválido** gravado na opção global `meta_app_id`
(valor `admind`), que vence o fallback válido `facebook_login_app_id`
(`763786439394524`) por ser "não-vazio". Com o App ID inválido, o `FB.init()`
falha silenciosamente no SDK e o `FB.login()` subsequente reporta "called
before FB.init()".

Este fix garante que o App ID usado no onboarding seja **sempre um App ID
numérico válido**, tanto no frontend (SDK) quanto no backend (troca do code
por token), sem quebrar o fallback existente nem as conexões já ativas.

## Histórias

### US-020 — Onboarding da Meta usa um App ID válido

Como usuário que conecta uma conta Cloud API, quero que o onboarding use sempre
um App ID numérico válido (mesmo que `meta_app_id` esteja gravado com lixo),
para que o popup de autenticação da Meta abra e conclua a conexão.

#### AC-053 — Valor não-numérico em `meta_app_id` é ignorado em favor do fallback válido

- **Dado** `meta_app_id = "admind"` e `facebook_login_app_id = "763786439394524"`
- **Quando** o App ID do onboarding é resolvido
- **Então** o resultado é `763786439394524` (o valor inválido é descartado)

#### AC-054 — `meta_app_id` numérico válido tem prioridade sobre o fallback

- **Dado** `meta_app_id = "123456789012345"` e `facebook_login_app_id = "763786439394524"`
- **Quando** o App ID do onboarding é resolvido
- **Então** o resultado é `123456789012345` (prioridade do campo específico preservada)

#### AC-055 — Ambos vazios ou inválidos caem no fallback fixo numérico

- **Dado** `meta_app_id` vazio (ou não-numérico) e `facebook_login_app_id` vazio
- **Quando** o App ID do onboarding é resolvido
- **Então** o resultado é o fallback fixo `763786439394524`

#### AC-056 — Backend e frontend usam o MESMO resolvedor de App ID

- **Dado** o resolvedor de App ID centralizado
- **Quando** o controller (`save_embedded`) e a view (`oauth`) montam o App ID
- **Então** ambos chamam a mesma função de resolução (sem duplicar a regra)

#### AC-058 — Secret inválido em `meta_app_secret` é ignorado em favor do fallback válido

- **Dado** `meta_app_secret = "LeoN1982PP@@"` (lixo) e `facebook_login_app_secret` = secret hex de 32 chars
- **Quando** o App Secret do onboarding é resolvido
- **Então** o resultado é o secret hex válido (o valor inválido é descartado)
- **E** ambos vazios/inválidos retornam string vazia

#### AC-059 — Formulário de Configurações recusa App ID/Secret inválidos

- **Dado** o formulário "Global Meta Configuration" aberto
- **Quando** o usuário salva `meta_app_id` não-numérico ou `meta_app_secret` fora do formato 32 hex
- **Então** o save é recusado com mensagem de erro (o valor inválido não é gravado)
- **E** campos vazios são aceitos (significam "usar fallback")

### US-021 — SDK do Facebook só dispara login após inicializar

Como usuário do onboarding, quero que o login da Meta só seja chamado depois do
SDK estar inicializado, para que não apareça `FB.login() called before
FB.init()` e o popup abra de primeira.

#### AC-057 — `FB.login` nunca é chamado antes da inicialização do SDK

- **Dado** o SDK do Facebook carregando
- **Quando** o botão de conectar é acionado
- **Então** o código aguarda o SDK estar pronto (`FB` inicializado) antes de
  chamar `FB.login`

## Fora de escopo

- Os erros `403` das imagens `*.jpg` (conteúdo do painel/report) — não têm
  relação com o onboarding.

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-025 | App ID válido da Meta é sempre numérico (10+ dígitos, ex.: `763786439394524`); qualquer outro valor deve ser descartado. | aberta | — |
| ASM-026 | O valor `admind` em `meta_app_id` foi gravado por erro de digitação e não há outro lugar que dependa dele como texto. | aberta | — |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-017 | Devemos adicionar validação no formulário de Configurações para impedir salvar `meta_app_id` não-numérico? | respondida | Sim — implementado no `Social_network_settings::save()` (AC-059). |
