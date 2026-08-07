<?php

/**
 * Tema Clama — setup.
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CLAMA_THEME_DIR', get_template_directory());

require_once CLAMA_THEME_DIR . '/inc/url-do-app.php';
require_once CLAMA_THEME_DIR . '/inc/apresentacao.php';
require_once CLAMA_THEME_DIR . '/inc/historia-ilustrativa.php';
require_once CLAMA_THEME_DIR . '/inc/csp.php';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ]);

    register_nav_menus(['principal' => 'Navegação principal']);
});

/**
 * Um único stylesheet, sem dependência externa.
 *
 * `filemtime` como versão em vez de string fixa: o cache da Vercel e do
 * navegador seguem o nome do arquivo, e uma versão manual esquecida serve CSS
 * velho por dias.
 */
add_action('wp_enqueue_scripts', function () {
    $arquivo = CLAMA_THEME_DIR . '/assets/css/app.build.css';

    wp_enqueue_style(
        'clama',
        get_template_directory_uri() . '/assets/css/app.build.css',
        [],
        file_exists($arquivo) ? filemtime($arquivo) : null,
    );
}, 5);

/**
 * O editor recebe a mesma folha do frontend.
 *
 * É o que faz a pré-visualização do bloco corresponder ao render publicado
 * (AC4 da Story 2.6). Duas folhas separadas divergem no primeiro ajuste que
 * alguém esquece de replicar.
 */
add_action('after_setup_theme', function () {
    add_editor_style('assets/css/app.build.css');
});

/**
 * O tema não carrega web font nenhuma (WP-UXDR1), e não carrega o CSS de
 * blocos do core: `prose-clama` já estiliza parágrafo, lista, citação e
 * imagem. Sem isso o core injeta ~9 KB inline em toda página para reestilizar
 * o que já está estilizado.
 *
 * `global-styles` (o `theme.json` compilado) **fica**. Ele é o que faz um
 * bloco com `has-clama-gold-color` significar alguma coisa no conteúdo do
 * post — remover quebraria a paleta dentro do editor.
 */
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
}, 20);

/** Cada bloco usado carrega só o seu CSS, em vez da folha inteira do core. */
add_filter('should_load_separate_core_block_assets', '__return_true');

/**
 * Blocos próprios. O estilo deles vive no `app.build.css` do tema, não numa
 * folha por bloco — são poucas regras e uma requisição a mais custa mais que
 * elas.
 */
add_action('init', function () {
    foreach (glob(CLAMA_THEME_DIR . '/assets/blocks/*/block.json') as $manifesto) {
        register_block_type(dirname($manifesto));
    }
});

/**
 * Barra de progresso — só no post, e só ela.
 *
 * `defer` e sem dependência: o script não precisa de jQuery nem de nada do
 * core, e adiar não muda nada visualmente porque a barra já nasce em 0.
 */
add_action('wp_enqueue_scripts', function () {
    if (! is_singular('post')) {
        return;
    }

    $arquivo = CLAMA_THEME_DIR . '/assets/js/progresso-leitura.js';

    wp_enqueue_script(
        'clama-progresso-leitura',
        get_template_directory_uri() . '/assets/js/progresso-leitura.js',
        [],
        file_exists($arquivo) ? filemtime($arquivo) : null,
        ['strategy' => 'defer', 'in_footer' => true],
    );
});

/**
 * Meta description — fallback até o plugin de SEO chegar (Story 5.1).
 *
 * Sem ela o Lighthouse reprova a auditoria "Document does not have a meta
 * description" e a nota de SEO trava em 91, abaixo do 100 que o `WP-NFR3`
 * exige. Com ela, 100.
 *
 * Sai do caminho sozinha quando o Rank Math ou o Yoast estiverem ativos —
 * duas descrições no `<head>` é pior que nenhuma, porque o buscador escolhe
 * uma e não avisa qual.
 */
add_action('wp_head', function () {
    if (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION')) {
        return;
    }

    $descricao = is_singular()
        ? get_the_excerpt()
        : get_bloginfo('description');

    $descricao = trim(wp_strip_all_tags((string) $descricao));

    if ($descricao === '') {
        return;
    }

    printf(
        '<meta name="description" content="%s">' . "\n",
        esc_attr(wp_html_excerpt($descricao, 160, '…')),
    );
}, 1);

/**
 * Widget de comentários e likes (Story 3.8).
 *
 * `wp-element` como dependência — o React do core do WordPress. É literalmente
 * o que decide se o widget cabe no orçamento de 80 KB: embarcar uma segunda
 * cópia do React custaria mais que todo o resto do bundle.
 *
 * O que o widget recebe do PHP: a base da API, o slug e o `wp_post_id`. **Nada
 * de dado pessoal atravessa nesta direção** — nome, e-mail, IP e conteúdo de
 * comentário vivem só no Django. O WordPress não recebe, não armazena e não
 * enxerga nenhum deles.
 */
add_action('wp_enqueue_scripts', function () {
    if (! is_singular('post')) {
        return;
    }

    $asset = CLAMA_THEME_DIR . '/assets/widget/widget-engajamento/index.asset.php';

    if (! file_exists($asset)) {
        return;
    }

    $meta = require $asset;

    wp_enqueue_script(
        'clama-widget-engajamento',
        get_template_directory_uri() . '/assets/widget/widget-engajamento/index.js',
        $meta['dependencies'],
        $meta['version'],
        ['strategy' => 'defer', 'in_footer' => true],
    );

    wp_localize_script('clama-widget-engajamento', 'clamaWidget', [
        // Vazio = mesma origem. O rewrite de `/api/*` da Story 1.2 é o que
        // torna a API same-origin e elimina o preflight de CORS — o ganho
        // **não** vem do rewrite do blog.
        'apiBase' => '',
        'postSlug' => get_post_field('post_name', get_the_ID()),
        'wpPostId' => get_the_ID(),
        'loginUrl' => clama_url_do_app('/login'),
    ]);
});

/** Emojis do core custam um script e uma requisição de sprite. Não usamos. */
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
});

/**
 * Excerpt: reticências pastorais em vez de `[...]`, e comprimento alinhado ao
 * `line-clamp-3` dos cards — texto maior que isso é cortado pelo CSS de
 * qualquer jeito, e o que é cortado ainda pesa no HTML.
 */
add_filter('excerpt_more', fn() => '…');
add_filter('excerpt_length', fn() => 32);
