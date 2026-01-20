=== Advanced Blend Mode Block ===
Contributors: 
Tags: gutenberg, blocks, blend-mode, css, stripe-effect
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add mix-blend-mode controls to Gutenberg blocks with Stripe-style triple-layer effect.

== Description ==

This plugin adds a "Blend Mode" panel to the following core blocks:

* Paragraph
* Heading (H1-H6)
* Group
* Row
* Stack

**Features:**

* **Simple CSS Mode**: Apply any CSS mix-blend-mode directly to the block
* **Stripe Effect Mode**: Replicates the Stripe.com hero heading effect with triple-layer text that "burns" into gradient backgrounds while remaining readable on solid colors

**Stripe Effect Explained:**

The Stripe effect uses 3 stacked text layers:
1. Base layer with a light color
2. Color-burn layer that intensifies background colors
3. Soft overlay for legibility on white

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/advanced-blend-mode-block`
2. Run `npm install && npm run build` in the plugin directory
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Edit any page/post in the block editor
5. Select a supported block and find the "Blend Mode" panel in the sidebar

== Changelog ==

= 1.0.0 =
* Initial release
* Simple CSS blend mode support
* Stripe triple-layer effect support
* Customizable colors and opacity
