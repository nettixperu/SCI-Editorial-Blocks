// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

export default function Edit() {
	return (
		<span { ...useBlockProps() }>
			{ __(
				'El tiempo de lectura se calcula automáticamente al mostrar la entrada.',
				'sci-editorial-blocks'
			) }
		</span>
	);
}
