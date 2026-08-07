<?php

/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 *
 * A good default policy is to deviate from the production config as little as
 * possible. Try to define as much of your configuration in this file as you
 * can.
 */

use Roots\WPConfig\Config;

use function Env\env;

// CONVERT_* + STRIP_QUOTES + LOCAL_FIRST
Env\Env::$options
    = Env\Env::CONVERT_BOOL
    | Env\Env::CONVERT_NULL
    | Env\Env::CONVERT_INT
    | Env\Env::STRIP_QUOTES
    | Env\Env::LOCAL_FIRST;

/**
 * Directory containing all of the site's files
 *
 * @var string
 */
$root_dir = dirname(__DIR__);

/**
 * Document Root
 *
 * @var non-falsy-string
 */
$webroot_dir = $root_dir . '/web';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * .env.local will override .env if it exists
 */
if (file_exists($root_dir . '/.env')) {
    $env_files = file_exists($root_dir . '/.env.local')
        ? ['.env', '.env.local']
        : ['.env'];

    $repository = Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(Dotenv\Repository\Adapter\EnvConstAdapter::class)
        ->addAdapter(Dotenv\Repository\Adapter\PutenvAdapter::class)
        ->immutable()
        ->make();

    $dotenv = Dotenv\Dotenv::create($repository, $root_dir, $env_files, false);
    $dotenv->load();

    $dotenv->required(['WP_HOME', 'WP_SITEURL']);

    // Em SQLite nao existe credencial de banco para exigir — o "banco" e um
    // arquivo no volume. Exigir DB_USER aqui faria o boot falhar com uma
    // mensagem que nao descreve o problema.
    if (env('DB_ENGINE') !== 'sqlite' && !env('DATABASE_URL')) {
        $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
    }
}

/**
 * Set up our global environment constant and load its config first
 * Default: production
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

/**
 * Set WP_ENVIRONMENT_TYPE if not already defined
 */
if (!defined('WP_ENVIRONMENT_TYPE')) {
    $wp_environment_type = env('WP_ENVIRONMENT_TYPE');

    if ($wp_environment_type) {
        Config::define('WP_ENVIRONMENT_TYPE', $wp_environment_type);
    } elseif (in_array(WP_ENV, ['production', 'staging', 'development', 'local'], true)) {
        Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
    }
}

/**
 * Set WP_DEVELOPMENT_MODE if explicitly configured
 */
if (!defined('WP_DEVELOPMENT_MODE')) {
    $wp_development_mode = env('WP_DEVELOPMENT_MODE');

    if ($wp_development_mode) {
        Config::define('WP_DEVELOPMENT_MODE', $wp_development_mode);
    }
}

/**
 * URLs
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * URL do app Clama (a SPA na raiz do dominio).
 *
 * O tema linka /conta, /sobre, /privacidade e o fluxo de pedido atraves de
 * `clama_url_do_app()`. Vazio = caminho absoluto de raiz, que e o correto em
 * producao (blog em /blog do mesmo dominio). Em desenvolvimento aponte para a
 * SPA rodando, senao esses links caem em 404 do WordPress e nao ha como
 * testa-los.
 */
Config::define('CLAMA_APP_URL', env('CLAMA_APP_URL') ?: '');

/**
 * Custom Content Directory
 */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

/**
 * DB settings
 */
$table_prefix = env('DB_PREFIX') ?: 'wp_';

if (env('DB_ENGINE') === 'sqlite') {
    /**
     * SQLite. O "banco" e um arquivo, entregue pelo drop-in `web/app/db.php`
     * (plugin `sqlite-database-integration`).
     *
     * 🔴 `DB_DIR` PRECISA apontar para dentro do volume persistente. O padrao
     * do plugin e `WP_CONTENT_DIR . '/database/'`, que no container vive na
     * imagem — cada deploy comecaria com um site em branco, sem erro nenhum.
     * Em local o padrao serve, porque nao ha container.
     */
    Config::define('DB_ENGINE', 'sqlite');
    Config::define('DB_DIR', env('DB_DIR') ?: $webroot_dir . '/app/database/');
    Config::define('DB_FILE', env('DB_FILE') ?: '.ht.sqlite');

    // O core e varios plugins leem estas constantes sem checar o engine.
    // Indefinidas, geram notice; com credencial de verdade, confundem.
    Config::define('DB_NAME', 'clama_sqlite');
    Config::define('DB_USER', '');
    Config::define('DB_PASSWORD', '');
    Config::define('DB_HOST', '');
    Config::define('DB_CHARSET', 'utf8mb4');
    Config::define('DB_COLLATE', '');
} else {
    if (env('DB_SSL')) {
        Config::define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
    }

    Config::define('DB_NAME', env('DB_NAME'));
    Config::define('DB_USER', env('DB_USER'));
    Config::define('DB_PASSWORD', env('DB_PASSWORD'));
    Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
    Config::define('DB_CHARSET', 'utf8mb4');
    Config::define('DB_COLLATE', '');

    if (env('DATABASE_URL')) {
        $dsn = (object) parse_url(env('DATABASE_URL'));

        Config::define('DB_NAME', substr($dsn->path, 1));
        Config::define('DB_USER', $dsn->user);
        Config::define('DB_PASSWORD', isset($dsn->pass) ? $dsn->pass : null);
        Config::define('DB_HOST', isset($dsn->port) ? "{$dsn->host}:{$dsn->port}" : $dsn->host);
    }
}

/**
 * Authentication Unique Keys and Salts
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * Custom Settings
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: false);

// Disable the plugin and theme file editor in the admin
Config::define('DISALLOW_FILE_EDIT', true);

// Disable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', true);

// Limit the number of post revisions
Config::define('WP_POST_REVISIONS', env('WP_POST_REVISIONS') ?? true);

// Disable script concatenation
Config::define('CONCATENATE_SCRIPTS', false);

// O Rank Math recusa carregar o frontend enquanto a "registration" for
// inválida — e ela é inválida até o site ser conectado a uma conta
// rankmath.com. Sem esta constante o plugin fica ativo e **não emite nada**:
// nem meta description, nem Open Graph, nem JSON-LD. Falha silenciosa, que só
// aparece inspecionando o HTML servido.
//
// `RANK_MATH_REGISTRATION_SKIP` é a saída prevista pelo próprio plugin
// (`Conditional::is_invalid_registration`). Não queremos conta em serviço de
// terceiro para um blog que usa só os módulos locais.
Config::define('RANK_MATH_REGISTRATION_SKIP', true);

// Indexação é decisão de ambiente, nunca dado de banco (Story 2.4). Definir
// aqui garante valor em TODO ambiente — sem isso a constante fica indefinida
// em produção e `blog_public` do banco volta a mandar, que é como um clone de
// staging desindexaria clama.me inteiro sob a ADR-03.
// Os arquivos de environments/ sobrescrevem para `true`.
Config::define('DISALLOW_INDEXING', false);

/**
 * Debugging Settings
 */
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('WP_DEBUG_LOG', false);
Config::define('SCRIPT_DEBUG', false);
ini_set('display_errors', '0');

/**
 * Allow WordPress to detect HTTPS when used behind a reverse proxy or a load balancer
 * See https://codex.wordpress.org/Function_Reference/is_ssl#Notes
 */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
    require_once $env_config;
}

Config::apply();

/**
 * Bootstrap WordPress
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}
