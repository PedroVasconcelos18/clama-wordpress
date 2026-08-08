<?php

/**
 * As classes do widget existem no CSS buildado?
 *
 * ⚠️ **Este é o teste que faltava, e o bug que ele pega já aconteceu duas vezes.**
 *
 * O tema tem duas fontes de classe Tailwind: os templates PHP e o JavaScript do
 * widget (`src/widget-engajamento/index.js`). O `build:css` e o `build:widget`
 * são scripts separados — dá para editar o widget, rodar só o build do JS, e
 * publicar com o CSS velho.
 *
 * O sintoma é **silencioso e específico**: a classe aparece no HTML, a regra não
 * existe no CSS, e o elemento renderiza sem ela. Foi assim que `px-4`, `px-5`,
 * `pl-4`, `pr-5` e `py-2.5` sumiram — o textarea do comentário ficou com padding
 * horizontal **zero**, o texto colado na borda, e os botões com metade da altura.
 * Nada quebrou, nada avisou. É primo do purge dos `img-placeholder-N` da
 * Story 2.5.
 *
 * A checagem é de espaçamento de propósito: são as utilidades cujo nome de
 * regra é previsível, então dá para verificar sem reimplementar o purge.
 */

$tema = dirname(__DIR__, 2) . '/web/app/themes/clama';

/** Utilidades de espaçamento citadas em `className` no fonte do widget. */
function espacamentosDoWidget(string $tema): array
{
    $fonte = file_get_contents("$tema/src/widget-engajamento/index.js");

    preg_match_all('~className="([^"]+)"~', $fonte, $blocos);

    $classes = [];

    foreach ($blocos[1] as $bloco) {
        foreach (preg_split('~\s+~', $bloco) as $classe) {
            // p/m + eixo opcional + número (aceita 2.5). Sem variantes:
            // `sm:px-4` gera regra dentro de media query e a checagem simples
            // daria falso negativo.
            if (preg_match('~^[pm][xytrbl]?-\d+(\.\d+)?$~', $classe)) {
                $classes[$classe] = true;
            }
        }
    }

    return array_keys($classes);
}

it('toda utilidade de espaçamento do widget tem regra no CSS buildado', function () use ($tema) {
    $css = file_get_contents("$tema/assets/css/app.build.css");
    $classes = espacamentosDoWidget($tema);

    expect($classes)->not->toBeEmpty('o extrator não achou classe nenhuma — regex furada?');

    $ausentes = array_values(array_filter($classes, function ($classe) use ($css) {
        // `.py-2.5` sai como `.py-2\.5` no CSS.
        $seletor = '.' . str_replace('.', '\\.', $classe);

        return ! str_contains($css, $seletor . '{') && ! str_contains($css, $seletor . ',');
    }));

    expect($ausentes)->toBeEmpty(
        'classe usada no widget e ausente do CSS — rode `npm run build:css`: '
        . implode(', ', $ausentes),
    );
});

/**
 * ⚠️ **Deliberadamente NÃO há teste de mtime aqui**, apesar de o
 * `WidgetEngajamentoTest` usar esse padrão para o build do JS.
 *
 * O motivo apareceu escrevendo isto: o webpack **não reescreve o arquivo de
 * saída quando o conteúdo compilado é idêntico**. Editar o fonte e desfazer a
 * edição deixa a fonte com mtime novo e a saída com o antigo — e o teste falha
 * para sempre, mesmo com build e fonte em perfeita sincronia. A única saída é
 * `touch` no artefato, que é o teste mandando mentir para ele.
 *
 * A checagem de conteúdo acima não tem esse problema: ela pergunta se a regra
 * existe, não quando o arquivo foi escrito.
 *
 * **Limite honesto:** ela cobre espaçamento, não cor nem borda nem tipografia,
 * porque só as utilidades de espaçamento têm nome de regra previsível o
 * bastante para verificar sem reimplementar o purge do Tailwind. Foi espaçamento
 * que quebrou duas vezes; se um dia quebrar cor, o jeito certo é comparar o CSS
 * contra um build feito na hora, não olhar relógio.
 */

/**
 * Sobra do build: cópias mortas do bloco fora de `assets/blocks/`.
 *
 * O `wp-scripts build` **auto-detecta blocos** em `--webpack-src-dir=src` e os
 * emite junto de qualquer entrada explícita. Como o `build:painel` e o
 * `build:widget` usam `--output-path` próprios (é o que impediu o bloco de
 * sumir do build, na Story 2.6), cada um deles despeja também uma cópia do
 * `versiculo-destacado` no diretório errado.
 *
 * Não quebra nada — o registro só varre `assets/blocks/*​/block.json`. Mas são
 * dois blocos mortos indo para a imagem do Docker, e um `block.json` a mais
 * é exatamente o tipo de coisa que alguém registra por engano depois.
 *
 * O `build:limpar` remove; este teste garante que continua removendo.
 */
it('o build não deixa cópias do bloco fora de assets/blocks', function () use ($tema) {
    $manifestos = glob("$tema/assets/*/*/block.json");

    $forasteiros = array_values(array_filter($manifestos, fn ($m) => ! str_contains($m, '/assets/blocks/')));
    $forasteiros = array_map(fn ($m) => str_replace("$tema/", '', $m), $forasteiros);

    expect($forasteiros)->toBeEmpty(
        'cópia de bloco fora de assets/blocks — rode `npm run build:limpar`: '
        . implode(', ', $forasteiros),
    );
});
