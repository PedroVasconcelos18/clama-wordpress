<?php

/*
Plugin Name:  Clama — cache de borda
Description:  Controla o que a borda da Vercel pode guardar, e purga por evento.
Version:      1.0.0
Author:       Clama
*/

/**
 * Stories 6.3 e 6.4.
 *
 * A borda da Vercel honra `cache-control`, `CDN-Cache-Control` e
 * `Vercel-CDN-Cache-Control` vindos da origem em rewrite externo. Isso é o
 * mecanismo — não há painel a configurar do lado da Vercel; **quem decide o
 * que é cacheável é o WordPress**, por header.
 *
 * Duas coisas moram aqui porque são a mesma decisão vista de dois ângulos: o
 * que pode ser guardado (6.4) e por quanto tempo antes de ser jogado fora
 * (6.3).
 */

if (! defined('ABSPATH')) {
    exit;
}

/** TTL da borda para página pública. */
const CLAMA_TTL_BORDA = 300;

/**
 * Tag de cache para purga seletiva.
 *
 * A Vercel purga por tag; sem tag só dá para purgar o deploy inteiro, que
 * derrubaria o cache do SPA junto — um post corrigido invalidaria a landing.
 */
function clama_tag_de_cache(int $post_id): string
{
    return 'post-' . $post_id;
}

/**
 * O que a borda pode guardar (Story 6.4).
 *
 * ⚠️ Com o `wp-admin` alcançável pelo caminho proxiado (Story 6.2), o
 * WordPress emite HTML **diferente** para operador logado: barra de admin,
 * links de edição e **nonces**. Nonce é material de sessão.
 *
 * Se a borda guardar uma dessas respostas, ela passa a servi-la a visitantes
 * anônimos. O vazamento de interface é o sintoma visível; o de nonce é o
 * perigoso.
 */
add_action('send_headers', function () {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    if (clama_resposta_e_privada()) {
        // `private` impede a borda; `no-store` impede também proxies
        // intermediários e o próprio navegador de reaproveitar entre sessões.
        header('Cache-Control: private, no-store, max-age=0');
        header('CDN-Cache-Control: private, no-store');
        // A Vercel decide pelo header dela quando ele existe; declarar os três
        // evita depender de qual delas a borda escolhe honrar.
        header('Vercel-CDN-Cache-Control: private, no-store');

        return;
    }

    if (! is_singular('post') && ! is_home() && ! is_archive()) {
        return;
    }

    header('Cache-Control: public, max-age=0, must-revalidate');
    header('CDN-Cache-Control: public, max-age=' . CLAMA_TTL_BORDA);
    header(
        'Vercel-CDN-Cache-Control: public, max-age=' . CLAMA_TTL_BORDA
        . ', stale-while-revalidate=86400'
    );

    if (is_singular('post')) {
        header('Vercel-Cache-Tag: ' . clama_tag_de_cache(get_the_ID()));
    }
});

/**
 * Se esta resposta carrega sinal de sessão.
 *
 * Duas condições, e a segunda é a que importa: a primeira cobre o que a gente
 * prevê (operador logado), a segunda cobre o que não prevê — qualquer código
 * que decida emitir `Set-Cookie` no meio da resposta.
 */
function clama_resposta_e_privada(): bool
{
    foreach (array_keys($_COOKIE) as $nome) {
        if (str_starts_with((string) $nome, 'wordpress_logged_in_')) {
            return true;
        }
    }

    if (is_user_logged_in()) {
        return true;
    }

    foreach (headers_list() as $cabecalho) {
        if (stripos($cabecalho, 'set-cookie:') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Purga por evento (Story 6.3).
 *
 * Cache de borda expira por **TTL**, não por evento. Sem isto, um post
 * despublicado por erro editorial ou por exigência legal **continua sendo
 * servido pela borda** até o TTL vencer. É o risco R17, e é o que torna esta
 * story obrigatória em vez de otimização.
 *
 * `transition_post_status` é o mesmo hook que dispara o webhook da Story 3.2 —
 * de propósito: a purga e o aviso ao Django saem do mesmo evento, então não
 * existe estado em que um aconteceu e o outro não por causa de gatilhos
 * diferentes.
 */
add_action('transition_post_status', function ($novo, $antigo, $post) {
    if ($post->post_type !== 'post') {
        return;
    }

    // ⚠️ **Não** basta purgar quando o status muda.
    //
    // O AC4 exige os três estados refletidos: publicado, **corrigido** e
    // despublicado. Uma correção de conteúdo dispara este hook com
    // `$novo === $antigo`, e um early-return ali deixaria a correção invisível
    // pelo TTL inteiro — que é metade do problema que a story descreve.
    //
    // O que se descarta é o que não tem URL pública: revisão e autosave.
    if (wp_is_post_revision($post) || wp_is_post_autosave($post)) {
        return;
    }

    // Rascunho que nunca foi publicado não tem nada na borda para purgar.
    if ($novo !== 'publish' && $antigo !== 'publish') {
        return;
    }

    clama_purgar_na_borda($post);
}, 10, 3);

function clama_purgar_na_borda(WP_Post $post): void
{
    $token = defined('VERCEL_PURGE_TOKEN') ? VERCEL_PURGE_TOKEN : '';
    $projeto = defined('VERCEL_PROJECT_ID') ? VERCEL_PROJECT_ID : '';

    if (! $token || ! $projeto) {
        // Sem credencial não dá para purgar. Registra e segue — travar a
        // publicação por causa do cache seria pior que servir conteúdo velho
        // por cinco minutos.
        error_log(
            'clama_purga_sem_credencial ' . wp_json_encode([
                'post_id' => $post->ID,
                'detalhe' => 'VERCEL_PURGE_TOKEN/VERCEL_PROJECT_ID ausentes',
            ])
        );

        return;
    }

    $resposta = wp_remote_post(
        'https://api.vercel.com/v1/edge-cache/purge',
        [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'projectId' => $projeto,
                'tags' => [clama_tag_de_cache($post->ID)],
            ]),
        ]
    );

    $codigo = is_wp_error($resposta) ? 0 : wp_remote_retrieve_response_code($resposta);
    $ok = $codigo >= 200 && $codigo < 300;

    // AC3: falha na purga dispara alerta. Silêncio aqui significaria conteúdo
    // removido continuando no ar sem ninguém saber.
    error_log(
        ($ok ? 'clama_purga_ok ' : 'clama_purga_falhou ') . wp_json_encode([
            'post_id' => $post->ID,
            'tag' => clama_tag_de_cache($post->ID),
            'status' => $codigo,
            'erro' => is_wp_error($resposta) ? $resposta->get_error_message() : '',
        ])
    );

    if (! $ok) {
        /**
         * Gancho para o alerta sair do log e virar notificação.
         *
         * Deixado como ação em vez de e-mail direto: quem alerta é decisão de
         * operação, e o Clama já tem Sentry no Django, que recebe o webhook do
         * mesmo evento.
         */
        do_action('clama/purga_de_borda_falhou', $post->ID, $codigo);
    }
}
