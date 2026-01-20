# Advanced Blend Mode Block

A WordPress plugin that injects mix-blend-mode controls to the basic group, row, stack, P, and H blocks of the Gutenberg editor. It features a special "Stripe Mode" that replicates the triple-layer text effect found on Stripe.com.

## Features

- **Simple Mode**: Apply standard CSS `mix-blend-mode` values to blocks.
- **Stripe Mode**: A sophisticated triple-layer effect for text:
  - **Base Layer**: Standard text color.
  - **Color Burn Layer**: "Burns" into the background (great for gradients).
  - **Soft Overlay**: Ensures legibility on solid/white backgrounds.
- **Supported Blocks**: Paragraph, Heading, Group, Row, Stack.
- **Customizable**: Full control over colors, opacity, and blend modes via the editor sidebar.

## Installation

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/exzenter/advanced-blendmode-block.git
   ```
2. Navigate to the plugin directory and install dependencies:
   ```bash
   cd advanced-blendmode-block
   npm install
   ```
3. Build the assets:
   ```bash
   npm run build
   ```
4. Activate the plugin in WordPress Admin.

## Usage

1. Select a supported block (e.g., Heading).
2. Open the **Blend Mode** panel in the block settings sidebar.
3. Toggle **Enable Blend Mode**.
4. Choose between **Simple CSS** or **Stripe Effect**.
5. Adjust settings to achieve your desired look.

## Development

- `npm start`: Watches for changes and rebuilds in development mode.
- `npm run build`: Builds production assets.
