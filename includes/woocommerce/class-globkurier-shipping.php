<?php
/**
 * Globkurier Shipping Method.
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Globkurier_Shipping class.
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
	 * Transport type
	 *
	 * @var string
	 */
	public $transport_code = 'road';

	/**
	 * Default rate
	 *
	 * @var float|null
	 */
	public $default_rate = null;

	/**
	 * Default delivery time
	 *
	 * @var string
	 */
	public $default_delivery_time = '';

	/**
	 * API error
	 *
	 * @var null|boolean
	 */
	private $api_error = null;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id           = 'globkurier';
		$this->instance_id  = absint( $instance_id );
		$this->method_title = 'Globkurier';
		$this->supports     = array(
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
		$this->instance_form_fields  = include __DIR__ . '/settings-globkurier.php';
		$this->title                 = $this->get_option( 'title' );
		$this->transport_code        = $this->get_option( 'transport_code' );
		$this->default_rate          = $this->get_option( 'default_rate' );
		$this->default_delivery_time = $this->get_option( 'default_delivery_time' );
	}

	/**
	 * Calculate the shipping costs.
	 *
	 * @param array $package Package of items from cart.
	 */
	public function calculate_shipping( $package = array() ) {
		$product = $this->find_product();

		if ( isset( $this->api_error ) ) {
			if ( isset( $this->default_rate ) && $this->default_rate > 0 ) {
				$rate = array(
					'id'      => $this->get_rate_id(),
					'label'   => $this->get_label( $this->title, null, $this->default_delivery_time ),
					'cost'    => $this->default_rate,
					'package' => $package,
				);
			}
		}

		if ( isset( $product ) ) {
			$rate = array(
				'id'      => $this->get_rate_id(),
				'label'   => $this->get_label( $product['carrier'], $product['delivery_time'], $this->default_delivery_time ),
				'cost'    => $product['price'],
				'package' => $package,
			);
		}

		if ( isset( $rate ) ) {
			$this->add_rate( $rate );
			do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
		}
	}

	/**
	 * Return shipping method label
	 *
	 * @param string   $carrier_name Carrier name.
	 * @param int|null $delivery_time Days of delivery.
	 * @param string   $default_delivery_time Default delivery time.
	 * @return string
	 */
	public function get_label( $carrier_name, $delivery_time, $default_delivery_time = '' ) {
		$label = $carrier_name;

		if ( isset( $delivery_time ) ) {
			$label .= sprintf( ' (%d %s)', (int) $delivery_time + 1, __( 'days', 'woocommerce' ) );
		} elseif ( ! empty( $default_delivery_time ) ) {
			$label .= sprintf( ' (%s %s)', $default_delivery_time, __( 'days', 'woocommerce' ) );
		}

		return $label;
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
			$this->api_error = true;
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

		$this->api_error = true;
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
			$this->api_error = true;
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

		$this->api_error = true;
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
			$this->api_error = true;
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
			$this->api_error = true;
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
		$shipping_postcode = WC()->customer->get_shipping_postcode();
		$store_postcode    = WC()->countries->get_base_postcode();

		if ( ! empty( wc_trim_string( $shipping_postcode ) ) && ! WC_Validation::is_postcode( $shipping_postcode, WC()->customer->get_shipping_country() ) ) {
			return null;
		}

		$packages = $this->get_cart_packages();

		$params = array(
			'weight'            => isset( $packages['weight'] ) ? $packages['weight'] : 1,
			'length'            => isset( $packages['length'] ) ? $packages['length'] : 30,
			'width'             => isset( $packages['width'] ) ? $packages['width'] : 30,
			'height'            => isset( $packages['height'] ) ? $packages['height'] : 20,
			'quantity'          => isset( $packages['quantity'] ) ? $packages['quantity'] : 1,
			'senderCountryId'   => $this->get_country_id_by_iso( WC()->countries->get_base_country() ),
			'receiverCountryId' => $this->get_country_id_by_iso( WC()->customer->get_shipping_country() ),
		);

		if ( isset( $shipping_postcode ) && ! empty( wc_trim_string( $shipping_postcode ) ) ) {
			$params['receiverPostCode'] = $shipping_postcode;
		}

		if ( isset( $store_postcode ) && ! empty( wc_trim_string( $store_postcode ) ) ) {
			$params['senderPostCode'] = $store_postcode;
		}

		$products = $this->get_products( $params );

		if ( ! is_array( $products ) || empty( $products['standard'] ) ) {
			return null;
		}

		foreach ( $products['standard'] as $product ) {
			if ( isset( $product['collectionTypes'], $product['deliveryTypes'], $product['grossPrice'], $product['carrierName'], $product['transport'] ) &&
				in_array( 'PICKUP', $product['collectionTypes'], true ) &&
				in_array( 'PICKUP', $product['deliveryTypes'], true ) &&
				strtolower( $product['transport']['code'] ) === $this->transport_code ) {
				return array(
					'carrier'       => $product['carrierName'],
					'price'         => (float) $product['grossPrice'],
					'delivery_time' => $product['averageDelivery'],
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

		// UK.
		if ( 'GB' === $iso_code ) {
			$iso_code = 'GB1';
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

			if ( ! isset( $product_weight ) || empty( $product_weight ) ) {
				$product_weight = 0.25;
			}

			$total_weight += (float) $product_weight * $cart_item['quantity'];
		}

		if ( $total_weight <= 1 ) {
			return array(
				'weight'   => $total_weight,
				'quantity' => 1,
				'length'   => 20,
				'width'    => 20,
				'height'   => 8,
			);
		} elseif ( $total_weight <= 3 ) {
			return array(
				'weight'   => $total_weight,
				'quantity' => 1,
				'length'   => 20,
				'width'    => 30,
				'height'   => 20,
			);
		} elseif ( $total_weight <= 7 ) {
			return array(
				'weight'   => $total_weight,
				'quantity' => 1,
				'length'   => 30,
				'width'    => 30,
				'height'   => 30,
			);
		} else {
			return array(
				'weight'   => $total_weight,
				'quantity' => ceil( $total_weight / 7 ),
				'length'   => 40,
				'width'    => 40,
				'height'   => 40,
			);
		}
	}

	/**
	 * Sanitize default rate
	 *
	 * @param string $rate Default rate.
	 * @return float
	 */
	public function sanitize_default_rate( $rate ) {
		return floatval( $rate );
	}
}
