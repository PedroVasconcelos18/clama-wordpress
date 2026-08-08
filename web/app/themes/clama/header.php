<?php

/**
 * Cabeçalho. Reproduz o `<header>` sticky dos mockups de
 * `clama-docs/blog-design/`.
 *
 * O cluster de usuário logado (avatar + "Sair") **não** é renderizado aqui.
 * Sob a ADR-01, o cookie de sessão tem escopo `Path=/api` e o servidor
 * WordPress nunca o recebe — ele não tem como saber quem está logado, e
 * fingir que sabe produziria cache envenenado. Esse cluster é ilha do Django
 * (Story 3.8).
 */

if (! defined('ABSPATH')) {
    exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('min-h-full bg-clama-night text-clama-cream font-sans antialiased'); ?>>
<?php wp_body_open(); ?>

<a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded focus:bg-clama-gold focus:px-3 focus:py-2 focus:text-clama-night">
    Pular para o conteúdo
</a>

<header class="sticky top-0 z-30 border-b border-clama-gold/15 bg-clama-night/80 backdrop-blur">
    <?php if (is_singular('post')) : ?>
        <?php // O trilho tem altura fixa e já está no DOM com a barra em 0 —
              // por isso a barra nunca desloca layout ao aparecer.?>
        <div class="absolute left-0 right-0 top-0 h-[3px] bg-clama-night-deep"
             role="progressbar"
             aria-label="Progresso de leitura"
             aria-valuenow="0"
             aria-valuemin="0"
             aria-valuemax="100">
            <div id="clama-progresso" class="h-full w-0 bg-gradient-to-r from-clama-gold-soft via-clama-gold to-clama-gold-soft"></div>
        </div>
    <?php endif; ?>

    <div class="mx-auto flex h-16 max-w-[1240px] items-center justify-between px-6 sm:px-8">
        <?php
        /**
         * A marca leva ao Clama, não ao blog.
         *
         * Já apontou para `home_url('/')`, e o resultado era o wordmark e o
         * item "Blog" da navegação — vizinhos na mesma barra — indo para o
         * mesmo lugar. Além da redundância, quebrava a convenção que todo mundo
         * traz de outros sites: a marca no canto volta para a **casa da marca**.
         * Quem estava lendo um post e queria sair do blog não tinha por onde.
         */
        ?>
        <a href="<?php echo esc_url(clama_url_do_app('/')); ?>"
           class="font-serif text-2xl tracking-wide text-clama-gold transition-colors hover:text-clama-cream"
           aria-label="Clama — página inicial">
            Clama
        </a>

        <nav class="flex items-center gap-1 text-sm sm:gap-2" aria-label="Navegação principal">
            <a href="<?php echo esc_url(home_url('/')); ?>"
               class="rounded-md px-3 py-2 text-clama-cream transition-colors"
               <?php echo (is_home() || is_archive()) ? 'aria-current="page"' : ''; ?>>
                Blog
                <?php if (is_home() || is_archive()) : ?>
                    <?php // Filete dourado marca o item ativo. Cor sozinha não marcaria nada
                          // para quem não distingue as duas — daí o filete, e daí o
                          // aria-current acima para quem não vê nenhum dos dois.?>
                    <span aria-hidden="true" class="mt-0.5 block h-px bg-clama-gold"></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(clama_url_do_app('/conta')); ?>" class="rounded-md px-3 py-2 text-clama-cream/65 transition-colors hover:text-clama-cream">
                Conta
            </a>
        </nav>
    </div>
</header>
