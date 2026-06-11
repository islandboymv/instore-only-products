<?php
/**
 * Plugin Name: In-Store Only Products
 * Plugin URI:  https://github.com/islandboymv/instore-only-products
 * Description: Adds an "In-store only" checkbox to WooCommerce products. When ticked, the product still
 *              shows its price and stock availability but cannot be purchased online — the Add to Cart
 *              button is replaced with a polite in-store notice. Each product can optionally override
 *              the default heading/message. Self-updates from GitHub Releases.
 * Author:      Islandboy
 * Version:     1.0.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 10.7
 * Text Domain: instore-only
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INSTORE_ONLY_VERSION', '1.0.3' );
define( 'INSTORE_ONLY_TELEMETRY_URL', 'https://mgga-shop.islandboy.xyz/wp-json/telemetry/v1/ping' );

/**
 * Declare WooCommerce feature compatibility. This plugin only touches products
 * (never orders), so it is fully compatible with HPOS and the Cart/Checkout blocks.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * GitHub-powered automatic updates.
 *
 * Once installed, the plugin checks this repository's GitHub Releases and offers
 * one-click updates from the WordPress Plugins screen. To ship an update: bump the
 * Version header, commit, and publish a GitHub Release (e.g. tag v1.1.0).
 */
add_action( 'plugins_loaded', function () {
	require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';

	\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/islandboymv/instore-only-products/',
		__FILE__,
		'instore-only-products'
	);
} );

/* ---------------------------------------------------------------------------
 * Anonymous active-install telemetry.
 *
 * Sends a daily ping containing ONLY the plugin slug, a one-way SHA-256 hash of
 * the site URL (never the URL itself), and the version — so the central server can
 * show an "active installs" count. It cannot identify your site.
 *
 * Opt out entirely with:
 *   add_filter( 'instore_only_telemetry_enabled', '__return_false' );
 * ------------------------------------------------------------------------- */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'instore_only_telemetry_ping' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'instore_only_telemetry_ping' );
	}
} );

add_action( 'instore_only_telemetry_ping', 'instore_only_send_telemetry' );

function instore_only_send_telemetry() {
	if ( ! apply_filters( 'instore_only_telemetry_enabled', true ) ) {
		return;
	}
	$home = home_url();
	foreach ( array( 'localhost', '127.0.0.1', '.test', '.local', '.localhost', '.example' ) as $needle ) {
		if ( false !== strpos( $home, $needle ) ) {
			return; // don't count local/dev sites
		}
	}
	wp_remote_post( INSTORE_ONLY_TELEMETRY_URL, array(
		'timeout'  => 5,
		'blocking' => false,
		'headers'  => array( 'Content-Type' => 'application/json' ),
		'body'     => wp_json_encode( array(
			'slug'    => 'instore-only-products',
			'site'    => hash( 'sha256', $home ),
			'version' => INSTORE_ONLY_VERSION,
		) ),
	) );
}

register_activation_hook( __FILE__, function () {
	wp_schedule_single_event( time() + 30, 'instore_only_telemetry_ping' );
} );
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'instore_only_telemetry_ping' );
} );

const INSTORE_META_KEY     = '_instore_only';
const INSTORE_HEADING_KEY  = '_instore_heading';
const INSTORE_MESSAGE_KEY  = '_instore_message';

/**
 * Site-wide DEFAULT wording. Used whenever a product has no per-item override.
 */
function instore_only_default_heading() {
	return apply_filters( 'instore_only_default_heading', __( 'Available in Our Physical Shop', 'instore-only' ) );
}

function instore_only_default_message() {
	return apply_filters(
		'instore_only_default_message',
		__( 'This item isn’t available for online checkout, but we’d be delighted to welcome you to our shop to view and purchase it in person.', 'instore-only' )
	);
}

/**
 * Resolve the heading/message for a specific product:
 * per-product override if set, otherwise the site default.
 */
function instore_only_heading_for( $id ) {
	$custom = trim( (string) get_post_meta( $id, INSTORE_HEADING_KEY, true ) );
	return '' !== $custom ? $custom : instore_only_default_heading();
}

function instore_only_message_for( $id ) {
	$custom = trim( (string) get_post_meta( $id, INSTORE_MESSAGE_KEY, true ) );
	return '' !== $custom ? $custom : instore_only_default_message();
}

/**
 * Is a given product flagged in-store only?
 */
function instore_only_is_flagged( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	// Variations inherit the flag from their parent.
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return 'yes' === get_post_meta( $id, INSTORE_META_KEY, true );
}

/* ---------------------------------------------------------------------------
 * Admin: checkbox + optional override fields in the product data > General panel
 * ------------------------------------------------------------------------- */
add_action( 'woocommerce_product_options_general_product_data', function () {
	echo '<div class="options_group">';

	woocommerce_wp_checkbox( array(
		'id'          => INSTORE_META_KEY,
		'label'       => __( 'In-store only', 'instore-only' ),
		'description' => __( 'Show price & availability but disable online purchase (Add to Cart hidden).', 'instore-only' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => INSTORE_HEADING_KEY,
		'label'       => __( 'Notice heading', 'instore-only' ),
		'placeholder' => instore_only_default_heading(),
		'desc_tip'    => true,
		'description' => __( 'Optional. Leave blank to use the default heading.', 'instore-only' ),
	) );

	woocommerce_wp_textarea_input( array(
		'id'          => INSTORE_MESSAGE_KEY,
		'label'       => __( 'Notice message', 'instore-only' ),
		'placeholder' => instore_only_default_message(),
		'desc_tip'    => true,
		'description' => __( 'Optional. Leave blank to use the default message for this product.', 'instore-only' ),
		'rows'        => 3,
	) );

	echo '</div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	$product->update_meta_data( INSTORE_META_KEY, isset( $_POST[ INSTORE_META_KEY ] ) ? 'yes' : 'no' );

	$heading = isset( $_POST[ INSTORE_HEADING_KEY ] ) ? sanitize_text_field( wp_unslash( $_POST[ INSTORE_HEADING_KEY ] ) ) : '';
	$message = isset( $_POST[ INSTORE_MESSAGE_KEY ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ INSTORE_MESSAGE_KEY ] ) ) : '';

	$product->update_meta_data( INSTORE_HEADING_KEY, $heading );
	$product->update_meta_data( INSTORE_MESSAGE_KEY, $message );
} );

/* ---------------------------------------------------------------------------
 * Front end: make the product non-purchasable
 * ------------------------------------------------------------------------- */
add_filter( 'woocommerce_is_purchasable', function ( $purchasable, $product ) {
	return instore_only_is_flagged( $product ) ? false : $purchasable;
}, 10, 2 );

/**
 * Replace the Add to Cart button with the in-store notice on the single product page.
 */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( instore_only_is_flagged( $product ) ) {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		add_action( 'woocommerce_single_product_summary', 'instore_only_render_notice', 30 );
	}
}, 1 );

/**
 * Notice styled with Blocksy's theme palette variables so it inherits the
 * site's colours. Falls back to neutral tones if the variables aren't present.
 */
function instore_only_render_notice() {
	global $product;
	$id = $product instanceof WC_Product ? $product->get_id() : 0;

	$accent  = 'var(--theme-palette-color-1, #2872fa)';
	$surface = 'var(--theme-palette-color-7, #f3f5f7)';
	$border  = 'var(--theme-palette-color-5, #e1e6eb)';
	$heading = 'var(--theme-palette-color-4, #1d2228)';
	$text    = 'var(--theme-palette-color-3, #495057)';
	?>
	<div class="instore-only-notice" style="display:flex;gap:14px;align-items:flex-start;margin:1.5em 0;padding:18px 20px;background:<?php echo $surface; ?>;border:1px solid <?php echo $border; ?>;border-left:3px solid <?php echo $accent; ?>;border-radius:8px;">
		<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo $accent; ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px;" aria-hidden="true">
			<path d="M3 9l1-5h16l1 5"/><path d="M4 9v11a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><path d="M3 9h18"/>
		</svg>
		<div>
			<p style="margin:0 0 4px;font-weight:600;font-size:1.02em;color:<?php echo $heading; ?>;line-height:1.4;"><?php echo esc_html( instore_only_heading_for( $id ) ); ?></p>
			<p style="margin:0;color:<?php echo $text; ?>;line-height:1.55;"><?php echo esc_html( instore_only_message_for( $id ) ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * On shop/category loop cards, replace the add-to-cart link with a small label.
 */
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( instore_only_is_flagged( $product ) ) {
		return '<span class="button instore-only-loop" style="background:var(--theme-palette-color-7,#f3f5f7);color:var(--theme-palette-color-3,#495057);border:1px solid var(--theme-palette-color-5,#e1e6eb);cursor:default;pointer-events:none;">'
			. esc_html__( 'In-store only', 'instore-only' )
			. '</span>';
	}
	return $html;
}, 10, 2 );
