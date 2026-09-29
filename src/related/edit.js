// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

export default function Edit() {
	return (
		<aside { ...useBlockProps() }>
			<p>{ __( 'Relacionado', 'sci-editorial-blocks' ) }</p>
			<p>
				{ __(
					'El artículo relacionado se selecciona automáticamente según las categorías y etiquetas de la entrada.',
					'sci-editorial-blocks'
				) }
			</p>
		</aside>
	);
}
