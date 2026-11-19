// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

const VARIANT_LABELS = {
	context: {
		source: 'En contexto',
		translated: () => __( 'En contexto', 'sci-editorial-blocks' ),
	},
	key: {
		source: 'Clave',
		translated: () => __( 'Clave', 'sci-editorial-blocks' ),
	},
	practice: {
		source: 'En la práctica',
		translated: () => __( 'En la práctica', 'sci-editorial-blocks' ),
	},
	decision: {
		source: 'Para decidir',
		translated: () => __( 'Para decidir', 'sci-editorial-blocks' ),
	},
	warning: {
		source: 'Advertencia',
		translated: () => __( 'Advertencia', 'sci-editorial-blocks' ),
	},
};

export function getVariantOptions() {
	return Object.keys( VARIANT_LABELS ).map( ( value ) => ( {
		value,
		label: getVariantLabel( value ),
	} ) );
}

export function getVariant( value ) {
	return Object.hasOwn( VARIANT_LABELS, value ) ? value : 'context';
}

export function getVariantLabel( value ) {
	return VARIANT_LABELS[ getVariant( value ) ].translated();
}

export function getVariantSourceLabel( value ) {
	return VARIANT_LABELS[ getVariant( value ) ].source;
}
