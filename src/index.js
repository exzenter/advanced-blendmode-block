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
    ColorPalette,
    ColorIndicator,
    Dropdown,
    Button,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack,
    BaseControl,
    TextControl
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

// Default settings (matching user's tested values)
const DEFAULT_BLEND_SETTINGS = {
    enabled: false,
    mode: 'simple',
    blendMode: 'color-burn',
    baseBlendMode: 'color-burn',  // Blend mode for base element (default: color-burn)
    burnBlendMode: 'normal',      // Blend mode for burn layer (default: normal/unset)
    burnColor: '#bdc6d2',         // Burn layer text color
    softColor: '#0000003b',       // Soft layer color with alpha (black 23% opacity)
    softOpacity: 1,               // Soft layer opacity
    softZIndex: -2                // Soft layer z-index (behind base)
};

/**
 * Add blend mode attributes to supported blocks
 */
function addBlendModeAttributes(settings, name) {
    if (!SUPPORTED_BLOCKS.includes(name)) {
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
const withBlendModeControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { name, attributes, setAttributes, isSelected } = props;

        if (!SUPPORTED_BLOCKS.includes(name)) {
            return <BlockEdit {...props} />;
        }

        const blendSettings = {
            ...DEFAULT_BLEND_SETTINGS,
            ...(attributes.advancedBlendMode || {})
        };

        const updateBlendSettings = (newSettings) => {
            setAttributes({
                advancedBlendMode: {
                    ...blendSettings,
                    ...newSettings
                }
            });
        };

        return (
            <>
                <BlockEdit {...props} />
                {isSelected && (
                    <InspectorControls>
                        <PanelBody
                            title={__('Blend Mode', 'advanced-blend-mode-block')}
                            initialOpen={blendSettings.enabled}
                        >
                            <VStack spacing={4}>
                                <ToggleControl
                                    label={__('Enable Blend Mode', 'advanced-blend-mode-block')}
                                    checked={blendSettings.enabled}
                                    onChange={(enabled) => updateBlendSettings({ enabled })}
                                />

                                {blendSettings.enabled && (
                                    <>
                                        <RadioControl
                                            label={__('Effect Type', 'advanced-blend-mode-block')}
                                            selected={blendSettings.mode}
                                            options={[
                                                { label: __('Simple CSS', 'advanced-blend-mode-block'), value: 'simple' },
                                                { label: __('Stripe Effect', 'advanced-blend-mode-block'), value: 'stripe' }
                                            ]}
                                            onChange={(mode) => updateBlendSettings({ mode })}
                                        />

                                        {blendSettings.mode === 'simple' && (
                                            <SelectControl
                                                label={__('Blend Mode', 'advanced-blend-mode-block')}
                                                value={blendSettings.blendMode}
                                                options={BLEND_MODES}
                                                onChange={(blendMode) => updateBlendSettings({ blendMode })}
                                            />
                                        )}

                                        {blendSettings.mode === 'stripe' && (
                                            <>
                                                <BaseControl
                                                    label={__('Burn Color', 'advanced-blend-mode-block')}
                                                >
                                                    <Dropdown
                                                        renderToggle={({ isOpen, onToggle }) => (
                                                            <Button
                                                                onClick={onToggle}
                                                                aria-expanded={isOpen}
                                                                style={{ padding: '4px 8px', height: 'auto' }}
                                                            >
                                                                <HStack spacing={2}>
                                                                    <ColorIndicator colorValue={blendSettings.burnColor} />
                                                                    <span>{blendSettings.burnColor || 'Select'}</span>
                                                                </HStack>
                                                            </Button>
                                                        )}
                                                        renderContent={() => (
                                                            <div style={{ padding: '16px', minWidth: '260px' }}>
                                                                <ColorPalette
                                                                    value={blendSettings.burnColor}
                                                                    onChange={(burnColor) => updateBlendSettings({ burnColor })}
                                                                    enableAlpha={true}
                                                                />
                                                            </div>
                                                        )}
                                                    />
                                                </BaseControl>

                                                <BaseControl
                                                    label={__('Soft Color', 'advanced-blend-mode-block')}
                                                >
                                                    <Dropdown
                                                        renderToggle={({ isOpen, onToggle }) => (
                                                            <Button
                                                                onClick={onToggle}
                                                                aria-expanded={isOpen}
                                                                style={{ padding: '4px 8px', height: 'auto' }}
                                                            >
                                                                <HStack spacing={2}>
                                                                    <ColorIndicator colorValue={blendSettings.softColor} />
                                                                    <span>{blendSettings.softColor || 'Select'}</span>
                                                                </HStack>
                                                            </Button>
                                                        )}
                                                        renderContent={() => (
                                                            <div style={{ padding: '16px', minWidth: '260px' }}>
                                                                <ColorPalette
                                                                    value={blendSettings.softColor}
                                                                    onChange={(softColor) => updateBlendSettings({ softColor })}
                                                                    enableAlpha={true}
                                                                />
                                                            </div>
                                                        )}
                                                    />
                                                </BaseControl>

                                                <SelectControl
                                                    label={__('Base Layer Blend Mode', 'advanced-blend-mode-block')}
                                                    value={blendSettings.baseBlendMode || 'color-burn'}
                                                    options={BLEND_MODES}
                                                    onChange={(baseBlendMode) => updateBlendSettings({ baseBlendMode })}
                                                />

                                                <SelectControl
                                                    label={__('Burn Layer Blend Mode', 'advanced-blend-mode-block')}
                                                    value={blendSettings.burnBlendMode || 'normal'}
                                                    options={BLEND_MODES}
                                                    onChange={(burnBlendMode) => updateBlendSettings({ burnBlendMode })}
                                                />

                                                <RangeControl
                                                    label={__('Soft Layer Opacity', 'advanced-blend-mode-block')}
                                                    value={blendSettings.softOpacity ?? 1}
                                                    onChange={(softOpacity) => updateBlendSettings({ softOpacity })}
                                                    min={0}
                                                    max={1}
                                                    step={0.05}
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
}, 'withBlendModeControls');

addFilter(
    'editor.BlockEdit',
    'advanced-blend-mode-block/with-controls',
    withBlendModeControls
);

/**
 * Add blend mode classes to block wrapper in editor
 */
const withBlendModeClasses = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { name, attributes } = props;

        if (!SUPPORTED_BLOCKS.includes(name)) {
            return <BlockListBlock {...props} />;
        }

        const blendSettings = attributes.advancedBlendMode || {};

        if (!blendSettings.enabled) {
            return <BlockListBlock {...props} />;
        }

        const { mode, blendMode, baseColor, overlayColor, overlayOpacity } = {
            ...DEFAULT_BLEND_SETTINGS,
            ...blendSettings
        };

        // Build inline styles for editor preview
        const wrapperProps = {
            ...props.wrapperProps,
            style: {
                ...(props.wrapperProps?.style || {}),
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
                {...props}
                wrapperProps={wrapperProps}
                className={className}
            />
        );
    };
}, 'withBlendModeClasses');

addFilter(
    'editor.BlockListBlock',
    'advanced-blend-mode-block/with-classes',
    withBlendModeClasses
);
