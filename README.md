# In-Store Only Products

[![Active installs](https://img.shields.io/endpoint?url=https%3A%2F%2Fplugin-telemetry.islandboy.workers.dev%2Fbadge%2Finstore-only-products)](https://github.com/islandboymv/instore-only-products)
[![Latest release](https://img.shields.io/github/v/release/islandboymv/instore-only-products)](https://github.com/islandboymv/instore-only-products/releases)

A small WooCommerce plugin that marks products as **available in your physical shop only**. They still appear online with price and stock status, but can't be bought online — the Add to Cart button is replaced with a polite in-store notice.

## Features

- **Per-product toggle** — an "In-store only" checkbox under Product data → General
- **Optional per-product overrides** — set a custom notice heading/message for any product
- **Site-wide default wording** — used when a product has no override
- **Theme-matched styling** — the notice uses the active theme's colour palette (great with Blocksy)
- **Shop grid badge** — the product card shows an "In-store only" label instead of Add to Cart
- **Variations** inherit the flag from their parent product
- **Truly non-purchasable** server-side — it can't be forced into the cart via a direct link
- **Self-updating** — installs updates straight from GitHub Releases via the WordPress Plugins screen

## Installation

1. Copy the `instore-only-products` folder into `wp-content/plugins/` (or upload the zip).
2. Activate **In-Store Only Products**.

## Usage

1. Edit a product → **Product data → General**.
2. Tick **In-store only**. Optionally fill the **Notice heading** / **Notice message** to override the default for that product.
3. Set **Inventory → Stock status = In stock** so availability still shows.
4. **Update**.

Change the site-wide default wording with the `instore_only_default_heading` and `instore_only_default_message` filters.

## License

GPL-2.0-or-later.
