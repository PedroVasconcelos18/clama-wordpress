<?php

/**
 * CSP em Report-Only (Story 2.11).
 *
 * O que estes testes protegem é o que se perde numa edição apressada: alguém
 * afrouxando `script-src` para calar uma violação, ou ligando `enforce` sem o
 * critério de saída cumprido.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

test('a política é publicada em Report-Only, não em enforce', function () use ($tema) {
    // AC1. Virar para `Content-Security-Policy` sem o critério de saída
    // cumprido quebra o editor de blocos, e quebra em silêncio.
    $csp = file_get_contents("$tema/inc/csp.php");

    expect($csp)->toContain('Content-Security-Policy-Report-Only:');
    expect($csp)->not->toContain("header('Content-Security-Policy:");
});

test('script-src continua estrito', function () use ($tema) {
    // É a diretiva que importa. `script-src 'unsafe-inline'` protege contra
    // aproximadamente nada, e é o afrouxamento mais tentador quando aparece
    // uma violação chata.
    $csp = file_get_contents("$tema/inc/csp.php");

    expect($csp)->toContain('"script-src \'self\'"');
    expect($csp)->not->toContain("script-src 'self' 'unsafe-inline'");
    expect($csp)->not->toContain("script-src 'unsafe-inline'");
});

test('as diretivas de contenção estão todas presentes', function () use ($tema) {
    $csp = file_get_contents("$tema/inc/csp.php");

    foreach ([
        "default-src 'self'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        'report-uri ',
    ] as $diretiva) {
        expect($csp)->toContain($diretiva);
    }
});

test('a política não vale no wp-admin', function () use ($tema) {
    // O editor de blocos gera inline sem limite; incluí-lo afogaria o
    // relatório e esconderia a violação da página pública.
    $csp = file_get_contents("$tema/inc/csp.php");

    expect($csp)->toContain('if (is_admin() || wp_doing_ajax()');
});

test('o endpoint de relatório não confia no corpo recebido', function () use ($tema) {
    // Corpo vem do navegador de qualquer visitante, sem credencial.
    $csp = file_get_contents("$tema/inc/csp.php");

    expect($csp)->toContain("'document-uri'");
    expect($csp)->toContain('mb_substr(');
    expect($csp)->toContain('300');
});

test('o papel da CSP e o critério de saída estão escritos', function () use ($raiz) {
    // AC3 e AC4. O critério é critério, não data — uma data produz ou uma CSP
    // frouxa ou um enforce que quebra o editor no dia marcado.
    $doc = "$raiz/../clama-docs/csp-do-blog.md";

    expect(file_exists($doc))->toBeTrue();

    $texto = file_get_contents($doc);

    expect($texto)->toContain('não é o controle primário');
    expect($texto)->toContain('30 dias consecutivos sem violação de fonte não catalogada');
    expect($texto)->toContain('Se um plugin novo entrar, o relógio reinicia');
});
