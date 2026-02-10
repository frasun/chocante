<?php
/**
 * Chocante WooCommerce product page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Product_Page class.
 */
class Chocante_Product_Page {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_head', array( __CLASS__, 'preload_assets' ), 0 );

		remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices' );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 5 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_breadcrumb', 7 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'open_product_info_section' ), 9 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'open_product_header' ), 13 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 14 );
		add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_title', 16 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'close_product_header' ), 18 );

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 20 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'display_product_info' ), 30 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'display_product_attributes' ), 35 );

		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'close_product_info_section' ), 30 );

		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'display_related_products' ), 10 );
		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'output_product_description' ), 20 );

		// Product variation.
		add_action( 'woocommerce_after_variations_table', 'woocommerce_single_variation', 10 );
		add_action( 'woocommerce_after_variations_table', 'woocommerce_single_variation_add_to_cart_button', 20 );
		add_filter( 'woocommerce_show_variation_price', '__return_true' );
		add_filter( 'woocommerce_reset_variations_link', '__return_false' );
		add_filter( 'woocommerce_dropdown_variation_attribute_options_args', array( __CLASS__, 'select_variation' ) );

		// Product attributes.
		add_filter( 'woocommerce_display_product_attributes', array( __CLASS__, 'filter_product_attributes' ), 10, 2 );
		add_filter( 'woocommerce_format_weight', array( __CLASS__, 'format_weight_dimension' ), 10, 2 );

		// Product gallery.
		add_filter( 'woocommerce_gallery_image_html_attachment_image_params', array( __CLASS__, 'add_atts_to_main_image' ), 10, 4 );

		// Product stock.
		add_filter( 'woocommerce_get_availability_text', array( __CLASS__, 'get_stock_text' ), 10, 2 );
		add_filter( 'woocommerce_get_availability_text', array( __CLASS__, 'display_low_amount_info' ), 20, 2 );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		$product_js = Chocante::asset( 'single-product-scripts' );

		wp_enqueue_script(
			'chocante-single-product-js',
			get_theme_file_uri( 'build/single-product-scripts.js' ),
			array_merge( $product_js['dependencies'], array() ),
			$product_js['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$product_css = Chocante::asset( 'single-product' );

		wp_enqueue_style(
			'chocante-single-product-css',
			get_theme_file_uri( 'build/single-product.css' ),
			$product_css['dependencies'],
			$product_css['version'],
		);
	}

	/**
	 * Preload assets.
	 */
	public static function preload_assets() {
		// Preload styles.
		$product_css      = Chocante::asset( 'single-product' );
		$product_css_path = get_theme_file_uri( 'build/single-product.css' ) . '?ver=' . $product_css['version'];
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"preload\" href=\"{$product_css_path}\" as=\"style\" />";

		// Preload main image.
		$post_thumbnail_id = wc_get_product( get_the_ID() )->get_image_id();

		if ( $post_thumbnail_id ) {
			$image_size = 'woocommerce_single';
			$image_url  = wp_get_attachment_image_url( $post_thumbnail_id, $image_size );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<link rel=\"preload\" href=\"{$image_url}\" as=\"image\" />";
		}
	}

	/**
	 * Open product info section
	 */
	public static function open_product_info_section() {
		echo '<section class="product__summary">';
	}

	/**
	 * Open product page header
	 */
	public static function open_product_header() {
		echo '<header class="product__header">';
	}

	/**
	 * Close product info section
	 */
	public static function close_product_info_section() {
		echo '</section>';
	}

	/**
	 * Close product page header
	 */
	public static function close_product_header() {
		echo '</header>';
	}

	/**
	 * Display additional information on product page
	 */
	public static function display_product_info() {
		get_template_part( 'template-parts/info', 'product' );
	}

	/**
	 * Display product attributes on product page
	 */
	public static function display_product_attributes() {
		get_template_part( 'template-parts/product', 'details' );
	}

	/**
	 * Display related products
	 */
	public static function display_related_products() {
		global $product;

		$product_categories = get_the_terms( $product->get_id(), 'product_cat' );

		if ( is_wp_error( $product_categories ) ) {
			return;
		}

		$heading  = join( ', ', wp_list_pluck( $product_categories, 'name' ) );
		$cta_link = esc_url( get_permalink( wc_get_page_id( 'shop' ) ) . '?filter_product_cat=' . join( ',', wp_list_pluck( $product_categories, 'slug' ) ) );

		Chocante_Product_Section::display_product_section(
			array(
				'heading'    => $heading,
				'subheading' => _x( 'Products from category', 'product slider', 'chocante' ),
				'cta_link'   => $cta_link,
				'category'   => wp_list_pluck( $product_categories, 'term_id' ),
			)
		);
	}

	/**
	 * Display additional description on product page
	 */
	public static function output_product_description() {
		get_template_part( 'template-parts/product', 'description' );
	}

	/**
	 * Filter product attributes
	 * Show weight dimension for variable products in order to switch to chosen variation
	 * Show weight attribute for simple attribute
	 *
	 * @param array      $product_attributes Product attributes.
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	public static function filter_product_attributes( $product_attributes, $product ) {
		if ( is_a( $product, 'WC_Product_Simple' ) ) {
			unset( $product_attributes['weight'] );
		} elseif ( is_a( $product, 'WC_Product_Variable' ) ) {
			if ( isset( $product_attributes['weight'] ) ) {
				$weight = $product_attributes['weight'];
				unset( $product_attributes['weight'] );
				$keys  = array_keys( $product_attributes );
				$index = array_search( 'attribute_pa_waga', $keys, true );

				if ( false !== $index ) {
					$before             = array_slice( $product_attributes, 0, $index + 1, true );
					$after              = array_slice( $product_attributes, $index + 1, null, true );
					$product_attributes = $before + array( 'weight' => $weight ) + $after;
				}
			}

			unset( $product_attributes['attribute_pa_waga'] );
		}

		return $product_attributes;
	}

	/**
	 * Format weight dimension to show grams when vale is below 1 kg
	 *
	 * @param string $weight_string Weight dimension string.
	 * @param float  $weight Weight.
	 *
	 * @return string
	 */
	public static function format_weight_dimension( $weight_string, $weight ) {
		$w = floatval( $weight );

		if ( $w > 0 && $w < 1 ) {
			return $w * 1000 . ' g';
		}

		return $weight_string;
	}

	/**
	 * Add LCP attributes to main product image
	 *
	 * @param array  $image_attributes Attributes for the image markup.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $image_size Image size.
	 * @param bool   $main_image Is this the main image or a thumbnail?.
	 * @return array
	 */
	public static function add_atts_to_main_image( $image_attributes, $attachment_id, $image_size, $main_image ) {
		if ( $main_image ) {
			$image_attributes['fetchpriority'] = 'high';
		} else {
			$image_attributes['loading'] = 'lazy';
		}

		return $image_attributes;
	}

	/**
	 * Modify quantity text by adding weight info and suffix
	 *
	 * @param string     $availability Availability text.
	 * @param WC_Product $product Product object.
	 */
	public static function get_stock_text( $availability, $product ) {
		/**
		 * Return any content so that the .stock element gets printed and can be used in JS for replacing with selected variation data.
		 *
		 * @see: wc_get_stock_html()
		 */
		if ( $product instanceof WC_Product_Variable && empty( $availability ) ) {
			return ' ';
		}

		if ( $product->managing_stock() && $product->is_in_stock() && ! $product->is_on_backorder( 1 ) ) {
			// translators: Number of pieces.
			$stock_amount = sprintf( __( '%s pcs', 'chocante' ), $product->get_stock_quantity() );

			if ( $product instanceof WC_Product_Variation && function_exists( 'chocante_get_attribute' ) ) {
				// @todo: move this function to theme.
				$weight = chocante_get_attribute( 'pa_waga', $product );

				if ( isset( $weight ) && '' !== $weight ) {
					return "{$weight} x {$stock_amount}";
				}
			}

			return $stock_amount;
		}

		return $availability;
	}

	/**
	 * Display message about low amount left in stock
	 *
	 * @param string     $availability Availability text.
	 * @param WC_Product $product Product object.
	 */
	public static function display_low_amount_info( $availability, $product ) {
		if ( ! empty( trim( $availability ) ) && $product->managing_stock() && $product->is_in_stock() && ! $product->is_on_backorder( 1 ) ) {
			$stock_amount = $product->get_stock_quantity();

			if ( $stock_amount <= wc_get_low_stock_amount( $product ) ) {
				$availability .= '<br /><strong class="low-amount">' . __( 'Last items in stock!', 'chocante' ) . '</strong>';
			}
		}

		return $availability;
	}

	/**
	 * Select product variation on page load
	 *
	 * Disable variation dropdown placeholder
	 * Preselect variation:
	 * 1. Variation param set in the url and available
	 * 2. Default variation is defined and available
	 * 3. Take first available variation
	 *
	 * @param array $args Variation attribute options.
	 * @return array
	 */
	public static function select_variation( $args ) {
		$args['show_option_none'] = false;
		$args['attribute']        = 'pa_waga';

		$product = $args['product'];

		if ( $product instanceof WC_Product_Variable ) {
			$variations = $product->get_available_variations();

			if ( empty( $variations ) ) {
				return $args;
			}

			$available_variations = array_column( array_column( $variations, 'attributes' ), "attribute_{$args['attribute']}" );
			// Skip fetching and filtering options later.
			$args['options'] = $available_variations;

			// 1. Variation param set in the url and available.
			$selected_key = 'attribute_' . sanitize_title( $args['attribute'] );
		  // phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST[ $selected_key ] ) && in_array( wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ), $available_variations, true ) ) {
				return $args;
			}

			// 2. Default variation is defined and available
			$default_attribute = $product->get_variation_default_attribute( $args['attribute'] );
			if ( in_array( $default_attribute, $available_variations, true ) ) {
				return $args;
			}

			// 3. Take first available variation.
			$args['selected'] = $available_variations[0];
		}

		return $args;
	}
}
