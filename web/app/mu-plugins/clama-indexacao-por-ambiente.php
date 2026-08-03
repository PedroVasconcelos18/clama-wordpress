<?php

/*
Plugin Name:  Clama — indexação por ambiente
Description:  Faz `blog_public` ser decidido pelo ambiente, nunca pelo banco.
Version:      1.0.0
Author:       Clama
*/

/**
 * Story 2.4. O `bedrock-disallow-indexing` que vem no Bedrock cobre só uma
 * direção: quando `DISALLOW_INDEXING` é true, força `blog_public = 0`. Em
 * produção a constante não é true, aquele mu-plugin retorna cedo, e o valor do
 * banco volta a mandar.
 *
 * É exatamente o modo de falha que a story aponta como mais provável: não é
 * alguém marcar "Search Engine Visibility" por engano em produção — é um banco
 * de staging, com `blog_public = 0`, ser promovido para produção. O WordPress
 * então escreve `Disallow: /` no robots.txt.
 *
 * E sob a ADR-03 o alcance disso não é o blog: o robots.txt da origem é o que
 * passa a ser servido em `clama.me/robots.txt`. Desindexaria a landing, a
 * /conta e o fluxo de pedido junto.
 *
 * Por isso a decisão aqui vai nas duas direções — em produção `blog_public` é
 * forçado a 1. Um banco clonado de staging não carrega desindexação consigo,
 * porque o valor do banco deixa de ser consultado.
 */

/**
 * A decisão, isolada de WordPress para poder ser testada sozinha.
 *
 * @param  bool|null  $disallowIndexing  valor da constante DISALLOW_INDEXING,
 *                                       ou null se ela não estiver definida
 * @return int 1 = indexável, 0 = desencorajado
 */
function clama_blog_public_do_ambiente(?bool $disallowIndexing): int
{
    // Constante ausente é tratada como "não desencorajar". A `application.php`
    // define `false` na base justamente para que este caso não aconteça —
    // depender de default aqui seria depender de sorte.
    return $disallowIndexing === true ? 0 : 1;
}

function clama_disallow_indexing_atual(): ?bool
{
    return defined('DISALLOW_INDEXING') ? (bool) constant('DISALLOW_INDEXING') : null;
}

// Sem WordPress carregado, o arquivo é só a definição das funções acima —
// é o que permite o teste exercitar a decisão sem subir o CMS.
if (! function_exists('add_filter')) {
    return;
}

add_filter('pre_option_blog_public', function () {
    return clama_blog_public_do_ambiente(clama_disallow_indexing_atual());
}, 20);

/**
 * O campo em Configurações → Leitura continua clicável e passa a mentir: o
 * clique grava no banco e nada muda. Um aviso é mais barato que a confusão.
 */
add_action('admin_notices', function () {
    global $pagenow;

    if ($pagenow !== 'options-reading.php') {
        return;
    }

    $ambiente = defined('WP_ENV') ? WP_ENV : wp_get_environment_type();
    $indexavel = clama_blog_public_do_ambiente(clama_disallow_indexing_atual()) === 1;

    printf(
        '<div class="notice notice-info"><p>%s</p></div>',
        sprintf(
            'A visibilidade para motores de busca é definida pelo ambiente (<code>%s</code>), não por esta tela. '
            . 'Neste ambiente o site está <strong>%s</strong>. Alterar a caixa abaixo não tem efeito.',
            esc_html($ambiente),
            $indexavel ? 'indexável' : 'não indexável',
        ),
    );
});
