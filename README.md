# 🤖 Zapmatic — Flow Builder

> **WhatsApp Automation Platform** — Visual flow builder for creating intelligent WhatsApp chatbot conversations with native template support, multi-instance management, and real-time simulation.

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Flow Builder Modules](#-flow-builder-modules)
- [Database](#-database)
- [Installation](#-installation)
- [Development](#-development)
- [Deployment](#-deployment)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🎯 Overview

Zapmatic Flow Builder is a **visual drag-and-drop** chatbot builder for WhatsApp. It allows you to design complex conversation flows using a node-based editor, with support for:

- **50+ node types** — text, image, video, audio, buttons, lists, carousels, conditions, A/B testing, API calls, OpenAI integration, and more
- **Native WhatsApp templates** — buttons, list menus, and carousel templates from your WhatsApp Business Account
- **Multi-instance** — connect and manage multiple WhatsApp instances (Cloud API, Baileys)
- **Real-time simulation** — test your flow inside the editor before publishing
- **Variable system** — capture user input, store session variables, personalize messages
- **Dynamic content** — build buttons, image choices, and cards on-the-fly

---

## 🏗 Architecture

### Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8+ (CodeIgniter 4) |
| **Database** | MySQL 8+ |
| **Frontend** | Vanilla JavaScript (modular ES5), CSS3 |
| **WhatsApp API** | Meta Cloud API + Baileys (Node.js) |
| **Deployment** | Nginx, Supervisor |

### Project Structure

```
📁 app_zapmatic_app/
├── inc/core/Bot_builder/          ← Flow Builder Module
│   ├── Assets/
│   │   ├── css/bot_builder.css     ← Builder styles (~2100 lines)
│   │   ├── js/
│   │   │   ├── bot_builder.js      ← Canvas core (654 lines)
│   │   │   └── builder/            ← Modular components (16 files)
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── inc/core/Whatsapp/             ← WhatsApp integration
├── sql/flow_builder/              ← Database migrations
└── ...
```

### JavaScript Module Architecture

The original monolithic `bot_builder.js` (3093 lines) was refactored into **16 focused modules**:

```
📦 bot_builder.js  654 lines   ← Canvas core + delegators
📁 builder/
├── utils.js                68  ← $, escHtml, uuidv4, showToast
├── history.js              70  ← undo/redo
├── canvas-core.js         133  ← zoom, pan, keyboard
├── persistence.js          99  ← save, auto-save
├── node-defs.js            76  ← node type definitions
├── connections.js         173  ← SVG edge drawing
├── validation.js           73  ← flow validation
├── simulator-ui.js        132  ← chat UI, typing indicators
├── simulator.js           593  ← flow execution engine
├── publish-modal.js       348  ← publish & instance management
├── inspector-core.js      125  ← common inspector helpers
├── inspector-templates.js 162  ← native WhatsApp templates
├── inspector-variables.js 240  ← variable system
├── inspector-dynamics.js  346  ← dynamic button/card builders
├── inspector-integrations.js 245 ← 28 API integrations
└── inspector.js           382  ← main inspector entry
```

---

## 🧩 Flow Builder Modules

### Node Types

| Category | Types |
|----------|-------|
| **Flow** | Start, End, Condition, A/B Test, Command |
| **Message** | Text, Image, Video, Audio, Document |
| **Interaction** | Buttons, List Menu, Pic Choice, Cards |
| **AI** | OpenAI Completion, OpenAI Vision |
| **Integration** | HTTP Request, Webhook, Sheets, Telegram, Email, and 24+ more |
| **Tools** | Variable Set, Delay, Forward, Notes |

### Key Features

- **Drag & Drop** — drag nodes from sidebar onto canvas
- **Visual Connections** — SVG curves with animated flow indicators
- **Native Templates** — auto-load WhatsApp templates for Buttons, List, Carousel
- **Dynamic Content** — build response options programmatically (buttons, cards, image choices)
- **Variable Picker** — insert `{{variables}}` into any text field
- **Undo/Redo** — 50-level undo stack
- **Auto-Save** — saves every 2 seconds when dirty
- **Session History** — simulator tracks path history and variables
- **Multi-Language** — UI in Portuguese

---

## 🗄 Database

Database migrations are organized under `sql/flow_builder/`:

| Migration | Description |
|-----------|-------------|
| `phase1.sql` | Core flow tables: `sp_whatsapp_flow_endpoints`, `sp_whatsapp_flows`, `sp_whatsapp_flow_blocks`, `sp_whatsapp_flow_edges` |
| `phase2_builder_state.sql` | Builder UI state persistence |
| `phase3_meta_details.sql` | WhatsApp template metadata columns |
| `phase3_meta_sync.sql` | Template sync tracking |
| `bot_builder.sql` | Bot builder core tables |
| `tabelas_faltantes.sql` | Supplementary tables |

---

## 🚀 Installation

### Prerequisites

- PHP 8.0+
- MySQL 8.0+
- Node.js 18+ (for Baileys WhatsApp instances)
- Composer
- Nginx or Apache

### Setup

```bash
# 1. Clone the repository
git clone https://github.com/lonardonetto/zapmatic_builder.git
cd zapmatic_builder

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Run database migrations
# Execute SQL files in order from sql/flow_builder/

# 5. Set up WhatsApp instances (optional)
node app_zapmatic_api/server.js
```

### Configuration

Key environment variables in `.env`:

```
database.default.hostname = localhost
database.default.database = zapmatic
database.default.username = root
database.default.password = your_password
database.default.DBDriver = MySQLi
```

---

## 💻 Development

### Module Loading Order

Scripts are loaded in `editor.php` in dependency order:

```html
utils.js → history.js → canvas-core.js → persistence.js → node-defs.js →
connections.js → validation.js → simulator-ui.js → simulator.js →
publish-modal.js → inspector-core.js → inspector-templates.js →
inspector-variables.js → inspector-dynamics.js →
inspector-integrations.js → inspector.js → bot_builder.js
```

### Adding a New Node Type

1. Add definition in `node-defs.js`
2. Add inspector fields in the appropriate `inspector-*.js`
3. Add simulation logic in `simulator.js` (if interactive)
4. Add preview rendering in `renderNodeBodyHTML()` in `bot_builder.js`

### Code Conventions

- All modules use IIFE pattern with `window.BotBuilderModules.*` namespace
- The core `bot_builder.js` exposes a `window.BotBuilder` state object
- Delegators in `bot_builder.js` call module methods (never duplicate logic)
- CSS uses `tb-` prefix for toolbar, `pm-` for publish modal, `bb-` for builder

---

## 📦 Deployment

```bash
# Build assets (if using any bundler)
npm run build

# Deploy via git
git push origin main

# Restart workers (if using Baileys)
supervisorctl restart zapmatic_api
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Commit Guidelines

- Use conventional commits: `feat:`, `fix:`, `refactor:`, `docs:`, `chore:`
- Keep commits focused on a single change
- Reference issues when applicable

---

## 📄 License

This project is licensed under the MIT License. See `LICENSE` for details.

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/lonardonetto">@lonardonetto</a>
</p>
