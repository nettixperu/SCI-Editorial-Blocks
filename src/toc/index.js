import metadata from './block.json';
import Edit from './edit';
import './editor.css';
import './style.scss';
// WordPress supplies this external package at editor runtime.
// eslint-disable-next-line import/no-unresolved
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null,
} );
