// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

const ALLOWED_BLOCKS = [ 'core/list' ];
const TEMPLATE = [
	[
		'core/list',
		{
			lock: {
				move: true,
				remove: true,
			},
		},
	],
];

export default function Edit() {
	return (
		<aside { ...useBlockProps() }>
			<p className="sci-editorial-sources__label">
				{ __( 'Fuentes y documentación', 'sci-editorial-blocks' ) }
			</p>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				template={ TEMPLATE }
				renderAppender={ () => null }
			/>
		</aside>
	);
}
