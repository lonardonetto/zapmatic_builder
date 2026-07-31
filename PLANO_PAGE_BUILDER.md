# 🚀 Plano: Page Builder para Landing Pages (estilo Elementor)

## 1. Objetivo

Substituir as views PHP estáticas dos 5 temas frontend por um **editor visual drag-and-drop**, onde o administrador monta a página por blocos (seções), igual ao Elementor do WordPress — mas nativo no sistema.

---

## 2. Estrutura Atual (como funciona hoje)

```
inc/themes/frontend/
├── Stackdark/
│   ├── Views/
│   │   ├── index.php          ← Layout principal (head, header, footer)
│   │   ├── home.php           ← Conteúdo da HOME (FIXO, PHP)
│   │   ├── features.php       ← Conteúdo (FIXO)
│   │   ├── pricing.php        ← Conteúdo (FIXO)
│   │   ├── faqs.php           ← Conteúdo (FIXO)
│   │   ├── signup.php         ← Conteúdo (FIXO)
│   │   ├── login.php          ← Conteúdo (FIXO)
│   │   └── ... (~20 arquivos)
│   └── Assets/                ← CSS, JS, imagens
├── Stackgo/ (mesma estrutura)
├── Stacklight/ (mesma estrutura)
├── Wzdark/ (mesma estrutura)
└── Wzlight/ (mesma estrutura)
```

**Problema**: Para alterar qualquer texto, imagem ou seção, precisa editar PHP manualmente.

---

## 3. Estrutura Futura (como vai ficar)

```
inc/core/
└── Page_builder/              ← NOVO MÓDULO
    ├── Assets/
    │   ├── js/
    │   │   ├── builder.js     ← Editor drag-and-drop principal
    │   │   ├── blocks.js      ← Registro de blocos
    │   │   ├── draggable.js   ← Engine de arrastar/soltar
    │   │   └── preview.js     ← Preview em tempo real
    │   ├── css/
    │   │   ├── builder.css    ← Estilo do editor
    │   │   └── frontend.css   ← Estilo dos blocos renderizados
    │   └── lib/               ← Bibliotecas (SortableJS, etc.)
    ├── Config/
    │   └── Routes.php         ← /admin/page-builder, /page-builder/render
    ├── Controllers/
    │   ├── Admin.php          ← CRUD + editor no admin
    │   └── Render.php         ← Renderiza páginas no frontend
    ├── Models/
    │   └── Page_builderModel.php
    ├── Views/
    │   ├── index.php          ← Lista de páginas
    │   ├── editor.php         ← Editor visual (iframe principal)
    │   └── render.php         ← Layout de renderização
    └── Blocks/                ← Cada bloco é uma classe separada
        ├── BlockBase.php      ← Classe base para blocos
        ├── HeroBlock.php
        ├── FeaturesBlock.php
        ├── PricingBlock.php
        ├── FaqBlock.php
        ├── TestimonialsBlock.php
        ├── ContactBlock.php
        ├── CtaBlock.php
        ├── FooterBlock.php
        └── CustomHtmlBlock.php
```

---

## 4. Banco de Dados

```sql
-- Tabela principal: páginas
CREATE TABLE `sp_landing_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,              -- Nome interno (ex: "Home", "Landing Campanha X")
  `slug` varchar(255) NOT NULL UNIQUE,        -- URL: /landing/minha-pagina
  `is_home` tinyint(1) DEFAULT 0,             -- 1 = substitui a home padrão
  `theme` varchar(50) DEFAULT 'Stackdark',    -- Tema base (herda CSS)
  `settings` longtext DEFAULT NULL,           -- JSON: SEO, CSS custom, scripts, cor, fonte
  `is_published` tinyint(1) DEFAULT 0,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: blocos/seções de cada página
CREATE TABLE `sp_landing_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,                 -- FK sp_landing_pages.id
  `parent_id` int(11) DEFAULT NULL,           -- Para aninhamento
  `type` varchar(50) NOT NULL,                -- hero, features, pricing, faq, cta, custom_html
  `sort_order` int(11) DEFAULT 0,             -- Ordem dos blocos
  `data` longtext DEFAULT NULL,               -- JSON com conteúdo do bloco
  `settings` longtext DEFAULT NULL,           -- JSON: css custom, animação, padding, bg
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Exemplo de `data` de um bloco Hero:
```json
{
  "title": "Gestão de Redes Sociais",
  "subtitle": "Automatize seu atendimento no WhatsApp",
  "button_text": "Começar Agora",
  "button_url": "/signup",
  "button_color": "#6C5CE7",
  "background_image": "/Assets/img/bg-hero.jpg",
  "background_color": "#0F0E17",
  "text_color": "#FFFFFF",
  "alignment": "center",
  "animation": "fade-up"
}
```

---

## 5. Arquitetura do Editor (o "Elementor")

### 5.1. Interface

```
┌─────────────────────────────────────────────────────────┐
│  🔧 Page Builder - Home                                 │
├──────────┬──────────────────────────────────────────────┤
│  BLOCOS  │                                              │
│  ─────── │          ┌──────────────────────────────┐    │
│  [Hero]  │          │                              │    │
│  [Feat.] │          │    PREVIEW EM TEMPO REAL      │    │
│  [Pric.] │          │    (iframe da página)         │    │
│  [FAQ]   │          │                              │    │
│  [CTA]   │          │   ┌────────────────────┐     │    │
│  [Footer]│          │   │  HERO BLOCK        │     │    │
│  [Custom]│          │   │  Título Editável    │     │    │
│          │          │   │  [Clique para editar]│     │    │
│  ─────── │          │   └────────────────────┘     │    │
│  Drag &  │          │                              │    │
│  Drop    │          │   ┌────────────────────┐     │    │
│          │          │   │  FEATURES           │     │    │
│          │          │   │  Card 1 | Card 2   │     │    │
│          │          │   └────────────────────┘     │    │
│          │          │                              │    │
└──────────┴──────────────────────────────────────────────┘
```

### 5.2. Fluxo de edição

1. **Admin → Page Builder → Lista de páginas** (CRUD)
2. **Clicar em "Editar"** → abre o editor visual
3. **Lado esquerdo**: paleta de blocos disponíveis
4. **Centro**: preview em iframe da página (atualiza em tempo real)
5. **Clicar em um bloco** → inspector lateral abre com campos editáveis
6. **Arrastar blocos** para reordenar
7. **Salvar** → salva JSON no banco

### 5.3. Tecnologias sugeridas

| Funcionalidade | Biblioteca |
|---|---|
| Drag & Drop | [SortableJS](https://sortablejs.github.io/Sortable/) (minificada, ~6kb) |
| Editor inline | ContentEditable + handlers |
| Preview | iframe com a página renderizada |
| Inspector | Painel JS custom com inputs por tipo de bloco |
| Núcleo | Vanilla JS (sem jQuery pesado) |

---

## 6. Renderização (Frontend)

Quando um visitante acessa a página, o fluxo é:

```
1. Usuário acessa "/" ou "/landing/minha-pagina"
2. Router → Render::index()
3. Carrega sp_landing_pages + sp_landing_blocks do banco
4. Itera sobre blocos em ordem
5. Cada bloco → classe Block::render($data)
6. Monta HTML completo
7. Aplica CSS custom + tema base
8. Exibe para o visitante
```

**Se `is_home = 1`**: substitui a home estática (`home.php`)
**Se `is_home = 0`**: rota `/landing/{slug}`

### Fallback
Se não houver landing pages criadas, mantém o comportamento atual (arquivos PHP estáticos) — **zero quebra**.

---

## 7. Blocos (8 sugeridos para MVP)

| Bloco | Campos | Funcionalidades |
|---|---|---|
| **Hero** | título, subtítulo, CTA botão, imagem fundo, cor | Seção principal de apresentação |
| **Features** | título, grid (3-4 cards com ícone + texto) | Destaque de funcionalidades |
| **Pricing** | título, 3 planos (nome, preço, recursos, CTA) | Tabela de preços + link checkout |
| **FAQ** | título, lista pergunta/resposta (acordeão) | Perguntas frequentes |
| **Testimonials** | título, cards de depoimento (foto, nome, texto) | Prova social |
| **CTA** | texto, botão, cor de fundo | Call-to-action entre seções |
| **Footer** | logo, links, redes sociais, copyright | Rodapé da página |
| **Custom HTML** | editor HTML livre | Bloco para qualquer coisa |

### Bloco Pricing → Checkout
O bloco Pricing é especial — os botões "Comprar" chamam a URL `/checkout/{plan_id}` que já existe no sistema (integrado com Stripe/PayPal). Não precisa recriar pagamento.

---

## 8. Migração

Para não quebrar nada:

1. Landing pages começam **vazias** = sistema funciona como antes
2. Quando o admin cria a 1ª landing page com `is_home = 1`, ela passa a substituir `home.php`
3. As outras páginas (signup, login, etc.) continuam como estão
4. O admin pode criar landing pages avulsas para campanhas em `/landing/promocao-x`

---

## 9. Cronograma

| Fase | Tarefa | Dias |
|---|---|---|
| **1** | Estrutura do módulo, DB, CRUD básico de páginas | 2 |
| **2** | Engine de blocos (BlockBase + renderizador) | 2 |
| **3** | Editor visual (sidebar + drag & drop + iframe + preview) | 4 |
| **4** | Blocos MVP (Hero, Features, Pricing, FAQ, CTA, Footer, Custom) | 3 |
| **5** | Inspector (campos editáveis por bloco) | 3 |
| **6** | Renderização frontend + roteamento + fallback | 1 |
| **7** | Bloco Pricing → integração com checkout | 1 |
| **8** | CSS responsivo + temas base | 1 |
| **9** | Testes + ajustes finos | 2 |
| | **Total** | **~19 dias** |

---

## 10. E se quiser algo ainda mais rápido?

**Opção alternativa**: Integrar o [GrapesJS](https://grapesjs.com/) — um framework open-source de page builder visual completo.

- Já tem drag-and-drop, blocos, estilos, responsivo
- Integração via JS + API
- Reduz o tempo de implementação para **~7-10 dias**
- Exemplo: `grapesjs-preset-webpage` já vem com blocos prontos

**Prós**: Entrega muito mais rápido, editor profissional
**Contras**: Dependência externa, personalização limitada ao que o GrapesJS oferece

Minha recomendação: **GrapesJS como base**, adaptado com os blocos específicos (Pricing com checkout integrado).
