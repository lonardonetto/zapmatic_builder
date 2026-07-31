# Debug carousel-baileys

Status: [OPEN]

## Sintoma
Bloco Cards/Carrossel do Bot Builder no Baileys não envia mensagem. Antes enviava apenas o título/texto principal.

## Hipóteses
1. `relayMessage` falha por formato inválido do `interactiveMessage`.
2. `prepareWAMessageMedia` falha com a URL da imagem e aborta o carrossel.
3. Endpoint `bot_builder_send` recebe `cards` vazio ou payload diferente do esperado.
4. Erro interno ocorre no Node, mas não aparece claramente no PHP/fluxo.
5. A versão atual do Baileys não aceita o formato de carrossel usado.

## Plano
1. Adicionar instrumentação mínima no branch `carousel` do Node.
2. Reproduzir envio pelo usuário.
3. Analisar logs/runtime.
4. Aplicar correção mínima baseada em evidência.
5. Validar novamente e limpar instrumentação após confirmação.
