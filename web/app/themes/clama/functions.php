<?php

/**
 * Tema Clama — setup.
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CLAMA_THEME_DIR', get_template_directory());

require_once CLAMA_THEME_DIR . '/inc/apresentacao.php';

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
