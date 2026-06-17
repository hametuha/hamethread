/**
 * Thread Button block.
 *
 * Dynamic block: the front-end markup is produced server-side by render.php
 * (which reuses the existing hamethread_button() PHP function), so the editor
 * shows a server-rendered preview via ServerSideRender.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

/**
 * Edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {JSX.Element} Editor markup.
 */
function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Thread Button', 'hamethread' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Button label', 'hamethread' ) }
						value={ attributes.label }
						onChange={ ( label ) => setAttributes( { label } ) }
						help={ __( 'Leave empty to use the default label.', 'hamethread' ) }
					/>
					<TextControl
						type="number"
						label={ __( 'Parent post ID', 'hamethread' ) }
						value={ attributes.parent }
						onChange={ ( parent ) =>
							setAttributes( { parent: parseInt( parent, 10 ) || 0 } )
						}
						help={ __(
							'Optional. Attach new threads to this post ID. 0 for none.',
							'hamethread'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
