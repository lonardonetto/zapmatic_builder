# Tasks: Carrossel correcao

> feature: carrossel-correcao

## T-049 — Investigar causa raiz: diff git, inspecao de routing [concluida]

- Refs: US-036, AC-095, AC-096, AC-097
- Arquivos: app/Config/Routes.php, inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php, inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php
- Notas: git diff 7d3b0f20..HEAD mostrou que o codigo do carrossel NUNCA mudou. Causa raiz: auto-routing do CI4 nao resolvia namespace para carousel. Banco do Astros confirmou template id=19 com dados de carrossel mas type=2.
- Esforço: medio

## T-050 — Adicionar widget_carousel em 3 paginas de envio [concluida]

- Refs: US-040, AC-106, AC-107, AC-108
- Arquivos: inc/core/Whatsapp_callresponder/Views/info.php, inc/core/Whatsapp_api/Views/info.php, inc/core/Criptografia_copy/Views/info.php
- Notas: widget_menu e widget_content do carrossel (e poll onde faltava) adicionados. Commit ca6ac121.
- Esforço: baixo

## T-051 — Adicionar rotas explicitas para carousel template [concluida]

- Refs: US-036, AC-095, AC-097
- Arquivos: app/Config/Routes.php
- Notas: 6 rotas `$routes->add()` para save, delete, ajax_list, index do carrossel. NUCLEAR OPTION seguindo padrao de whatsapp_profiles. Commit 86687e7e.
- Esforço: baixo

## T-052 — Corrigir template existente com type errado no banco [concluida]

- Refs: US-036, AC-105
- Arquivos: (migracao manual no banco do Astros)
- Notas: Template id=19 "ELITEZAP" tinha dados de carrossel (cards) mas type=2. Corrigido para type=5 via UPDATE direto.
- Esforço: baixo

## T-053 — Validar compatibilidade com Bot Builder [concluida]

- Refs: US-039, AC-102, AC-103, AC-104
- Arquivos: inc/core/Bot_builder/Assets/js/builder/inspector-templates.js, inc/core/Bot_builder/Controllers/Bot_builder.php
- Notas: nativeTypeForNode('cards')=5, native_templates?type=5, native_template_create_url(5) tudo correto. Nenhuma alteracao necessaria.
- Esforço: baixo

## T-054 — Deploy para Astros e verificacao [concluida]

- Refs: US-036, AC-095, AC-096, AC-097, AC-105
- Arquivos: (deploy via rsync + cp direto)
- Notas: Routes.php deployado no Astros. Template id=19 corrigido no banco. PHP syntax OK. Servicos Go e PM2 reiniciados.
- Esforço: medio

## T-055 — Escrever testes TDD anotados com @spec [em-andamento]

- Refs: US-036, US-037, US-038, US-039, US-040, AC-095..AC-108
- Arquivos: tests/CarouselRoutingTest.php, tests/CarouselWidgetTest.php
- Notas: Testes PHP unit para verificar (1) rota save vai para controller correto, (2) type=5 no banco, (3) widget presente em todas as paginas, (4) ajax_list filtra type=5, (5) edicao preserva cards.
- Esforço: alto
