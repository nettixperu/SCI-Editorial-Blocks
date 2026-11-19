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
// eslint-disable-next-line import/no-extraneous-dependencies
import { useEffect } from '@wordpress/element';
import { getVariant, getVariantLabel, getVariantOptions } from './variants';

const ALLOWED_BLOCKS = [ 'core/paragraph', 'core/list' ];
const TEMPLATE = [ [ 'core/paragraph' ] ];

export default function Edit( { attributes, setAttributes } ) {
	const variant = getVariant( attributes.variant );
	const options = getVariantOptions();
	const { label } = attributes;
	const defaultLabel = getVariantLabel( variant );
	useEffect( () => {
		if ( label === undefined ) {
			setAttributes( { label: defaultLabel } );
		}
	}, [ defaultLabel, label, setAttributes ] );
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
						options={ options }
						onChange={ ( value ) => {
							if (
								options.some( ( item ) => item.value === value )
							) {
								setAttributes( {
									variant: value,
									label: getVariantLabel( value ),
								} );
							}
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<aside { ...blockProps }>
				<p className="sci-editorial-callout__label">
					{ label ?? defaultLabel }
				</p>
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
