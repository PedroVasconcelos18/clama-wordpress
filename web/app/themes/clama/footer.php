<?php

if (! defined('ABSPATH')) {
    exit;
}

?>
<footer class="mt-24 border-t border-clama-gold/15 py-10">
    <div class="mx-auto flex max-w-[1240px] flex-col items-start justify-between gap-4 px-6 sm:flex-row sm:items-center sm:px-8">
        <div class="flex items-center gap-3">
            <span class="font-serif text-lg text-clama-gold">Clama</span>
            <span aria-hidden="true" class="text-xs text-clama-cream/45">·</span>
            <span class="text-xs italic text-clama-cream/55">uma vela acesa no escuro</span>
        </div>
        <div class="flex gap-5 text-xs text-clama-cream/55">
            <a href="/sobre" class="hover:text-clama-gold">Sobre</a>
            <a href="/privacidade" class="hover:text-clama-gold">Privacidade</a>
            <a href="/contato" class="hover:text-clama-gold">Contato</a>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
