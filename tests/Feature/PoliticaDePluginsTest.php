<?php

/**
 * Política de plugins como teste, não como documento (Story 2.3).
 *
 * 91% das vulnerabilidades de WordPress em 2025 vieram de plugins; o core teve
 * 6. Contagem de plugin é orçamento de segurança. Um documento pedindo
 * justificativa é ignorável; um teste que quebra o build, não.
 *
 * Quem adicionar plugin sem entrada em `config/plugins-aprovados.json` — com as
 * três justificativas preenchidas — vê estes testes falharem.
 */

$raiz = dirname(__DIR__, 2);

/** Pacotes que o Composer instala como plugin do WordPress. */
function pluginsNoLockfile(string $raiz): array
{
    $lock = json_decode(file_get_contents("$raiz/composer.lock"), true);
    $tipos = ['wordpress-plugin', 'wordpress-muplugin'];
    $encontrados = [];

    foreach (['packages', 'packages-dev'] as $secao) {
        foreach ($lock[$secao] ?? [] as $pacote) {
            if (in_array($pacote['type'] ?? '', $tipos, true)) {
                $encontrados[] = $pacote['name'];
            }
        }
    }

    sort($encontrados);

    return $encontrados;
}

function politica(string $raiz): array
{
    return json_decode(file_get_contents("$raiz/config/plugins-aprovados.json"), true);
}

test('todo plugin instalado tem entrada na política', function () use ($raiz) {
    $instalados = pluginsNoLockfile($raiz);
    $aprovados = array_keys(politica($raiz)['plugins']);

    $semAprovacao = array_diff($instalados, $aprovados);

    expect($semAprovacao)->toBeEmpty(
        'plugin no composer.lock sem entrada em config/plugins-aprovados.json: '
        . implode(', ', $semAprovacao),
    );
});

test('a política não acumula entradas de plugin já removido', function () use ($raiz) {
    // Aprovação órfã é pior que ausente: dá a impressão de que alguém revisou.
    $instalados = pluginsNoLockfile($raiz);
    $aprovados = array_keys(politica($raiz)['plugins']);

    expect(array_diff($aprovados, $instalados))->toBeEmpty();
});

test('cada aprovação cobre as três dimensões exigidas', function () use ($raiz) {
    foreach (politica($raiz)['plugins'] as $nome => $justificativa) {
        foreach (['necessidade', 'injeta_no_frontend', 'historico_cve'] as $dimensao) {
            expect($justificativa[$dimensao] ?? '')
                ->not->toBeEmpty("$nome não declara '$dimensao'");
        }
    }
});

test('a justificativa não é preenchida com placeholder', function () use ($raiz) {
    $vazios = ['tbd', 'todo', 'n/a', 'na', '-', '?', 'a definir', 'pendente'];

    foreach (politica($raiz)['plugins'] as $nome => $justificativa) {
        foreach (['necessidade', 'injeta_no_frontend', 'historico_cve'] as $dimensao) {
            $valor = strtolower(trim($justificativa[$dimensao] ?? ''));

            expect(in_array($valor, $vazios, true))->toBeFalse("$nome usa placeholder em '$dimensao'");
            expect(strlen($valor))->toBeGreaterThan(15, "$nome tem '$dimensao' curta demais para ser justificativa");
        }
    }
});

test('não há mais de um plugin de segurança instalado', function () use ($raiz) {
    // Empilhar Wordfence + Solid Security + Patchstack soma superfície e
    // conflito, não proteção.
    $seguranca = array_filter(
        politica($raiz)['plugins'],
        fn($j) => ($j['categoria'] ?? '') === 'seguranca',
    );

    expect(count($seguranca))->toBeLessThanOrEqual(1);
});

test('o plugin de segurança escolhido está declarado', function () use ($raiz) {
    // Sem isso, "escolher um" vira "não escolher nenhum" por omissão.
    $bloco = politica($raiz)['plugins_de_seguranca'];

    expect($bloco['escolhido'] ?? '')->not->toBeEmpty();

    if (! ($bloco['instalado'] ?? false)) {
        expect($bloco['motivo_nao_instalado'] ?? '')->not->toBeEmpty(
            'plugin de segurança não instalado exige motivo registrado',
        );
    }
});
