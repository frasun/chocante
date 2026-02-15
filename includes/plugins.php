<?php
/**
 * Plugin assets
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Plugins;

defined( 'ABSPATH' ) || exit;

add_filter( 'chocante_assets_defer_scripts', __NAMESPACE__ . '\defer_scripts' );
add_filter( 'chocante_assets_remove_scripts', __NAMESPACE__ . '\remove_scripts' );
add_filter( 'chocante_assets_async_styles', __NAMESPACE__ . '\async_styles' );
add_filter( 'chocante_assets_remove_styles', __NAMESPACE__ . '\remove_styles' );
add_filter( 'chocante_assets_preconnect', __NAMESPACE__ . '\preconnect' );
add_filter( 'chocante_assets_prefetch', __NAMESPACE__ . '\prefetch' );

// Rate My Post.
add_action( 'wp_head', __NAMESPACE__ . '\disable_rmp_font_preload', 0 );
// WPML.
if ( class_exists( 'SitePress' ) ) {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_wpml_scripts', 1000 );
}
// Curcy - Bug with select (premium version).
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts', 99 );

/**
 * Defer plugin scripts
 *
 * @param array $scripts Script handles.
 * @return array
 */
function defer_scripts( $scripts ) {
	// Curcy.
	if ( class_exists( 'WOOMULTI_CURRENCY' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
		$scripts[] = 'woo-multi-currency';
		$scripts[] = 'woo-multi-currency-cart';
		$scripts[] = 'woocommerce-multi-currency';
		$scripts[] = 'woocommerce-multi-currency-cart';
	}

	// WooCommerce.
	if ( class_exists( 'WooCommerce' ) ) {
		$scripts[] = 'js-cookie';
		$scripts[] = 'woocommerce';
		$scripts[] = 'wc-single-product';

		if ( is_product() ) {
			$scripts[] = 'flexslider';
			$scripts[] = 'photoswipe';
			$scripts[] = 'photoswipe-ui-default';
		}
	}

	return $scripts;
}

/**
 * Remove plugin scripts
 *
 * @param array $scripts Script handles.
 * @return array
 */
function remove_scripts( $scripts ) {
	// Rate My Post.
	if ( class_exists( 'Rate_My_Post' ) && ( ! is_singular( 'post' ) ) ) {
		$scripts[] = 'rate-my-post';
	}

	// Back In Stock Notifier.
	if ( class_exists( 'CWG_Instock_Notifier' ) ) {
		$scripts[] = 'sweetalert2';
		$scripts[] = 'cwginstock_popup';

		if ( class_exists( 'WooCommerce' ) && ( ! is_product() || wc_get_product( get_the_ID() )->is_in_stock() ) ) {
			$scripts[] = 'cwginstock_js';
			$scripts[] = 'wc-jquery-blockui';
		}
	}

	// P24.
	if ( class_exists( 'WC_P24\Plugin' ) ) {
		$scripts[] = 'p24-block-checkout';

		if ( class_exists( 'WooCommerce' ) && ! is_checkout() ) {
			$scripts[] = 'p24-styles';
			$scripts[] = 'p24-online-payments';
		}
	}

	// PayU.
	if ( class_exists( 'Payu\PaymentGateway\WC_Payu' ) ) {
		if ( class_exists( 'WooCommerce' ) && ! is_checkout() ) {
			$scripts[] = 'payu-gateway';
		}
	}

	// Flexible Shipping.
	if ( class_exists( 'Flexible_Shipping_Plugin' ) ) {
		$scripts[] = 'flexible_shipping_notices';
	}

	// Curcy.
	if ( class_exists( 'WOOMULTI_CURRENCY' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
		$scripts[] = 'woocommerce-multi-currency-switcher';
		$scripts[] = 'woocommerce-multi-currency-convertor';
	}

	return $scripts;
}

/**
 * Async load plugin styles
 *
 * @param array $styles Style handles.
 * @return array
 */
function async_styles( $styles ) {
	// WooCommerce.
	if ( class_exists( 'WooCommerce' ) ) {
		if ( is_product() ) {
			$styles[] = 'photoswipe';
			$styles[] = 'photoswipe-default-skin';
		}
	}

	return $styles;
}

/**
 * Remove plugin scripts
 *
 * @param array $styles Style handles.
 * @return array
 */
function remove_styles( $styles ) {
	// Rate My Post.
	if ( class_exists( 'Rate_My_Post' ) && ( ! is_singular( 'post' ) ) ) {
		$styles[] = 'rate-my-post';
	}

	// Back In Stock Notifier.
	if ( class_exists( 'CWG_Instock_Notifier' ) ) {
		$styles[] = 'cwginstock_frontend_css';
		$styles[] = 'cwginstock_bootstrap';
	}

	// PayU.
	if ( class_exists( 'Payu\PaymentGateway\WC_Payu' ) ) {
		$styles[] = 'payu-gateway';
	}

	// Flexible Shipping.
	if ( class_exists( 'Flexible_Shipping_Plugin' ) ) {
		$styles[] = 'flexible_shipping_notices';
		$styles[] = 'flexible-shipping-free-shipping';
	}

	if ( class_exists( 'WPDesk_Paczkomaty_Plugin' ) ) {
		$styles[] = 'woocommerce-paczkomaty-inpost-blocks-integration-frontend';
		$styles[] = 'woocommerce-paczkomaty-inpost-blocks-integration-editor';
	}

	// Curcy.
	if ( class_exists( 'WOOMULTI_CURRENCY' ) || class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
		$styles[] = 'woo-multi-currency';
		$styles[] = 'woocommerce-multi-currency';
		$styles[] = 'wmc-flags';
	}

	// WooCommerce.
	if ( class_exists( 'WooCommerce' ) ) {
		$styles[] = 'brands-styles';
		$styles[] = 'wc-blocks-style';

		if ( ! is_product() ) {
			$styles[] = 'photoswipe';
			$styles[] = 'photoswipe-default-skin';
		}
	}

	return $styles;
}

/**
 * Preconnect to sources
 *
 * @param array $links Url list.
 * @return array
 */
function preconnect( $links ) {
	if ( 'production' !== wp_get_environment_type() ) {
		return $links;
	}

	$preconnect = array( 'https://gtm.chocante.pl', 'https://assets.mailerlite.com', 'https://www.gstatic.com' );

	return array_merge( $links, $preconnect );
}

/**
 * DNS preferch sources.
 *
 * @param array $links Url list..
 * @return array
 */
function prefetch( $links ) {
	if ( 'production' !== wp_get_environment_type() ) {
		return $links;
	}

	$prefetch = array( 'https://static.ads-twitter.com', 'https://connect.facebook.net', 'https://cdn-cookieyes.com' );

	return array_merge( $links, $prefetch );
}

/**
 * Disable loading Rate My Post font outside post
 */
function disable_rmp_font_preload() {
	if ( class_exists( 'Rate_My_Post' ) && ( ! is_singular( 'post' ) ) ) {
		add_filter( 'rmp_font_preload', '__return_false' );
	}
}

/**
 * Move WPML language switcher scripts to footer and disable styles (moved to main theme)
 */
function manage_wpml_scripts() {
	$wpml_dropdown  = 'wpml-legacy-dropdown';
	$wpml_menu_item = 'wpml-menu-item';

	foreach ( wp_styles()->queue as $handle ) {
		if ( str_contains( $handle, $wpml_dropdown ) || str_contains( $handle, $wpml_menu_item ) ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	foreach ( wp_scripts()->queue as $handle ) {
		$script = wp_scripts()->registered[ $handle ];

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
	if ( ! class_exists( 'WOOMULTI_CURRENCY' ) || 'toplevel_page_woocommerce-multi-currency' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'select2' );
}
