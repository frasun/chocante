<?php
/**
 * Register/enqueue custom scripts and styles
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( class_exists( 'Chocante_WooCommerce' ) ) {
			if ( Chocante_WooCommerce::bricks_disabled() ) {
				wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
			} elseif ( ! bricks_is_builder_main() ) {
				wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
			}
		} elseif ( ! bricks_is_builder_main() ) {
			wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), array( 'bricks-frontend' ), filemtime( get_stylesheet_directory() . '/style.css' ) );
		}

		// Feedback WP Rating.
		if ( is_singular( 'post' ) ) {
			wp_enqueue_style( 'feedbackwp-css', get_stylesheet_directory_uri() . '/css/feedbackwp.css' );
		}
	}
);

/**
 * Register custom elements
 */
add_action(
	'init',
	function () {
		$element_files = array(
			__DIR__ . '/elements/title.php',
		);

		foreach ( $element_files as $file ) {
			\Bricks\Elements::register_element( $file );
		}
	},
	11
);

/**
 * Add text strings to builder
 */
add_filter(
	'bricks/builder/i18n',
	function ( $i18n ) {
		// For element category 'custom'
		$i18n['custom'] = esc_html__( 'Custom', 'bricks' );

		return $i18n;
	}
);

/**
 * Funkcja do walidacji polskiego numeru NIP
 */
function wpdesk_fcf_validate_nip( $field_label, $value ) {

	$valid = false;

	$weights = array( 6, 5, 7, 2, 3, 4, 5, 6, 7 );
	$nip     = preg_replace( '/^PL/', '', $value );

	if ( strlen( $nip ) == 10 && is_numeric( $nip ) ) {
		$sum = 0;

		for ( $i = 0; $i < 9; $i++ ) {
			$sum += $nip[ $i ] * $weights[ $i ];
		}

		$valid = ( $sum % 11 ) == $nip[9];
	} elseif ( strlen( $nip ) == 0 ) {
		$valid = true;
	}

	if ( $valid === false ) {
		wc_add_notice( sprintf( __( 'Pole %s jest wypełnione niepoprawnie. Wpisz polski numer NIP bez spacji i myślników.', 'wpdesk' ), '<strong>' . $field_label . '</strong>' ), 'error' );
	}
}

add_filter( 'flexible_checkout_fields_custom_validation', 'wpdesk_fcf_custom_validation_nip' );
/**
 * Własna walidacja polskiego numeru NIP
 */
function wpdesk_fcf_custom_validation_nip( $custom_validation ) {
	$custom_validation['nip'] = array(
		'label'    => __( 'Polski NIP', 'wpdesk' ),
		'callback' => 'wpdesk_fcf_validate_nip',
	);

	return $custom_validation;
}

//
// search only in products
//
function searchfilter( $query ) {

	if ( $query->is_search && ! is_admin() ) {
		$query->set( 'post_type', array( 'product' ) );
	}

	return $query;
}

add_filter( 'pre_get_posts', 'searchfilter' );


// remove
function remove_cart_item_ajax_handler() {
	if ( isset( $_POST['cart_item_key'] ) ) {
		WC()->cart->remove_cart_item( sanitize_text_field( $_POST['cart_item_key'] ) );
	}
	wp_die();
}

add_action( 'wp_ajax_remove_cart_item', 'remove_cart_item_ajax_handler' );
add_action( 'wp_ajax_nopriv_remove_cart_item', 'remove_cart_item_ajax_handler' );


// stock quantity for variations
//
// // Dodaj endpoint AJAX
add_action( 'wp_ajax_get_variation_stock_quantity', 'get_variation_stock_quantity' );
add_action( 'wp_ajax_nopriv_get_variation_stock_quantity', 'get_variation_stock_quantity' );

// Funkcja obsługująca żądania AJAX
function get_variation_stock_quantity() {
	if ( isset( $_POST['variation_id'] ) ) {
		$variation_id = absint( $_POST['variation_id'] );
		$variation    = wc_get_product( $variation_id );

		// Pobierz ilość na stanie wybranego wariantu
		$stock_quantity = $variation->get_stock_quantity();

		// Zwróć ilość na stanie jako odpowiedź AJAX
		echo $stock_quantity;
	}
	wp_die(); // Zakończ proces AJAX
}


/**
 * Add media sizes for slider images
 * Remove unused default media sizes
 */
add_action(
	'init',
	function () {
		remove_image_size( '1536x1536' );
		remove_image_size( '2048x2048' );
		add_image_size( 'slider', 600, 700, true );
		add_image_size( 'slider_mobile', 460, 460, true );
	}
);

/**
 * Chocante Theme
 *
 * @package Chocante_Theme
 */
require_once get_stylesheet_directory() . '/includes/class-chocante.php';
Chocante::init();
