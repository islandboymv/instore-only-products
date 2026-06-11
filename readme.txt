=== In-Store Only Products ===
Contributors: islandboy
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.7
Stable tag: 1.0.1
License: GPLv2 or later

Mark WooCommerce products as available in your physical shop only — shown online
with price and availability, but not purchasable.

== Description ==

Adds an "In-store only" checkbox to each product. When ticked the product still
appears in your store with its price and stock status, but the Add to Cart button
is replaced with a polite notice directing shoppers to your physical shop.

Features:
* Per-product "In-store only" toggle (Product data → General)
* Optional per-product override of the notice heading and message
* A site-wide default heading/message used when no override is set
* Notice styled with the active theme's palette (works great with Blocksy)
* Shop/category grid shows an "In-store only" badge instead of Add to Cart
* Variations inherit the flag from their parent product
* Truly non-purchasable server-side (can't be forced into the cart)
* Self-updates from GitHub Releases

== Configuration ==

1. Edit a product → Product data → General.
2. Tick "In-store only". Optionally set a custom heading/message for that product.
3. Make sure Inventory → Stock status is "In stock" so availability still shows.
4. Update.

== Changelog ==

= 1.0.1 =
* Mark as tested up to WordPress 7.0.

= 1.0.0 =
* Initial release as a standalone, self-updating plugin (converted from an mu-plugin).
