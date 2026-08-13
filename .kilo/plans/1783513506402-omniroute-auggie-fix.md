# OmniRoute Auggie CLI Fix

## Problema
O OmniRoute precisa do CLI `auggie` instalado para rotear pelas contas Augment/Antigravity.
Erro atual: `[502]: Auggie CLI not found: auggie`

## Solução

Execute os comandos abaixo no terminal SSH da VPS:

### 1. Instalar o Auggie CLI
```bash
export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm use 22
npm install -g @augmentcode/auggie
```

### 2. Verificar instalação
```bash
which auggie && auggie --version
```

### 3. Fazer login (interativo — requer navegador)
```bash
auggie login
```
Siga as instruções no terminal. Isso abre um link no navegador para autenticar as 3 contas.

### 4. Testar roteamento
```bash
curl -s http://localhost:20129/v1/chat/completions \
  -H "Content-Type: application/json" \
  -d '{"model": "aug/claude-haiku-4.5", "messages": [{"role": "user", "content": "Say just: routing works"}], "max_tokens": 30, "stream": false}'
```

### 5. Confirmar que respondeu
Se funcionar, teste também:
```bash
curl -s http://localhost:20129/v1/chat/completions \
  -H "Content-Type: application/json" \
  -d '{"model": "aug/claude-sonnet-4.6", "messages": [{"role": "user", "content": "Diga: funcional"}], "max_tokens": 30, "stream": false}'
```

## Contexto
- OmniRoute v3.8.46 rodando via systemd
- Dashboard: http://168.75.102.17:20130 (senha: CHANGEME)
- 3 contas Antigravity/Augment conectadas via dashboard
