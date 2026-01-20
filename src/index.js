/**
 * Advanced Blend Mode Block - Editor Extension
 * 
 * Adds blend mode controls to supported Gutenberg blocks
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    ToggleControl, 
    SelectControl, 
    RangeControl,
    RadioControl,
    ColorPicker,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
    BaseControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './editor.scss';
import './style.scss';

// Get supported blocks from PHP
const SUPPORTED_BLOCKS = window.abmbSettings?.supportedBlocks || [
    'core/paragraph',
    'core/heading', 
    'core/group',
    'core/row',
    'core/stack'
];

// Available blend modes
const BLEND_MODES = [
    { label: 'Normal', value: 'normal' },
    { label: 'Multiply', value: 'multiply' },
    { label: 'Screen', value: 'screen' },
    { label: 'Overlay', value: 'overlay' },
    { label: 'Darken', value: 'darken' },
    { label: 'Lighten', value: 'lighten' },
    { label: 'Color Dodge', value: 'color-dodge' },
    { label: 'Color Burn', value: 'color-burn' },
    { label: 'Hard Light', value: 'hard-light' },
    { label: 'Soft Light', value: 'soft-light' },
    { label: 'Difference', value: 'difference' },
    { label: 'Exclusion', value: 'exclusion' },
    { label: 'Hue', value: 'hue' },
    { label: 'Saturation', value: 'saturation' },
    { label: 'Color', value: 'color' },
    { label: 'Luminosity', value: 'luminosity' },
];

// Default settings
const DEFAULT_BLEND_SETTINGS = {
    enabled: false,
    mode: 'simple',
    blendMode: 'color-burn',
    baseColor: '#bdc6d2',
    overlayColor: '#3a3a3a',
    overlayOpacity: 0.3
};

/**
 * Add blend mode attributes to supported blocks
 */
function addBlendModeAttributes( settings, name ) {
    if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            advancedBlendMode: {
                type: 'object',
                default: DEFAULT_BLEND_SETTINGS
            }
        }
    };
}

addFilter(
    'blocks.registerBlockType',
    'advanced-blend-mode-block/add-attributes',
    addBlendModeAttributes
);

/**
 * Add blend mode controls to block inspector
 */
const withBlendModeControls = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {
        const { name, attributes, setAttributes, isSelected } = props;

        if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
            return <BlockEdit { ...props } />;
        }

        const blendSettings = {
            ...DEFAULT_BLEND_SETTINGS,
            ...( attributes.advancedBlendMode || {} )
        };

        const updateBlendSettings = ( newSettings ) => {
            setAttributes({
                advancedBlendMode: {
                    ...blendSettings,
                    ...newSettings
                }
            });
        };

        return (
            <>
                <BlockEdit { ...props } />
                { isSelected && (
                    <InspectorControls>
                        <PanelBody 
                            title={ __( 'Blend Mode', 'advanced-blend-mode-block' ) }
                            initialOpen={ blendSettings.enabled }
                        >
                            <VStack spacing={ 4 }>
                                <ToggleControl
                                    label={ __( 'Enable Blend Mode', 'advanced-blend-mode-block' ) }
                                    checked={ blendSettings.enabled }
                                    onChange={ ( enabled ) => updateBlendSettings({ enabled }) }
                                />

                                { blendSettings.enabled && (
                                    <>
                                        <RadioControl
                                            label={ __( 'Effect Type', 'advanced-blend-mode-block' ) }
                                            selected={ blendSettings.mode }
                                            options={ [
                                                { label: __( 'Simple CSS', 'advanced-blend-mode-block' ), value: 'simple' },
                                                { label: __( 'Stripe Effect', 'advanced-blend-mode-block' ), value: 'stripe' }
                                            ] }
                                            onChange={ ( mode ) => updateBlendSettings({ mode }) }
                                        />

                                        <SelectControl
                                            label={ __( 'Blend Mode', 'advanced-blend-mode-block' ) }
                                            value={ blendSettings.blendMode }
                                            options={ BLEND_MODES }
                                            onChange={ ( blendMode ) => updateBlendSettings({ blendMode }) }
                                        />

                                        { blendSettings.mode === 'stripe' && (
                                            <>
                                                <BaseControl
                                                    label={ __( 'Base Color', 'advanced-blend-mode-block' ) }
                                                    help={ __( 'The underlying text color (light color works best)', 'advanced-blend-mode-block' ) }
                                                >
                                                    <ColorPicker
                                                        color={ blendSettings.baseColor }
                                                        onChange={ ( baseColor ) => updateBlendSettings({ baseColor }) }
                                                        enableAlpha={ false }
                                                    />
                                                </BaseControl>

                                                <BaseControl
                                                    label={ __( 'Overlay Color', 'advanced-blend-mode-block' ) }
                                                    help={ __( 'The blending layer color (dark color works best)', 'advanced-blend-mode-block' ) }
                                                >
                                                    <ColorPicker
                                                        color={ blendSettings.overlayColor }
                                                        onChange={ ( overlayColor ) => updateBlendSettings({ overlayColor }) }
                                                        enableAlpha={ false }
                                                    />
                                                </BaseControl>

                                                <RangeControl
                                                    label={ __( 'Soft Overlay Opacity', 'advanced-blend-mode-block' ) }
                                                    value={ blendSettings.overlayOpacity }
                                                    onChange={ ( overlayOpacity ) => updateBlendSettings({ overlayOpacity }) }
                                                    min={ 0 }
                                                    max={ 1 }
                                                    step={ 0.05 }
                                                />
                                            </>
                                        )}
                                    </>
                                )}
                            </VStack>
                        </PanelBody>
                    </InspectorControls>
                )}
            </>
        );
    };
}, 'withBlendModeControls' );

addFilter(
    'editor.BlockEdit',
    'advanced-blend-mode-block/with-controls',
    withBlendModeControls
);

/**
 * Add blend mode classes to block wrapper in editor
 */
const withBlendModeClasses = createHigherOrderComponent( ( BlockListBlock ) => {
    return ( props ) => {
        const { name, attributes } = props;

        if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
            return <BlockListBlock { ...props } />;
        }

        const blendSettings = attributes.advancedBlendMode || {};

        if ( ! blendSettings.enabled ) {
            return <BlockListBlock { ...props } />;
        }

        const { mode, blendMode, baseColor, overlayColor, overlayOpacity } = {
            ...DEFAULT_BLEND_SETTINGS,
            ...blendSettings
        };

        // Build inline styles for editor preview
        const wrapperProps = {
            ...props.wrapperProps,
            style: {
                ...( props.wrapperProps?.style || {} ),
                '--abmb-blend-mode': blendMode,
                '--abmb-base-color': baseColor,
                '--abmb-overlay-color': overlayColor,
                '--abmb-overlay-opacity': overlayOpacity
            }
        };

        const className = mode === 'simple' 
            ? 'abmb-blend-simple abmb-editor-preview'
            : 'abmb-blend-stripe-preview abmb-editor-preview';

        return (
            <BlockListBlock 
                { ...props } 
                wrapperProps={ wrapperProps }
                className={ className }
            />
        );
    };
}, 'withBlendModeClasses' );

addFilter(
    'editor.BlockListBlock',
    'advanced-blend-mode-block/with-classes',
    withBlendModeClasses
);
