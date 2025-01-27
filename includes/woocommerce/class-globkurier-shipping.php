<?php
/**
 * Globkurier Shipping Method.
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Shipping_Flat_Rate class.
 */
class Globkurier_Shipping extends WC_Shipping_Method {
	/**
	 * API Base URL.
	 *
	 * @var string
	 */
	private $base_url = 'https://api.globkurier.pl/v1';

	/**
	 * Client email.
	 *
	 * @var string
	 */
	private $email = 'admin@chocante.pl';

	/**
	 * Client password
	 *
	 * @var string
	 */
	private $password = 'BsudaKuda92%';

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'globkurier';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = 'Globkurier';
		$this->method_description = __( 'Calculates shipping cost using Globkurier API.', 'chocante' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);
		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init user set variables.
	 */
	public function init() {
		$this->instance_form_fields = include __DIR__ . '/settings-globkurier.php';
		$this->title                = $this->get_option( 'title' );
		$this->tax_status           = $this->get_option( 'tax_status' );
	}

	/**
	 * Calculate the shipping costs.
	 *
	 * @param array $package Package of items from cart.
	 */
	public function calculate_shipping( $package = array() ) {
		$product = $this->find_product();

		if ( isset( $product ) ) {
			$rate = array(
				'id'      => $this->get_rate_id(),
				'label'   => $product['carrier'],
				'cost'    => $product['price'],
				'package' => $package,
			);

			$this->add_rate( $rate );

			/**
		 * Developers can add additional flat rates based on this one via this action since @version 2.4.
		 *
		 * Previously there were (overly complex) options to add additional rates however this was not user.
		 * friendly and goes against what Flat Rate Shipping was originally intended for.
		 */
			do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
		}
	}

	/**
	 * Authenticate with the API and retrieve the token.
	 *
	 * @return string|bool
	 */
	public function authenticate() {
		$token = wp_cache_get( 'globkurier_auth_token', '', false, $token_found );

		if ( $token_found ) {
			return $token;
		}

		$url = $this->base_url . '/auth/login';

		$args = array(
			'body' => array(
				'email'    => $this->email,
				'password' => $this->password,
			),
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'Authentication failed: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['token'] ) ) {
			$token = $data['token'];
			wp_cache_set( 'globkurier_auth_token', $data['token'], '', 23 * HOUR_IN_SECONDS );
			return $token;
		}

		error_log( 'Authentication failed: Invalid credentials or response.' );
		return false;
	}

	/**
	 * Fetch country list from the API.
	 *
	 * @return array|null Country list data or null on failure.
	 */
	public function get_country_list() {
		$cached_countries = wp_cache_get( 'globkurier_country_list' );

		if ( $cached_countries ) {
			return $cached_countries;
		}

		$url = $this->base_url . '/countries';

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			error_log( 'Failed to fetch countries: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $data ) {
			// Cache the country list for 23 hours.
			wp_cache_set( 'globkurier_country_list', $data );
			return $data;
		}

		error_log( 'Failed to fetch countries: Invalid response.' );
		return null;
	}

	/**
	 * Get products from the API.
	 *
	 * @param array $params Parameters such as weight, quantity, and length.
	 * @return array|null API response data or null on failure.
	 */
	public function get_products( $params ) {
		// Retrieve the token from cache.
		$token = $this->authenticate();

		if ( ! $token ) {
			error_log( 'Cannot fetch products: No authentication token.' );
			return null;
		}

		$query_string = http_build_query( $params );
		$url          = $this->base_url . '/products?' . $query_string;

		$args = array(
			'headers' => array(
				'x-auth-token' => $token,
			),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'Failed to fetch products: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return $data;
	}

	/**
	 * Find the first product matching specific criteria and return its gross price.
	 *
	 * @return float|null Gross price of the matching product or null if not found.
	 */
	public function find_product() {
		$packages = $this->get_cart_packages();

		$params = array(
			'weight'            => isset( $packages['weight'] ) ? $packages['weight'] : 1.5,
			'length'            => isset( $packages['length'] ) ? $packages['length'] : 30,
			'width'             => isset( $packages['width'] ) ? $packages['width'] : 30,
			'height'            => isset( $packages['height'] ) ? $packages['height'] : 20,
			'quantity'          => isset( $packages['quantity'] ) ? $packages['quantity'] : 2,
			'senderCountryId'   => $this->get_country_id_by_iso( WC()->countries->get_base_country() ),
			'receiverCountryId' => $this->get_country_id_by_iso( WC()->customer->get_shipping_country() ),
		);

		$products = $this->get_products( $params );

		if ( ! is_array( $products ) || empty( $products['standard'] ) ) {
			return null;
		}

		foreach ( $products['standard'] as $product ) {
			if ( isset( $product['collectionTypes'], $product['deliveryTypes'], $product['grossPrice'], $product['carrierName'] ) &&
				in_array( 'PICKUP', $product['collectionTypes'], true ) &&
				in_array( 'PICKUP', $product['deliveryTypes'], true ) ) {
				return array(
					'carrier' => $product['carrierName'],
					'price'   => (float) $product['grossPrice'],
				);
			}
		}

		return null;
	}

	/**
	 * Get country ID based on ISO code.
	 *
	 * @param string $iso_code ISO code of the country.
	 * @return int|null Country ID or null if not found.
	 */
	public function get_country_id_by_iso( $iso_code ) {
		$countries = $this->get_country_list();

		if ( ! is_array( $countries ) ) {
			return null;
		}

		foreach ( $countries as $country ) {
			if ( isset( $country['isoCode'] ) && $country['isoCode'] === $iso_code ) {
				return $country['id'];
			}
		}

		return null;
	}

	/**
	 * Get WooCommerce cart package size and quantity based on total weight.
	 *
	 * @return array Package dimensions and quantity.
	 */
	public function get_cart_packages() {
		$cart         = WC()->cart;
		$total_weight = 0;

		foreach ( $cart->get_cart() as $cart_item ) {
			$product_weight = $cart_item['data']->get_weight();

			if ( ! isset( $product_weight ) ) {
				$product_weight = 0.25;
			}

			$total_weight += (float) $product_weight * $cart_item['quantity'];
		}

		if ( $total_weight < 2 ) {
			return array(
				'weight'   => $total_weight,
				'quantity' => 1,
				'length'   => 30,
				'width'    => 30,
				'height'   => 20,
			);
		} elseif ( $total_weight >= 2 && $total_weight <= 5 ) {
			return array(
				'weight'   => $total_weight,
				'quantity' => 1,
				'length'   => 30,
				'width'    => 30,
				'height'   => 40,
			);
		} else {
			return array(
				'weight'   => $total_weight,
				'quantity' => ceil( $total_weight / 5 ),
				'length'   => 30,
				'width'    => 30,
				'height'   => 40,
			);
		}
	}
}
