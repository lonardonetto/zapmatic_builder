#!/bin/bash
# =============================================================
# Script Generico de Deploy - Zapmatic
# Envia QUALQUER pasta/diretorio para todos os servidores
#
# Uso:
#   ./deploy_folder.sh app              # envia app/ para todos
#   ./deploy_folder.sh inc              # envia inc/ para todos
#   ./deploy_folder.sh app_zapmatic_api # envia app_zapmatic_api/ para todos
#   ./deploy_folder.sh app/Services     # envia app/Services/ para todos
#   ./deploy_folder.sh arquivo.php      # envia 1 arquivo especifico
#   ./deploy_folder.sh inc/core/Bot_builder  # envia subpasta
#
# Flags opcionais:
#   --no-go    Nao reinicia servicos Go
#   --dry-run  Mostra o que faria sem executar
#   --servers  Envia apenas para servidores remotos (pula local)
# =============================================================

set -e

MAIN="/www/wwwroot/app_zapmatic_app"
SSH_KEY="/home/ubuntu/.ssh/chave_zapmatic.key"
NO_GO=false
DRY_RUN=false
REMOTE_ONLY=false
TARGET="$1"

# Parse flags
shift
while [ "$#" -gt 0 ]; do
    case "$1" in
        --no-go)    NO_GO=true ;;
        --dry-run)  DRY_RUN=true ;;
        --servers)  REMOTE_ONLY=true ;;
    esac
    shift
done

if [ -z "$TARGET" ]; then
    echo "Uso: $0 <pasta/arquivo> [--no-go] [--dry-run] [--servers]"
    echo ""
    echo "Exemplos:"
    echo "  $0 app              # envia pasta app/"
    echo "  $0 inc              # envia pasta inc/"
    echo "  $0 app/Services     # envia subpasta"
    echo "  $0 arquivo.php      # envia 1 arquivo"
    exit 1
fi

# Verificar se existe localmente
SOURCE="$MAIN/$TARGET"
if [ ! -e "$SOURCE" ]; then
    echo "ERRO: $SOURCE nao existe"
    exit 1
fi

# Determinar se e arquivo ou pasta
if [ -f "$SOURCE" ]; then
    IS_FILE=true
else
    IS_FILE=false
fi

echo "=========================================="
echo " ZAPMATIC DEPLOY GENERICO"
echo " Arquivo/Pasta: $TARGET"
echo " Tipo: $([ $IS_FILE = true ] && echo 'arquivo' || echo 'pasta')"
echo " $(date)"
echo "=========================================="

# ========================================
# SERVIDOR LOCAL - Clientes
# ========================================
if [ $REMOTE_ONLY = false ]; then
    echo ""
    echo "=== SERVIDOR LOCAL ==="

    LOCAL_CLIENTS=(
        "Paulo:/www/wwwroot/app_paulo_app"
        "Elias:/www/wwwroot/app_elias_app"
        "Renovo:/www/wwwroot/renovo_app"
        "Astros:/www/wwwroot/app.astroscomunicacaodigital.com"
    )

    for client in "${LOCAL_CLIENTS[@]}"; do
        name=$(echo $client | cut -d: -f1)
        path=$(echo $client | cut -d: -f2)
        dest="$path/$TARGET"

        echo -n "  $name... "

        if [ $DRY_RUN = true ]; then
            echo "(dry-run: $SOURCE -> $dest)"
            continue
        fi

        if [ $IS_FILE = true ]; then
            mkdir -p "$(dirname $dest)" 2>/dev/null
            cp "$SOURCE" "$dest" 2>/dev/null
        else
            rsync -avz --delete --force "$SOURCE/" "$dest/" 2>/dev/null
        fi
        echo "OK"
    done
fi

# ========================================
# SERVIDOR 144 - Kivozap, AgenciaMCW, Chatbut
# ========================================
echo ""
echo "=== SERVIDOR 144.22.167.45 ==="

SSH_144="ssh -i $SSH_KEY"

REMOTE_144_CLIENTS=(
    "Kivozap:/www/wwwroot/app_abner_app"
    "AgenciaMCW:/www/wwwroot/app_frank_agencia"
    "Chatbut:/www/wwwroot/app_alex_pedidu_app"
)

for client in "${REMOTE_144_CLIENTS[@]}"; do
    name=$(echo $client | cut -d: -f1)
    path=$(echo $client | cut -d: -f2)

    echo -n "  $name... "

    if [ $DRY_RUN = true ]; then
        echo "(dry-run: $SOURCE -> $path/$TARGET)"
        continue
    fi

    if [ $IS_FILE = true ]; then
        mkdir_cmd="mkdir -p $(dirname $path/$TARGET)"
        ssh -i $SSH_KEY ubuntu@144.22.167.45 "$mkdir_cmd" 2>/dev/null
        scp -q -i $SSH_KEY "$SOURCE" ubuntu@144.22.167.45:"$path/$TARGET" 2>/dev/null
    else
        rsync -avz --delete --force \
          -e "ssh -i $SSH_KEY" \
          "$SOURCE/" ubuntu@144.22.167.45:"$path/$TARGET/" 2>/dev/null
    fi
    echo "OK"
done

# ========================================
# SERVIDOR 45 - IaClicks
# ========================================
echo ""
echo "=== SERVIDOR 45.148.29.92 ==="

IACLICKS_PATH="/www/wwwroot/app_zapmatic_app"
echo -n "  IaClicks... "

if [ $DRY_RUN = true ]; then
    echo "(dry-run: $SOURCE -> $IACLICKS_PATH/$TARGET)"
else
    if [ $IS_FILE = true ]; then
        sshpass -p 'Leonetto1982' ssh -o StrictHostKeyChecking=no admin@45.148.29.92 \
          "mkdir -p $(dirname $IACLICKS_PATH/$TARGET)" 2>/dev/null
        sshpass -p 'Leonetto1982' scp -o StrictHostKeyChecking=no "$SOURCE" \
          admin@45.148.29.92:"$IACLICKS_PATH/$TARGET" 2>/dev/null
    else
        rsync -avz --delete --force \
          -e "sshpass -p 'Leonetto1982' ssh -o StrictHostKeyChecking=no" \
          "$SOURCE/" admin@45.148.29.92:"$IACLICKS_PATH/$TARGET/" 2>/dev/null
    fi
    echo "OK"
fi

# ========================================
# SERVIDOR 193 - Elite
# ========================================
echo ""
echo "=== SERVIDOR 193.180.211.190 ==="

ELITE_PATH="/www/wwwroot/elitecomunicacao.zapmatic.tec.br"
echo -n "  Elite... "

if [ $DRY_RUN = true ]; then
    echo "(dry-run: $SOURCE -> $ELITE_PATH/$TARGET)"
else
    if [ $IS_FILE = true ]; then
        sshpass -p 'Leonetto1982' ssh -o StrictHostKeyChecking=no admin@193.180.211.190 \
          "mkdir -p $(dirname $ELITE_PATH/$TARGET)" 2>/dev/null
        sshpass -p 'Leonetto1982' scp -o StrictHostKeyChecking=no "$SOURCE" \
          admin@193.180.211.190:"$ELITE_PATH/$TARGET" 2>/dev/null
    else
        rsync -avz --delete --force \
          -e "sshpass -p 'Leonetto1982' ssh -o StrictHostKeyChecking=no" \
          "$SOURCE/" admin@193.180.211.190:"$ELITE_PATH/$TARGET/" 2>/dev/null
    fi
    echo "OK"
fi

# ========================================
# SERVIDOR 92 - PlusZap
# ========================================
echo ""
echo "=== SERVIDOR 92.113.144.161 ==="

PLUSZAP_PATH="/www/wwwroot/app_zapmatic_app"
echo -n "  PlusZap... "

if [ $DRY_RUN = true ]; then
    echo "(dry-run: $SOURCE -> $PLUSZAP_PATH/$TARGET)"
else
    if [ $IS_FILE = true ]; then
        sshpass -p 'Leonetto1982' ssh -o StrictHostKeyChecking=no admin@92.113.144.161 \
          "mkdir -p $(dirname $PLUSZAP_PATH/$TARGET)" 2>/dev/null
        sshpass -p 'Leonetto1982' scp -o StrictHostKeyChecking=no "$SOURCE" \
          admin@92.113.144.161:"$PLUSZAP_PATH/$TARGET" 2>/dev/null
    else
        rsync -avz --delete --force \
          -e "sshpass -p 'Leonetto1982' ssh -o StrictHostKeyChecking=no" \
          "$SOURCE/" admin@92.113.144.161:"$PLUSZAP_PATH/$TARGET/" 2>/dev/null
    fi
    echo "OK"
fi

echo ""
echo "=========================================="
echo " DEPLOY FINALIZADO - $(date)"
echo " $TARGET enviado para todos os servidores"
echo "=========================================="
