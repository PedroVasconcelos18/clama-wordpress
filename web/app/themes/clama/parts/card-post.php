<?php

/**
 * Card de post. `$args['variante']` é 'destaque' ou 'grid'.
 *
 * ⚠️ O padrão de card clicável é deliberado e não deve ser "simplificado":
 * o `<article>` é `relative` e tem como PRIMEIRO filho um `<a>` de
 * `absolute inset-0` com `aria-label`. O título não é link.
 *
 * A alternativa ingênua — envolver o card inteiro num `<a>` — coloca
 * parágrafos e listas dentro de um link, o que quebra a semântica e faz o
 * leitor de tela anunciar o card inteiro como um rótulo só. Este padrão dá
 * área clicável grande com um destino nomeado.
 */

if (! defined('ABSPATH')) {
    exit;
}

$variante = $args['variante'] ?? 'grid';
$post_id = get_the_ID();
$destaque = $variante === 'destaque';

$placeholder = clama_placeholder_de_capa($post_id);
$likes = clama_contagem_de_likes($post_id);
$comentarios = clama_contagem_de_comentarios($post_id);

$classes_article = $destaque
    ? 'group relative grid gap-0 overflow-hidden rounded-2xl border border-clama-gold/20 bg-clama-night-deep transition-colors hover:border-clama-gold/45 md:grid-cols-[1.15fr_1fr]'
    : 'group relative flex flex-col overflow-hidden rounded-xl border border-clama-gold/15 bg-clama-night-deep transition-all duration-200 hover:-translate-y-0.5 hover:border-clama-gold/40';
?>

<article class="<?php echo esc_attr($classes_article); ?>">
    <a href="<?php the_permalink(); ?>"
       class="absolute inset-0 z-10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clama-gold"
       aria-label="<?php echo esc_attr(get_the_title()); ?>"></a>

    <?php if (has_post_thumbnail()) : ?>
        <div class="relative <?php echo $destaque ? 'aspect-[16/10] md:aspect-auto' : 'aspect-[16/9]'; ?> overflow-hidden">
            <?php the_post_thumbnail($destaque ? 'large' : 'medium_large', [
                'class' => 'h-full w-full object-cover',
                'loading' => 'lazy',
                'alt' => '',
            ]); ?>
            <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-clama-night-deep/60 via-transparent to-transparent"></div>
        </div>
    <?php else : ?>
        <?php // Sem capa: gradiente decorativo, não uma imagem quebrada nem um alt mentiroso.?>
        <div aria-hidden="true" class="relative <?php echo $destaque ? 'aspect-[16/10] md:aspect-auto' : 'aspect-[16/9]'; ?> <?php echo esc_attr($placeholder); ?>">
            <div class="absolute inset-0 <?php echo $destaque
                ? 'bg-gradient-to-tr from-clama-night-deep/70 via-transparent to-transparent'
                : 'bg-gradient-to-t from-clama-night-deep/60 via-transparent to-transparent'; ?>"></div>
        </div>
    <?php endif; ?>

    <div class="<?php echo $destaque ? 'flex flex-col p-7 md:p-9' : 'flex flex-1 flex-col p-6'; ?>">
        <div class="eyebrow <?php echo $destaque ? 'mb-4' : 'mb-3'; ?>">
            <?php echo esc_html(clama_categoria_principal($post_id)); ?>
        </div>

        <?php if ($destaque) : ?>
            <h2 class="font-serif text-[34px] leading-[1.08] tracking-[-0.01em] text-clama-cream transition-colors group-hover:text-clama-gold md:text-[40px]">
                <?php the_title(); ?>
            </h2>
            <p class="mt-4 line-clamp-3 text-[15.5px] leading-[1.65] text-clama-cream/70"><?php echo esc_html(get_the_excerpt()); ?></p>
        <?php else : ?>
            <h3 class="font-serif text-[22px] leading-[1.18] tracking-[-0.005em] text-clama-cream transition-colors group-hover:text-clama-gold">
                <?php the_title(); ?>
            </h3>
            <p class="mt-3 line-clamp-3 text-[14.5px] leading-[1.6] text-clama-cream/65"><?php echo esc_html(get_the_excerpt()); ?></p>
        <?php endif; ?>

        <div class="mt-auto <?php echo $destaque ? 'pt-7' : 'pt-5'; ?>">
            <div aria-hidden="true" class="flex items-center gap-3 <?php echo $destaque ? 'mb-4' : 'mb-3.5'; ?>">
                <div class="h-px flex-1 bg-clama-gold/20"></div>
                <span class="text-xs text-clama-gold-soft">✦</span>
                <div class="h-px flex-1 bg-clama-gold/20"></div>
            </div>

            <div class="flex items-center justify-between <?php echo $destaque ? 'gap-4' : 'gap-3'; ?>">
                <div class="flex min-w-0 items-center <?php echo $destaque ? 'gap-3' : 'gap-2.5'; ?>">
                    <div aria-hidden="true"
                         class="relative inline-flex shrink-0 items-center justify-center rounded-full border border-clama-gold/30 bg-clama-night-soft/70 font-serif text-clama-gold <?php echo $destaque ? 'h-10 w-10 text-base' : 'h-9 w-9 text-sm'; ?>">
                        <?php echo esc_html(clama_inicial_do_autor($post_id)); ?>
                    </div>
                    <div class="min-w-0 leading-tight">
                        <div class="truncate text-clama-cream <?php echo $destaque ? 'text-sm' : 'text-[13px]'; ?>">
                            <?php the_author(); ?>
                        </div>
                        <div class="text-clama-cream/55 <?php echo $destaque ? 'text-xs' : 'text-[11.5px]'; ?>">
                            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(clama_data_relativa($post_id)); ?></time>
                        </div>
                    </div>
                </div>

                <ul class="flex shrink-0 items-center text-clama-cream/55 <?php echo $destaque ? 'gap-4 text-xs' : 'gap-3 text-[11.5px]'; ?>">
                    <?php // Likes e comentários vivem no Django. Enquanto o widget da Story 3.8
                          // não existir, omitimos — zero é uma afirmação, e seria falsa.?>
                    <?php if ($likes !== null) : ?>
                        <li class="inline-flex items-center gap-1">
                            <span aria-hidden="true">♥</span>
                            <span><span class="sr-only">curtidas: </span><?php echo esc_html((string) $likes); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if ($comentarios !== null) : ?>
                        <li class="inline-flex items-center gap-1">
                            <span aria-hidden="true">💬</span>
                            <span><span class="sr-only">comentários: </span><?php echo esc_html((string) $comentarios); ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="inline-flex items-center gap-1">
                        <span aria-hidden="true">◷</span>
                        <span><?php echo esc_html(clama_tempo_de_leitura($post_id)); ?> min<span class="sr-only"> de leitura</span></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</article>
