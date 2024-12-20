<?php
/**
 * Chocante ACF
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_ACF class.
 */
class Chocante_ACF {
	const ACF_PRODUCT_TITLE = 'tekst_przed_tytulem';
	const ACF_PRODUCT_TYPE  = 'tekst_po_tytule';

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Cart & mini-cart.
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'get_custom_product_title' ), 10, 2 );

		// Product loop item.
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'display_loop_item_type' ), 3 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
		add_action( 'woocommerce_shop_loop_item_title', array( __CLASS__, 'modify_loop_item_title' ) );

		// Product page.
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'modify_product_breadcrumb_title' ), 6 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'modify_product_page_title' ), 15 );
		add_filter( 'woocommerce_display_product_attributes', array( __CLASS__, 'add_product_attributes' ), 20, 2 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'display_nutritional_data' ), 36 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'display_diet_icons' ), 25 );

		// Product category page.
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'display_category_description' ), 20 );

		// View order.
		add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'modify_order_item_name' ), 10, 2 );
	}

	/**
	 * Modify product title using ACF fields
	 *
	 * @param string $product_name Product title.
	 * @param array  $cart_item Product in the cart.
	 * @return string
	 */
	public static function get_custom_product_title( $product_name, $cart_item ) {
		$product_id = $cart_item['product_id'];
		$product    = $cart_item['data'];

		if ( 'product' === get_post_type( $product_id ) ) {
			$product_short_name = get_field( self::ACF_PRODUCT_TITLE, $product_id );
			$product_type       = get_field( self::ACF_PRODUCT_TYPE, $product_id );

			if ( $product_short_name ) {
				$product_name = $product_short_name;

				if ( $product_type ) {
					$product_name .= '<small>' . $product_type . '</small>';
				}

				return sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ), wp_kses_post( $product_name ) );
			}
		}

		return $product_name;
	}

	/**
	 * Display product type ACF field in the loop item.
	 */
	public static function display_loop_item_type() {
		global $product;

		if ( ! isset( $product ) ) {
			return;
		}

		$product_type = get_field( self::ACF_PRODUCT_TYPE, $product->get_id() );

		if ( $product_type ) {
			echo "<span class='woocommerce-loop-product__type'>" . esc_html( $product_type ) . '</span>';
		}
	}

	/**
	 * Display ACF field instead of default product title in the loop item.
	 */
	public static function modify_loop_item_title() {
		global $product;

		if ( ! isset( $product ) ) {
			return;
		}

		$product_name  = get_field( self::ACF_PRODUCT_TITLE, $product->get_id() );
		$product_title = $product_name ? $product_name : get_the_title();

		echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . $product_title . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Display ACF short name in breadcrumbs
	 */
	public static function modify_product_breadcrumb_title() {
		add_filter( 'the_title', array( self::class, 'get_product_page_short_title' ), 10, 2 );
	}

	/**
	 * Replace default product name with ACF fields
	 *
	 * @param string $title Product name.
	 * @param int    $id Product ID.
	 * @return string
	 */
	public static function get_product_page_short_title( $title, $id ) {
		$product_short_name = get_field( self::ACF_PRODUCT_TITLE, $id );

		if ( $product_short_name ) {
			return $product_short_name;
		}

		return $title;
	}

	/**
	 * Display ACF fields in product page title
	 */
	public static function modify_product_page_title() {
		add_filter( 'the_title', array( self::class, 'get_product_page_title' ), 10, 2 );
	}

	/**
	 * Replace default product name with ACF fields
	 *
	 * @param string $title Product name.
	 * @param int    $id Product ID.
	 * @return string
	 */
	public static function get_product_page_title( $title, $id ) {
		$product_short_name = get_field( self::ACF_PRODUCT_TITLE, $id );
		$product_type       = get_field( self::ACF_PRODUCT_TYPE, $id );

		if ( $product_short_name ) {
			$product_title = $product_short_name;

			if ( $product_type ) {
				$product_title .= " {$product_type}";
			}

			return $product_title;
		}

		return $title;
	}

	/**
	 * Add ACF product attributes
	 *
	 * @param array      $product_attributes Product attributes.
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	public static function add_product_attributes( $product_attributes, $product ) {
		$field_name = 'szczegoly_produktu';
		$attributes = get_field( $field_name, $product->get_id() );

		if ( $attributes ) {
			$index = 0;
			foreach ( $attributes as $attribute ) {
				$product_attributes[ $field_name . '_' . $index ] = array(
					'label' => $attribute['nazwa_parametru'],
					'value' => $attribute['wartosc_parametru'],
				);
				++$index;
			}
		}

		return $product_attributes;
	}

	/**
	 * Display nutritional data table
	 */
	public static function display_nutritional_data() {
		global $product;

		$data_field = get_field( 'tabela_odzywcza', $product->get_id() );

		if ( ! $data_field ) {
			return;
		}

		$data = array();

		foreach ( $data_field as $field ) {
			if ( empty( $field ) ) {
				continue;
			}

			array_push(
				$data,
				array(
					'label' => $field['parametry_'],
					'value' => $field['wartosc_parametru'],
				)
			);
		}

		get_template_part(
			'template-parts/product',
			'nutritional-data',
			array(
				'data' => $data,
			)
		);
	}

	/**
	 * Display diet information
	 */
	public static function display_diet_icons() {
		global $product;

		$data_field = get_field( 'ikonki', $product->get_id() );

		if ( ! $data_field ) {
			return;
		}

		$data = array();

		foreach ( $data_field as $field ) {
			array_push(
				$data,
				$field['ikonka']
			);
		}

		get_template_part(
			'template-parts/product',
			'diet-info',
			array(
				'data' => $data,
			)
		);
	}

	/**
	 * Display diet information
	 */
	public static function display_category_description() {
		if ( ! is_product_category() ) {
			return;
		}

		$queried_object       = get_queried_object();
		$taxonomy             = $queried_object->taxonomy;
		$term_id              = $queried_object->term_id;
		$category_description = get_field( 'dlugi_opis_kategorii', $taxonomy . '_' . $term_id );

		if ( $category_description ) {
			echo '<div class="page-description">' . wp_kses_post( $category_description ) . '</div>';
		}
	}

	/**
	 * Replace default order item name with ACF fields
	 *
	 * @param string        $item_name Order line item name HTML.
	 * @param WC_Order_Item $item Order line item.
	 * @return string
	 */
	public static function modify_order_item_name( $item_name, $item ) {
		$product_id         = $item->get_data()['product_id'];
		$product_short_name = get_field( self::ACF_PRODUCT_TITLE, $product_id );
		$product_type       = get_field( self::ACF_PRODUCT_TYPE, $product_id );

		if ( $product_short_name ) {
			$product_title = '<strong>' . $product_short_name . '</strong>';

			if ( $product_type ) {
				$product_title .= '<small>' . $product_type . '</small>';
			}

			return $product_title;
		}

		return '<strong>' . $item->get_name() . '</strong>';
	}
}
