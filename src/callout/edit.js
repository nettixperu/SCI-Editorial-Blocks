// WordPress supplies this external package at editor runtime.
/* eslint-disable import/no-unresolved */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
/* eslint-enable import/no-unresolved */
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';
import { getVariant, VARIANTS } from './variants';

const ALLOWED_BLOCKS = [ 'core/paragraph', 'core/list' ];
const TEMPLATE = [ [ 'core/paragraph' ] ];

export default function Edit( { attributes, setAttributes } ) {
	const variant = getVariant( attributes.variant );
	const label = VARIANTS.find( ( item ) => item.value === variant ).label;
	const blockProps = useBlockProps( {
		className: `is-variant-${ variant }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Callout settings', 'sci-editorial-blocks' ) }
				>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Variant', 'sci-editorial-blocks' ) }
						value={ variant }
						options={ VARIANTS }
						onChange={ ( value ) => {
							if (
								VARIANTS.some(
									( item ) => item.value === value
								)
							) {
								setAttributes( { variant: value } );
							}
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<aside { ...blockProps }>
				<p className="sci-editorial-callout__label">{ label }</p>
				<div className="sci-editorial-callout__content">
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						template={ TEMPLATE }
					/>
				</div>
			</aside>
		</>
	);
}
