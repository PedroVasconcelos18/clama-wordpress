<?php

/**
 * Widget de comentários e likes (Stories 3.8 a 3.10).
 *
 * O que estes testes protegem: a fronteira de dados pessoais e o orçamento de
 * 80 KB. Ambos se perdem em silêncio — um `wp_localize_script` que passa a
 * carregar o e-mail de quem comentou, ou uma dependência nova que dobra o
 * bundle, não quebram nada visivelmente.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

test('o widget está compilado e usa o React do core', function () use ($tema) {
    // AC2 da 3.8. `wp-element` como dependência em vez de React embarcado é
    // literalmente o que decide se o widget cabe no orçamento.
    $asset = "$tema/assets/widget/widget-engajamento/index.asset.php";

    expect(file_exists($asset))->toBeTrue();
    expect(file_exists("$tema/assets/widget/widget-engajamento/index.js"))->toBeTrue();

    $meta = require $asset;

    expect($meta['dependencies'])->toContain('wp-element');
    // Se `react` aparecesse aqui como dependência direta do bundle, seria
    // sinal de que o webpack não externalizou e há uma segunda cópia dentro.
    expect(file_get_contents("$tema/assets/widget/widget-engajamento/index.js"))
        ->not->toContain('__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED');
});

test('o build do widget está em dia com a fonte', function () use ($tema) {
    $fonte = filemtime("$tema/src/widget-engajamento/index.js");
    $build = filemtime("$tema/assets/widget/widget-engajamento/index.js");

    expect($build)->toBeGreaterThanOrEqual($fonte, 'rode `npm run build:widget`');
});

test('o widget é enfileirado só no post, com wp-element', function () use ($tema) {
    // AC1 da 3.8. Enfileirar na listagem faria toda a home carregar React
    // para nada.
    $functions = file_get_contents("$tema/functions.php");

    expect($functions)->toContain("wp_enqueue_script(\n        'clama-widget-engajamento'");
    expect($functions)->toContain("if (! is_singular('post'))");
    // As dependências vêm do `index.asset.php` gerado pelo build, não de uma
    // lista escrita à mão que sai de sincronia.
    expect($functions)->toContain("\$meta['dependencies']");
});

test('apenas dados não-pessoais atravessam para o widget', function () use ($tema) {
    // AC8 da 3.8, e é a fronteira que a migração inteira existe para manter.
    // Nome, e-mail, IP e conteúdo de comentário vivem **só** no Django.
    $functions = file_get_contents("$tema/functions.php");

    $inicio = strpos($functions, "wp_localize_script('clama-widget-engajamento'");
    $bloco = substr($functions, $inicio, 500);

    expect($bloco)->toContain("'apiBase'");
    expect($bloco)->toContain("'postSlug'");
    expect($bloco)->toContain("'wpPostId'");
    expect($bloco)->toContain("'loginUrl'");

    foreach (['email', 'e_mail', 'nome', 'ip_address', 'conteudo', 'comentario'] as $proibido) {
        expect(str_contains(strtolower($bloco), $proibido))
            ->toBeFalse("o widget não pode receber '$proibido'");
    }
});

test('o widget não guarda credencial em storage', function () use ($tema) {
    // O cookie é HttpOnly com escopo Path=/api (ADR-01). Guardar token de CSRF
    // ou qualquer credencial em storage recriaria exatamente o problema que
    // aquele ADR existe para resolver.
    $fonte = file_get_contents("$tema/src/widget-engajamento/index.js");

    expect($fonte)->not->toContain('localStorage');
    expect($fonte)->not->toContain('sessionStorage');
    expect($fonte)->toContain("credentials: 'include'");
});

test('o texto digitado é preservado quando a escrita é recusada', function () use ($tema) {
    // AC4 da Story 3.9. O caso é real: a Juliana escreve um comentário longo,
    // o post é despublicado nesse meio-tempo, ela clica em enviar. Rejeitar é
    // correto; limpar a caixa é cruel.
    $fonte = file_get_contents("$tema/src/widget-engajamento/index.js");

    // Escopa no callback de envio: `strpos` sobre o arquivo inteiro acharia
    // o `setErro` do botão de curtir, que vem antes e não tem nada a ver.
    $inicio = strpos($fonte, 'const enviar = useCallback(');
    $fim = strpos($fonte, 'return (', $inicio);
    $enviar = substr($fonte, $inicio, $fim - $inicio);

    // `setTexto('')` aparece **uma vez**, e dentro do ramo de sucesso.
    expect(substr_count($enviar, "setTexto( '' )"))->toBe(1);
    expect(strpos($enviar, "setTexto( '' )"))->toBeGreaterThan(
        strpos($enviar, 'if ( ok ) {'),
    );
    expect(strpos($enviar, "setTexto( '' )"))->toBeLessThan(
        strpos($enviar, '} else if'),
    );

    // E o bloco que mostra o que ela escreveu existe.
    expect($fonte)->toContain('comment-form-fechado');
    expect($fonte)->toContain('O que você escreveu');
});

test('409 fecha o formulário; 401 manda para o login', function () use ($tema) {
    // 409 é estado do post, 401 é falta de sessão — tratar os dois igual
    // mandaria a Juliana logada para a tela de login sem motivo.
    $fonte = file_get_contents("$tema/src/widget-engajamento/index.js");

    expect($fonte)->toContain('status === 401 || status === 403');
    expect($fonte)->toContain('if ( status === 409 )');
    expect($fonte)->toContain('urlDeLogin()');
});

test('o JavaScript próprio cabe com folga no orçamento', function () use ($tema) {
    // AC7 da 3.8. O que a página serve inclui a cadeia do React do core; o
    // que **nós** controlamos é isto.
    $nossos = [
        "$tema/assets/js/progresso-leitura.js",
        "$tema/assets/widget/widget-engajamento/index.js",
    ];

    $total = array_sum(array_map('tamanhoGzip', $nossos));

    // 10 KB é folga generosa sobre os ~3 KB atuais e ainda deixa o grosso do
    // orçamento para a cadeia do React, que sozinha consome ~53 KB gz.
    expect($total)->toBeLessThan(10 * 1024);
});
