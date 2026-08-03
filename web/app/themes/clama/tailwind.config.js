/**
 * Tokens portados de `clama-frontend/tailwind.config.ts:11-18,69`.
 *
 * Portados, não reinterpretados: os mockups de `clama-docs/blog-design/`
 * carregam esses nomes de classe literalmente (`bg-clama-night`,
 * `border-clama-gold/15`, `text-[64px]`). Renomear qualquer token aqui
 * quebraria a correspondência com o design de referência.
 */

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./**/*.php', './assets/js/**/*.js'],

  /**
   * `clama_placeholder_de_capa()` monta o nome por concatenação
   * (`'img-placeholder-' . $n`), então o scanner do Tailwind nunca vê a string
   * completa e purga as seis. O sintoma é silencioso: a classe aparece no
   * HTML, a regra não existe no CSS, e o card renderiza sem gradiente nenhum.
   */
  safelist: [
    'img-placeholder-1',
    'img-placeholder-2',
    'img-placeholder-3',
    'img-placeholder-4',
    'img-placeholder-5',
    'img-placeholder-6',
  ],
  theme: {
    extend: {
      colors: {
        'clama-night': '#1a0a2e',
        'clama-night-deep': '#110720',
        'clama-night-soft': '#2a1a40',
        'clama-gold': '#f0c040',
        'clama-gold-soft': '#d4a017',
        'clama-cream': '#fdf8f0',
        'clama-cream-warm': '#f5ecd9',
        'clama-accent': '#8a5cf6',
        // Verde do WhatsApp — usado no botão de compartilhar do post.
        'wa-green': '#25d366',
      },
      borderRadius: {
        'clama-card': '16px',
      },
      fontFamily: {
        // Zero web fonts (WP-UXDR1 / decisão de UX). Georgia é ubíqua em
        // desktop e o fallback serif do sistema cobre o resto.
        serif: ['Georgia', '"Times New Roman"', 'serif'],
        sans: ['-apple-system', '"Helvetica Neue"', 'Arial', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
