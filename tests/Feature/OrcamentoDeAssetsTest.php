<?php

/**
 * Orçamento de assets do blog (Story 2.9, AC4 e AC5).
 *
 * Substitui `clama-frontend/scripts/check-bundle-size.mjs`, que casa entradas
 * `pages_blog_*` do build do Vike e fica **sem sentido depois do Epic 7**,
 * quando `pages/blog/*` deixa de existir.
 *
 * A medição aqui é do que o tema serve. O que um plugin injetar não aparece —
 * por isso o AC5 exige nome, não número, e por isso a política de plugins da
 * Story 2.3 pede declarar o peso de frontend de cada plugin no PR.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

/** Bytes de um arquivo depois de gzip — é assim que ele viaja. */
function tamanhoGzip(string $caminho): int
{
    return strlen(gzencode(file_get_contents($caminho), 9));
}

const ORCAMENTO_JS_GZ = 80 * 1024;

test('o JavaScript do frontend cabe no orçamento de 80 KB gzipped', function () use ($tema) {
    // Só o que é enfileirado em página pública. `assets/blocks` e
    // `assets/editor` rodam no wp-admin e não contam para o leitor.
    $scripts = glob("$tema/assets/js/*.js");

    $total = array_sum(array_map('tamanhoGzip', $scripts));

    $detalhe = implode(', ', array_map(
        fn($s) => basename($s) . ' ' . tamanhoGzip($s) . 'B',
        $scripts,
    ));

    expect($total)->toBeLessThan(ORCAMENTO_JS_GZ, "JS do tema: $detalhe");
});

test('o JS do editor não vaza para o frontend', function () use ($tema) {
    // O build do bloco e o do painel só podem ser enfileirados em hooks de
    // admin. Se algum aparecer em `wp_enqueue_scripts`, o leitor paga por
    // código de edição que nunca vai usar.
    $functions = file_get_contents("$tema/functions.php");
    $historia = file_get_contents("$tema/inc/historia-ilustrativa.php");

    // O painel é enfileirado só em `enqueue_block_editor_assets`.
    expect($historia)->toContain("add_action('enqueue_block_editor_assets'");

    // E nenhum enqueue do `functions.php` — que é onde vivem os de frontend —
    // aponta para asset de editor. O `register_block_type` também cita
    // `assets/blocks`, mas não é enqueue: quem enfileira o script do bloco no
    // editor é o WordPress, a partir do `block.json`.
    preg_match_all(
        '/wp_enqueue_(?:script|style)\(.*?\);/s',
        $functions,
        $chamadas,
    );

    expect($chamadas[0])->not->toBeEmpty();

    foreach ($chamadas[0] as $chamada) {
        expect($chamada)->not->toContain('assets/editor');
        expect($chamada)->not->toContain('assets/blocks');
    }
});

test('o CSS do tema é um arquivo só e continua modesto', function () use ($tema) {
    $folhas = glob("$tema/assets/css/*.build.css");

    expect(count($folhas))->toBe(1);
    // Sem teto formal no PRD; 40 KB gz seria sinal de que algo cresceu
    // sozinho e merece olhar.
    expect(tamanhoGzip($folhas[0]))->toBeLessThan(40 * 1024);
});
