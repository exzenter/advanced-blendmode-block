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
        // Extract inner content (allow for nested HTML tags)
        $first_close = strpos( $block_content, '>' );
        $last_open = strrpos( $block_content, '<' );
        
        $inner_content = '';
        if ( $first_close !== false && $last_open !== false && $last_open > $first_close ) {
            $inner_content = substr( $block_content, $first_close + 1, $last_open - $first_close - 1 );
        } else {
             $inner_content = strip_tags( $block_content );
        }
        
        // Extract attributes from original block to copy to siblings
        $existing_classes = '';
        $existing_styles = '';
        
        if ( preg_match( '/^(\s*<\w+)([^>]+)>/', $block_content, $matches ) ) {
             $attrs_string = $matches[2];
             if ( preg_match( '/class=["\']([^"\']+)["\']/', $attrs_string, $c_match ) ) {
                 $existing_classes = $c_match[1];
             }
             if ( preg_match( '/style=["\']([^"\']+)["\']/', $attrs_string, $s_match ) ) {
                 $existing_styles = $s_match[1];
             }
        }
        
        // CSS custom properties
        $css_vars = sprintf(
            '--abmb-blend-mode: %s; --abmb-base-color: %s; --abmb-overlay-color: %s; --abmb-overlay-opacity: %s;',
            esc_attr( $blend_mode ),
            esc_attr( $base_color ),
            esc_attr( $overlay_color ),
            esc_attr( $overlay_opacity )
        );
        
        // Add base class to original block (allow leading whitespace)
        $block_content = preg_replace(
            '/^(\s*<\w+)([^>]*class=["\'])/',
            '$1$2abmb-stripe-base ',
            $block_content,
            1
        );
        
        // If no class attribute existed, add one
        if ( strpos( $block_content, 'abmb-stripe-base' ) === false ) {
             $block_content = preg_replace(
                '/^(\s*<\w+)(\s|>)/',
                '$1 class="abmb-stripe-base"$2',
                $block_content,
                1
            );
        }

        // Construct the sibling layers
        // We ensure they have the same classes and styles as the original for visual matching
        // But we add our specific identifers
        $layers = sprintf(
            '<div class="%1$s abmb-stripe-burn" style="%2$s" aria-hidden="true">%3$s</div>' .
            '<div class="%1$s abmb-stripe-soft" style="%2$s" aria-hidden="true">%3$s</div>',
            esc_attr( $existing_classes ),
            esc_attr( $existing_styles ),
            $inner_content
        );
        
        // Wrap everything in a container
        $wrapped_content = sprintf(
            '<div class="abmb-stripe-wrapper" style="%s">%s%s</div>',
            $css_vars,
            $block_content,
            $layers
        );
        
        return $wrapped_content;
    }
    
    return $block_content;
}
add_filter( 'render_block', 'abmb_render_block_with_blend', 10, 2 );

/**
 * Helper functions removed as logic is now inlined
 */
