<?php

/**
 * Listagem do blog: hero, post em destaque e grid.
 *
 * Reproduz `clama-docs/blog-design/_blog _ Desktop _ logado.html` e
 * `_blog _ Mobile _ deslogado.html`.
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$eh_primeira_pagina = ! is_paged();
$tem_posts = have_posts();
?>

<section class="relative overflow-hidden">
    <div aria-hidden="true" class="gold-halo pointer-events-none absolute inset-0"></div>

    <div class="relative mx-auto max-w-[1240px] px-6 pb-14 pt-20 sm:px-8">
        <div class="eyebrow mb-5">Blog do Clama</div>

        <h1 class="font-serif text-[64px] leading-[1.02] tracking-[-0.015em] text-clama-cream md:text-[84px]">
            Orações, reflexões e <em class="font-serif not-italic text-clama-gold">histórias</em>
            <span class="text-clama-cream/75">pra caminhar com você.</span>
        </h1>

        <div class="mt-8 flex items-center gap-6">
            <div aria-hidden="true" class="flex max-w-[180px] flex-1 items-center gap-3">
                <div class="h-px flex-1 bg-clama-gold/20"></div>
                <span class="text-xs text-clama-gold-soft">✦</span>
                <div class="h-px flex-1 bg-clama-gold/20"></div>
            </div>
            <p class="text-sm italic text-clama-cream/55">publicamos quando há algo verdadeiro a dizer</p>
            <?php // O segundo filete some abaixo de `sm` — no mobile o texto ocupa a largura.?>
            <div aria-hidden="true" class="hidden max-w-[180px] flex-1 items-center gap-3 sm:flex">
                <div class="h-px flex-1 bg-clama-gold/20"></div>
                <span class="text-xs text-clama-gold-soft">✦</span>
                <div class="h-px flex-1 bg-clama-gold/20"></div>
            </div>
        </div>
    </div>
</section>

<main id="conteudo" class="relative mx-auto max-w-[1240px] px-6 pb-24 sm:px-8">
    <?php if (! $tem_posts) : ?>
        <?php get_template_part('parts/estado-vazio'); ?>
    <?php else : ?>
        <?php
        // Destaque só na primeira página. Nas seguintes, tudo vai para o grid —
        // "em destaque" na página 4 destacaria o quarto post mais recente, que
        // não é destaque de nada.
        $primeiro = true;
        $do_grid = [];

        while (have_posts()) :
            the_post();

            if ($eh_primeira_pagina && $primeiro) {
                $primeiro = false;
                ?>
                <div class="mb-16">
                    <?php get_template_part('parts/card-post', null, ['variante' => 'destaque']); ?>
                </div>
                <?php
                continue;
            }

            $do_grid[] = get_the_ID();
        endwhile;
        ?>

        <?php if ($do_grid !== []) : ?>
            <div class="mb-7 flex items-center justify-between">
                <h2 class="font-serif text-2xl tracking-[-0.01em] text-clama-cream sm:text-3xl">Publicações recentes</h2>
                <div class="hidden items-center gap-3 text-xs text-clama-cream/55 sm:flex">
                    <span class="eyebrow">
                        <?php
                        $total = (int) $GLOBALS['wp_query']->found_posts;
            echo esc_html($total . ' ' . ($total === 1 ? 'post' : 'posts'));
            ?>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($do_grid as $id) : ?>
                    <?php
                                $GLOBALS['post'] = get_post($id);
                    setup_postdata($GLOBALS['post']);
                    get_template_part('parts/card-post', null, ['variante' => 'grid']);
                    ?>
                <?php endforeach; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>

        <?php
        $paginacao = get_the_posts_pagination([
            'mid_size' => 1,
            'prev_text' => '← Anterior',
            'next_text' => 'Próxima →',
            'screen_reader_text' => 'Paginação',
        ]);

if ($paginacao) :
    ?>
            <nav class="mt-14 flex items-center justify-between border-t border-clama-gold/10 pt-6 text-sm text-clama-cream/70 [&_a:hover]:bg-clama-night-soft [&_a]:rounded-full [&_a]:border [&_a]:border-clama-gold/30 [&_a]:px-5 [&_a]:py-2 [&_a]:transition-colors"
                 aria-label="Paginação">
                <?php echo wp_kses_post($paginacao); ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
