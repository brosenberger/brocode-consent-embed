import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import './style.css';
import metadata from './block.json';

// A pasted <iframe …> snippet is reduced to its src.
const urlFromInput = ( value ) => {
	const src = value.match( /src="([^"]+)"/ );
	return src ? src[ 1 ] : value.trim();
};

registerBlockType( metadata.name, {
	edit: function ConsentEmbedEdit( { attributes, setAttributes } ) {
		const { url, title, height, notice } = attributes;

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Embed', 'brocode-consent-embed' ) }>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Embed link', 'brocode-consent-embed' ) }
							help={ __(
								'Google Maps or Google Calendar embed link, a YouTube or Vimeo video link, or an OpenStreetMap embed link. A pasted <iframe> snippet works too.',
								'brocode-consent-embed'
							) }
							value={ url }
							onChange={ ( v ) =>
								setAttributes( { url: urlFromInput( v ) } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __(
								'Title (for screen readers)',
								'brocode-consent-embed'
							) }
							value={ title }
							onChange={ ( v ) => setAttributes( { title: v } ) }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __(
								'Privacy notice',
								'brocode-consent-embed'
							) }
							help={ __(
								'Leave empty for the default text. %s is replaced with the service name.',
								'brocode-consent-embed'
							) }
							value={ notice }
							onChange={ ( v ) => setAttributes( { notice: v } ) }
						/>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __(
								'Height (px, maps and calendars)',
								'brocode-consent-embed'
							) }
							value={ height }
							min={ 200 }
							max={ 1200 }
							step={ 50 }
							onChange={ ( v ) => setAttributes( { height: v } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...useBlockProps() }>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},

	save() {
		return null;
	},
} );
