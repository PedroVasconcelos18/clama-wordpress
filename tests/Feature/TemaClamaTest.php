<?php

/**
 * Tema Clama (Story 2.5).
 *
 * O que estes testes protegem é o que se perde em silêncio: um web font que
 * alguém adiciona "só pra testar", o build de CSS que sai de sincronia com a
 * fonte, e o padrão de card clicável que parece complicado e convida a ser
 * "simplificado" para um `<a>` envolvendo tudo.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

test('o tema tem os arquivos que o WordPress exige e os que o design exige', function () use ($tema) {
    foreach ([
        'style.css',            // cabeçalho do tema — sem ele o WP não reconhece
        'functions.php',
        'theme.json',           // AC1: híbrido = PHP + theme.json
        'index.php',
        'single.php',
        'header.php',
        'footer.php',
        'parts/card-post.php',
        'parts/estado-vazio.php',
        'assets/css/app.css',
        'assets/css/app.build.css',
    ] as $arquivo) {
        expect(file_exists("$tema/$arquivo"))->toBeTrue("faltando: $arquivo");
    }
});

test('as quatro classes semânticas ausentes do projeto estão definidas', function () use ($tema) {
    // Elas aparecem nos mockups e não têm definição em lugar nenhum — sem
    // escrevê-las, a página renderiza sem eyebrow, sem halo e sem capa.
    $css = file_get_contents("$tema/assets/css/app.css");

    expect($css)->toContain('.eyebrow');
    expect($css)->toContain('.gold-halo');

    foreach (range(1, 6) as $n) {
        expect($css)->toContain(".img-placeholder-$n");
    }
});

test('o CSS compilado está em dia com a fonte', function () use ($tema) {
    // O build é versionado (o deploy não roda npm). Se a fonte for editada e o
    // build não for regerado, o site serve CSS velho sem nenhum sinal.
    $fonte = filemtime("$tema/assets/css/app.css");
    $build = filemtime("$tema/assets/css/app.build.css");

    expect($build)->toBeGreaterThanOrEqual($fonte, 'rode `npm run build` no tema');
});

test('o build contém as classes semânticas, não só a fonte', function () use ($tema) {
    $build = file_get_contents("$tema/assets/css/app.build.css");

    expect($build)->toContain('.eyebrow');
    expect($build)->toContain('.gold-halo');
    expect($build)->toContain('.img-placeholder-1');
});

test('nenhum web font externo, em lugar nenhum do tema', function () use ($tema) {
    // WP-UXDR1. Georgia + stack de sistema; zero requisição de fonte.
    $suspeitos = ['fonts.googleapis', 'fonts.gstatic', 'use.typekit', '@font-face', 'cdn.jsdelivr', 'unpkg.com', 'cdnjs.'];

    $arquivos = array_merge(
        glob("$tema/*.php"),
        glob("$tema/parts/*.php"),
        glob("$tema/inc/*.php"),
        glob("$tema/assets/css/*.css"),
        [$tema . '/theme.json'],
    );

    foreach ($arquivos as $arquivo) {
        $conteudo = file_get_contents($arquivo);
        foreach ($suspeitos as $suspeito) {
            expect(str_contains($conteudo, $suspeito))
                ->toBeFalse(basename($arquivo) . " referencia $suspeito");
        }
    }
});

test('o padrão de card clicável está preservado', function () use ($tema) {
    // O título não é link; o hover vem de group/group-hover no article; e o
    // link que cobre o card carrega aria-label. Envolver o card inteiro num
    // <a> colocaria parágrafo e lista dentro de link.
    $card = file_get_contents("$tema/parts/card-post.php");

    expect($card)->toContain('absolute inset-0 z-10');
    expect($card)->toContain('aria-label="<?php echo esc_attr(get_the_title()); ?>"');
    expect($card)->toContain('group-hover:text-clama-gold');

    // O <a> tem que vir antes do primeiro heading — se vier depois, deixa de
    // ser o primeiro filho e a ordem de foco muda.
    $posLink = strpos($card, 'absolute inset-0 z-10');
    $posTitulo = strpos($card, '<h2 class="font-serif');
    expect($posLink)->toBeLessThan($posTitulo);
});

test('os tokens do tema não divergiram do frontend React', function () use ($raiz, $tema) {
    // Os mockups carregam os nomes de classe literalmente. Se um hex mudar de
    // um lado só, o blog do WordPress e o resto do site passam a ter cores
    // diferentes com o mesmo nome — divergência que ninguém percebe até estar
    // em produção lado a lado.
    $frontend = file_get_contents("$raiz/../clama-frontend/tailwind.config.ts");
    $doTema = file_get_contents("$tema/tailwind.config.js");

    $tokens = [
        'clama-night' => '#1a0a2e',
        'clama-night-deep' => '#110720',
        'clama-night-soft' => '#2a1a40',
        'clama-gold' => '#f0c040',
        'clama-gold-soft' => '#d4a017',
        'clama-cream' => '#fdf8f0',
    ];

    foreach ($tokens as $nome => $hex) {
        expect(str_contains($frontend, "\"$nome\": \"$hex\""))
            ->toBeTrue("o frontend mudou o token $nome — este teste é a referência desatualizada");
        expect(str_contains($doTema, "'$nome': '$hex'"))
            ->toBeTrue("o tema divergiu no token $nome");
    }
});

test('o tema declara Georgia e stack de sistema, sem fonte baixada', function () use ($tema) {
    $config = file_get_contents("$tema/tailwind.config.js");
    $themeJson = file_get_contents("$tema/theme.json");

    expect($config)->toContain("serif: ['Georgia'");
    expect($themeJson)->toContain('Georgia');
});
