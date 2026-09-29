/* eslint-disable import/no-unresolved */
/* eslint-disable @wordpress/no-unsafe-wp-apis -- UnitControl is experimental in the WP 7.x baseline. */

import {
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { itemGap = '0.75rem', accentColor = '#000000' } = attributes;
	const blockProps = useBlockProps( {
		className: 'sci-editorial-toc-placeholder',
		style: {
			'--sci-toc-item-gap': itemGap,
			'--sci-toc-accent-color': accentColor,
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Presentación del índice',
						'sci-editorial-blocks'
					) }
				>
					<UnitControl
						size="small"
						label={ __(
							'Espaciado entre títulos',
							'sci-editorial-blocks'
						) }
						value={ itemGap }
						onChange={ ( value ) =>
							setAttributes( { itemGap: value || '0.75rem' } )
						}
						units={ [
							{ value: 'px' },
							{ value: 'rem' },
							{ value: 'em' },
						] }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Color del acento', 'sci-editorial-blocks' ) }
					colorSettings={ [
						{
							value: accentColor,
							onChange: ( value ) =>
								setAttributes( {
									accentColor: value || '#000000',
								} ),
							label: __(
								'Color del acento',
								'sci-editorial-blocks'
							),
						},
					] }
					initialOpen
				/>
			</InspectorControls>
			<div { ...blockProps }>
				<p>{ __( 'En este artículo', 'sci-editorial-blocks' ) }</p>
				<p>
					{ __(
						'El índice se genera automáticamente con los encabezados H2 de la entrada.',
						'sci-editorial-blocks'
					) }
				</p>
			</div>
		</>
	);
}
