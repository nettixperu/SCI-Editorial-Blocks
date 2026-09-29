// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-extraneous-dependencies
import { __ } from '@wordpress/i18n';

export const VARIANTS = [
	{ value: 'context', label: __( 'En contexto', 'sci-editorial-blocks' ) },
	{ value: 'key', label: __( 'Clave', 'sci-editorial-blocks' ) },
	{
		value: 'practice',
		label: __( 'En la práctica', 'sci-editorial-blocks' ),
	},
	{ value: 'decision', label: __( 'Para decidir', 'sci-editorial-blocks' ) },
	{ value: 'warning', label: __( 'Advertencia', 'sci-editorial-blocks' ) },
];

export function getVariant( value ) {
	return VARIANTS.some( ( variant ) => variant.value === value )
		? value
		: 'context';
}

export function getVariantLabel( value ) {
	const variant = getVariant( value );
	return VARIANTS.find( ( item ) => item.value === variant ).label;
}
