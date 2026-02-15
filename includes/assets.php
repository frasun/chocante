<?php
/**
 * Theme assets
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Assets;

defined( 'ABSPATH' ) || exit;

use Chocante\Assets_Handler;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts', 20 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\manage_external_scripts', 1100 );
add_action( 'wp_head', __NAMESPACE__ . '\preload_assets', 0 );
add_action( 'wp_head', __NAMESPACE__ . '\preconnect_to_sources', 1 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_action( 'wp_default_scripts', __NAMESPACE__ . '\disable_jquery_migrate' );
add_filter( 'woocommerce_enqueue_styles', '__return_false' );

// Editor.
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );

/**
 * Enqueue scripts & styles
 */
function enqueue_scripts() {
	// Theme assets.
	$styles  = array();
	$scripts = array();

	// Common.
	$styles[]            = 'chocante';
	$scripts['chocante'] = array(
		'filename'     => 'chocante-scripts',
		'dependencies' => class_exists( 'WooCommerce' ) ? array( 'wc-cart-fragments' ) : array(),
	);

	// Blog.
	if ( is_home() ) {
		$styles[] = 'blog';
	}

	// Single post.
	if ( is_singular( 'post' ) ) {
		$styles[] = 'single-post';
	}

	// WooCommerce.
	if ( class_exists( 'WooCommerce' ) ) {
		// Product archive.
		if ( is_shop() || is_product_category() || is_product_taxonomy() || is_product_tag() ) {
			$styles[]                 = 'shop';
			$scripts['chocante-shop'] = array(
				'filename' => 'shop-scripts',
			);
		}

		// Product page.
		if ( is_product() ) {
			$styles[]                    = 'single-product';
			$scripts['chocante-product'] = array(
				'filename' => 'single-product-scripts',
			);
		}

		// Cart.
		if ( is_cart() ) {
			$styles[]                 = 'cart';
			$scripts['chocante-cart'] = array(
				'filename'     => 'cart-scripts',
				'dependencies' => array( 'jquery' ),
			);
		}

		// Checkout.
		if ( is_checkout() ) {
			$styles[]                     = 'checkout';
			$scripts['chocante-checkout'] = array(
				'filename'     => 'checkout-scripts',
				'dependencies' => array( 'jquery' ),
			);
		}

		// Account page.
		if ( is_account_page() ) {
			$styles[]                    = 'account';
			$scripts['chocante-account'] = array(
				'filename' => 'account-scripts',
			);
		}
	}

	add_theme_styles( $styles );
	add_theme_scripts( $scripts );

	if ( class_exists( 'WooCommerce' ) ) {
		// WooCommerce cart fragments.
		wp_enqueue_script( 'wc-cart-fragments' );

		// Checkout AJAX VAT validation.
		if ( is_checkout() ) {
			wp_localize_script(
				'chocante-checkout',
				'chocante',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'chocante' ),
				)
			);
		}
	}
}

/**
 * Enqueue styles.
 *
 * @param array $styles Style assets data.
 */
function add_theme_styles( $styles ) {
	foreach ( $styles as $handle ) {
		$asset = Assets_Handler::include( $handle );

		wp_enqueue_style(
			$handle,
			get_theme_file_uri( "build/{$handle}.css" ),
			$asset['dependencies'],
			$asset['version'],
		);
	}
}

/**
 * Enqueue styles.
 *
 * @param array $scripts Script assets data.
 */
function add_theme_scripts( $scripts ) {
	foreach ( $scripts as $handle => $asset ) {
		$script = Assets_Handler::include( $asset['filename'] );

		wp_enqueue_script(
			$handle,
			get_theme_file_uri( "build/{$asset['filename']}.js" ),
			array_merge( $script['dependencies'], $asset['dependencies'] ?? array() ),
			$script['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}

/**
 * Add assets to Gutenberg editor
 */
function enqueue_editor_assets() {
	// Editor specific.
	$editor_styles = Assets_Handler::include( 'editor' );

	wp_enqueue_style(
		'chocante-editor-css',
		get_theme_file_uri( 'build/editor.css' ),
		$editor_styles['dependencies'],
		$editor_styles['version'],
	);

	$ediotr_scripts = Assets_Handler::include( 'editor-scripts' );

	wp_enqueue_script(
		'chocante-editor-js',
		get_theme_file_uri( 'build/editor-scripts.js' ),
		array(
			'wp-blocks',
			'wp-dom-ready',
			'wp-edit-post',
		),
		$ediotr_scripts['version'],
		true
	);
}

/**
 * Preload critical assets
 */
function preload_assets() {
	$links  = array();
	$styles = array();

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
	if ( wp_script_is( 'jquery' ) ) {
		$jquery  = wp_scripts()->registered['jquery'];
		$suffix  = wp_scripts_get_suffix();
		$version = $jquery->ver;
		$links[] = array(
			'path' => "/wp-includes/js/jquery/jquery{$suffix}.js?ver={$version}",
			'as'   => 'script',
		);
	}

	// Theme styles.
	$styles[] = 'chocante';

	if ( is_singular( 'post' ) ) {
		$styles[] = 'single-post';
	}

	if ( is_home() ) {
		$styles[] = 'blog';
	}

	// WooCommerce.
	if ( class_exists( 'WooCommerce' ) ) {
		// Product page.
		if ( is_product() ) {
			$styles[] = 'single-product';

			// Preload main image.
			$post_thumbnail_id = wc_get_product( get_the_ID() )->get_image_id();

			if ( $post_thumbnail_id ) {
				$image_size = 'woocommerce_single';
				$image_url  = wp_get_attachment_image_url( $post_thumbnail_id, $image_size );
				$links[]    = array(
					'path' => $image_url,
					'as'   => 'image',
				);
			}
		}

		// Cart.
		if ( is_cart() ) {
			$styles[] = 'cart';
		}

		// Account page.
		if ( is_account_page() ) {
			$styles[] = 'account';
		}

		// Checkout.
		if ( is_checkout() ) {
			$styles[] = 'checkout';
		}

		// Shop catalog.
		if ( is_shop() || is_product_category() || is_product_taxonomy() || is_product_tag() ) {
			$styles[] = 'shop';
		}
	}

	foreach ( $styles as $handle ) {
		$links[] = array(
			'path' => get_theme_style_path( $handle ),
			'as'   => 'style',
		);
	}

	foreach ( $links as $link ) {
		$extra = isset( $link['extra'] ) ? implode( ' ', $link['extra'] ) : '';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"preload\" href=\"{$link['path']}\" as=\"{$link['as']}\" {$extra} />";
	}
}

/**
 * Get theme style asset path
 *
 * @param string $filename Style asset filename.
 * @return string
 */
function get_theme_style_path( $filename ) {
	$asset = Assets_Handler::include( $filename );

	return get_theme_file_uri( "build/{$filename}.css" ) . '?ver=' . $asset['version'];
}

/**
 * Insert SVG icon
 *
 * @param string $filename Filename from /icons directory.
 */
function icon( $filename ) {
	$file = get_theme_file_path( "icons/icon-{$filename}.svg" );

	if ( file_exists( $file ) ) {
		include $file;
	}
}

/**
 * Disable jQuery migrate on frontend
 *
 * @param WP_Scripts $scripts WP Scripts object.
 */
function disable_jquery_migrate( $scripts ) {
	if ( is_admin() || apply_filters( 'chocante_assets_use_jquery_migrate', false ) ) {
		return;
	}

	$jquery = $scripts->registered['jquery'];

	if ( isset( $jquery ) ) {
		$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
	}
}

/**
 * Manage loading 3rd party assets.
 */
function manage_external_scripts() {
	// Move scripts to footer.
	$footer_scripts = apply_filters( 'chocante_assets_defer_scripts', array() );

	foreach ( $footer_scripts as $handle ) {
		if ( wp_script_is( $handle ) ) {
			$script = wp_scripts()->registered[ $handle ];

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

	// Do not load scripts.
	$remove_scripts = apply_filters( 'chocante_assets_remove_scripts', array() );

	foreach ( $remove_scripts as $handle ) {
		wp_deregister_script( $handle );
		wp_dequeue_script( $handle );
	}

	// Load non-critical styles.
	$async_styles = apply_filters( 'chocante_assets_async_styles', array() );

	foreach ( $async_styles as $handle ) {
		if ( wp_style_is( $handle ) ) {
			$style = wp_styles()->registered[ $handle ];

			wp_deregister_style( $handle );
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

	// Do not load styles.
	$remove_styles = apply_filters( 'chocante_assets_remove_styles', array() );

	foreach ( $remove_styles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}

/**
 * Preconnect to external sources
 */
function preconnect_to_sources() {
	$preconnect = apply_filters( 'chocante_assets_preconnect', array() );
	$prefetch   = apply_filters( 'chocante_assets_prefetch', array() );

	foreach ( $preconnect as $link ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"preconnect\" href=\"{$link}\" />";
	}

	foreach ( $prefetch as $link ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"dns-prefetch\" href=\"{$link}\" />";
	}
}
