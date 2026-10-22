# clama-wordpress

Blog do Clama em WordPress, servido em `https://clama.me/blog`.

Este é o **quarto repositório** do projeto, ao lado de `clama-backend/` (Django), `clama-frontend/` (React/Vike) e `clama-docs/`. Eles não compartilham dependências e têm deploys independentes.

O WordPress **não é autoridade sobre identidade**. Autenticação, dados de cliente e pedidos de oração vivem no Django; este repositório serve conteúdo editorial e nada mais.

---

## Por que Bedrock

O modo padrão de operar WordPress — instalar plugin clicando no admin — é incompatível com as regras já escritas do projeto: nada de dependência sem necessidade clara, tudo versionado, revisão em PR.

[Bedrock](https://roots.io/bedrock/) aplica Twelve-Factor ao WordPress e reconcilia os dois. O custo é um setup inicial maior que um WordPress "clica e usa"; é custo aceito conscientemente.

**Instalar plugin pelo painel está ligado** (`DISALLOW_FILE_MODS` e `DISALLOW_FILE_EDIT` com default `false`, mesma decisão da A.Lab). Funciona porque `docker/docker-entrypoint.sh` aponta `plugins`, `themes`, `languages` e `upgrade` para o volume — sem isso o deploy seguinte apagaria o que o painel instalou, sem erro.

| origem do plugin | no deploy |
| --- | --- |
| `composer.json` | a **imagem** sobrescreve o volume, todo boot |
| painel | o **volume** manda, e sobrevive |

Plugin removido do `composer.json` fica órfão no volume e ativo — remoção manual com `wp plugin delete <nome>`. O que entra pelo painel não passa pela [política de plugins](../clama-docs/politica-de-plugins-wordpress.md) nem pelo lock; infraestrutura do blog continua entrando por `composer require` + PR.

Para endurecer de volta sem código: `DISALLOW_FILE_MODS=true` e/ou `DISALLOW_FILE_EDIT=true` nas variáveis do Railway.

---

## Estrutura

```text
clama-wordpress/
├── composer.json        # core, plugins e tema declarados
├── composer.lock        # versões travadas — é o lock que o deploy instala
├── config/
│   ├── application.php  # config base = PRODUÇÃO (convenção Bedrock)
│   └── environments/    # overrides por WP_ENV
├── web/                 # ← DOCUMENT ROOT
│   ├── app/             # equivale a wp-content
│   │   ├── themes/clama/    # tema próprio (Story 2.5), versionado
│   │   ├── plugins/         # instalados por Composer, ignorados pelo git
│   │   └── uploads/         # mídia — NUNCA no repositório
│   └── wp/              # core do WordPress, instalado por Composer
├── vendor/              # fora do docroot
└── .env                 # fora do docroot, nunca commitado
```

`config/`, `vendor/`, `composer.*` e `.env` ficam **fora** de `web/`. Uma requisição ao document root não os alcança.

`web/app/uploads` é o análogo exato de `clama-backend/media/` e `clama-frontend/node_modules/`: pertence ao storage do provedor, nunca ao repositório.

---

## Requisitos

| | Versão | Por quê |
| --- | --- | --- |
| PHP | **8.3+** | Bedrock 1.31 exige `>=8.3` |
| WordPress | **7.0.2** | travado em `composer.json`, não em range |
| Banco | **MySQL 8.4 LTS** ou **MariaDB 11.4 LTS** | MySQL 8.0 chegou ao EOL em abril/2026 |

### `config.platform.php` está fixado em `8.3.0`

```json
"config": { "platform": { "php": "8.3.0" } }
```

Isso não é decoração. Sem esse pin, o Composer resolve contra o PHP da máquina de quem rodou `composer update` — resolver num laptop com PHP 8.5 pode travar pacotes que não instalam num host com 8.3. O pin faz o lockfile descrever o **runtime de destino**, não o de quem gerou.

Ao subir o PHP do host, mude o pin no mesmo PR.

---

## Desenvolvimento local

```bash
composer install
cp .env.example .env      # e preencha; salts em https://roots.io/salts.html

# banco local
docker run -d --name clama-wp-mariadb \
  -e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=clama_wp \
  -e MARIADB_USER=clama -e MARIADB_PASSWORD=clama \
  -p 3307:3306 mariadb:11.4

wp core install --url=http://localhost:8080 --title="Blog do Clama" \
  --admin_user=<usuario> --admin_password=<senha> --admin_email=<email> --skip-email

php -S 127.0.0.1:8080 -t web
```

Em `WP_ENV=development` `DISALLOW_INDEXING` vira `true` e `DISALLOW_FILE_MODS`/`DISALLOW_FILE_EDIT` ficam `false` mesmo que produção seja endurecida por variável.

---

## Deploy

O deploy instala **a partir do lockfile**:

```bash
composer install --no-dev --optimize-autoloader
```

`composer install` (sem `update`) lê `composer.lock` e nunca re-resolve. É isso que faz um push publicar exatamente as versões travadas — a saída do comando é literalmente `Installing dependencies from lock file`.

**Nunca rodar `composer update` no host.** Atualização de dependência é mudança de código: roda-se localmente, o `composer.lock` alterado entra no PR, e o deploy publica o resultado revisado.

### Antes de abrir PR

```bash
composer validate --strict   # composer.json íntegro e lock em sincronia
composer lint                # pint --test
composer test                # pest
```

> ⚠️ **Não existe CI em nenhum repositório do Clama.** Não há `.github/` em lugar nenhum; nada roda em push ou PR hoje. Os comandos acima são disciplina manual, não gate automático. Se um gate for desejado, é construção do zero — não extensão de algo existente.

---

## Fronteiras que não podem ser cruzadas

- **`web/app/uploads` nunca entra no git.** O `.gitignore` cobre; se algum dia um `git add -f` aparecer num PR, é rejeição automática.
- **`.env` nunca é commitado.** `.env.example` documenta as variáveis.
- **Nada de `composer update` no host.**
- **O WordPress nunca recebe credencial do Clama.** O cookie de autenticação do Django tem escopo `Path=/api`; o navegador não o envia para `/blog/*`. Ver ADR-01.
- **`WP_HOME` em produção é `https://clama.me/blog`**, não o hostname da origem — senão o WordPress emite URLs absolutas da origem e vaza o hostname.
- **Todo plugin passa pela política.** Entrada em `config/plugins-aprovados.json` com as três justificativas (necessidade, o que injeta no frontend, histórico de CVE), ou `composer test` falha. Um plugin de segurança, só. Ver [política completa](../clama-docs/politica-de-plugins-wordpress.md).

---

## Licença

Este repositório é **proprietário**. O esqueleto veio do [Bedrock](https://roots.io/bedrock/), que é MIT — a licença original está preservada em [`LICENSE-bedrock.md`](LICENSE-bedrock.md) e cobre os arquivos do esqueleto, não o código do Clama.
