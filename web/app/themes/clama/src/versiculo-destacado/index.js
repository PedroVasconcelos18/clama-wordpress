/**
 * Bloco "Versículo destacado" (Story 2.6).
 *
 * Substitui a extensão Tiptap `VersiculoDestacado` do editor próprio.
 *
 * ⚠️ Sobre o "contrato de HTML" que a story manda preservar: ele nunca
 * existiu inteiro. A extensão Tiptap serializa
 *
 *   <blockquote class="versiculo" data-referencia="…" data-versao="…">TEXTO</blockquote>
 *
 * mas o `bleach` do Django (`clama/blog/sanitization.py`) só permite `class`
 * em `blockquote`. Verificado rodando o sanitizador: `data-referencia` e
 * `data-versao` são removidos antes de gravar, e `<cite>` não está na lista
 * de tags permitidas. O que existe no banco hoje é
 *
 *   <blockquote class="versiculo">TEXTO</blockquote>
 *
 * — sem referência, sem versão, e com o texto como nó direto, não dentro de
 * `<p>`. Por isso o CSS do tema estiliza as DUAS formas: a legada, que chega
 * na migração do Epic 4, e esta, que o bloco passa a emitir.
 */

import { registerBlockType } from '@wordpress/blocks'
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor'
import { PanelBody, TextControl } from '@wordpress/components'
import metadata from './block.json'

/** Rótulo da citação: "João 3:16 (ARC)". Sem versão, só a referência. */
function legenda( referencia, versao ) {
	if ( ! referencia ) {
		return ''
	}
	return versao ? `${ referencia } (${ versao })` : referencia
}

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const { texto, referencia, versao } = attributes
		const blockProps = useBlockProps( { className: 'versiculo' } )

		return (
			<>
				<InspectorControls>
					<PanelBody title="Versículo">
						<TextControl
							label="Referência"
							help="Ex.: João 3:16"
							value={ referencia }
							onChange={ ( valor ) =>
								setAttributes( { referencia: valor } )
							}
						/>
						<TextControl
							label="Versão"
							help="Tradução. Padrão: ARC."
							value={ versao }
							onChange={ ( valor ) =>
								setAttributes( { versao: valor } )
							}
						/>
					</PanelBody>
				</InspectorControls>

				{ /* A pré-visualização usa o mesmo markup e as mesmas classes
				     do `save` — é o que faz o editor corresponder ao frontend
				     em vez de aproximá-lo. */ }
				<blockquote { ...blockProps }>
					<RichText
						tagName="p"
						value={ texto }
						allowedFormats={ [ 'core/italic', 'core/bold' ] }
						onChange={ ( valor ) => setAttributes( { texto: valor } ) }
						placeholder="Cole o versículo aqui…"
					/>
					{ referencia && (
						<cite>{ legenda( referencia, versao ) }</cite>
					) }
				</blockquote>
			</>
		)
	},

	save( { attributes } ) {
		const { texto, referencia, versao } = attributes
		const blockProps = useBlockProps.save( { className: 'versiculo' } )

		return (
			<blockquote
				{ ...blockProps }
				data-referencia={ referencia || undefined }
				data-versao={ versao || undefined }
			>
				<RichText.Content tagName="p" value={ texto } />
				{ referencia && <cite>{ legenda( referencia, versao ) }</cite> }
			</blockquote>
		)
	},
} )
