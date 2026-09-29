// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

export default function save() {
	return (
		<aside { ...useBlockProps.save() }>
			<p className="sci-editorial-sources__label">
				{ __( 'Fuentes y documentación', 'sci-editorial-blocks' ) }
			</p>
			<InnerBlocks.Content />
		</aside>
	);
}
