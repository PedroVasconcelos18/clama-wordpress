<?php

/**
 * Ícone da aba.
 *
 * Sem declaração explícita o WordPress deixa o navegador cair no `/favicon.ico`
 * do core, e a aba de `clama.me/blog` mostra a marca do **WordPress**. É o tipo
 * de coisa que ninguém vê em revisão de código e todo mundo vê no navegador.
 */

$tema = dirname(__DIR__, 2) . '/web/app/themes/clama';

it('o tema declara ícone no site, no painel e no login', function () use ($tema) {
    $fonte = file_get_contents("$tema/inc/favicon.php");

    // Painel e login importam: quem escreve o post também olha a aba, e ali o
    // sintoma é idêntico.
    //
    // ⚠️ `toContain` do Pest é **variádico** — o segundo argumento vira outra
    // agulha a procurar, não mensagem de falha. Passar a mensagem ali faz o
    // teste exigir que o texto da mensagem esteja no arquivo, e ele falha
    // dizendo justamente o contrário do que aconteceu. Daí o `str_contains`.
    foreach (['wp_head', 'admin_head', 'login_head'] as $hook) {
        expect(str_contains($fonte, "add_action('$hook', 'clama_declarar_icones');"))
            ->toBeTrue("faltou declarar o ícone em `$hook`");
    }
});

it('os arquivos do ícone existem no tema', function () use ($tema) {
    // Versionados no tema de propósito, e não no "Site Icon" do painel: a opção
    // do WordPress guarda o ícone como anexo, ou seja no banco e no volume —
    // vira dado, some em restauração malfeita e não aparece em instância nova.
    expect(file_exists("$tema/assets/img/favicon.svg"))->toBeTrue();
    expect(file_exists("$tema/assets/img/apple-touch-icon.png"))->toBeTrue();

    expect(file_get_contents("$tema/assets/img/favicon.svg"))
        ->toStartWith('<svg');
});

it('o ícone do blog é o mesmo do app', function () use ($tema) {
    // A aba precisa ser a mesma marca em `clama.me` e em `clama.me/blog`. Como
    // são dois repositórios, a cópia pode divergir sem nada acusar.
    $doApp = dirname(__DIR__, 3) . '/clama-frontend/public/favicon.svg';

    if (! file_exists($doApp)) {
        // Em CI só este repositório é clonado. Pular é honesto; falhar seria
        // reprovar por ausência de contexto, não por divergência.
        test()->markTestSkipped('clama-frontend não está ao lado — nada a comparar');
    }

    expect(md5_file("$tema/assets/img/favicon.svg"))->toBe(
        md5_file($doApp),
        'o ícone do tema divergiu do app — copie de clama-frontend/public/favicon.svg',
    );
});
