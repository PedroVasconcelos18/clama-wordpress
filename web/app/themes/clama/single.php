<?php

/**
 * Post individual.
 *
 * Reproduz `clama-docs/blog-design/_blog_slug_ _ Desktop _ logado.html`.
 * Larguras do design: header do post `max-w-[820px]`, capa `max-w-[1040px]`,
 * corpo `max-w-[680px]` — a medida de leitura é mais estreita que a capa de
 * propósito.
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    $post_id = get_the_ID();
    ?>

    <header class="relative">
        <div aria-hidden="true" class="gold-halo pointer-events-none absolute inset-0"></div>

        <div class="relative mx-auto max-w-[820px] px-6 pb-10 pt-16 sm:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>"
               class="eyebrow inline-flex items-center gap-1.5 text-clama-cream/55 transition-colors hover:text-clama-gold">
                <span aria-hidden="true">←</span> Voltar ao blog
            </a>

            <div class="eyebrow mb-4 mt-7"><?php echo esc_html(clama_categoria_principal($post_id)); ?></div>

            <h1 class="font-serif text-[56px] leading-[1.03] tracking-[-0.018em] text-clama-cream md:text-[68px]">
                <?php the_title(); ?>
            </h1>

            <div class="mt-7 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-3">
                    <div aria-hidden="true" class="relative inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-clama-gold/30 bg-clama-night-soft/70 font-serif text-lg text-clama-gold">
                        <?php echo esc_html(clama_inicial_do_autor($post_id)); ?>
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm text-clama-cream"><?php the_author(); ?></div>
                        <div class="text-xs text-clama-cream/55">
                            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(clama_data_relativa($post_id)); ?></time>
                        </div>
                    </div>
                </div>

                <span aria-hidden="true" class="hidden h-5 w-px bg-clama-gold/20 sm:inline-block"></span>

                <div class="inline-flex items-center gap-2 rounded-full border border-clama-gold/25 bg-clama-night-deep px-3 py-1.5 text-xs text-clama-gold-soft">
                    <span aria-hidden="true">◷</span>
                    <?php echo esc_html(clama_tempo_de_leitura($post_id)); ?> min de leitura
                </div>

                <?php if (clama_e_historia_ilustrativa($post_id)) : ?>
                    <?php
                    // CDC art. 37. Fica no cabeçalho, na linha de autoria — quem
                    // lê precisa saber antes de começar, não depois de terminar.
                    // `role="note"` faz o leitor de tela anunciar como aviso em
                    // vez de texto solto.
                    ?>
                    <span role="note"
                          class="rounded-full border border-clama-gold/30 bg-clama-gold/10 px-3 py-1 font-sans text-[0.66rem] font-semibold uppercase tracking-[0.16em] text-clama-gold">
                        <?php echo esc_html(CLAMA_AVISO_HISTORIA); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="relative mx-auto max-w-[1040px] px-6 sm:px-8">
        <?php if (has_post_thumbnail()) : ?>
            <div class="relative aspect-[16/9] overflow-hidden rounded-2xl border border-clama-gold/15">
                <?php the_post_thumbnail('full', ['class' => 'h-full w-full object-cover', 'alt' => '']); ?>
                <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-clama-night/40"></div>
            </div>
        <?php else : ?>
            <div aria-hidden="true" class="relative aspect-[16/9] overflow-hidden rounded-2xl border border-clama-gold/15 <?php echo esc_attr(clama_placeholder_de_capa($post_id)); ?>">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-clama-night/40"></div>
            </div>
        <?php endif; ?>
    </div>

    <main id="conteudo" class="relative mx-auto max-w-[680px] px-6 sm:px-8">
        <article class="prose-clama mt-12">
            <?php the_content(); ?>
        </article>

        <?php
        // Curtir, comentar e compartilhar são ilhas do Django (Story 3.8). O
        // WordPress não recebe o cookie de sessão — ADR-01 — então nada aqui
        // pode depender de saber quem está lendo.
    ?>
        <?php // Primeira posição: logo depois do corpo, ANTES do widget.
              //
              // ⚠️ Este CTA já esteve depois do widget, e o resultado era os dois
              // CTAs colados com 55px entre eles. O comentário original dizia
              // "depois de onde os comentários entram" — mas os comentários não
              // entram entre os dois: o widget da Story 3.8 os renderiza dentro
              // da própria âncora, acima de tudo isto. A separação que o desenho
              // pressupunha nunca existiu.?>
        <div class="mt-14">
            <?php get_template_part('parts/cta-clamar', null, ['variante' => 'bottom']); ?>
        </div>

        <div id="clama-widget-engajamento" data-post-slug="<?php echo esc_attr(get_post_field('post_name', $post_id)); ?>" class="mt-14"></div>

        <?php // Segunda posição: fim absoluto, depois dos comentários. Copy mais
              // curta de propósito — quem chegou até aqui já leu o argumento longo.?>
        <div class="mb-24 mt-14">
            <?php get_template_part('parts/cta-clamar', null, ['variante' => 'after-comments']); ?>
        </div>
    </main>

<?php endwhile; ?>

<?php get_footer(); ?>
