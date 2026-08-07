<?php

/**
 * Estado vazio — reproduz `clama-docs/blog-design/_blog _ Estado vazio.html`.
 *
 * O tom aqui é o da regra de ouro pastoral: quem chega e não encontra nada
 * precisa sair com um caminho, não com um aviso técnico.
 */

if (! defined('ABSPATH')) {
    exit;
}

?>
<div class="mx-auto mt-8 max-w-3xl rounded-2xl border border-dashed border-clama-gold/25 bg-clama-night-deep/60 px-6 py-16 text-center">
    <div aria-hidden="true" class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full border border-clama-gold/40 bg-clama-gold/10">
        <span class="text-2xl">🕯️</span>
    </div>

    <h2 class="font-serif text-2xl text-clama-cream">Ainda nada por aqui.</h2>

    <p class="mx-auto mt-3 max-w-md text-[0.92rem] leading-relaxed text-clama-cream/55">
        A primeira oração está sendo escrita — ou peça a sua agora, e ela pode virar o próximo texto deste blog.
    </p>

    <a href="<?php echo esc_url(clama_url_do_app('/')); ?>"
       class="mt-7 inline-flex items-center gap-2 rounded-full bg-clama-gold px-6 py-3 text-sm font-semibold text-clama-night transition-colors hover:bg-clama-gold-soft">
        Pedir minha oração →
    </a>
</div>
