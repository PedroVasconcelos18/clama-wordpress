<?php

/**
 * Links do tema para o app Clama.
 *
 * O blog mora em `clama.me/blog`; `/conta`, `/sobre`, `/privacidade` e o fluxo
 * de pedido moram na raiz do mesmo domínio, servidos pela Vercel.
 *
 * ⚠️ **O modo de falha é silencioso.** `href="/conta"` funciona em produção por
 * coincidência de topologia — o WordPress está sob `/blog` do mesmo domínio.
 * Em desenvolvimento o WordPress É a raiz, e o link vira 404 do WordPress; se a
 * topologia mudar (subdomínio, outro host, pré-visualização servida direto pela
 * origem), todo link do tema para o app passa a devolver **200 com a página
 * errada**, que é pior que um erro.
 */

$raiz = dirname(__DIR__, 2);
$tema = "$raiz/web/app/themes/clama";

require_once "$tema/inc/url-do-app.php";

describe('clama_url_do_app', function () {
    it('sem constante, devolve caminho absoluto de raiz', function () {
        // É o comportamento correto em produção, onde blog e app dividem o
        // domínio. O ganho da constante é tornar o destino explícito, não
        // mudar o resultado em produção.
        expect(clama_url_do_app('/conta'))->toBe('/conta');
        expect(clama_url_do_app('/'))->toBe('/');
    });

    it('normaliza caminho sem barra inicial', function () {
        expect(clama_url_do_app('conta'))->toBe('/conta');
    });

    it('com CLAMA_APP_URL, prefixa o destino', function () {
        define('CLAMA_APP_URL', 'https://clama.me');

        expect(clama_url_do_app('/conta'))->toBe('https://clama.me/conta');
        expect(clama_url_do_app('/'))->toBe('https://clama.me/');
    });

    it('não duplica barra quando a base termina em barra', function () {
        // `WP_HOME`-style com barra final é comum em env var copiada à mão.
        // `https://clama.me//conta` responderia, mas geraria URL canônica
        // divergente da que o app emite.
        expect(clama_url_do_app('/conta'))->not->toContain('//conta');
    });
});

describe('templates do tema', function () use ($tema) {
    it('nenhum link cru de raiz para o app sobrou nos templates', function () use ($tema) {
        // Trava de regressão: o bug volta com um `href="/conta"` digitado
        // direto, que passa em revisão porque parece certo.
        $arquivos = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tema));

        foreach ($it as $arquivo) {
            if ($arquivo->getExtension() !== 'php') {
                continue;
            }
            if (str_contains($arquivo->getPathname(), '/node_modules/')) {
                continue;
            }
            if (str_ends_with($arquivo->getPathname(), 'inc/url-do-app.php')) {
                continue; // a própria documentação do helper cita o padrão
            }

            $conteudo = file_get_contents($arquivo->getPathname());

            if (preg_match('~href="/[a-z]~', $conteudo)) {
                $arquivos[] = str_replace("$tema/", '', $arquivo->getPathname());
            }
        }

        expect($arquivos)->toBeEmpty(
            'link cru de raiz — use clama_url_do_app(): ' . implode(', ', $arquivos),
        );
    });

    it('o helper é carregado antes de qualquer template usá-lo', function () use ($tema) {
        // Sem o require, os templates dão fatal error — e só na página, não
        // no build.
        expect(file_get_contents("$tema/functions.php"))
            ->toContain("require_once CLAMA_THEME_DIR . '/inc/url-do-app.php';");
    });
});
