<?php

/**
 * Ícone da aba — o do Clama, não o do WordPress.
 *
 * Sem isto o WordPress não declara `<link rel="icon">` nenhum, e o navegador
 * cai no `/favicon.ico` que o core responde: a aba do blog mostra a marca do
 * WordPress dentro de `clama.me/blog`.
 *
 * ⚠️ **Por que no tema e não no "Site Icon" do painel.** A opção do WordPress
 * guarda o ícone como anexo de mídia — ou seja, no banco e no diretório de
 * uploads, que aqui é o volume do Railway. Isso torna o ícone **dado**, e não
 * código: some numa restauração de backup mal feita, não aparece numa instância
 * nova antes de alguém reconfigurar pelo painel, e não é revisável em PR. No
 * tema ele é versionado e deploya junto.
 *
 * Os arquivos são cópia dos do app (`clama-frontend/public/`), de propósito: a
 * aba precisa ser a mesma marca em `clama.me` e em `clama.me/blog`. Se o app
 * trocar o ícone, trocar aqui também — ver o teste que trava isso.
 *
 * @package clama
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Emite as declarações de ícone.
 *
 * SVG primeiro: é nítido em qualquer densidade e pesa 879 bytes. O
 * apple-touch-icon é PNG porque o iOS não aceita SVG ao salvar na tela inicial.
 */
function clama_declarar_icones(): void
{
    $base = get_template_directory_uri() . '/assets/img';
    $versao = wp_get_theme()->get('Version');

    printf(
        '<link rel="icon" type="image/svg+xml" href="%s">' . "\n",
        esc_url("$base/favicon.svg?v=$versao"),
    );

    printf(
        '<link rel="apple-touch-icon" sizes="180x180" href="%s">' . "\n",
        esc_url("$base/apple-touch-icon.png?v=$versao"),
    );
}

// No site e no painel: quem escreve o post também vê a aba, e ver o ícone do
// WordPress ali é o mesmo sintoma.
add_action('wp_head', 'clama_declarar_icones');
add_action('admin_head', 'clama_declarar_icones');
add_action('login_head', 'clama_declarar_icones');
