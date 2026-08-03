<?php

/**
 * Bloco Versículo destacado (Story 2.6).
 *
 * O risco desta story não é o bloco novo funcionar — é o versículo dos posts
 * migrados parar de renderizar. Esses testes existem para pegar isso.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

test('o bloco existe com block.json, fonte e build', function () use ($tema) {
    // AC1: block.json + edit/save + build por @wordpress/scripts.
    expect(file_exists("$tema/src/versiculo-destacado/block.json"))->toBeTrue();
    expect(file_exists("$tema/src/versiculo-destacado/index.js"))->toBeTrue();
    expect(file_exists("$tema/assets/blocks/versiculo-destacado/index.js"))->toBeTrue();
    expect(file_exists("$tema/assets/blocks/versiculo-destacado/block.json"))->toBeTrue();
});

test('a versão da tradução tem ARC como padrão', function () use ($tema) {
    // AC5. Trocar esse default muda a legenda de todo versículo novo.
    $manifesto = json_decode(file_get_contents("$tema/src/versiculo-destacado/block.json"), true);

    expect($manifesto['attributes']['versao']['default'])->toBe('ARC');
    expect($manifesto['attributes']['referencia']['default'])->toBe('');
});

test('os atributos leem dos mesmos data-* que a extensão Tiptap emitia', function () use ($tema) {
    // Se alguém renomear para `data-ref`, o parse do HTML migrado silencia:
    // o bloco carrega sem referência e ninguém percebe até publicar.
    $manifesto = json_decode(file_get_contents("$tema/src/versiculo-destacado/block.json"), true);

    expect($manifesto['attributes']['referencia']['attribute'])->toBe('data-referencia');
    expect($manifesto['attributes']['versao']['attribute'])->toBe('data-versao');
    expect($manifesto['attributes']['referencia']['selector'])->toBe('blockquote');
});

test('o CSS estiliza a forma LEGADA, com texto solto sem <p>', function () use ($tema) {
    // O ponto da story. A extensão Tiptap serializa `content: "text*"` — texto
    // como nó direto. O CSS original (`index.css:89-133`) só mira `> p`, então
    // portá-lo literalmente deixaria todo versículo migrado sem tipografia,
    // com a moldura e nada dentro.
    $css = file_get_contents("$tema/assets/css/app.css");

    // Só o corpo da regra do próprio blockquote — as declarações de `> p`
    // não contam aqui, é justamente delas que a forma legada não se serve.
    $inicio = strpos($css, '.prose-clama blockquote.versiculo {');
    $bloco = substr($css, $inicio, strpos($css, '}', $inicio) - $inicio);

    expect($bloco)->toContain('font-family: Georgia');
    expect($bloco)->toContain('font-style: italic');
    expect($bloco)->toContain('font-size: 1.45rem');
});

test('o CSS também estiliza a forma NOVA, com <p> e <cite>', function () use ($tema) {
    $css = file_get_contents("$tema/assets/css/app.css");

    expect($css)->toContain('.prose-clama blockquote.versiculo > p');
    expect($css)->toContain('.prose-clama blockquote.versiculo > cite');
    expect($css)->toContain('.prose-clama blockquote.versiculo::before');
});

test('o editor recebe o mesmo visual, sem depender de .prose-clama', function () use ($tema) {
    // AC4. O editor não envolve o conteúdo em `.prose-clama`, então sem esses
    // seletores a pré-visualização mostra um blockquote cru.
    $css = file_get_contents("$tema/assets/css/app.css");

    expect($css)->toContain('.editor-styles-wrapper blockquote.versiculo');
    expect($css)->toContain('.editor-styles-wrapper blockquote.versiculo > cite');

    $functions = file_get_contents("$tema/functions.php");
    expect($functions)->toContain("add_editor_style('assets/css/app.build.css')");
});

test('o build do bloco está em dia com a fonte', function () use ($tema) {
    $fonte = max(
        filemtime("$tema/src/versiculo-destacado/index.js"),
        filemtime("$tema/src/versiculo-destacado/block.json"),
    );
    $build = filemtime("$tema/assets/blocks/versiculo-destacado/index.js");

    expect($build)->toBeGreaterThanOrEqual($fonte, 'rode `npm run build:blocks` no tema');
});
