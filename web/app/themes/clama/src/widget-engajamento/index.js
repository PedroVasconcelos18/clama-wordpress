/**
 * Widget de comentários e likes (Story 3.8).
 *
 * ⚠️ **Reimplementação contra o mesmo contrato, não porte dos islands.**
 *
 * Os islands do Vike (`pages/blog/_islands/*.tsx`) são React puro e seriam
 * portáveis — nenhum importa `useData` nem `usePageContext`. Mas eles carregam
 * seis dependências que não vêm de graça: `sonner`, `lucide-react`, o
 * `Textarea` do shadcn, `@/i18n/microcopy`, `@/lib/blog/presentation` e
 * `@/lib/datetime`, além de `useCustomerApi`, que lê `import.meta.env` — API
 * exclusiva do Vite, inexistente sob `wp-scripts`.
 *
 * Arrastar essa árvore estouraria o orçamento de 80 KB gzipped do `WP-NFR4`,
 * que é justamente o requisito que esta story precisa fechar. O que se
 * preserva é o **contrato**: mesmos endpoints, mesmos payloads, mesmos
 * `data-slot` que os testes usam.
 *
 * React vem do `wp-element` (o do core do WordPress), declarado como
 * dependência do `wp_enqueue_script`. É literalmente o que decide se o widget
 * cabe: embarcar uma segunda cópia do React custaria mais que todo o resto.
 */

import { render, useCallback, useEffect, useRef, useState } from '@wordpress/element'

/** Injetado por `wp_localize_script`. */
const dados = window.clamaWidget || {}
const API = (dados.apiBase || '').replace(/\/$/, '')
const SLUG = dados.postSlug || ''

/* ------------------------------------------------------------------ *
 * CSRF
 *
 * O cookie de sessão é `HttpOnly` com escopo `Path=/api` (ADR-01), então o
 * JavaScript não o lê — e por isso o double-submit nativo do Django não
 * funciona. O token vem de `GET /api/csrf/` e fica **só em memória**: gravá-lo
 * em storage recriaria exatamente o problema que o ADR-01 existe para
 * resolver.
 * ------------------------------------------------------------------ */

let tokenCsrf = null
let buscaCsrf = null

async function obterCsrf() {
	if ( tokenCsrf ) {
		return tokenCsrf
	}
	if ( ! buscaCsrf ) {
		buscaCsrf = fetch( `${ API }/api/csrf/`, { credentials: 'include' } )
			.then( ( r ) => ( r.ok ? r.json() : null ) )
			.then( ( corpo ) => {
				tokenCsrf = corpo?.csrf_token || null
				return tokenCsrf
			} )
			.catch( () => null )
			.finally( () => {
				buscaCsrf = null
			} )
	}
	return buscaCsrf
}

async function chamar( caminho, opcoes = {} ) {
	const metodo = opcoes.method || 'GET'
	const cabecalhos = { Accept: 'application/json', ...( opcoes.headers || {} ) }

	if ( ! [ 'GET', 'HEAD', 'OPTIONS' ].includes( metodo ) ) {
		cabecalhos[ 'Content-Type' ] = 'application/json'
		const token = await obterCsrf()
		if ( token ) {
			cabecalhos[ 'X-CSRFToken' ] = token
		}
	}

	// `credentials: 'include'` e caminho relativo à mesma origem: é o rewrite
	// de `/api/*` da Story 1.2 que torna isso same-origin e elimina preflight.
	// O ganho **não** vem do rewrite do blog.
	const resposta = await fetch( `${ API }${ caminho }`, {
		...opcoes,
		method: metodo,
		headers: cabecalhos,
		credentials: 'include',
	} )

	let corpo = null
	try {
		corpo = await resposta.json()
	} catch ( e ) {
		corpo = null
	}

	return { ok: resposta.ok, status: resposta.status, corpo }
}

/** O envelope de erro do projeto tem três chaves, aninhadas em `error`. */
function mensagemDe( corpo, padrao ) {
	return (
		corpo?.error?.pastoral_message ||
		corpo?.pastoral_message ||
		corpo?.conteudo?.pastoral_message ||
		padrao
	)
}

function urlDeLogin() {
	const destino = encodeURIComponent( window.location.pathname )
	return `${ dados.loginUrl || '/login' }?next=${ destino }`
}

/* ------------------------------------------------------------------ */

function Curtir() {
	const [ curtido, setCurtido ] = useState( false )
	const [ total, setTotal ] = useState( null )
	const [ ocupado, setOcupado ] = useState( false )
	const [ erro, setErro ] = useState( '' )

	const alternar = useCallback( async () => {
		setOcupado( true )
		setErro( '' )

		const { ok, status, corpo } = await chamar(
			`/api/blog/posts/${ SLUG }/like/`,
			{ method: 'POST' }
		)

		if ( ok ) {
			setCurtido( !! corpo?.liked )
			setTotal( corpo?.like_count ?? null )
		} else if ( status === 401 || status === 403 ) {
			window.location.href = urlDeLogin()
			return
		} else {
			setErro( mensagemDe( corpo, 'Não conseguimos registrar agora.' ) )
		}

		setOcupado( false )
	}, [] )

	return (
		<div>
			<button
				type="button"
				data-slot="like-button"
				onClick={ alternar }
				disabled={ ocupado }
				aria-pressed={ curtido }
				className="group inline-flex items-center gap-2.5 rounded-full border border-clama-gold/30 py-2.5 pl-4 pr-5 text-sm transition-colors hover:border-clama-gold/60 disabled:opacity-60"
			>
				<span aria-hidden="true" className={ curtido ? 'text-clama-gold' : '' }>
					♥
				</span>
				<span className="tabular-nums">
					{ total === null ? '—' : total }
				</span>
				<span className="sr-only">
					{ curtido ? 'Remover curtida' : 'Curtir este texto' }
				</span>
			</button>
			{ erro && (
				<p role="alert" className="mt-2 text-xs text-clama-cream/70">
					{ erro }
				</p>
			) }
		</div>
	)
}

function Comentario( { comentario } ) {
	const nome = comentario.customer_nome || 'Alguém'

	return (
		<li data-slot="comment-item" className="border-t border-clama-gold/10 py-5">
			<div className="flex items-center gap-2.5">
				<span
					aria-hidden="true"
					className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-clama-gold/30 bg-clama-night-soft/70 font-serif text-sm text-clama-gold"
				>
					{ nome.slice( 0, 1 ).toUpperCase() }
				</span>
				<span className="text-[13px] text-clama-cream">{ nome }</span>
			</div>
			<p className="mt-2.5 whitespace-pre-line text-[15px] leading-[1.6] text-clama-cream/80">
				{ comentario.conteudo }
			</p>
		</li>
	)
}

function Comentarios() {
	const [ lista, setLista ] = useState( [] )
	const [ carregando, setCarregando ] = useState( true )
	const [ texto, setTexto ] = useState( '' )
	const [ enviando, setEnviando ] = useState( false )
	const [ erro, setErro ] = useState( '' )
	const [ aceitaEscrita, setAceitaEscrita ] = useState( true )
	const campo = useRef( null )

	const buscar = useCallback( async () => {
		const { ok, corpo } = await chamar( `/api/blog/posts/${ SLUG }/comments/` )
		if ( ok ) {
			setLista( corpo?.results || [] )
		}
		setCarregando( false )
	}, [] )

	useEffect( () => {
		buscar()
	}, [ buscar ] )

	const enviar = useCallback(
		async ( evento ) => {
			evento.preventDefault()
			setEnviando( true )
			setErro( '' )

			const { ok, status, corpo } = await chamar(
				`/api/blog/posts/${ SLUG }/comments/`,
				{ method: 'POST', body: JSON.stringify( { conteudo: texto } ) }
			)

			if ( ok ) {
				// Só limpa quando o comentário foi de fato publicado.
				setTexto( '' )
				await buscar()
			} else if ( status === 401 || status === 403 ) {
				window.location.href = urlDeLogin()
				return
			} else {
				// ⚠️ AC4 da Story 3.9: **o texto não é limpo**.
				//
				// O caso é real: a Juliana escreve um comentário longo, o post é
				// despublicado nesse meio-tempo, ela clica em enviar. Rejeitar é
				// correto; limpar a caixa é cruel.
				setErro( mensagemDe( corpo, 'Não conseguimos publicar agora.' ) )

				// 409 é estado do post, não falta de permissão dela — o formulário
				// fecha, mas o que ela escreveu continua na tela para ser copiado.
				if ( status === 409 ) {
					setAceitaEscrita( false )
				}
			}

			setEnviando( false )
		},
		[ texto, buscar ]
	)

	return (
		<section className="mt-14" aria-labelledby="clama-comentarios-titulo">
			<h2
				id="clama-comentarios-titulo"
				className="font-serif text-2xl text-clama-cream"
			>
				Comentários
			</h2>

			{ aceitaEscrita ? (
				<form onSubmit={ enviar } className="mt-6" data-slot="comment-form">
					<label htmlFor="clama-comentario" className="sr-only">
						Seu comentário
					</label>
					<textarea
						id="clama-comentario"
						ref={ campo }
						value={ texto }
						onChange={ ( e ) => setTexto( e.target.value ) }
						rows={ 4 }
						maxLength={ 2000 }
						placeholder="Escreva o que este texto mexeu em você…"
						className="w-full rounded-xl border border-clama-gold/25 bg-clama-night-deep px-4 py-3 text-[15px] text-clama-cream placeholder:text-clama-cream/40 focus-visible:border-clama-gold/60 focus-visible:outline-none"
					/>
					<div className="mt-3 flex items-center justify-between gap-4">
						{ erro ? (
							<p role="alert" className="text-xs text-clama-cream/75">
								{ erro }
							</p>
						) : (
							<span />
						) }
						<button
							type="submit"
							disabled={ enviando || texto.trim().length === 0 }
							className="rounded-full bg-clama-gold px-5 py-2.5 text-sm font-semibold text-clama-night transition-colors hover:bg-clama-gold-soft disabled:opacity-50"
						>
							{ enviando ? 'Publicando…' : 'Publicar' }
						</button>
					</div>
				</form>
			) : (
				<div
					role="alert"
					data-slot="comment-form-fechado"
					className="mt-6 rounded-xl border border-dashed border-clama-gold/25 px-4 py-5"
				>
					<p className="text-sm text-clama-cream/80">{ erro }</p>
					{ /* O texto digitado permanece visível e selecionável. */ }
					{ texto && (
						<>
							<p className="mt-4 text-xs uppercase tracking-[0.16em] text-clama-gold-soft">
								O que você escreveu
							</p>
							<p className="mt-2 whitespace-pre-line rounded-lg bg-clama-night-deep px-4 py-3 text-[15px] text-clama-cream/85">
								{ texto }
							</p>
						</>
					) }
				</div>
			) }

			{ carregando ? (
				<p className="mt-8 text-sm text-clama-cream/55">Carregando…</p>
			) : (
				<ul className="mt-8">
					{ lista.map( ( c ) => (
						<Comentario key={ c.id } comentario={ c } />
					) ) }
				</ul>
			) }
		</section>
	)
}

function Widget() {
	return (
		<>
			<Curtir />
			<Comentarios />
		</>
	)
}

const ancora = document.getElementById( 'clama-widget-engajamento' )

if ( ancora && SLUG ) {
	render( <Widget />, ancora )
}
