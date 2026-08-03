<?php

/**
 * Indexação decidida pelo ambiente (Story 2.4).
 *
 * Sob a ADR-03 o `robots.txt` da origem é o que passa a ser servido em
 * `clama.me/robots.txt`. Um `Disallow: /` vindo de um banco de staging
 * promovido não desindexaria o blog — desindexaria o domínio inteiro.
 */

$raiz = dirname(__DIR__, 2);

require_once "$raiz/web/app/mu-plugins/clama-indexacao-por-ambiente.php";

test('produção é indexável mesmo com o banco dizendo o contrário', function () {
    // O cenário real: banco clonado de staging, com blog_public = 0.
    // Em produção DISALLOW_INDEXING é false, e o valor do banco não é lido.
    expect(clama_blog_public_do_ambiente(false))->toBe(1);
});

test('ambiente que não é produção é desencorajado', function () {
    expect(clama_blog_public_do_ambiente(true))->toBe(0);
});

test('constante ausente não desindexa por acidente', function () {
    // Falha segura: na dúvida, indexável. O contrário derrubaria clama.me
    // inteiro por causa de um arquivo de config esquecido.
    expect(clama_blog_public_do_ambiente(null))->toBe(1);
});

test('a base define DISALLOW_INDEXING, para não sobrar indefinida em produção', function () use ($raiz) {
    // É o que quebra o mu-plugin do Bedrock: lá a constante só existe fora de
    // produção, então em produção o valor do banco volta a mandar.
    $base = file_get_contents("$raiz/config/application.php");

    expect($base)->toContain("Config::define('DISALLOW_INDEXING', false)");
});

test('todo ambiente que não é produção desencoraja indexação', function () use ($raiz) {
    $arquivos = glob("$raiz/config/environments/*.php");

    expect($arquivos)->not->toBeEmpty();

    foreach ($arquivos as $arquivo) {
        $ambiente = basename($arquivo, '.php');

        // `application.php` É a config de produção no Bedrock; não existe
        // environments/production.php. Se um dia existir, ele não pode
        // desencorajar indexação.
        $esperado = $ambiente === 'production'
            ? "Config::define('DISALLOW_INDEXING', false)"
            : "Config::define('DISALLOW_INDEXING', true)";

        // `toContain` é variádico — um segundo argumento vira outra agulha,
        // não mensagem. Daí o str_contains explícito.
        expect(str_contains(file_get_contents($arquivo), $esperado))
            ->toBeTrue("$ambiente deveria conter: $esperado");
    }
});

test('WP_SITEURL aponta para onde o core realmente vive', function () use ($raiz) {
    // As Dev Notes da story mandam WP_SITEURL = WP_HOME = https://clama.me/blog.
    // Num layout Bedrock isso emite /blog/wp-includes/... e /blog/wp-admin/,
    // mas o core está em web/wp — todo asset 404 e o wp-admin some.
    expect(is_dir("$raiz/web/wp/wp-includes"))->toBeTrue();
    expect(is_dir("$raiz/web/wp-includes"))->toBeFalse();

    $exemplo = file_get_contents("$raiz/.env.example");
    expect($exemplo)->toContain('WP_SITEURL="${WP_HOME}/wp"');
});
