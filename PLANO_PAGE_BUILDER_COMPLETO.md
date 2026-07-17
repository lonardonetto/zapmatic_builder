# 🚀 Page Builder - Plano Completo de Implementação

## Sistema de Landing Pages Editáveis (100% em português)

> Data: 16/07/2026
> Status: Plano detalhado para implementação
> Sistema: CodeIgniter 4 + Bootstrap 5

---

## 1. VISÃO GERAL

Substituir as views PHP estáticas dos temas frontend por um **construtor visual de páginas**, onde o administrador monta e edita cada seção através de formulários simples, salvando tudo no banco de dados como JSON.

**Como funciona hoje:**
```
Tema Stackdark → Views/home.php → PHP fixo → editar manualmente
```
**Como vai funcionar:**
```
Admin → Page Builder → Formulários → JSON no banco → Render dinâmico
```

---

## 2. O QUE MUDA E O QUE FICA

### ✅ FICA EXATAMENTE IGUAL (NÃO MEXE):
```
/login          → Autenticação
/signup         → Cadastro
/recovery_password → Recuperar senha
/activation     → Ativação de conta
/resend_activation → Reenviar ativação
/admin/*        → Painel administrativo
/checkout       → Página de pagamento (já funciona)
```

### ✅ TORNA-SE EDITÁVEL:
```
/               → HOME (página principal)
/features       → Funcionalidades
/pricing        → Preços e planos
/faqs           → FAQ
/blog           → Blog (lista)
/blog/{slug}    → Post individual
/privacy_policy → Política de privacidade
/terms_of_service → Termos de serviço
```

### 🔄 MECANISMO DE FALLBACK:
Se o admin nunca criar uma página, o sistema **continua funcionando como antes** (arquivos PHP).
Quando criar, a página do banco **substitui** a estática.

---

## 3. ARQUITETURA DO MÓDULO

```
inc/core/
└── Page_builder/                     ← NOVO MÓDULO
    ├── Assets/
    │   ├── js/
    │   │   └── page-builder.js       ← Editor interativo
    │   └── css/
    │       └── page-builder.css
    ├── Config/
    │   ├── Config.php                ← Metadados do módulo
    │   └── Routes.php                ← Rotas do admin + render
    ├── Controllers/
    │   ├── Page_builder.php          ← Admin: CRUD + editor
    │   └── Render.php                ← Frontend: renderiza páginas
    ├── Models/
    │   └── Page_builderModel.php     ← DB queries
    ├── Views/
    │   ├── list.php                  ← Lista de páginas criadas
    │   ├── editor.php                ← Editor de seções
    │   └── empty.php                 ← Estado vazio
    ├── Helpers/
    │   └── Page_builder_helper.php   ← Funções auxiliares
    └── Language/
        └── Page_builder_lang.php     ← Textos em português (se necessário)
```

---

## 4. BANCO DE DADOS

### 4.1. Tabela: `sp_landing_pages`

```sql
CREATE TABLE IF NOT EXISTS `sp_landing_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Nome interno',
  `slug` varchar(255) NOT NULL COMMENT 'URL amigável',
  `page_type` varchar(50) NOT NULL DEFAULT 'custom' COMMENT 'home, pricing, faqs, features, blog, custom',
  `is_home` tinyint(1) DEFAULT 0 COMMENT '1 = substitui a home',
  `is_published` tinyint(1) DEFAULT 1,
  `theme` varchar(50) DEFAULT NULL COMMENT 'Tema base para CSS',
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `slug` (`slug`),
  KEY `page_type` (`page_type`),
  UNIQUE KEY `unique_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.2. Tabela: `sp_landing_sections`

```sql
CREATE TABLE IF NOT EXISTS `sp_landing_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL COMMENT 'FK sp_landing_pages.id',
  `block_type` varchar(50) NOT NULL COMMENT 'hero, features, pricing, faq, testimonials, cta, footer, custom_html',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Ordem de exibição',
  `title` varchar(255) DEFAULT NULL COMMENT 'Título interno da seção',
  `data` longtext DEFAULT NULL COMMENT 'JSON com conteúdo e configurações',
  `settings` longtext DEFAULT NULL COMMENT 'JSON: css custom, padding, margem, fundo',
  `is_active` tinyint(1) DEFAULT 1,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3. Exemplo de JSON do bloco Hero:

**Salvo em `sp_landing_sections.data`:**
```json
{
  "hero_title": "#1 Plataforma de Marketing",
  "hero_subtitle": "Automatize seu atendimento no WhatsApp com inteligência artificial",
  "button_text": "Começar Grátis",
  "button_url": "/signup",
  "button_color": "#6C5CE7",
  "background_type": "color",
  "background_color": "#0F0E17",
  "background_image": "",
  "text_color": "#FFFFFF",
  "subtitle_color": "#A0A0B0",
  "animation": "fade-up",
  "image_right": "/Assets/img/hero-mockup.png"
}
```

### 4.4. Exemplo de Section Settings:

**Salvo em `sp_landing_sections.settings`:**
```json
{
  "padding_top": "80px",
  "padding_bottom": "80px",
  "margin_top": "0",
  "margin_bottom": "0",
  "custom_css": "",
  "custom_class": "",
  "full_width": false
}
```

---

## 5. BLOCOS DISPONÍVEIS (10 blocos)

### 5.1. HERO
**Uso**: Seção principal de apresentação (banner)
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título | Texto | Título principal (h1) |
| Subtítulo | Textarea | Descrição abaixo do título |
| Texto do Botão | Texto | CTA principal |
| URL do Botão | Texto | Link de destino |
| Cor do Botão | Cor | Seletor de cor |
| Tipo de Fundo | Select | Cor sólida / Imagem / Gradiente |
| Cor/Imagem Fundo | Cor/Upload | Fundo da seção |
| Cor do Texto | Cor | Cor do título |
| Altura | Select | Pequena / Média / Grande / Tela cheia |
| Animação | Select | Efeito de entrada (fade, slide, etc) |
| Imagem Ilustrativa | Upload | Imagem do lado direito |

### 5.2. RECURSOS (Features)
**Uso**: Mostrar funcionalidades do sistema
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título da Seção | Texto | Ex: "Funcionalidades" |
| Subtítulo | Textarea | Descrição da seção |
| Layout | Select | 3 colunas / 4 colunas / Grid |
| Cards | Lista | Cada card: ícone (emoji/svg), título, descrição, cor |
| Animação | Select | Efeito de entrada |

### 5.3. PREÇOS (Pricing)
**Uso**: Tabela de planos e preços
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título da Seção | Texto | Ex: "Nossos Planos" |
| Subtítulo | Textarea | Descrição |
| Exibir Planos | Checkbox | Automático (busca do DB) / Manual |
| Planos Manuais | Lista | Nome, preço, recursos, cor botão, link checkout |
| Destaque | Select | Qual plano fica destacado |

**Integração Checkout**: O botão de cada plano pode:
- Link direto: `/checkout/{plan_id}` (já existe)
- Plano fixo: Exibe texto descritivo, redireciona para checkout

### 5.4. FAQ
**Uso**: Perguntas frequentes
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título da Seção | Texto | Ex: "Perguntas Frequentes" |
| Subtítulo | Textarea | Descrição |
| Origem | Select | Automático (busca do DB) / Manual |
| Perguntas Manuais | Lista | Pergunta + Resposta (editor HTML) |

### 5.5. DEPOIMENTOS (Testimonials)
**Uso**: Prova social
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título | Texto | Título da seção |
| Depoimentos | Lista | Foto (upload), Nome, Cargo, Texto, Avaliação (estrelas) |
| Estilo | Select | Cards / Slider / Grid |

### 5.6. CTA (Call to Action)
**Uso**: Seção de conversão
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Texto | Texto | Ex: "Pronto para começar?" |
| Subtexto | Textarea | Descrição |
| Texto do Botão | Texto | CTA |
| URL do Botão | Texto | Link |
| Cor de Fundo | Cor | Cor sólida |
| Cor do Texto | Cor | Cor do texto |

### 5.7. RODAPÉ (Footer)
**Uso**: Rodapé da página
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Logo | Upload | Imagem do logo |
| Descrição | Textarea | Texto sobre a empresa |
| Redes Sociais | Lista | Facebook, Instagram, YouTube, TikTok, Twitter |
| Links Úteis | Lista | Título + URL |
| Copyright | Texto | Texto de direitos autorais |

### 5.8. ESTATÍSTICAS (Stats)
**Uso**: Números de impacto
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título | Texto | Título da seção |
| Itens | Lista | Número, rótulo, ícone |
| Cor | Cor | Cor dos números |

### 5.9. BLOG (Blog Preview)
**Uso**: Mostrar últimos posts do blog
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título | Texto | Título da seção |
| Quantidade | Select | 3 / 6 / 9 posts |
| Origem | Select | Automático (do DB) |
| Estilo | Select | Cards / Lista |

### 5.10. HTML LIVRE (Custom HTML)
**Uso**: Bloco livre para HTML/JS personalizado
**Campos editáveis**:
| Campo | Tipo | Descrição |
|---|---|---|
| Título Interno | Texto | Apenas para organização |
| HTML | Textarea (editor) | Conteúdo HTML livre |
| CSS | Textarea | CSS adicional |

---

## 6. INTERFACE DO ADMIN

### 6.1. Tela: Lista de Páginas

```
┌────────────────────────────────────────────────────────────┐
│  Construtor de Páginas                          [+ NOVA]   │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  🏠 Página Principal                    🟢 Publicada ✏️ 🗑️  │
│     slug: /                                              │
│     6 seções: Hero, Recursos, Preços, FAQ, CTA, Footer     │
│                                                             │
│  💲 Preços e Planos                      🟢 Publicada ✏️ 🗑️│
│     slug: /pricing                                        │
│     1 seção: Preços (automático do DB)                    │
│                                                             │
│  📰 Funcionalidades                      ⚪ Rascunho   ✏️ 🗑️ │
│     slug: /features                                       │
│     3 seções: Hero, Features, CTA                         │
│                                                             │
│  ...                                                        │
│                                                             │
└────────────────────────────────────────────────────────────┘
```

### 6.2. Tela: Editor de Seções

```
┌─────────────────────────────────────────────────────────────┐
│  Editando: 🏠 Página Principal             [👁️ Preview] 💾 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ ⬆ ⬇ ✏️ 🗑️  HERO                                   │   │
│  │ ┌────────────────────────────────────────────────┐  │   │
│  │ │ Título: [#1 Plataforma de Marketing       ]    │  │   │
│  │ │ Subtítulo:[Automatize seu WhatsApp...     ]    │  │   │
│  │ │ Texto BTN:[Começar Grátis                ]    │  │   │
│  │ │ URL BTN:  [/signup                       ]    │  │   │
│  │ │ Cor BTN:  [■ #6C5CE7                    ]    │  │   │
│  │ │ Fundo:    [○ Cor ● Imagem ○ Gradiente   ]    │  │   │
│  │ │           [■ #0F0E17           [Upload] ]    │  │   │
│  │ │ Altura:   [○ Pequena ● Média ○ Grande  ]    │  │   │
│  │ └────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ ⬆ ⬇ ✏️ 🗑️  RECURSOS (Features)                    │   │
│  │ ┌────────────────────────────────────────────────┐  │   │
│  │ │ Título:   [Funcionalidades                ]    │  │   │
│  │ │ Layout:   [○ 3 colunas ● 4 colunas ○ Grid]    │  │   │
│  │ │                                                  │  │   │
│  │ │ ┌── Card 1 ─────────────────────────────────┐   │  │   │
│  │ │ │ Ícone: [🤖]  Título:[Automação WhatsApp] │   │  │   │
│  │ │ │ Descrição:[Texto aqui...                ] │   │  │   │
│  │ │ └──────────────────────────────────────────┘   │  │   │
│  │ │ [+ Adicionar Card]                             │  │   │
│  │ └────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ ⬆ ⬇ ✏️ 🗑️  PREÇOS (Pricing)                      │   │
│  │ ┌────────────────────────────────────────────────┐  │   │
│  │ │ Origem: [● Automático (do DB) ○ Manual]        │  │   │
│  │ │ Plano destaque: [Profissional]                 │  │   │
│  │ │                                                  │  │   │
│  │ │ ▶ Os planos são carregados automaticamente     │  │   │
│  │ │   do banco de dados (sp_plans).                 │  │   │
│  │ │   Para editar → [Gerenciar Planos]             │  │   │
│  │ └────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  [➕ ADICIONAR BLOCO]                                        │
│  ┌──────────────────────────────┐                            │
│  │  Hero                        │                            │
│  │  Recursos                    │                            │
│  │  Preços                      │                            │
│  │  FAQ                         │                            │
│  │  Depoimentos                 │                            │
│  │  CTA                         │                            │
│  │  Estatísticas                │                            │
│  │  Blog                        │                            │
│  │  Rodapé                      │                            │
│  │  HTML Livre                  │                            │
│  └──────────────────────────────┘                            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. TECNOLOGIAS

| Funcionalidade | Tecnologia | Motivo |
|---|---|---|
| Framework PHP | CodeIgniter 4 | Já existe no sistema |
| CSS | Bootstrap 5 | Já usado no sistema |
| Ícones | Font Awesome + Emoji | Já usado |
| Drag & Drop (reorder) | SortableJS (6kb) | Leve, nativo JS |
| Seletor de cor | input type="color" | Nativo HTML5 |
| Upload de imagens | Upload nativo + jQuery | Já existe no sistema |
| Editor de texto | ContentEditable | Para HTML livre |
| Armazenamento | MySQL JSON | Já tem MySQL |

**NENHUMA dependência externa nova pesada.** Tudo é PHP + JS vanilla + Bootstrap (já usado).

---

## 8. RENDERIZAÇÃO NO FRONTEND

### 8.1. O que acontece quando o visitante acessa a página:

```
1. Visitante → GET /
2. Rota padrão inc/core/Home/Controllers/Home.php::index()
3. Verifica se existe sp_landing_pages com page_type='home' E is_published=1
   ├── SIM → Render::home($page_id) 
   │   ├── Carrega sp_landing_sections WHERE page_id=X ORDER BY sort_order
   │   ├── Para cada section:
   │   │   ├── Determina block_type
   │   │   ├── Chama renderizador específico (renderHero, renderFeatures, etc)
   │   │   └── Monta HTML com os dados do JSON
   │   ├── Aplica tema base (CSS do tema escolhido)
   │   └── Retorna HTML completo
   │
   └── NÃO → Comportamento atual (carrega home.php estática)
```

### 8.2. Renderizadores de cada bloco:

Cada bloco tem uma função PHP que monta o HTML a partir dos dados JSON:

```php
// Exemplo de renderização do Hero
function renderHero($data, $settings) {
    $bg_style = $data['background_type'] == 'color' 
        ? "background-color: {$data['background_color']};"
        : "background-image: url({$data['background_image']}); background-size: cover;";
    
    return "
        <section style=\"{$bg_style} padding: {$settings['padding_top']} 0 {$settings['padding_bottom']};\">
            <div class=\"container\">
                <div class=\"row align-items-center\">
                    <div class=\"col-md-6\">
                        <h1 style=\"color: {$data['text_color']}\">{$data['hero_title']}</h1>
                        <p style=\"color: {$data['subtitle_color']}\">{$data['hero_subtitle']}</p>
                        <a href=\"{$data['button_url']}\" class=\"btn btn-round\" 
                           style=\"background: {$data['button_color']}; color: #fff;\">
                            {$data['button_text']}
                        </a>
                    </div>
                    <div class=\"col-md-6\">
                        <img src=\"{$data['image_right']}\" class=\"w-100\" alt=\"\">
                    </div>
                </div>
            </div>
        </section>
    ";
}
```

### 8.3. O wrapper (layout) permanece o mesmo:

A página renderizada usa o **mesmo `index.php` do tema** — header, navbar, footer, scripts, CSS — só muda o **conteúdo**. Isso significa que:
- CSS do tema continua funcionando
- Menu de navegação continua igual
- Responsividade mantida
- Popup de notificação continua

---

## 9. MIGRAÇÃO SEGURA

### 9.1. Zero impacto nos dados existentes:
- Nenhuma tabela existente é alterada
- Arquivos PHP continuam intactos
- Fallback automático

### 9.2. Degradação graciosa:
| Cenário | Comportamento |
|---|---|
| Nenhuma página criada | Sistema funciona normalmente (PHP) |
| Página criada, não publicada | Sistema usa PHP |
| Página publicada | Substitui PHP |
| Página despublicada | Volta ao PHP |
| Erro no JSON | Log + fallback pro PHP |

### 9.3. Não afeta:
- Admin panel
- Login/autenticação
- Checkout/pagamento
- Webhooks
- APIs
- Bot Builder
- Nenhum outro módulo

---

## 10. CRONOGRAMA DE IMPLEMENTAÇÃO

### Fase 1: Base (Dias 1-2)
| Dia | Tarefa | Detalhes |
|---|---|---|
| 1 | Módulo Page Builder + DB | Criar estrutura do módulo, tabelas, config, rotas |
| 1 | CRUD de páginas | Listar, criar, editar, deletar páginas |
| 2 | CRUD de seções | Adicionar/remover/reordenar seções em uma página |
| 2 | Editor básico | Interface de edição com blocos listados no admin |

### Fase 2: Blocos (Dias 3-5)
| Dia | Tarefa | Detalhes |
|---|---|---|
| 3 | Bloco HERO | Formulário completo + renderização frontend |
| 3 | Bloco RECURSOS | Cards com ícone/título/descrição |
| 4 | Bloco PREÇOS | Integração com sp_plans + modo manual + checkout |
| 4 | Bloco FAQ | Acordeão com perguntas/respostas |
| 4 | Bloco CTA | Seção de call-to-action |
| 5 | Bloco DEPOIMENTOS | Cards de depoimento com foto |
| 5 | Bloco RODAPÉ | Footer com redes sociais e links |
| 5 | Bloco HTML LIVRE | Editor de HTML personalizado |

### Fase 3: Renderização + Ajustes (Dias 6-7)
| Dia | Tarefa | Detalhes |
|---|---|---|
| 6 | Renderizador central | Engine que renderiza páginas do banco |
| 6 | Integração com roteamento | Home + páginas custom: /features, /faqs, /pricing |
| 6 | Fallback | Quando não tem página no banco, usa PHP original |
| 7 | Preview | Botão "Ver página" no admin |
| 7 | Reorder com SortableJS | Arrastar seções pra reordenar |
| 7 | Testes finais | Validação completa + ajustes CSS |

### Fase 4: Extras (Dias 8-9) — Se necessário
| Dia | Tarefa | Detalhes |
|---|---|---|
| 8 | Bloco ESTATÍSTICAS | Seção com números de impacto |
| 8 | Bloco BLOG | Preview dos últimos posts |
| 9 | CSS responsivo | Ajustes finos nos blocos |
| 9 | Documentação | Como usar o page builder |

---

## 11. RESUMO DO ESFORÇO

| Versão | Blocos | Tempo | Descrição |
|---|---|---|---|
| MVP | 6 blocos (Hero, Recursos, Preços, FAQ, CTA, Rodapé) | ~5 dias | Essencial para substituir a home |
| Completa | 10 blocos | ~7 dias | Todos os blocos + extras |
| Premium | 10 blocos + CSS templates | ~9 dias | Com ajustes finos de design |

**Recomendação**: Começar com **MVP em 5 dias** (6 blocos essenciais), depois adicionar os extras.

---

## 12. PRÓXIMOS PASSOS

1. ✅ **Plano aprovado** → Iniciar implementação
2. Criar estrutura do módulo `inc/core/Page_builder/`
3. Criar tabelas `sp_landing_pages` e `sp_landing_sections`
4. Implementar CRUD de páginas + seções
5. Implementar os blocos um a um
6. Implementar renderização frontal
7. Testar integração com checkout

---

**Deseja iniciar a implementação?** 🚀
