#!/bin/bash
# ============================================================
# release.sh - Criar RELEASE para distribuir aos sistemas
#
# Só use quando o código estiver 100% OK para distribuir!
# Trabalho normal (WIP/backup) NÃO deve usar este script.
#
# Uso: sudo bash release.sh 8.4.0 "Descrição do que mudou"
# ============================================================

VERSION="$1"
NOTES="${2:-Release v$1}"

if [ -z "$VERSION" ]; then
    echo "Uso: sudo bash release.sh 8.4.0 \"Descrição do que mudou\""
    echo ""
    echo "Exemplo:"
    echo "  sudo bash release.sh 8.4.0 \"Correcao de envio + novo modulo\""
    exit 1
fi

cd /www/wwwroot/app_zapmatic_app

echo "=============================================="
echo "CRIANDO RELEASE v$VERSION"
echo "Nota: $NOTES"
echo "=============================================="

# 1. Atualizar version.json
cat > version.json << EOF
{
    "version": "$VERSION",
    "channel": "stable",
    "build_date": "$(date '+%Y-%m-%dT%H:%M:%S')-03:00",
    "git_commit": "",
    "min_php": "8.0",
    "notes": "$NOTES"
}
EOF
echo "✅ version.json atualizado para $VERSION"

# 2. Commit
git add -A
git commit -m "release: v$VERSION - $NOTES"
echo "✅ Commit criado"

# 3. Push main
git push origin main
echo "✅ main enviado ao GitHub"

# 4. Tag + push
git tag "v$VERSION"
git push origin "v$VERSION"
echo "✅ Tag v$VERSION criada e enviada"

echo ""
echo "=============================================="
echo "RELEASE v$VERSION DISTRIBUÍDO!"
echo "=============================================="
echo "Os sistemas agora vão mostrar:"
echo "  'Atualização disponível: v$VERSION'"
echo "=============================================="
