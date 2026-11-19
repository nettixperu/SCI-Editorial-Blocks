// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	return (
		<aside { ...useBlockProps.save() }>
			<p className="sci-editorial-sources__label">
				{ attributes.label ?? 'Fuentes y documentación' }
			</p>
			<InnerBlocks.Content />
		</aside>
	);
}
