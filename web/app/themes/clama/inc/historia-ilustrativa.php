<?php

/**
 * Marcação de história ilustrativa (Story 2.7).
 *
 * ⚠️ Isto é requisito legal, não item de UI. O **CDC art. 37** trata
 * publicidade enganosa; uma história apresentada como relato real quando não é
 * cai nisso. Remover o badge, escondê-lo no rodapé ou trocar o texto por algo
 * mais leve não é decisão de design.
 *
 * Equivale a `Post.historia_ilustrativa` (`clama/blog/models.py:40`).
 */

if (! defined('ABSPATH')) {
    exit;
}

const CLAMA_META_HISTORIA = 'clama_historia_ilustrativa';

/** Texto do aviso. Literal — não parafrasear. */
const CLAMA_AVISO_HISTORIA = 'História ilustrativa — não é um relato literal';

add_action('init', function () {
    register_post_meta('post', CLAMA_META_HISTORIA, [
        'type' => 'boolean',
        'single' => true,
        'default' => false,
        'show_in_rest' => true,
        'description' => 'Marca o post como história ilustrativa (CDC art. 37).',
        // Só quem pode editar o post pode mudar a marcação.
        'auth_callback' => fn() => current_user_can('edit_posts'),
    ]);
});

function clama_e_historia_ilustrativa(int $post_id): bool
{
    return (bool) get_post_meta($post_id, CLAMA_META_HISTORIA, true);
}

/**
 * Painel no editor.
 *
 * Um script de ~1 KB em vez de metabox clássica: o editor de blocos renderiza
 * metabox clássica numa área separada, embaixo, longe de onde a decisão é
 * tomada.
 */
add_action('enqueue_block_editor_assets', function () {
    $asset = CLAMA_THEME_DIR . '/assets/editor/painel-historia/index.asset.php';

    if (! file_exists($asset)) {
        return;
    }

    $meta = require $asset;

    wp_enqueue_script(
        'clama-painel-historia',
        get_template_directory_uri() . '/assets/editor/painel-historia/index.js',
        $meta['dependencies'],
        $meta['version'],
        true,
    );
});
