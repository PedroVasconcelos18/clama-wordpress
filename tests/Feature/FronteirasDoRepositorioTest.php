<?php

/**
 * Fronteiras do repositório (Story 2.2).
 *
 * O que estes testes protegem não é comportamento do WordPress — é a decisão
 * de que segredo e mídia não entram no git nem no document root. Ambas as
 * regras são fáceis de quebrar sem perceber: basta um `git add -f`, um plugin
 * que escreve config dentro de `web/`, ou alguém "simplificando" o .gitignore.
 */

$raiz = dirname(__DIR__, 2);

function ignoradoPeloGit(string $raiz, string $caminho): bool
{
    exec(
        sprintf('git -C %s check-ignore -q %s', escapeshellarg($raiz), escapeshellarg($caminho)),
        $saida,
        $status,
    );

    return $status === 0;
}

test('mídia de upload nunca é rastreável pelo git', function () use ($raiz) {
    // Caminho inexistente serve: `check-ignore` avalia o padrão, não o arquivo.
    expect(ignoradoPeloGit($raiz, 'web/app/uploads/2026/08/foto.jpg'))->toBeTrue();
    expect(ignoradoPeloGit($raiz, 'web/app/uploads/qualquer.png'))->toBeTrue();
});

test('o placeholder de uploads continua rastreado', function () use ($raiz) {
    // Sem ele o diretório some do clone e o WordPress falha ao gravar mídia.
    expect(ignoradoPeloGit($raiz, 'web/app/uploads/.gitkeep'))->toBeFalse();
});

test('segredos e dependências não são rastreados', function () use ($raiz) {
    foreach (['.env', 'vendor/autoload.php', 'web/wp/wp-load.php', 'auth.json'] as $caminho) {
        expect(ignoradoPeloGit($raiz, $caminho))->toBeTrue("esperava $caminho ignorado");
    }
});

test('o que define o build é rastreado', function () use ($raiz) {
    foreach (['composer.json', 'composer.lock', 'config/application.php', '.env.example'] as $caminho) {
        expect(ignoradoPeloGit($raiz, $caminho))->toBeFalse("esperava $caminho rastreado");
    }
});

test('o tema próprio é rastreado, o tema de terceiro não', function () use ($raiz) {
    expect(ignoradoPeloGit($raiz, 'web/app/themes/clama/style.css'))->toBeFalse();
    expect(ignoradoPeloGit($raiz, 'web/app/themes/twentytwentyfive/style.css'))->toBeTrue();
});

test('segredo e configuração ficam fora do document root', function () use ($raiz) {
    foreach (['.env', '.env.example', 'composer.json', 'composer.lock', 'config', 'vendor'] as $alvo) {
        expect(file_exists("$raiz/web/$alvo"))
            ->toBeFalse("$alvo não pode existir dentro de web/");
    }
});

test('o document root contém apenas o que deve ser público', function () use ($raiz) {
    $conteudo = array_values(array_diff(scandir("$raiz/web"), ['.', '..']));
    sort($conteudo);

    expect($conteudo)->toBe(['app', 'index.php', 'wp', 'wp-config.php']);
});

test('o lockfile trava o WordPress numa versão exata', function () use ($raiz) {
    $lock = json_decode(file_get_contents("$raiz/composer.lock"), true);

    $versoes = array_column($lock['packages'], 'version', 'name');

    expect($versoes)->toHaveKey('roots/wordpress');
    expect($versoes['roots/wordpress'])->toBe('7.0.2');
});

test('o lockfile foi resolvido contra o PHP do host, não o da máquina local', function () use ($raiz) {
    $lock = json_decode(file_get_contents("$raiz/composer.lock"), true);

    // Sem este pin, quem rodar `composer update` num PHP mais novo trava
    // pacotes que podem não instalar no host.
    expect($lock['platform-overrides']['php'] ?? null)->toBe('8.3.0');
});
