/**
 * Barra de progresso de leitura (Story 2.8).
 *
 * Porte de `clama-frontend/src/components/blog/ReadingProgressBar.tsx`. Sem
 * framework, sem build: são 40 linhas, e qualquer runtime aqui sairia do
 * orçamento de 80 KB gzipped por causa de uma barrinha.
 *
 * A barra já existe no DOM com largura 0 (`header.php`), então nunca causa
 * layout shift — ela vive dentro de um trilho de altura fixa que é pintado
 * junto com o cabeçalho.
 */
;( function () {
	var barra = document.getElementById( 'clama-progresso' )

	if ( ! barra ) {
		return
	}

	var trilho = barra.parentElement
	var pendente = false

	function percentual() {
		var alturaRolavel =
			document.documentElement.scrollHeight - window.innerHeight

		// Página menor que a janela: não há o que percorrer, e dividir por
		// zero aqui produziria Infinity no aria-valuenow.
		if ( alturaRolavel <= 0 ) {
			return 0
		}

		return Math.min( 100, Math.max( 0, ( window.scrollY / alturaRolavel ) * 100 ) )
	}

	function atualizar() {
		pendente = false

		var valor = percentual()

		barra.style.width = valor + '%'
		trilho.setAttribute( 'aria-valuenow', Math.round( valor ) )
	}

	function agendar() {
		// Throttle por frame: o evento de scroll dispara muito mais vezes que
		// a tela pinta, e escrever `style.width` a cada um força layout à toa.
		if ( pendente ) {
			return
		}

		pendente = true
		window.requestAnimationFrame( atualizar )
	}

	window.addEventListener( 'scroll', agendar, { passive: true } )
	window.addEventListener( 'resize', agendar )

	atualizar()
} )()
