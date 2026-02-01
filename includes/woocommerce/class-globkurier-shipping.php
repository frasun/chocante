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
	 * API Base URL
	 */
	const API_URL = 'https://api.globkurier.pl/v1';

	/**
	 * API request timeout (seconds)
	 */
	const API_TIMEOUT = 12;

	/**
	 * Client email
	 *
	 * @var string
	 */
	private $email;

	/**
	 * Client password
	 *
	 * @var string
	 */
	private $password;

	/**
	 * Transport codes
	 */
	const TRANSPORT_CODE_AIR  = 'air';
	const TRANSPORT_CODE_ROAD = 'road';

	/**
	 * Method transport type
	 *
	 * @var string
	 */
	public $transport_code = self::TRANSPORT_CODE_ROAD;

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
	 * Cache keys
	 */
	const TOKEN_TRANSIENT_KEY        = 'chocante_globkurier_auth_token';
	const COUNTRY_LIST_TRANSIENT_KEY = 'chocante_globkurier_country_list';
	const TOKEN_LIFETIME             = 23 * HOUR_IN_SECONDS;
	const COUNTRY_LIST_LIFETIME      = 24 * HOUR_IN_SECONDS;
	const CACHED_PRODUCTS            = 'chocante_globkurier';

	/**
	 * Default product weight (if not set)
	 */
	const DEFAULT_WEIGHT = 0.25;

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

		$this->email    = defined( 'GLOBKURIER_EMAIL' ) ? GLOBKURIER_EMAIL : null;
		$this->password = defined( 'GLOBKURIER_PASSWORD' ) ? GLOBKURIER_PASSWORD : null;

		if ( ! isset( $this->email ) || ! isset( $this->password ) ) {
			add_action( 'admin_notices', array( $this, 'credentials_missing_notice' ) );
		} else {
			$this->init();
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}
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
	 * Display notice about missing credentials.
	 */
	public function credentials_missing_notice() {
		echo '<div class="error"><p>';
		esc_html_e( 'GlobKurier: Missing credentials. Please define GLOBKURIER_EMAIL and GLOBKURIER_PASSWORD constants.', 'chocante' );
		echo '</p></div>';
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
				'label'   => $this->get_label( $product['carrier'], $product['delivery_time'], $this->default_delivery_time ),
				'cost'    => $product['price'],
				'package' => $package,
			);
		} elseif ( isset( $this->api_error ) ) {
			if ( isset( $this->default_rate ) && $this->default_rate > 0 ) {
				$rate = array(
					'id'      => $this->get_rate_id(),
					'label'   => $this->get_label( $this->title, null, $this->default_delivery_time ),
					'cost'    => $this->default_rate,
					'package' => $package,
				);
			}
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

		if ( self::TRANSPORT_CODE_AIR === $this->transport_code ) {
			$label .= ' - ' . _x( 'Air', 'globkurier', 'chocante' );
		}

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
		$token = get_transient( self::TOKEN_TRANSIENT_KEY );

		if ( false !== $token ) {
			return $token;
		}

		$url      = self::API_URL . '/auth/login';
		$args     = array(
			'body' => array(
				'email'    => $this->email,
				'password' => $this->password,
			),
		);
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->api_error = true;
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[GLOBKURIER] Authentication failed: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['token'] ) ) {
			$token = $data['token'];
			set_transient( self::TOKEN_TRANSIENT_KEY, $token, self::TOKEN_LIFETIME );
			return $token;
		}

		$this->api_error = true;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[GLOBKURIER] Authentication failed: Invalid credentials or response.' );
		return false;
	}

	/**
	 * Fetch country list from the API.
	 *
	 * @return array|null Country list data or null on failure.
	 */
	public function get_country_list() {
		$cached_countries = get_transient( self::COUNTRY_LIST_TRANSIENT_KEY );

		if ( false !== $cached_countries ) {
			return $cached_countries;
		}

		$url      = self::API_URL . '/countries';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$this->api_error = true;
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[GLOBKURIER] Failed to fetch countries: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $data ) {
			set_transient( self::COUNTRY_LIST_TRANSIENT_KEY, $data, self::COUNTRY_LIST_LIFETIME );
			return $data;
		}

		$this->api_error = true;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[GLOBKURIER] Failed to fetch countries: Invalid response.' );
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[GLOBKURIER] Cannot fetch products: No authentication token.' );
			return null;
		}

		$query_string = http_build_query( $params );
		$url          = self::API_URL . '/products?' . $query_string;
		$args         = array(
			'headers' => array(
				'x-auth-token' => $token,
			),
			'timeout' => self::API_TIMEOUT,
		);
		$response     = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->api_error = true;
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[GLOBKURIER] Failed to fetch products: ' . $response->get_error_message() );
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
		$packages          = $this->get_cart_packages();
		$params            = array(
			'weight'            => isset( $packages['weight'] ) ? $packages['weight'] : 1,
			'length'            => isset( $packages['length'] ) ? $packages['length'] : 30,
			'width'             => isset( $packages['width'] ) ? $packages['width'] : 30,
			'height'            => isset( $packages['height'] ) ? $packages['height'] : 20,
			'quantity'          => isset( $packages['quantity'] ) ? $packages['quantity'] : 1,
			'senderCountryId'   => $this->get_country_id_by_iso( WC()->countries->get_base_country() ),
			'receiverCountryId' => $this->get_country_id_by_iso( WC()->customer->get_shipping_country() ),
		);

		if ( isset( $shipping_postcode ) ) {
			$trimmed_shipping_post_code = wc_trim_string( $shipping_postcode );

			if ( ! empty( $trimmed_shipping_post_code ) && WC_Validation::is_postcode( $trimmed_shipping_post_code, WC()->customer->get_shipping_country() ) ) {
				$params['receiverPostCode'] = $trimmed_shipping_post_code;
			}
		}

		if ( isset( $store_postcode ) ) {
			$trimmed_store_postcode = wc_trim_string( $store_postcode );

			if ( ! empty( $trimmed_store_postcode ) ) {
				$params['senderPostCode'] = $trimmed_store_postcode;
			}
		}

		$products_hash = $this::generate_request_hash( $params );
		$products      = wp_cache_get( $products_hash, self::CACHED_PRODUCTS );

		if ( false === $products ) {
			$products = $this->get_products( $params );
			wp_cache_set( $products_hash, $products, self::CACHED_PRODUCTS, 3600 );
		}

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
				$product_weight = self::DEFAULT_WEIGHT;
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

	/**
	 * Generate request hash from parameters
	 *
	 * @param array $params Request parameters.
	 * @return string Hash.
	 */
	private static function generate_request_hash( $params ) {
		$hash_data = array(
			'weight'            => $params['weight'] ?? 0,
			'length'            => $params['length'] ?? 0,
			'width'             => $params['width'] ?? 0,
			'height'            => $params['height'] ?? 0,
			'quantity'          => $params['quantity'] ?? 1,
			'senderCountryId'   => $params['senderCountryId'] ?? '',
			'receiverCountryId' => $params['receiverCountryId'] ?? '',
			'receiverPostCode'  => $params['receiverPostCode'] ?? '',
			'senderPostCode'    => $params['senderPostCode'] ?? '',
		);

		return md5( wp_json_encode( $hash_data ) );
	}
}
