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

# ─────────────────────────────────────────────────────────────────────────────
# Plugins, temas e traducoes: instalaveis pelo painel, e por isso no volume.
#
# `DISALLOW_FILE_MODS` esta DESLIGADO (config/application.php). Sem este bloco
# o painel instalaria, diria "Plugin ativado", e o proximo deploy apagaria.
#
# 🔴 PRECEDENCIA:
#   o que vem do composer.json (e o tema clama) → a IMAGEM ganha, todo boot
#   o que veio do painel                        → o VOLUME ganha, sobrevive
#
# Item a item, e nao `rsync --delete`: o `--delete` apagaria justamente o que o
# painel instalou. O preco: plugin removido do composer.json fica ORFAO no
# volume e continua ativo — remocao manual, `wp plugin delete <nome>`.
# ─────────────────────────────────────────────────────────────────────────────
persistir_no_volume() {
    nome="$1"
    origem="/app/web/app/$nome"
    destino="$DADOS/$nome"

    mkdir -p "$destino"

    # `-L`: se ja e symlink, a imagem nao tem mais o original e copiar daqui
    # copiaria o volume sobre si mesmo.
    if [ -d "$origem" ] && [ ! -L "$origem" ]; then
        for item in "$origem"/* "$origem"/.[!.]*; do
            [ -e "$item" ] || continue
            base=$(basename "$item")
            rm -rf "${destino:?}/$base"
            cp -a "$item" "$destino/"
        done
    fi

    rm -rf "$origem"
    ln -sfn "$destino" "$origem"
}

# ⚠️ `plugins` nao e opcional: `web/app/db.php` faz realpath em
# `plugins/sqlite-database-integration` em TODA requisicao. Se isto falhar, o
# `set -e` derruba o container — melhor que subir sem banco.
persistir_no_volume plugins
persistir_no_volume themes
persistir_no_volume languages

# Instalar plugin baixa o .zip para `upgrade/` antes de mover. Sem ele gravavel,
# o painel falha com "Nao foi possivel criar o diretorio", sem dizer qual.
mkdir -p "$DADOS/upgrade"
rm -rf /app/web/app/upgrade
ln -sfn "$DADOS/upgrade" /app/web/app/upgrade

# www-data em TUDO: um diretorio de dono errado no volume vira falha no meio
# da instalacao, mesmo com FS_METHOD=direct.
chown -R www-data:www-data \
    "$DADOS/database" "$DADOS/uploads" \
    "$DADOS/plugins" "$DADOS/themes" "$DADOS/languages" "$DADOS/upgrade"

# O Railway injeta PORT e espera que o processo escute nela.
PORTA="${PORT:-8080}"
sed -i "s/PORTA_DO_RAILWAY/${PORTA}/" /etc/apache2/sites-available/000-default.conf
sed -i "s/^Listen .*/Listen ${PORTA}/" /etc/apache2/ports.conf

echo "clama-blog: docroot=/app/web  porta=${PORTA}  dados=${DADOS}"
echo "clama-blog: banco=${DB_DIR:-$DADOS/database/}${DB_FILE:-.ht.sqlite}"

# ─────────────────────────────────────────────────────────────────────────────
# Um MPM, conferido na subida.
#
# O Dockerfile ja desabilita `mpm_event`/`mpm_worker`, e a imagem construida
# local em amd64 sai correta — `apache2ctl -M` lista so `mpm_prefork`. Ainda
# assim o container no Railway subiu com os dois habilitados e o Apache abortou
# com `AH00534: More than one MPM loaded`, em loop de restart.
#
# Nao consegui provar de onde vem o segundo: build e imagem estao certos, o
# runtime nao. Como o custo de conferir aqui e uma listagem de diretorio, e o
# custo de nao conferir e o site fora do ar, a verificacao fica na subida.
# ─────────────────────────────────────────────────────────────────────────────
habilitados=$(ls /etc/apache2/mods-enabled/ 2>/dev/null | grep -c 'mpm_.*\.load' || true)

if [ "$habilitados" -gt 1 ]; then
    echo "clama-blog: ATENCAO — $habilitados MPMs habilitados, corrigindo:" >&2
    ls /etc/apache2/mods-enabled/ | grep 'mpm_' >&2
    a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
    a2enmod mpm_prefork >/dev/null 2>&1 || true
    echo "clama-blog: agora: $(ls /etc/apache2/mods-enabled/ | grep 'mpm_.*\.load' | tr '\n' ' ')" >&2
fi

# ─────────────────────────────────────────────────────────────────────────────
# Provisionamento inicial — OPT-IN, e de proposito.
#
# Roda so quando CLAMA_PROVISIONAR=1. Depois do primeiro boot, remova a
# variavel.
#
# 🔴 Por que nao "instala sozinho se o banco nao existir": porque banco ausente
# tem duas causas — primeiro boot, e perda do volume. Instalar automaticamente
# nas duas trata catastrofe como rotina: o site volta no ar vazio, com 200 em
# tudo, e ninguem e avisado de que o conteudo sumiu. O flag separa as duas.
# ─────────────────────────────────────────────────────────────────────────────
if [ "${CLAMA_PROVISIONAR:-}" = "1" ]; then
    WP="wp --path=/app/web/wp --allow-root"

    if $WP core is-installed 2>/dev/null; then
        echo "clama-blog: WordPress ja instalado"
    else
        echo "clama-blog: instalando WordPress..."
        $WP core install \
            --url="${WP_HOME}" \
            --title="${CLAMA_TITULO:-Clama}" \
            --admin_user="${CLAMA_ADMIN_USER:?defina CLAMA_ADMIN_USER}" \
            --admin_password="${CLAMA_ADMIN_SENHA:?defina CLAMA_ADMIN_SENHA}" \
            --admin_email="${CLAMA_ADMIN_EMAIL:?defina CLAMA_ADMIN_EMAIL}" \
            --skip-email
    fi

    # Daqui para baixo e idempotente: ativar o que ja esta ativo e no-op.
    $WP theme activate clama || true

    # ⚠️ UM PLUGIN POR COMANDO. Ativar os dois de uma vez falha silenciosamente
    # — o WP-CLI aborta no meio e o segundo nunca ativa. Aconteceu local e de
    # novo no Railway: o tema subiu, o Rank Math nao, e a unica pista era a
    # ausencia do namespace `rankmath` no /wp-json.
    $WP plugin activate sqlite-database-integration || true
    $WP plugin activate seo-by-rank-math || true

    # ⚠️ Rank Math ANTES do flush: as regras do sitemap sao registradas na
    # ativacao. Invertendo, sitemap_index.xml da 404 sem nada indicar o motivo.
    $WP rewrite structure '/%postname%' --hard || true
    $WP rewrite flush --hard || true

    chown -R www-data:www-data "$DADOS"

    echo "clama-blog: provisionado. Plugins ativos:"
    $WP plugin list --status=active --field=name 2>/dev/null | sed 's/^/  - /'
    echo "clama-blog: REMOVA CLAMA_PROVISIONAR das variaveis."
fi

# ─────────────────────────────────────────────────────────────────────────────
# Application Password para o Django (integracao I3) — OPT-IN.
#
# O Django precisa dela para `buscar_post_por_slug`, que usa `context=edit` e
# portanto exige autenticacao. Sem ela, comentar num post do WordPress devolve
# 404: o espelho nao consegue ser criado sob demanda.
#
# Roda so com CLAMA_CRIAR_APP_PASSWORD=1. Imprime a senha UMA vez no log —
# nao ha outro canal, e o container nao tem shell exposto. Remova a variavel
# depois de copiar, e rotacione se o log for compartilhado.
# ─────────────────────────────────────────────────────────────────────────────
if [ "${CLAMA_CRIAR_APP_PASSWORD:-}" = "1" ]; then
    SENHA_APP=$(wp --path=/app/web/wp --allow-root user application-password create \
        "${CLAMA_ADMIN_USER:-pedro}" django-clama --porcelain 2>/dev/null || true)
    if [ -n "$SENHA_APP" ]; then
        echo "clama-blog: APP_PASSWORD=$SENHA_APP"
        echo "clama-blog: copie, defina no backend e REMOVA CLAMA_CRIAR_APP_PASSWORD."
    else
        echo "clama-blog: falhou ao criar application password" >&2
    fi
fi

exec "$@"
