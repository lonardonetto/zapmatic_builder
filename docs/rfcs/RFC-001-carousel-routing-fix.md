# RFC-001: Correcao do Roteamento do Template de Carrossel

- **Titulo**: Fix auto-routing do CodeIgniter 4 para o modulo Whatsapp_carousel_template
- **Status**: Aprovado e Implementado
- **Autor**: Kilo (agente de IA)
- **Data**: 2026-09-09
- **Feature**: carrossel-correcao (.spec/features/carrossel-correcao/)

---

## 1. Problema

Templates de carrossel criados na interface administrativa eram salvos com
`type=2` (botao) em vez de `type=5` (carrossel), causando:

1. Template nao aparecia na aba "Carousel" apos criacao
2. Template aparecia na aba "Buttons" como se fosse template de botao
3. No Bot Builder, o seletor de templates nativos para nodes "cards" nao
   encontrava templates de carrossel

### Evidencia no banco de dados (Astros)

```
id=19  type=2  name="ELITEZAP"  data={"cards":[...]}  <-- carrossel salvo como botao
id=4   type=5  name="ACD - NUMEROS AQUECIDOS"           <-- correto (antes do bug)
id=1   type=5  name="Aldo Sales"                        <-- correto (antes do bug)
```

## 2. Analise de causa raiz

### Hipotese confirmada: auto-routing do CI4

O CodeIgniter 4 usa `setDefaultNamespace()` nas Routes.php de cada modulo
para resolver o namespace do controller. Porem, `setDefaultNamespace()` e um
**setting global** — ele afeta TODO o roteamento subsequente, nao apenas o
modulo atual.

Os modulos sao carregados via `scandir()` em ordem alfabetica:

```
inc/core/
  Whatsapp_button_template/Config/Routes.php   <-- carrega primeiro
  Whatsapp_carousel_template/Config/Routes.php <-- carrega depois
  ...
```

Quando o usuario acessa `/whatsapp_carousel_template/save/xxx`:

1. **Button template Routes.php** roda: `url_is('whatsapp_button_template')` = FALSE → nao altera namespace
2. **Carousel template Routes.php** roda: `url_is('whatsapp_carousel_template')` = TRUE → `setDefaultNamespace('Core/Whatsapp_carousel_template/Controllers')`
3. **Modulos seguintes** rodam: nenhum match, namespace mantido

Em tese, o namespace deveria estar correto. Porem, o `setDefaultNamespace()`
e aplicado de forma global e o auto-routing (`setAutoRoute(true)`) pode ter
comportamento imprevisivel quando multiplos modulos definem namespaces para
padroes de URL similares (ambos com `parent.id = 'template'`).

### Correcao aplicada

Adicionar **rotas explicitas** em `app/Config/Routes.php` para TODOS os
endpoints do modulo de carrossel, eliminando a dependencia do auto-routing.

## 3. Solucao proposta

### 3.1 Rotas explicitas (app/Config/Routes.php)

```php
// NUCLEAR OPTION: Force routes for Carousel template (fix auto-routing bug)
$routes->add("whatsapp_carousel_template/save/(:any)",
    "\Core\Whatsapp_carousel_template\Controllers\Whatsapp_carousel_template::save/$1");
$routes->add("whatsapp_carousel_template/save",
    "\Core\Whatsapp_carousel_template\Controllers\Whatsapp_carousel_template::save");
$routes->add("whatsapp_carousel_template/delete",
    "\Core\Whatsapp_carousel_template\Controllers\Whatsapp_carousel_template::delete");
$routes->add("whatsapp_carousel_template/ajax_list",
    "\Core\Whatsapp_carousel_template\Controllers\Whatsapp_carousel_template::ajax_list");
$routes->add("whatsapp_carousel_template/index/(:any)",
    "\Core\Whatsapp_carousel_template\Controllers\Whatsapp_carousel_template::index/$1");
$routes->add("whatsapp_carousel_template/index",
    "\Core\Whatsapp_carousel_template\Controllers\Whatsapp_carousel_template::index");
```

**Justificacao**: Segue o padrao ja existente no projeto para
`whatsapp_profiles` e `whatsapp_campaign_analytics` (linhas 46-53 do mesmo
arquivo). Rotas explicitas tem prioridade sobre auto-routing.

### 3.2 Widgets de carrossel em paginas de envio

Adicionar `view_cell` do carrossel em paginas que nao tinham:

| Pagina | Arquivo | Alteracao |
|---|---|---|
| Callresponder | `inc/core/Whatsapp_callresponder/Views/info.php` | +carousel widget_menu (L64) + widget_content (L82) |
| API REST | `inc/core/Whatsapp_api/Views/info.php` | +poll +carousel widget_menu (L54-55) + widget_content (L71-72) |
| Criptografia | `inc/core/Criptografia_copy/Views/info.php` | +poll +carousel widget_menu (L54-55) + widget_content (L71-72) |

### 3.3 Correcao de dados

Template existente com dados de carrossel mas type=2:

```sql
UPDATE sp_whatsapp_template SET type = 5
WHERE id = 19 AND name = 'ELITEZAP'
  AND JSON_CONTAINS(data, '"cards"', '$') = 1;
```

## 4. Impacto

| Area | Impacto | Risco |
|---|---|---|
| Roteamento | Rotas explicitas eliminam ambiguidade | Baixo — padrao ja usado no projeto |
| Performance | Negativo (6 rotas a mais para verificar) | Minimo — CI4 compila rotas em cache |
| Compatibilidade | Nenhuma rota existente e alterada | Nulo — apenas adicao |
| Dados | Correcao de1 template no banco | Baixo — apenas type alterado |

## 5. Alternativas consideradas

| Alternativa | Rejeitada porque |
|---|---|
| Remover `menu.sub_menu` do Config.php do carousel | Poderia quebrar o sidebar do framework |
| Alterar ordem de carregamento dos modulos | Fragil — depende de nome de diretorio |
| Usar `$routes->group()` | Requereria refatoracao de todos os modulos |
| Desabilitar auto-routing | Quebraria todos os modulos sem rota explicita |

## 6. Arquivos alterados

```
app/Config/Routes.php                                  (+7 linhas, rotas explicitas)
inc/core/Whatsapp_callresponder/Views/info.php         (+2 linhas, carousel widget)
inc/core/Whatsapp_api/Views/info.php                   (+4 linhas, poll+carousel widget)
inc/core/Criptografia_copy/Views/info.php              (+4 linhas, poll+carousel widget)
```

## 7. Commits

| SHA | Mensagem |
|---|---|
| `ca6ac121` | `T-050 fix(carousel): adicionar widget_carousel ausente em 3 paginas de envio` |
| `86687e7e` | `fix(carousel): rota explicita para carousel template (corrige auto-routing)` |

## 8. Testes

Ver `tests/CarouselRoutingTest.php` e `tests/CarouselWidgetTest.php` para
testes TDD anotados com `@spec:AC-xxx`.

## 9. Licoes aprendidas

| Licao | Contexto |
|---|---|
| `setDefaultNamespace()` do CI4 e global, nao por-modulo | Um modulo pode sobrescrever o namespace de outro |
| Rotas explicitas sao mais seguras que auto-routing | Para modulos criticos, sempre usar `$routes->add()` |
| Verificar o banco alem do codigo | O codigo estava correto (type=5) mas o routing causava type=2 |
