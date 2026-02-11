<?php
/**
 * Performance functions
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Performance;

use Chocante;

// Common.
add_action( 'wp_head', __NAMESPACE__ . '\preload_assets', 1 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\disable_block_styles', 1000 );

if ( ! is_admin() ) {
	add_action( 'wp_default_scripts', __NAMESPACE__ . '\disable_jquery_migrate' );
}

// WooCommerce.
if ( class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_enqueue_styles', '__return_false' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_woo_scripts', 1000 );
}

// WPML.
if ( class_exists( 'SitePress' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_wpml_scripts', 1000 );
}
// Curcy.
if ( class_exists( 'WOOMULTI_CURRENCY' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_wmc_assets', 9999999 );
	// Bufix with premium?
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts', 99 );
}

if ( 'production' === wp_get_environment_type() ) {
	add_action( 'wp_head', __NAMESPACE__ . '\preconnect_to_sources', 1 );
}

// Rate My Post.
if ( class_exists( 'Rate_My_Post' ) ) {
	add_action( 'wp_head', __NAMESPACE__ . '\disable_rmp_font_preload', 0 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_rmp_assets', 1000 );
}

// Back In Stock Notifier.
if ( class_exists( 'CWG_Instock_Notifier' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_back_in_stock_assets', 1000 );
}

// P24.
if ( class_exists( 'WC_P24\Plugin' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_p24_assets', 1000 );
}

// PayU.
if ( class_exists( 'Payu\PaymentGateway\WC_Payu' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_payu_assets', 1000 );
}

// Flexible Shipping.
if ( class_exists( 'Flexible_Shipping_Plugin' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_fxs_assets', 1000 );
}

// Inpost for WooCommerce.
if ( class_exists( 'WPDesk_Paczkomaty_Plugin' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_inpost_assets', 1000 );
}

/**
 * Disable loading Rate My Post font outside post
 */
function disable_rmp_font_preload() {
	if ( ! is_singular( 'post' ) ) {
		add_filter( 'rmp_font_preload', '__return_false' );
	}
}

/**
 * Skip loading Rate My Post scripts outside post
 */
function manage_rmp_assets() {
	if ( ! is_singular( 'post' ) ) {
		wp_deregister_script( 'rate-my-post' );
		wp_dequeue_script( 'rate-my-post' );
		wp_deregister_style( 'rate-my-post' );
		wp_dequeue_style( 'rate-my-post' );
	}
}

/**
 * Load Back in stock notifier scripts only when product is out of stock
 */
function manage_back_in_stock_assets() {
	wp_dequeue_style( 'cwginstock_frontend_css' );
	wp_dequeue_style( 'cwginstock_bootstrap' );
	wp_dequeue_script( 'sweetalert2' );
	wp_dequeue_script( 'cwginstock_popup' );
	wp_dequeue_script( 'cwginstock_js' );

	if ( is_product() && ! wc_get_product( get_the_ID() )->is_in_stock() ) {
		wp_enqueue_script( 'cwginstock_js' );
	} else {
		wp_deregister_script( 'wc-jquery-blockui' );
		wp_dequeue_script( 'wc-jquery-blockui' );
	}
}

/**
 * Preconnect to external sources
 */
function preconnect_to_sources() {
	$preconnect = array( 'https://gtm.chocante.pl', 'https://assets.mailerlite.com', 'https://www.gstatic.com' );
	$prefetch   = array( 'https://static.ads-twitter.com', 'https://connect.facebook.net', 'https://cdn-cookieyes.com' );

	foreach ( $preconnect as $link ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"preconnect\" href=\"{$link}\" />";
	}

	foreach ( $prefetch as $link ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"dns-prefetch\" href=\"{$link}\" />";
	}
}


/**
 * Przelewy 24 - disable obsolote assets
 */
function manage_p24_assets() {
	wp_dequeue_script( 'p24-block-checkout' );

	if ( ! is_checkout() ) {
		wp_dequeue_script( 'p24-styles' );
		wp_dequeue_script( 'p24-online-payments' );

	}
}

/**
 * PayU - disable obsolote assets
 */
function manage_payu_assets() {
	wp_dequeue_style( 'payu-gateway' );

	if ( ! is_checkout() ) {
		wp_dequeue_script( 'payu-gateway' );
	}
}

/**
 * PayU - disable obsolote assets
 */
function manage_fxs_assets() {
	wp_dequeue_script( 'flexible_shipping_notices' );
	wp_dequeue_style( 'flexible_shipping_notices' );
	wp_dequeue_style( 'flexible-shipping-free-shipping' );
}

/**
 * PayU - disable obsolote assets
 */
function manage_inpost_assets() {
	wp_dequeue_style( 'woocommerce-paczkomaty-inpost-blocks-integration-frontend' );
	wp_dequeue_style( 'woocommerce-paczkomaty-inpost-blocks-integration-editor' );
}

/**
 * Preload critical assets
 */
function preload_assets() {
	$links = array();

	// Theme styles.
	$styles  = Chocante::asset( 'chocante' );
	$links[] = array(
		'path' => get_theme_file_uri( 'build/chocante.css' ) . '?ver=' . $styles['version'],
		'as'   => 'style',
	);

	if ( is_singular( 'post' ) ) {
		$post_styles = Chocante::asset( 'single-post' );
		$links[]     = array(
			'path' => get_theme_file_uri( 'build/single-post.css' ) . '?ver=' . $post_styles['version'],
			'as'   => 'style',
		);
	}

	if ( is_home() ) {
		$blog_styles = Chocante::asset( 'blog' );
		$links[]     = array(
			'path' => get_theme_file_uri( 'build/blog.css' ) . '?ver=' . $blog_styles['version'],
			'as'   => 'style',
		);
	}

	// Fonts.
	$fonts = array( 'fonts/montserrat-medium.woff2', 'fonts/montserrat-semibold.woff2', 'fonts/montserrat-bold.woff2', 'fonts/playfair_display-medium.woff2', 'build/fonts/glyphter.woff' );
	foreach ( $fonts as $font ) {
		$name    = explode( '.', $font );
		$ext     = end( $name );
		$links[] = array(
			'path'  => get_theme_file_uri( $font ),
			'as'    => 'font',
			'extra' => array(
				"type=\"font/{$ext}\"",
				'crossorigin',
			),
		);
	}

	// jQuery preload hack.
	global $wp_scripts;
	if ( isset( $wp_scripts->registered['jquery'] ) ) {
		$suffix  = wp_scripts_get_suffix();
		$version = $wp_scripts->registered['jquery']->ver;
		$links[] = array(
			'path' => "/wp-includes/js/jquery/jquery{$suffix}.js?ver={$version}",
			'as'   => 'script',
		);
	}

	foreach ( $links as $link ) {
		$extra = isset( $link['extra'] ) ? implode( ' ', $link['extra'] ) : '';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"preload\" href=\"{$link['path']}\" as=\"{$link['as']}\" {$extra} />";
	}
}

/**
 * Disable jQuery migrate on frontend
 *
 * @param WP_Scripts $scripts WP Scripts object.
 */
function disable_jquery_migrate( $scripts ) {
	if ( isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
}


/**
 * Disable WP blocks styles on pages without blocks.
 */
function disable_block_styles() {
	global $post;

	$has_blocks = false;
	if ( isset( $post->post_content ) ) {
		$has_blocks = has_blocks( $post->post_content );
	}

	if ( ! $has_blocks ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );

		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			wp_dequeue_style( 'wc-blocks-style' );
			wp_deregister_style( 'wc-blocks-style' );
		}
	}
}

/**
 * Move WPML language switcher scripts to footer and disable styles (moved to main theme)
 */
function manage_wpml_scripts() {
	global $wp_scripts;
	global $wp_styles;

	$wpml_dropdown  = 'wpml-legacy-dropdown';
	$wpml_menu_item = 'wpml-menu-item';

	foreach ( $wp_styles->queue as $handle ) {
		if ( str_contains( $handle, $wpml_dropdown ) || str_contains( $handle, $wpml_menu_item ) ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	foreach ( $wp_scripts->queue as $handle ) {
		$script = $wp_scripts->registered[ $handle ];

		if ( str_contains( $handle, $wpml_dropdown ) ) {
			wp_dequeue_script( $handle );
			wp_enqueue_script(
				$handle,
				$script->src,
				$script->deps,
				$script->ver,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}
}

/**
 * Fix Curcy admin scripts
 *
 * @todo check on update
 *
 * @param string $hook The current admin page.
 */
function admin_enqueue_scripts( $hook ) {
	if ( 'toplevel_page_woocommerce-multi-currency' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'select2' );
}

/**
 * Load Woo non-critical scripts in footer
 */
function manage_woo_scripts() {
	global $wp_scripts;

	$footer_scripts = array(
		'js-cookie',
		'woocommerce',
		'wc-single-product',
	);

	if ( is_product() ) {
		$footer_scripts[] = 'flexslider';
		$footer_scripts[] = 'photoswipe';
		$footer_scripts[] = 'photoswipe-ui-default';
	}

	foreach ( $footer_scripts as $handle ) {
		if ( isset( $wp_scripts->registered[ $handle ] ) ) {
			$script = $wp_scripts->registered[ $handle ];

			wp_dequeue_script( $handle );
			wp_enqueue_script(
				$handle,
				$script->src,
				$script->deps,
				$script->ver,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	global $wp_styles;

	$async_styles = array(
		'photoswipe',
		'photoswipe-default-skin',
	);

	foreach ( $async_styles as $handle ) {
		if ( isset( $wp_styles->registered[ $handle ] ) ) {
			$style = $wp_styles->registered[ $handle ];

			wp_deregister_style( $handle );

			if ( is_product() ) {
				wp_enqueue_style(
					$handle,
					$style->src,
					$style->deps,
					$style->ver,
					'print'
				);

				add_filter(
					'style_loader_tag',
					function ( $html, $h ) use ( $handle ) {
						if ( $h === $handle ) {
							$html = str_replace( "media='print'", "media='print' onload=\"this.media='all'\"", $html );
						}
						return $html;
					},
					10,
					2
				);
			}
		}
	}

	wp_dequeue_style( 'brands-styles' );
}

/**
 * Move Curcy scripts to footer and disable styles (moved to main theme)
 *
 * `woo` is a prefix for a free version
 * `woocommerce` for premium
 */
function manage_wmc_assets() {
	global $wp_scripts;
	$footer_scripts = array(
		'woo-multi-currency',
		'woo-multi-currency-cart',
		'woocommerce-multi-currency',
		'woocommerce-multi-currency-cart',
	);

	foreach ( $footer_scripts as $handle ) {
		if ( isset( $wp_scripts->registered[ $handle ] ) ) {
			$script = $wp_scripts->registered[ $handle ];

			wp_dequeue_script( $handle );
			wp_enqueue_script(
				$handle,
				$script->src,
				$script->deps,
				$script->ver,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	wp_deregister_script( 'woocommerce-multi-currency-switcher' );
	wp_dequeue_script( 'woocommerce-multi-currency-switcher' );
	wp_deregister_script( 'woocommerce-multi-currency-convertor' );
	wp_dequeue_script( 'woocommerce-multi-currency-convertor' );

	wp_dequeue_style( 'woo-multi-currency' );
	wp_dequeue_style( 'woocommerce-multi-currency' );
	wp_dequeue_style( 'wmc-flags' );
}
