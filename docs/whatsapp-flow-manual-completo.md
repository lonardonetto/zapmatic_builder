# Manual Completo de WhatsApp Flow

## 1. O que e um Flow

Flow e um mini aplicativo dentro do WhatsApp. Ele pode abrir:

- tela de boas-vindas
- imagem de capa
- menu principal
- submenu
- formulario
- confirmacao final

Ele nao e a mesma coisa que:

- lista
- botoes
- carrossel

Esses recursos sao mais simples. O Flow e mais rico e mais guiado.

## 2. Estrutura profissional recomendada

Um Flow profissional costuma seguir esta ordem:

1. Tela inicial
2. Menu principal
3. Submenu por categoria
4. Formulario final
5. Confirmacao

Exemplo:

1. Boas-vindas
2. Escolha a area: Financeiro, Suporte, Comercial
3. Escolha a acao: Emitir fatura, Segunda via, Falar com atendente
4. Preencha nome, telefone e detalhes
5. Enviar

## 3. Como montar bem cada parte

### Capa ou imagem inicial

Use quando quiser:

- dar contexto visual
- reforcar marca
- destacar a area do atendimento

Boas praticas:

- imagem limpa
- pouco texto
- foco em leitura no celular
- evitar poluicao visual

### Titulo principal

O titulo precisa dizer o que o usuario vai fazer.

Exemplos bons:

- Escolha uma opcao para continuar
- Finalize sua solicitacao
- Informe seus dados

### Texto de apoio

O texto curto abaixo do titulo deve orientar.

Exemplos:

- Selecione a area desejada
- Preencha os dados abaixo para prosseguir
- Escolha uma categoria e depois uma acao

### Menu principal

Use poucas opcoes e nomes claros.

Bom:

- Financeiro
- Suporte
- Comercial

Ruim:

- Clique aqui
- Opcao 1
- Continuar atendimento

### Submenu

Cada item deve explicar o resultado esperado.

Bom:

- Emitir fatura
- Consultar debitos
- Atualizar cadastro

Se possivel, use descricao curta embaixo do item.

### Formulario

Peça so o necessario.

Campos mais comuns:

- nome completo
- telefone
- CPF ou contrato
- detalhes adicionais

Boas praticas:

- marque como obrigatorio so o essencial
- nao faca formularios longos demais
- use labels objetivas

### Botao final

O texto do botao deve indicar a acao.

Exemplos:

- Enviar
- Continuar
- Confirmar solicitacao

## 4. Como pensar nas categorias

Categorias ajudam na organizacao do produto e da Meta.

Use uma categoria principal coerente com o objetivo do Flow:

- CUSTOMER_SUPPORT
- LEAD_GENERATION
- APPOINTMENT_BOOKING
- OTHER

Se tiver duvida:

- use `OTHER` para testes
- use a categoria real quando o processo estiver maduro

## 5. Como testar no Single Message

No `Send Single Message > Flow`, hoje o caminho estavel e:

- selecionar o Flow publicado
- usar `Published`
- usar `Navigate directly to a screen`
- preencher `Message body`
- opcionalmente definir `Header` e `Footer`
- deixar a `Entry screen` preenchida automaticamente pelo sistema

Observacao importante:

- no `Single Message`, nao use uma tela intermediaria como `MENU` se o Flow comeca em `WELCOME`
- se a `Entry screen` estiver errada, a Meta pode responder `(#131009) Parameter value is not valid`
- a tela certa precisa ser a tela inicial real do Flow publicado

## 6. Diferenca entre os 2 modos de abertura

### Navigate directly to a screen

Este e o modo recomendado hoje para envio real no sistema.

Ele:

- abre o Flow em uma tela especifica
- pode mandar JSON inicial
- esta validado no envio do Single Message

### Open via encrypted endpoint (data exchange)

Este modo depende mais da validacao da Meta.

Ele:

- tenta abrir o Flow pelo endpoint criptografado
- deixa a primeira resposta com o endpoint
- e mais avancado

Hoje, no ambiente atual, a Meta ainda esta respondendo `(#131009) Parameter value is not valid` neste modo no envio do Single.

Entao, para producao e teste funcional:

- use `navigate`

## 7. Como deixar o Flow mais profissional

- comece com uma tela de boas-vindas objetiva
- use imagem apenas quando ela realmente ajuda
- crie menus curtos
- use nomes claros nas opcoes
- leve o usuario ao formulario final sem excesso de passos
- termine com mensagem de confirmacao

## 8. Modelo simples e profissional

### Tela 1

- titulo: Bem-vindo ao atendimento
- texto: Escolha uma opcao para continuar
- imagem: opcional

### Tela 2

- menu: Financeiro, Cadastro, Suporte

### Tela 3

- submenu da categoria escolhida

### Tela 4

- formulario com:
- nome completo
- telefone
- detalhes adicionais

### Tela 5

- confirmacao:
- Solicitacao enviada com sucesso

## 9. Checklist antes de publicar

- nome do Flow claro
- slug simples
- categoria correta
- imagem carregando
- telas encadeadas corretamente
- formulario final funcionando
- textos curtos e claros
- endpoint configurado quando necessario
- teste no Single com `navigate`

## 10. Resumo pratico

Se quiser resultado rapido e estavel:

1. Monte a estrutura visual
2. Publique o Flow
3. Teste no `Single Message`
4. Use `Published`
5. Use `Navigate directly to a screen`

Se quiser usar `data_exchange`, trate como modo avancado e de validacao especifica da Meta.
