# Plano de execução — carrossel-correcao

> gerado por `onp-spec plano` em 2026-09-09 16:55 — NÃO edite à mão;
> mudou tasks.md ou a config? Regenere: `onp-spec plano carrossel-correcao`

## Resumo — o que vai acontecer

- **6 tarefa(s) pendente(s)**: 6 em 2 faixa(s) paralela(s) + 0 sequencial(is)
- **1 faixa = 1 worktree + 1 branch + 1 janela de contexto limpa** — faixas não compartilham nenhum arquivo entre si
- prefere outra seleção ou uma após a outra? Regenere com `onp-spec plano carrossel-correcao --paralelizar T-xxx,T-yyy` ou `--sequencial`
- tudo acontece na branch de trabalho `spec/carrossel-correcao`; levar para a main é decisão sua

## Faixas e ondas

### Onda 1 — faixa-1 ∥ faixa-2

#### faixa-1 — branch `spec/carrossel-correcao-faixa-1` — worktree `../onp-worktrees/app_zapmatic_app-carrossel-correcao-faixa-1`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-049 | Investigar causa raiz: debug save, redirect e listagem do carrossel | `claude-sonnet-5` | medium | `inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php`, `inc/core/Whatsapp/Models/WhatsappModel.php` |
| T-051 | Garantir isolamento entre queries de carrossel e botoes | `claude-sonnet-5` | low | `inc/core/Whatsapp_carousel_template/Models/Whatsapp_carousel_templateModel.php`, `inc/core/Whatsapp_button_template/Models/Whatsapp_button_templateModel.php`, `inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php` |
| T-052 | Corrigir edicao de template de carrossel | `claude-sonnet-5` | low | `inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php`, `inc/core/Whatsapp_carousel_template/Views/update.php` |
| T-053 | Validar compatibilidade com Bot Builder (inspector-templates.js) | `claude-sonnet-5` | low | `inc/core/Bot_builder/Assets/js/builder/inspector-templates.js`, `inc/core/Bot_builder/Controllers/Bot_builder.php`, `inc/core/Whatsapp/Controllers/Whatsapp.php` |
| T-054 | Testes end-to-end e remocao de debug | `claude-sonnet-5` | medium | `inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php`, `inc/core/Whatsapp/Controllers/Whatsapp.php` |

#### faixa-2 — branch `spec/carrossel-correcao-faixa-2` — worktree `../onp-worktrees/app_zapmatic_app-carrossel-correcao-faixa-2`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-050 | Corrigir renderizacao das abas de template no sidebar | `claude-sonnet-5` | high | `inc/core/Whatsapp_carousel_template/Views/widget/content.php`, `inc/core/Whatsapp_carousel_template/Views/widget/menu.php`, `inc/core/Whatsapp_button_template/Views/widget/content.php`, `inc/core/Whatsapp_button_template/Views/widget/menu.php` |

## Gestão de branches e commits

1. branch de trabalho `spec/carrossel-correcao` criada do ponto atual (se ainda não existir)
2. cada faixa nasce dela como branch própria e roda no seu worktree — **1 tarefa = 1 commit** (`T-xxx feature: título`)
3. terminou a onda → merge `--no-ff` de cada faixa de volta, na ordem; conflito interrompe a faixa e pede resolução humana
4. faixa mesclada → worktree removido, branch apagada, tarefa marcada `[concluida]` no tasks.md
5. gate final na branch de trabalho: `onp-spec verify carrossel-correcao` + `onp-spec audit --ci` — **exit 0 ou não está pronto**

## Como executar

### ▶ Execução — Claude Code headless

```bash
bash .spec/features/carrossel-correcao/executar-tarefas.sh
```

Cada faixa roda `claude -p` com **janela de contexto limpa**, no seu worktree, com
`--model` e `--effort` já definidos por tarefa e permissões `acceptEdits`. Os prompts exatos estão
embutidos no script — quer rodar uma faixa na mão, é só copiá-los de lá.
Logs: `../onp-worktrees/app_zapmatic_app-carrossel-correcao-logs/`.

### 📣 Acompanhamento — tabela + resumo no chat (a cada 1 min)

O script roda em **background**: o agente AVISA o usuário antes de iniciar e,
enquanto roda, posta no chat a cada ~1 minuto a **tabela de andamento** (qual
tarefa está rodando, qual não está, o que concluiu/falhou) junto com o
**resumo geral de andamento** (escrito por IA; sem IA, o motor resume). Ao
final, o usuário recebe o resumo completo da execução. A qualquer momento:

```bash
onp-spec resumo carrossel-correcao --tabela   # a tabela de andamento
onp-spec resumo carrossel-correcao            # o resumo em texto
```

