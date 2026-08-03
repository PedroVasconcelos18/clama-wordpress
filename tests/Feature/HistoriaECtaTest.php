<?php

/**
 * História ilustrativa (Story 2.7) e CTA + progresso de leitura (Story 2.8).
 *
 * O aviso de história ilustrativa é exigência do **CDC art. 37**. Um teste que
 * quebra quando o texto muda é mais barato que descobrir a alteração numa
 * notificação do Procon.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

test('o texto do aviso é exatamente o exigido', function () use ($tema) {
    // AC3 da 2.7. Literal — parafrasear enfraquece o aviso e é o que a norma
    // cobra.
    $inc = file_get_contents("$tema/inc/historia-ilustrativa.php");

    expect($inc)->toContain("const CLAMA_AVISO_HISTORIA = 'História ilustrativa — não é um relato literal';");
});

test('o badge fica no cabeçalho do post, não no rodapé', function () use ($tema) {
    // AC2. Quem lê precisa saber antes de começar.
    $single = file_get_contents("$tema/single.php");

    $posBadge = strpos($single, 'CLAMA_AVISO_HISTORIA');
    $posFimHeader = strpos($single, '</header>');
    $posConteudo = strpos($single, 'the_content()');

    expect($posBadge)->toBeLessThan($posFimHeader);
    expect($posBadge)->toBeLessThan($posConteudo);
});

test('o aviso é anunciado por leitor de tela', function () use ($tema) {
    // AC5. `role="note"` faz o aviso ser anunciado como aviso, não como texto
    // decorativo solto na linha de autoria.
    $single = file_get_contents("$tema/single.php");

    expect($single)->toContain('role="note"');
});

test('a marcação vence a categoria no eyebrow', function () use ($tema) {
    // AC4. O eyebrow é o único sinal que aparece no card da listagem, onde o
    // badge do cabeçalho não chega.
    $apresentacao = file_get_contents("$tema/inc/apresentacao.php");

    $inicio = strpos($apresentacao, 'function clama_categoria_principal');
    $corpo = substr($apresentacao, $inicio, 400);

    expect($corpo)->toContain('clama_e_historia_ilustrativa');
    expect($corpo)->toContain("return 'História';");

    // O early-return precisa vir ANTES de consultar a categoria, senão um post
    // categorizado nunca mostraria "História".
    expect(strpos($corpo, "return 'História';"))->toBeLessThan(strpos($corpo, 'get_the_category'));
});

test('o painel do editor está compilado e é enfileirado do caminho certo', function () use ($tema) {
    // O build do painel vive em `assets/editor`, separado de `assets/blocks`.
    // Um único `wp-scripts build` limpa o `--output-path`, então a entrada
    // explícita do painel não pode dividir diretório com os blocos
    // auto-detectados — quando dividiu, o bloco do versículo saiu do build
    // sem aviso, sobrando só o `block.json` copiado.
    expect(file_exists("$tema/assets/editor/painel-historia/index.js"))->toBeTrue();
    expect(file_exists("$tema/assets/blocks/versiculo-destacado/index.js"))->toBeTrue();

    $inc = file_get_contents("$tema/inc/historia-ilustrativa.php");
    expect($inc)->toContain('/assets/editor/painel-historia/index.js');
});

test('só quem edita posts muda a marcação', function () use ($tema) {
    $inc = file_get_contents("$tema/inc/historia-ilustrativa.php");

    // Sem aspas em volta do `fn` para não travar no espaçamento que o Pint aplica.
    expect($inc)->toContain("current_user_can('edit_posts')");
    expect($inc)->toContain("'show_in_rest' => true");
});

test('a copy do CTA é literal nas duas variantes', function () use ($tema) {
    // AC2 da 2.8. Copy de produto, não placeholder.
    $cta = file_get_contents("$tema/parts/cta-clamar.php");

    foreach ([
        'Sua oração personalizada',
        'Sua dor merece uma oração escrita especialmente pra você.',
        'O Clama escreve orações personalizadas — pra um luto, uma decisão difícil, um filho rebelde, uma cura que demora. Conta o que tá no peito, a gente cuida do resto.',
        'Quero clamar →',
        'Pronta pra clamar?',
        'Faça seu pedido — a gente devolve uma oração escrita em até 1h.',
        'Faça seu pedido →',
    ] as $trecho) {
        expect($cta)->toContain($trecho);
    }
});

test('o CTA é um link único, com rel e aria-label', function () use ($tema) {
    // AC3 da 2.8.
    $cta = file_get_contents("$tema/parts/cta-clamar.php");

    expect($cta)->toContain('rel="nofollow"');
    expect($cta)->toContain('data-slot="cta-clamar"');

    // O nome acessível começa pelo texto visível do botão e termina com a
    // frase que a AC3 exige — WCAG 2.5.3. Ver o comentário no template.
    expect($cta)->toContain("', quero pedir uma oração'");

    // Nada focável dentro dele — o "botão" é um <span>, não <button>.
    expect(substr_count($cta, '<button'))->toBe(0);
    expect(substr_count($cta, '<a '))->toBe(1);
});

test('o post monta o CTA duas vezes, com variantes diferentes', function () use ($tema) {
    // AC1 da 2.8.
    $single = file_get_contents("$tema/single.php");

    expect($single)->toContain("'variante' => 'bottom'");
    expect($single)->toContain("'variante' => 'after-comments'");
});

test('a barra de progresso declara ARIA completo', function () use ($tema) {
    // AC5 da 2.8. Sem valuemin/valuemax o valuenow não significa nada.
    $header = file_get_contents("$tema/header.php");

    foreach ([
        'role="progressbar"',
        'aria-label="Progresso de leitura"',
        'aria-valuenow="0"',
        'aria-valuemin="0"',
        'aria-valuemax="100"',
    ] as $atributo) {
        expect($header)->toContain($atributo);
    }
});

test('a barra nasce no DOM com largura zero, para não deslocar layout', function () use ($tema) {
    // AC4 da 2.8. Inserir o trilho depois do carregamento empurraria o
    // conteúdo 3px para baixo.
    $header = file_get_contents("$tema/header.php");

    expect($header)->toContain('h-[3px]');
    expect($header)->toContain('id="clama-progresso" class="h-full w-0');
});

test('o JS do progresso é enxuto e sem dependência', function () use ($tema) {
    // AC7 da 2.8: contabilizado contra o orçamento de 80 KB gzipped.
    $arquivo = "$tema/assets/js/progresso-leitura.js";
    $bytes = filesize($arquivo);

    expect($bytes)->toBeLessThan(4096, 'o script do progresso cresceu além do razoável');

    $js = file_get_contents($arquivo);
    expect($js)->toContain('requestAnimationFrame');   // throttle por frame
    expect($js)->toContain('{ passive: true }');       // scroll não bloqueia
    expect($js)->not->toContain('import ');            // sem dependência
});

test('prefers-reduced-motion é respeitado', function () use ($tema) {
    // AC6 da 2.8.
    $css = file_get_contents("$tema/assets/css/app.css");
    $build = file_get_contents("$tema/assets/css/app.build.css");

    expect($css)->toContain('@media (prefers-reduced-motion: reduce)');
    expect($build)->toContain('prefers-reduced-motion:reduce');
});
