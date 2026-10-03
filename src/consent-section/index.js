import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	RangeControl,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import metadata from './block.json';
import './editor.css';

// Registered providers, printed by the plugin before this script.
const providers = window.brocodeConsentEmbed?.providers ?? [];

const CATEGORIES = [
	{ value: 'marketing', label: __( 'Marketing', 'brocode-consent-embed' ) },
	{ value: 'statistics', label: __( 'Statistics', 'brocode-consent-embed' ) },
	{
		value: 'statistics-anonymous',
		label: __( 'Statistics (anonymous)', 'brocode-consent-embed' ),
	},
	{ value: 'preferences', label: __( 'Preferences', 'brocode-consent-embed' ) },
	{ value: 'functional', label: __( 'Functional', 'brocode-consent-embed' ) },
];

registerBlockType( metadata.name, {
	edit: function ConsentSectionEdit( { attributes, setAttributes } ) {
		const { provider, serviceName, company, category, title, notice, minHeight } =
			attributes;
		const isCustom = provider === 'custom';
		const label = isCustom
			? serviceName
			: providers.find( ( p ) => p.value === provider )?.label ?? provider;

		const blockProps = useBlockProps( { className: 'bce-section-editor' } );
		const innerBlocksProps = useInnerBlocksProps(
			{ className: 'bce-section-editor__content' },
			{ renderAppender: InnerBlocks.ButtonBlockAppender }
		);

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Service', 'brocode-consent-embed' ) }>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Service', 'brocode-consent-embed' ) }
							value={ provider }
							options={ [
								...providers,
								{
									value: 'custom',
									label: __( 'Other service…', 'brocode-consent-embed' ),
								},
							] }
							onChange={ ( v ) => setAttributes( { provider: v } ) }
						/>
						{ isCustom && (
							<>
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __( 'Service name', 'brocode-consent-embed' ) }
									help={ __(
										'Shown on the button, e.g. Instagram or Calendly. Visitors who tick "always load" are remembered per service name.',
										'brocode-consent-embed'
									) }
									value={ serviceName }
									onChange={ ( v ) => setAttributes( { serviceName: v } ) }
								/>
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __(
										'Company receiving the data',
										'brocode-consent-embed'
									) }
									help={ __(
										'Defaults to the service name.',
										'brocode-consent-embed'
									) }
									value={ company }
									onChange={ ( v ) => setAttributes( { company: v } ) }
								/>
								<SelectControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __(
										'Consent category (WP Consent API)',
										'brocode-consent-embed'
									) }
									value={ category }
									options={ CATEGORIES }
									onChange={ ( v ) => setAttributes( { category: v } ) }
								/>
							</>
						) }
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Heading', 'brocode-consent-embed' ) }
							value={ title }
							onChange={ ( v ) => setAttributes( { title: v } ) }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Privacy notice', 'brocode-consent-embed' ) }
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
								'Placeholder minimum height (px, 0 = auto)',
								'brocode-consent-embed'
							) }
							value={ minHeight }
							min={ 0 }
							max={ 1200 }
							step={ 50 }
							onChange={ ( v ) => setAttributes( { minHeight: v } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<p className="bce-section-editor__label">
						{ label
							? sprintf(
									/* translators: %s: service name */
									__( 'Loads only after consent to %s', 'brocode-consent-embed' ),
									label
							  )
							: __(
									'Name the service in the block settings. Until then nothing renders on the site.',
									'brocode-consent-embed'
							  ) }
					</p>
					{ ! label && (
						<Notice status="warning" isDismissible={ false }>
							{ __( 'No service named yet.', 'brocode-consent-embed' ) }
						</Notice>
					) }
					<div { ...innerBlocksProps } />
				</div>
			</>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},
} );
