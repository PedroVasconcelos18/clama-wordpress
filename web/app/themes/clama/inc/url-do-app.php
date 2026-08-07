<?php

/**
 * URLs do app Clama (a SPA), não do blog.
 *
 * O blog mora em `clama.me/blog`; `/conta`, `/sobre`, `/privacidade` e o fluxo
 * de pedido moram na raiz do mesmo domínio, servidos pela Vercel.
 *
 * ⚠️ **Por que isto não é só `href="/conta"`.** Em produção o caminho absoluto
 * funciona por coincidência de topologia: o WordPress está sob `/blog` do mesmo
 * domínio, então `/conta` cai na SPA. Mas a coincidência esconde duas coisas:
 *
 * 1. **Em desenvolvimento o WordPress é a raiz** (`localhost:8080`), e `/conta`
 *    vira 404 do WordPress. O link fica impossível de testar de verdade — que
 *    foi exatamente como o problema apareceu.
 * 2. **Se a topologia mudar** — subdomínio, outro host, pré-visualização
 *    servida direto pela origem — todo link do tema para o app quebra de uma
 *    vez, e sem erro nenhum: são 200 do WordPress, com a página errada.
 *
 * Com `CLAMA_APP_URL` definido, o destino é declarado, não inferido.
 *
 * @package clama
 */

declare(strict_types=1);

/**
 * Monta uma URL do app.
 *
 * Sem `CLAMA_APP_URL`, devolve o caminho absoluto de raiz — o comportamento
 * anterior, que é o correto quando blog e app dividem o domínio. Definir a
 * constante é o que torna o destino explícito e testável fora de produção.
 */
function clama_url_do_app(string $caminho = '/'): string
{
    $caminho = '/' . ltrim($caminho, '/');

    $base = defined('CLAMA_APP_URL') ? (string) CLAMA_APP_URL : '';

    if ($base === '') {
        return $caminho;
    }

    return rtrim($base, '/') . ($caminho === '/' ? '/' : $caminho);
}
