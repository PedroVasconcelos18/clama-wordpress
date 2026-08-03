<?php

/**
 * Helpers de apresentação — o que os templates precisam e o WordPress não dá
 * pronto no formato do design.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Qual dos seis placeholders de capa este post usa.
 *
 * Determinístico pelo ID: o mesmo post mantém o mesmo gradiente entre visitas
 * e entre a listagem e o detalhe. Aleatório aqui produziria um blog que muda
 * de cara a cada F5.
 */
function clama_placeholder_de_capa(int $post_id): string
{
    return 'img-placeholder-' . (($post_id % 6) + 1);
}

/** Iniciais para o avatar, sem Gravatar — o Gravatar é requisição externa. */
function clama_inicial_do_autor(int $post_id): string
{
    $nome = get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id));

    return mb_strtoupper(mb_substr(trim($nome) !== '' ? $nome : 'C', 0, 1));
}

/**
 * "há 2 dias", como no design — e não a data absoluta.
 *
 * Acima de um mês a distância relativa deixa de informar ("há 14 meses" não
 * diz nada), então volta para a data.
 */
function clama_data_relativa(int $post_id): string
{
    $publicado = get_post_timestamp($post_id);
    $agora = current_time('timestamp');

    if ($publicado > $agora || ($agora - $publicado) > YEAR_IN_SECONDS) {
        return get_the_date('j \d\e F \d\e Y', $post_id);
    }

    return 'há ' . human_time_diff($publicado, $agora);
}

/**
 * Tempo de leitura em minutos.
 *
 * 200 palavras/minuto é a média corrente para leitura em tela; o piso de 1
 * evita "0 min" em post curto, que lê como erro.
 */
function clama_tempo_de_leitura(int $post_id): int
{
    $palavras = str_word_count(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));

    return max(1, (int) ceil($palavras / 200));
}

/** Primeira categoria, para o eyebrow do card. */
function clama_categoria_principal(int $post_id): string
{
    $categorias = get_the_category($post_id);

    return $categorias ? $categorias[0]->name : 'Reflexão';
}

/**
 * Contagem de likes e comentários.
 *
 * Likes vivem no Django (`clama/blog`), não aqui — o WordPress nunca é
 * autoridade sobre engajamento. Até o widget da Story 3.8 existir, o card
 * omite o número em vez de exibir zero: zero é uma afirmação, e uma afirmação
 * falsa.
 */
function clama_contagem_de_likes(int $post_id): ?int
{
    /**
     * Filtro para o widget do Django alimentar a contagem quando existir.
     *
     * @param  int|null  $likes
     */
    $likes = apply_filters('clama/likes_do_post', null, $post_id);

    return is_int($likes) ? $likes : null;
}

function clama_contagem_de_comentarios(int $post_id): ?int
{
    $comentarios = apply_filters('clama/comentarios_do_post', null, $post_id);

    return is_int($comentarios) ? $comentarios : null;
}
