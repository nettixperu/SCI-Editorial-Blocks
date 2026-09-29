// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';

registerBlockType( metadata, {
	edit: Edit,
	save,
} );
