<?php
/**
 * Plugin Name:       Advanced Blend Mode Block
 * Description:       Add mix-blend-mode controls to core Gutenberg blocks with Stripe-style triple-layer effect
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            
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
 * Enqueue frontend styles
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
    $base_color = $blend_settings['baseColor'] ?? '#bdc6d2';
    $overlay_color = $blend_settings['overlayColor'] ?? '#3a3a3a';
    $overlay_opacity = $blend_settings['overlayOpacity'] ?? 0.3;
    
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
    
    // Stripe mode - triple layer effect
    if ( $mode === 'stripe' ) {
        // Extract inner content for duplication
        $inner_content = abmb_get_inner_text_content( $block_content );
        
        // CSS custom properties
        $css_vars = sprintf(
            '--abmb-blend-mode: %s; --abmb-base-color: %s; --abmb-overlay-color: %s; --abmb-overlay-opacity: %s;',
            esc_attr( $blend_mode ),
            esc_attr( $base_color ),
            esc_attr( $overlay_color ),
            esc_attr( $overlay_opacity )
        );
        
        // Create the layered structure
        $layers = sprintf(
            '<span class="abmb-stripe-base">%1$s</span>' .
            '<span class="abmb-stripe-burn" aria-hidden="true">%1$s</span>' .
            '<span class="abmb-stripe-soft" aria-hidden="true">%1$s</span>',
            $inner_content
        );
        
        // Replace inner content with layers
        $block_content = abmb_replace_inner_content( $block_content, $layers );
        
        // Add wrapper class (allow leading whitespace)
        $block_content = preg_replace(
            '/^(\s*<\w+)([^>]*class=["\'])/',
            '$1$2abmb-blend-stripe ',
            $block_content,
            1
        );
        
        // Check if style attribute exists in the opening tag
        if ( preg_match( '/^(\s*<\w+[^>]*?)style=["\']/', $block_content ) ) {
            $block_content = preg_replace(
                '/^(\s*<\w+[^>]*?style=["\'])/',
                '$1' . $css_vars,
                $block_content,
                1
            );
        } else {
            // Add style attribute if missing
            $block_content = preg_replace(
                '/^(\s*<\w+)(\s|>)/',
                '$1 style="' . $css_vars . '"$2',
                $block_content,
                1
            );
        }
        
        return $block_content;
    }
    
    return $block_content;
}
add_filter( 'render_block', 'abmb_render_block_with_blend', 10, 2 );

/**
 * Extract text content from block HTML
 */
function abmb_get_inner_text_content( $html ) {
    // Match content between opening and closing tags
    if ( preg_match( '/>([^<]*)<\//', $html, $matches ) ) {
        return $matches[1];
    }
    return '';
}

/**
 * Replace inner text content in block HTML
 */
function abmb_replace_inner_content( $html, $new_content ) {
    return preg_replace( '/(>)[^<]*(<\/)/', '$1' . $new_content . '$2', $html, 1 );
}
