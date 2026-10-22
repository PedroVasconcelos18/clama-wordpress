<?php

/*
Plugin Name:  Clama — configuração do Rank Math
Description:  Defaults de SEO em código, versionados, em vez de cliques no assistente.
Version:      1.0.0
Author:       Clama
*/

/**
 * Story 5.1. **Configuração como código, não como estado de banco.**
 *
 * O caminho normal do Rank Math é rodar um assistente que grava opções. Isso
 * colide com o desenho deste projeto: o Bedrock põe a infraestrutura sob
 * revisão em PR, e a promoção de staging da Story 2.4 **não copia
 * configuração** de propósito.
 *
 * Configuração que só existe no banco é configuração que se perde na primeira
 * restauração e diverge entre ambientes sem ninguém notar. Aqui os defaults
 * são filtro; o admin continua podendo sobrescrever, e o que ele sobrescrever
 * fica visível como divergência do que está em código.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Aplica um default em **ambos** os hooks de opção.
 *
 * ⚠️ Pegadinha do WordPress que custa uma hora se ninguém avisar:
 * `option_{$nome}` só dispara quando a opção **existe** no banco. Numa
 * instalação onde o assistente do Rank Math nunca rodou — que é exatamente o
 * nosso caso — a opção não existe, `get_option` devolve o default e o filtro
 * usado é `default_option_{$nome}`.
 *
 * Registrar só um dos dois faz a configuração funcionar em ambiente que já
 * teve o assistente e silenciosamente não funcionar em ambiente novo.
 */
function clama_default_de_opcao(string $nome, callable $filtro): void
{
    add_filter("option_{$nome}", $filtro, 5);
    add_filter("default_option_{$nome}", $filtro, 5);
}

/**
 * Defaults de títulos, social e dados estruturados.
 *
 * ⚠️ **O código vence o banco nas chaves declaradas aqui** — `array_merge` com
 * os padrões por último. A ordem inversa parecia mais gentil ("o admin pode
 * ajustar"), mas o instalador do Rank Math **já grava** a opção com os
 * defaults dele. Com o banco vencendo, nada do que está neste arquivo teria
 * efeito, e "configuração como código" seria mentira.
 *
 * O que não está declarado aqui continua livre no admin.
 */
clama_default_de_opcao('rank_math_options_titles', function ($opcoes) {
    $padroes = [
        // Título e descrição de post. `%seo_title%` e `%seo_description%`
        // caem para título e excerpt quando o campo do Rank Math está vazio,
        // que é o caso de todo post migrado no Epic 4.
        'pt_post_title' => '%title% %sep% %sitename%',
        'pt_post_description' => '%excerpt%',
        'pt_post_robots' => ['index'],
        'pt_post_custom_robots' => 'off',

        // JSON-LD de `Article`. O tipo `BlogPosting` é o que descreve um post
        // de blog; `Article` puro é mais genérico e rende menos rich result.
        'pt_post_default_rich_snippet' => 'article',
        'pt_post_default_article_type' => 'BlogPosting',
        'pt_post_default_snippet_name' => '%seo_title%',
        'pt_post_default_snippet_desc' => '%seo_description%',

        // Página e anexo não recebem schema de artigo — anexo indexado é
        // conteúdo duplicado clássico do WordPress.
        'pt_page_default_rich_snippet' => 'off',
        'pt_attachment_custom_robots' => 'on',
        'pt_attachment_robots' => ['noindex'],
        // Redireciona a página de anexo para o arquivo, em vez de servir uma
        // página vazia com uma imagem.
        'attachment_redirect_urls' => 'on',

        // Home.
        'homepage_title' => '%sitename% %sep% %sitedesc%',
        'homepage_description' => '%sitedesc%',
        'homepage_custom_robots' => 'off',
    ];

    return array_merge(is_array($opcoes) ? $opcoes : [], $padroes);
});

clama_default_de_opcao('rank_math_options_general', function ($opcoes) {
    $padroes = [
        // Open Graph e Twitter Card ligados. São o AC2 desta story.
        'disable_author_archives' => 'on',
        'disable_date_archives' => 'on',
        // Arquivo de autor e de data em blog de autor único são conteúdo
        // duplicado puro: as mesmas listagens sob URLs diferentes.

        'strip_category_base' => 'off',
        'nofollow_external_links' => 'off',
        'new_window_external_links' => 'off',

        // Breadcrumb do Rank Math — alimenta o JSON-LD de `BreadcrumbList`
        // mesmo sem o tema renderizar a trilha visualmente.
        'breadcrumbs' => 'on',
        'breadcrumbs_separator' => '›',
        'breadcrumbs_home' => 'on',
        'breadcrumbs_home_label' => 'Início',

        // ⚠️ Story 3.4, AC6: o Auto Post Redirect **vem desligado** no Rank
        // Math. É ele que cria o 301 a cada mudança de slug. Ligar aqui é o
        // que fecha aquela pendência — sem isto, renomear um post quebra a
        // URL indexada sem redirect nenhum.
        'redirections_post_redirect' => 'on',
        'redirections_header_code' => '301',
        'redirections_debug' => 'off',
    ];

    return array_merge(is_array($opcoes) ? $opcoes : [], $padroes);
});

/**
 * Módulos ativos.
 *
 * Menos módulo é menos superfície e menos consulta. Fora daqui ficam
 * `analytics` (exige conta Google e injeta script), `content-ai`,
 * `ai-visibility`, `woocommerce`, `buddypress`, `bbpress`, `acf` e
 * `web-stories` — nenhum deles descreve o Clama.
 */
clama_default_de_opcao('rank_math_modules', function ($modulos) {
    return [
        'sitemap',          // Story 5.3
        'rich-snippet',     // Story 5.1 — JSON-LD
        'redirections',     // Stories 3.4 e 5.6
        'robots-txt',       // Story 5.4
        'instant-indexing', // Story 5.5 — IndexNow
        'link-counter',
    ];
});

/**
 * O assistente nunca roda; sem isto o Rank Math insiste em redirecionar o
 * admin para ele e alguns módulos ficam inertes.
 */
clama_default_de_opcao('rank_math_wizard_completed', '__return_true');

/**
 * `BreadcrumbList` no JSON-LD sem trilha visível.
 *
 * O Rank Math só emite esse bloco quando o tema **renderiza** o breadcrumb, e
 * o design do blog (Story 2.5) não tem trilha — tem "← Voltar ao blog". Em vez
 * de acrescentar um elemento que os mockups não pedem só para satisfazer o
 * schema, monto o bloco direto.
 *
 * Dois níveis: a listagem e o post. Uma trilha mais funda seria mentira — não
 * há hierarquia de categoria navegável no blog.
 */
add_filter('rank_math/json_ld', function ($dados, $jsonld) {
    if (! is_singular('post')) {
        return $dados;
    }

    $dados['BreadcrumbList'] = [
        '@type' => 'BreadcrumbList',
        '@id' => home_url('/#breadcrumb'),
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Blog',
                'item' => home_url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => get_the_title(),
                // Sem `item` no último nível: é a recomendação do Google, e
                // repetir a URL da própria página não acrescenta nada.
            ],
        ],
    ];

    return $dados;
}, 10, 2);

/**
 * Sitemap de categoria fora.
 *
 * ⚠️ **Residual conhecido.** Diferente dos outros blocos deste arquivo, este
 * **não** tem efeito: o `Helper::get_settings` do Rank Math lê a opção uma vez
 * no boot e normaliza num objeto próprio, então o filtro de `get_option` não
 * o alcança. Verificado — o `category-sitemap.xml` continua no índice mesmo
 * com o valor `false` chegando na opção.
 *
 * Fica declarado porque documenta a intenção e passa a valer se o plugin mudar
 * essa leitura. No ambiente real, desligar em Rank Math → Sitemap → Taxonomies
 * é um clique, e o efeito é o mesmo. Registrado na Story 5.3.
 *
 * O blog não tem página de categoria navegável — o eyebrow do card mostra o
 * nome, mas não linka. Indexar `/category/<slug>` produziria listagens
 * concorrendo com a home do blog pelo mesmo conteúdo.
 */
clama_default_de_opcao('rank_math_options_sitemap', function ($opcoes) {
    $padroes = [
        'items_per_page' => 200,
        'include_images' => 'on',
        'pt_post_sitemap' => 'on',
        'pt_page_sitemap' => 'on',
        // ⚠️ `false` booleano, não a string `'off'`. O provider de taxonomia
        // compara com `false ===` estrito; a string passa pela comparação e o
        // sitemap de categoria continua sendo gerado, sem erro nenhum.
        'pt_attachment_sitemap' => false,
        'tax_category_sitemap' => false,
        'tax_post_tag_sitemap' => false,
        'exclude_roles' => [],
    ];

    return array_merge(is_array($opcoes) ? $opcoes : [], $padroes);
});
