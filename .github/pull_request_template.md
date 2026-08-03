# Resumo

<!-- 3 bullets, no máximo. -->

-

# Test plan

- [ ] `composer validate --strict`
- [ ] `composer lint`
- [ ] `composer test`

---

# Este PR adiciona plugin, tema ou mu-plugin?

**Se não, apague esta seção inteira.**

Se sim: contagem de plugin é orçamento de segurança — 91% das vulnerabilidades de WordPress em 2025 vieram de plugins, contra 6 no core. Responda as três perguntas abaixo e replique em `config/plugins-aprovados.json`, senão `composer test` falha.

## 1. Necessidade

<!-- O que quebra, ou fica impossível, sem ele. "Seria útil" e "facilita" não são
     resposta — se for conveniência de edição, a resposta é não. -->

## 2. O que injeta no frontend

<!-- CSS, JavaScript, fontes, requisições a terceiros. Quantos KB.
     Peso na área administrativa cobrado do blog inteiro é rejeição direta. -->

## 3. Histórico de CVE

<!-- Quantas, de que gravidade, corrigidas em quanto tempo.
     Consultar https://patchstack.com/database/ e https://wpscan.com/plugins/
     Histórico de RCE não-autenticado não entra. -->

## É plugin de segurança?

Só existe **um** neste projeto, e é o Patchstack. Empilhar Wordfence, Solid Security e Patchstack não soma proteção — soma superfície, conflito de hooks e peso. Se este PR propõe um segundo, ele precisa **substituir** o atual, não conviver.

📄 [Política completa](../../clama-docs/politica-de-plugins-wordpress.md)
