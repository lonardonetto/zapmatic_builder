# Debug capture multi buttons

Status: OPEN

Objetivo: tentar capturar payload real de mensagens interativas com mais de 3 botões enviadas/recebidas pelas conexões `5521970402529` e `5521968666544`, sem alterar lógica de negócio.

Hipóteses:
1. O payload já está salvo em logs de PM2 ou arquivos `.log` do app.
2. O payload foi salvo em alguma tabela de histórico do banco.
3. A mensagem usa `nativeFlowMessage` com `single_select` ou estrutura diferente de `quick_reply`.
4. O envio que aparenta vários botões é uma lista/menu estilizada, não botões quick reply.
5. Será necessário instrumentar temporariamente o handler de mensagens para capturar o payload bruto.
