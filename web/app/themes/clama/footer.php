<?php

if (! defined('ABSPATH')) {
    exit;
}

?>
<footer class="mt-24 border-t border-clama-gold/15 py-10">
    <div class="mx-auto flex max-w-[1240px] flex-col items-start justify-between gap-4 px-6 sm:flex-row sm:items-center sm:px-8">
        <div class="flex items-center gap-3">
            <span class="font-serif text-lg text-clama-gold">Clama</span>
            <?php // /55 e não /45 como no mockup: a /45 o separador dá 4,27:1 contra
                  // o fundo, abaixo do mínimo AA de 4,5:1 (NFR23). É decorativo e
                  // aria-hidden, então dava para argumentar isenção — mas subir uma
                  // parada de opacidade custa menos que o argumento. /55 dá 5,84:1.?>
            <span aria-hidden="true" class="text-xs text-clama-cream/55">·</span>
            <span class="text-xs italic text-clama-cream/55">uma vela acesa no escuro</span>
        </div>
        <div class="flex gap-5 text-xs text-clama-cream/55">
            <a href="<?php echo esc_url(clama_url_do_app('/sobre')); ?>" class="hover:text-clama-gold">Sobre</a>
            <a href="<?php echo esc_url(clama_url_do_app('/privacidade')); ?>" class="hover:text-clama-gold">Privacidade</a>
            <a href="<?php echo esc_url(clama_url_do_app('/contato')); ?>" class="hover:text-clama-gold">Contato</a>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
