#!/usr/bin/env bash
set -euo pipefail

# ─────────────────────────────────────────────────────────────────────────────
# O container é descartável. Estas duas coisas NÃO podem ser:
#
#   /data/database  → o banco SQLite inteiro
#   /data/uploads   → as imagens dos posts
#
# 🔴 Se o volume do Railway não estiver montado em /data, cada deploy sobe um
# site em branco — sem erro, sem log, sem nada que pareça quebrado. É o modo
# de falha mais perigoso deste arquivo, e por isso ele aborta abaixo em vez de
# seguir bonito.
# ─────────────────────────────────────────────────────────────────────────────

DADOS="${CLAMA_DATA_DIR:-/data}"

if [ ! -d "$DADOS" ]; then
    echo "ERRO: $DADOS não existe. O volume do Railway não está montado." >&2
    echo "      Sem ele, banco e uploads morrem a cada deploy." >&2
    exit 1
fi

mkdir -p "$DADOS/database" "$DADOS/uploads"

# Uploads: o WordPress escreve em web/app/uploads por convenção do Bedrock.
# Em vez de reconfigurar o WordPress (que espalharia caminho absoluto pelo
# banco, quebrando na próxima mudança de host), apontamos o caminho para o
# volume. As URLs continuam relativas e portáveis.
rm -rf /app/web/app/uploads
ln -sfn "$DADOS/uploads" /app/web/app/uploads

chown -R www-data:www-data "$DADOS/database" "$DADOS/uploads"

# O Railway injeta PORT e espera que o processo escute nela.
PORTA="${PORT:-8080}"
sed -i "s/PORTA_DO_RAILWAY/${PORTA}/" /etc/apache2/sites-available/000-default.conf
sed -i "s/^Listen .*/Listen ${PORTA}/" /etc/apache2/ports.conf

echo "clama-blog: docroot=/app/web  porta=${PORTA}  dados=${DADOS}"
echo "clama-blog: banco=${DB_DIR:-$DADOS/database/}${DB_FILE:-.ht.sqlite}"

exec "$@"
