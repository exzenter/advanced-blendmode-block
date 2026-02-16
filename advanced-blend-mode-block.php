<?php
/**
 * Plugin Name:       Advanced Blend Mode Block
 * Description:       Add mix-blend-mode controls to core Gutenberg blocks with Stripe-style triple-layer effect
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Plugin URI:        https://exzent.de/plugins/
 * Author:            EXZENT
 * Author URI:        https://exzent.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advanced-blend-mode-block
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ABMB_VERSION', '1.0.0' );
define( 'ABMB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABMB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Supported block types for blend mode extension
 */
function abmb_get_supported_blocks() {
    return array(
        'core/paragraph',
        'core/heading',
        'core/group',
        'core/row',
        'core/stack',
    );
}

/**
 * Enqueue editor scripts and styles
 */
function abmb_enqueue_editor_assets() {
    $asset_file = ABMB_PLUGIN_DIR . 'build/index.asset.php';
    
    if ( ! file_exists( $asset_file ) ) {
        return;
    }
    
    $asset = include $asset_file;
    
    wp_enqueue_script(
        'abmb-editor-script',
        ABMB_PLUGIN_URL . 'build/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    
    wp_enqueue_style(
        'abmb-editor-style',
        ABMB_PLUGIN_URL . 'build/index.css',
        array(),
        $asset['version']
    );
    
    // Pass supported blocks to JS
    wp_localize_script( 'abmb-editor-script', 'abmbSettings', array(
        'supportedBlocks' => abmb_get_supported_blocks(),
    ));
}
add_action( 'enqueue_block_editor_assets', 'abmb_enqueue_editor_assets' );

/**
 * Enqueue frontend styles and scripts
 */
function abmb_enqueue_frontend_assets() {
    $asset_file = ABMB_PLUGIN_DIR . 'build/index.asset.php';
    
    if ( ! file_exists( $asset_file ) ) {
        return;
    }
    
    $asset = include $asset_file;
    
    wp_enqueue_style(
        'abmb-frontend-style',
        ABMB_PLUGIN_URL . 'build/style-index.css',
        array(),
        $asset['version']
    );
    
    // Enqueue frontend JS for Stripe effect positioning
    $frontend_asset_file = ABMB_PLUGIN_DIR . 'build/frontend.asset.php';
    if ( file_exists( $frontend_asset_file ) ) {
        $frontend_asset = include $frontend_asset_file;
        wp_enqueue_script(
            'abmb-frontend-script',
            ABMB_PLUGIN_URL . 'build/frontend.js',
            array(),
            $frontend_asset['version'],
            true // Load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'abmb_enqueue_frontend_assets' );
add_action( 'enqueue_block_assets', 'abmb_enqueue_frontend_assets' );

/**
 * Render block with blend mode effect on frontend
 */
function abmb_render_block_with_blend( $block_content, $block ) {
    // Check if this block type is supported
    if ( ! in_array( $block['blockName'], abmb_get_supported_blocks(), true ) ) {
        return $block_content;
    }
    
    // Check if blend mode is enabled
    $attrs = $block['attrs'] ?? array();
    $blend_settings = $attrs['advancedBlendMode'] ?? array();
    
    if ( empty( $blend_settings['enabled'] ) ) {
        return $block_content;
    }
    
    $mode = $blend_settings['mode'] ?? 'simple';
    $blend_mode = $blend_settings['blendMode'] ?? 'color-burn';
    $burn_color = $blend_settings['burnColor'] ?? '#bdc6d2';
    $soft_color = $blend_settings['softColor'] ?? '#0000003b';
    
    // Simple mode - just add CSS custom property
    if ( $mode === 'simple' ) {
        $style = sprintf( '--abmb-blend-mode: %s;', esc_attr( $blend_mode ) );
        
        // Insert class into the block (allow leading whitespace)
        $block_content = preg_replace(
            '/^(\s*<\w+)([^>]*class=["\'])/',
            '$1$2abmb-blend-simple ',
            $block_content,
            1
        );
        
        // Check if style attribute exists in the opening tag
        if ( preg_match( '/^(\s*<\w+[^>]*?)style=["\']/', $block_content ) ) {
            $block_content = preg_replace(
                '/^(\s*<\w+[^>]*?style=["\'])/',
                '$1' . $style,
                $block_content,
                1
            );
        } else {
            // Add style attribute if missing
            $block_content = preg_replace(
                '/^(\s*<\w+)(\s|>)/',
                '$1 style="' . $style . '"$2',
                $block_content,
                1
            );
        }
        
        return $block_content;
    }
    
    // Stripe mode - triple layer effect with SIBLING structure
    // Structure: <h2 class="base">Text</h2><div class="burn" aria-hidden>Text</div><div class="soft" aria-hidden>Text</div>
    if ( $mode === 'stripe' ) {
        // Extract inner text content for the sibling divs
        $inner_content = abmb_get_inner_text_content( $block_content );
        
        // Get additional settings with new defaults
        $base_blend_mode = $blend_settings['baseBlendMode'] ?? 'color-burn';
        $burn_blend_mode = $blend_settings['burnBlendMode'] ?? 'normal';
        $soft_opacity = $blend_settings['softOpacity'] ?? 1;
        $soft_z_index = $blend_settings['softZIndex'] ?? -2;
        
        // CSS custom properties for the elements
        $css_vars = sprintf(
            '--abmb-burn-color: %s; --abmb-soft-color: %s; --abmb-base-blend-mode: %s; --abmb-burn-blend-mode: %s; --abmb-soft-opacity: %s; --abmb-soft-z-index: %s;',
            esc_attr( $burn_color ),
            esc_attr( $soft_color ),
            esc_attr( $base_blend_mode ),
            esc_attr( $burn_blend_mode ),
            esc_attr( $soft_opacity ),
            esc_attr( $soft_z_index )
        );
        
        // Add class to original element (allow leading whitespace)
        $block_content = preg_replace(
            '/^(\s*<\w+)([^>]*class=["\'])/',
            '$1$2abmb-stripe-base ',
            $block_content,
            1
        );
        
        // Add style attribute with CSS vars to original element
        if ( preg_match( '/^(\s*<\w+[^>]*?)style=["\']/', $block_content ) ) {
            $block_content = preg_replace(
                '/^(\s*<\w+[^>]*?style=["\'])/',
                '$1' . $css_vars,
                $block_content,
                1
            );
        } else {
            $block_content = preg_replace(
                '/^(\s*<\w+)(\s|>)/',
                '$1 style="' . $css_vars . '"$2',
                $block_content,
                1
            );
        }
        
        // Create the 2 sibling divs (NOT nested, appended AFTER the original element)
        // Convert newlines back to <br> for proper line breaks in the layers
        $layer_content = nl2br( esc_html( $inner_content ) );
        $sibling_divs = sprintf(
            '<div class="abmb-stripe-burn" aria-hidden="true" style="%s">%s</div>' .
            '<div class="abmb-stripe-soft" aria-hidden="true" style="%s">%s</div>',
            $css_vars,
            $layer_content,
            $css_vars,
            $layer_content
        );
        
        // Append sibling divs AFTER the original block content (as siblings)
        $block_content = $block_content . $sibling_divs;
        
        return $block_content;
    }
    
    return $block_content;
}
add_filter( 'render_block', 'abmb_render_block_with_blend', 10, 2 );

/**
 * Extract text content from block HTML (preserves line breaks as \n)
 */
function abmb_get_inner_text_content( $html ) {
    // Remove the outer tag first
    $inner = preg_replace( '/^<[^>]+>/', '', $html );
    $inner = preg_replace( '/<\/\w+>\s*$/', '', $inner );
    
    // Convert <br> tags to newlines, then strip remaining tags
    $inner = preg_replace( '/<br\s*\/?>/i', "\n", $inner );
    $inner = strip_tags( $inner );
    
    return trim( $inner );
}

/**
 * Replace inner text content in block HTML
 */
function abmb_replace_inner_content( $html, $new_content ) {
    return preg_replace( '/(>)[^<]*(<\/)/', '$1' . $new_content . '$2', $html, 1 );
}
