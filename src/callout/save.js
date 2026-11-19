// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { getVariant, getVariantSourceLabel } from './variants';

export default function save( { attributes } ) {
	const variant = getVariant( attributes.variant );
	const blockProps = useBlockProps.save( {
		className: `is-variant-${ variant }`,
	} );

	return (
		<aside { ...blockProps }>
			<p className="sci-editorial-callout__label">
				{ attributes.label ?? getVariantSourceLabel( variant ) }
			</p>
			<div className="sci-editorial-callout__content">
				<InnerBlocks.Content />
			</div>
		</aside>
	);
}
