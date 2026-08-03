/**
 * Painel "História ilustrativa" no editor (Story 2.7).
 *
 * Fica no painel de documento, junto de categoria e destaque — onde a decisão
 * editorial é tomada. Metabox clássica cairia numa área separada embaixo do
 * editor, longe do fluxo.
 */

import { registerPlugin } from '@wordpress/plugins'
import { PluginDocumentSettingPanel } from '@wordpress/editor'
import { ToggleControl, Notice } from '@wordpress/components'
import { useSelect, useDispatch } from '@wordpress/data'
import { store as editorStore } from '@wordpress/editor'

const META = 'clama_historia_ilustrativa'

function PainelHistoria() {
	const marcado = useSelect(
		( select ) =>
			!! select( editorStore ).getEditedPostAttribute( 'meta' )?.[ META ],
		[]
	)
	const { editPost } = useDispatch( editorStore )

	return (
		<PluginDocumentSettingPanel
			name="clama-historia-ilustrativa"
			title="História ilustrativa"
			className="clama-historia-ilustrativa"
		>
			<ToggleControl
				__nextHasNoMarginBottom
				label="Este post é uma história ilustrativa"
				help="Marque quando a história não for um relato literal de uma pessoa real."
				checked={ marcado }
				onChange={ ( valor ) =>
					editPost( { meta: { [ META ]: valor } } )
				}
			/>

			{ marcado && (
				<Notice status="info" isDismissible={ false }>
					O aviso <strong>“História ilustrativa — não é um relato
					literal”</strong> aparecerá no cabeçalho do post. É
					exigência do CDC art. 37, não um elemento opcional.
				</Notice>
			) }
		</PluginDocumentSettingPanel>
	)
}

registerPlugin( 'clama-historia-ilustrativa', { render: PainelHistoria } )
