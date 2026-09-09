# Tasks: Carrossel correcao

> feature: carrossel-correcao

## T-049 — Investigar causa raiz: debug save, redirect e listagem do carrossel [concluida]
- Refs: US-036, AC-095, AC-096, AC-097
- Arquivos: inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php, inc/core/Whatsapp/Models/WhatsappModel.php
- Notas: Adicionar logs temporarios de debug para rastrear: (1) se save() insere type=5 corretamente no banco, (2) qual URL get_module_url() retorna apos o save, (3) se o get_list() do model recebe type=5 do banco, (4) se widget_content() e chamado e retorna HTML. Verificar se a permissao whatsapp_carousel_template esta habilitada. Verificar git log para commits recentes que alteraram os modulos de template.
- Esforço: medio

## T-050 — Corrigir renderizacao das abas de template no sidebar [concluida]
- Refs: US-036, AC-095, AC-097
- Arquivos: inc/core/Whatsapp_carousel_template/Views/widget/content.php, inc/core/Whatsapp_carousel_template/Views/widget/menu.php, inc/core/Whatsapp_button_template/Views/widget/content.php, inc/core/Whatsapp_button_template/Views/widget/menu.php
- Notas: Garantir que o tab-pane #wa_carousel tenha id unico e nao conflite com #wa_button. Verificar se o radio name="type" nos menus causa conflito quando ambos os widgets estao na mesma pagina. Se necessario, usar name="type_carousel" para o radio do carrossel. Verificar se o redirect apos save() ativa a aba correta (result.type==5 ativa carousel tab).
- Esforço: alto

## T-051 — Garantir isolamento entre queries de carrossel e botoes [concluida]
- Refs: US-036, AC-096, AC-100, AC-101, US-038
- Arquivos: inc/core/Whatsapp_carousel_template/Models/Whatsapp_carousel_templateModel.php, inc/core/Whatsapp_button_template/Models/Whatsapp_button_templateModel.php, inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php
- Notas: Auditar que as queries SQL nos Models filtram estritamente por type=5 (carousel) e type=2 (button). Confirmar que o endpoint ajax_list do carousel usa exclusivamente o model do carousel. Validar que a paginacao AJAX e independente por modulo.
- Esforço: baixo

## T-052 — Corrigir edicao de template de carrossel [concluida]
- Refs: US-037, AC-098, AC-099
- Arquivos: inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php, inc/core/Whatsapp_carousel_template/Views/update.php
- Notas: Verificar que o case 'update' no index() do controller busca com type=5. Confirmar que o formulario renderiza cards, botoes (quick_reply, cta_url, cta_call, cta_copy, cta_catalog, payment_info, review_and_pay) e campos de midia corretamente.
- Esforço: baixo

## T-053 — Validar compatibilidade com Bot Builder (inspector-templates.js) [concluida]
- Refs: US-039, AC-102, AC-103, AC-104
- Arquivos: inc/core/Bot_builder/Assets/js/builder/inspector-templates.js, inc/core/Bot_builder/Controllers/Bot_builder.php, inc/core/Whatsapp/Controllers/Whatsapp.php
- Notas: Confirmar que nativeTypeForNode('cards') retorna 5, que loadNativeTemplatesIntoSelect(envia type=5) carrega templates corretos, que renderNativeTemplatePreview(type=5) exibe cards e nao botoes, que native_template_create_url(5) aponta para whatsapp_carousel_template/index/update, que o endpoint native_templates aceita type=5.
- Esforço: baixo

## T-054 — Testes end-to-end e remocao de debug [pendente]

- Refs: US-036, US-037, US-038, US-039, AC-095, AC-096, AC-097, AC-098, AC-099, AC-100, AC-101, AC-102, AC-103, AC-104
- Arquivos: inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php, inc/core/Whatsapp/Controllers/Whatsapp.php
- Notas: Executar cenario completo: (1) criar template de carrossel com 2 cards, (2) verificar que aparece na aba Carousel, (3) verificar que NAO aparece na aba Buttons, (4) editar template e verificar cards preservados, (5) testar Bot Builder seletor nativo com type=5, (6) testar paginacao AJAX. Remover todos os logs de debug de T-049.
- Esforço: medio
