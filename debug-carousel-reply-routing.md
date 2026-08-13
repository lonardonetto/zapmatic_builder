# Debug carousel-reply-routing

Status: [OPEN]

## Sintoma
Carrossel envia corretamente, mas ao selecionar uma opção o fluxo não dá reply. Se o usuário digita a mesma palavra no chat, o fluxo responde.

## Hipóteses
1. O clique do carrossel chega no webhook PHP com `Text` vazio.
2. O clique chega com `ButtonId` diferente do ID esperado pelo roteamento.
3. O clique não chega no webhook do Bot Builder.
4. A sessão ativa existe, mas está em bloco diferente do carrossel.
5. As edges do bloco de carrossel estão salvas com `condition_value` diferente dos labels/IDs gerados.

## Plano
1. Coletar evidência dos logs existentes e banco.
2. Simular internamente o roteamento com payloads prováveis.
3. Só aplicar correção após identificar a causa.
