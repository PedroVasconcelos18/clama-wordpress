<?php

/**
 * Content Security Policy em `Report-Only` (Story 2.11).
 *
 * ⚠️ **A CSP aqui é defesa em profundidade, não controle primário.**
 *
 * O controle primário contra o vetor que esta migração cria é o escopo
 * `Path=/api` do cookie de autenticação (ADR-01, Epic 1). A CSP reduz a
 * **probabilidade** de um script hostil executar; o escopo do cookie reduz a
 * **consequência** de ele executar. Confundir os dois papéis é o que faria
 * alguém aceitar uma CSP frouxa achando que está protegido.
 *
 * Risco residual que nenhuma das duas elimina: um XSS ativo na página do blog
 * ainda pode **agir como a Juliana enquanto ela está lá** — o cookie viaja
 * automaticamente em chamada same-origin à API. Só isolamento de origem por
 * subdomínio elimina isso, ao custo da autoridade de SEO, e essa troca foi
 * decidida e aceita.
 *
 * Por que `Report-Only` e não `enforce`: o WordPress é das plataformas mais
 * hostis a CSP. Cada plugin injeta script inline, cada tema injeta estilo
 * inline, e o editor de blocos gera CSS inline para cada escolha de layout —
 * nada disso sabe que uma CSP possa existir. Ligar `enforce` sem observar
 * primeiro quebra o editor.
 *
 * Critério de saída para `enforce` está em
 * `clama-docs/csp-do-blog.md` — é critério, não data.
 */

if (! defined('ABSPATH')) {
    exit;
}

/** Onde as violações são recebidas. Rota REST própria, sem serviço externo. */
const CLAMA_CSP_NAMESPACE = 'clama/v1';
const CLAMA_CSP_ROTA = '/csp-report';

/**
 * A política.
 *
 * Fontes inline legítimas, nomeadas (AC2) — todas do core do WordPress,
 * levantadas na página renderizada:
 *
 *   estilo inline   `wp-img-auto-sizes-contain-inline-css`     135 B
 *                   `global-styles-inline-css`              14.783 B  (theme.json compilado)
 *                   `wp-block-paragraph-inline-css`            742 B
 *   script inline   speculation rules do core                  351 B
 *   atributos style=""                                            0
 *
 * `style-src` precisa de `'unsafe-inline'` por causa dos três acima. Hash não
 * serve: o conteúdo do `global-styles` muda a cada alteração de `theme.json`,
 * e um hash fixo quebraria no primeiro ajuste de paleta. Nonce também não —
 * o core não passa esses estilos por um filtro de nonce confiável.
 *
 * `script-src` fica **sem** `'unsafe-inline'`. O único script inline hoje é a
 * speculation rules do core, e é justamente esse tipo de violação que
 * queremos ver no relatório antes de decidir.
 */
function clama_csp_politica(): string
{
    $relatorio = rest_url(CLAMA_CSP_NAMESPACE . CLAMA_CSP_ROTA);

    $diretivas = [
        "default-src 'self'",
        "script-src 'self'",
        // Ver acima: os três estilos inline do core.
        "style-src 'self' 'unsafe-inline'",
        // Imagem de post pode vir de host externo (o Django já permitia URL
        // absoluta no corpo). `data:` cobre placeholder e ícone embutido.
        "img-src 'self' data: https:",
        "font-src 'self'",
        // A API do Clama é same-origin sob `clama.me/api` (ADR-01/R12).
        "connect-src 'self'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        // Sem `upgrade-insecure-requests`: o navegador ignora essa diretiva em
        // política report-only e imprime um erro no console de todo visitante.
        // Ela entra junto com o `enforce`, não antes.
        'report-uri ' . esc_url_raw($relatorio),
    ];

    return implode('; ', $diretivas);
}

/**
 * Só no frontend.
 *
 * O `wp-admin` fica de fora de propósito: o editor de blocos gera inline sem
 * limite e o relatório viraria ruído que esconde a violação real da página
 * pública — que é a que o leitor visita e a única que um atacante alcança sem
 * credencial.
 */
add_action('send_headers', function () {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    header('Content-Security-Policy-Report-Only: ' . clama_csp_politica());
});

/**
 * Recebe as violações.
 *
 * `permission_callback` aberto é intencional: o relatório vem do navegador de
 * quem visita, sem credencial nenhuma. Em compensação o corpo nunca é
 * confiado — só campos conhecidos entram no log, truncados.
 */
add_action('rest_api_init', function () {
    register_rest_route(CLAMA_CSP_NAMESPACE, CLAMA_CSP_ROTA, [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            $corpo = json_decode($request->get_body(), true);
            $violacao = $corpo['csp-report'] ?? $corpo ?? [];

            if (! is_array($violacao)) {
                return new WP_REST_Response(null, 204);
            }

            $limpo = [];
            foreach (['document-uri', 'violated-directive', 'effective-directive', 'blocked-uri', 'source-file', 'line-number'] as $campo) {
                if (isset($violacao[$campo])) {
                    $limpo[$campo] = is_scalar($violacao[$campo])
                        ? mb_substr((string) $violacao[$campo], 0, 300)
                        : null;
                }
            }

            if ($limpo !== []) {
                error_log('clama_csp_violacao ' . wp_json_encode($limpo));
            }

            // 204 e nada no corpo: o navegador não lê a resposta, e devolver
            // conteúdo só gastaria banda em cada violação.
            return new WP_REST_Response(null, 204);
        },
    ]);
});
