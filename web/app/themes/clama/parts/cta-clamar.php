<?php

/**
 * CTA de pedido de oração (Story 2.8). `$args['variante']` é 'bottom' ou
 * 'after-comments'.
 *
 * ⚠️ A copy abaixo é copy de produto, reproduzida literalmente de
 * `clama-frontend/src/components/blog/CTAClamar.tsx:12-25`. Não reescrever,
 * não "melhorar", não encurtar.
 *
 * O card inteiro é UM `<a>` — aqui, ao contrário do card de post, isso é
 * correto: o conteúdo é uma frase e um botão, não parágrafos e listas, e o
 * destino é único.
 */

if (! defined('ABSPATH')) {
    exit;
}

$variante = $args['variante'] ?? 'bottom';
$compacto = $variante === 'after-comments';

$copy = $compacto
    ? [
        'eyebrow' => 'Sua oração personalizada',
        'titulo' => 'Pronta pra clamar?',
        'sub' => 'Faça seu pedido — a gente devolve uma oração escrita em até 1h.',
        'cta' => 'Faça seu pedido →',
    ]
    : [
        'eyebrow' => 'Sua oração personalizada',
        'titulo' => 'Sua dor merece uma oração escrita especialmente pra você.',
        'sub' => 'O Clama escreve orações personalizadas — pra um luto, uma decisão difícil, um filho rebelde, uma cura que demora. Conta o que tá no peito, a gente cuida do resto.',
        'cta' => 'Quero clamar →',
    ];
?>

<a href="/"
   rel="nofollow"
   aria-label="Quero pedir uma oração"
   data-slot="cta-clamar"
   data-variant="<?php echo esc_attr($variante); ?>"
   class="group relative block overflow-hidden rounded-2xl border border-clama-gold/35 transition-colors hover:border-clama-gold/60 <?php echo $compacto ? 'px-6 py-8 sm:px-9 sm:py-10' : 'px-7 py-10 sm:px-12 sm:py-14'; ?>">

    <?php // Filete superior e brilho radial — decorativos, fora da árvore de acessibilidade.?>
    <span aria-hidden="true" class="absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-clama-gold/60 to-transparent"></span>
    <span aria-hidden="true" class="gold-halo pointer-events-none absolute inset-0"></span>

    <span class="relative <?php echo $compacto ? 'flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between' : 'grid items-center gap-6 md:grid-cols-[1.4fr_auto]'; ?>">
        <span class="block">
            <span class="eyebrow block"><?php echo esc_html($copy['eyebrow']); ?></span>

            <span class="mt-3 block font-serif leading-[1.12] tracking-[-0.01em] text-clama-cream <?php echo $compacto ? 'text-[24px] sm:text-[28px]' : 'text-[28px] sm:text-[34px]'; ?>">
                <?php echo esc_html($copy['titulo']); ?>
            </span>

            <span class="mt-3 block text-[15px] leading-[1.6] text-clama-cream/70">
                <?php echo esc_html($copy['sub']); ?>
            </span>
        </span>

        <span class="inline-flex shrink-0 items-center gap-2 rounded-full bg-clama-gold px-6 py-3 text-sm font-semibold text-clama-night transition-colors group-hover:bg-clama-gold-soft">
            <?php echo esc_html($copy['cta']); ?>
        </span>
    </span>
</a>
