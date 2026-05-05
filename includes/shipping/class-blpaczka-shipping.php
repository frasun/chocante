<?php
/**
 * BL Paczka Shipping Method.
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Shipping_Method' ) ) {
	return;
}

use function Chocante\BLPaczka\get_valuation;
use function Chocante\Currency\get_currency;
use function Chocante\Currency\get_shop_currencies;

/**
 * BLPaczka_Shipping class.
 */
class BLPaczka_Shipping extends WC_Shipping_Method {
	/**
	 * Cash on delivery option
	 *
	 * @var bool
	 */
	public $cod = false;

	/**
	 * COD uptake value
	 *
	 * @var int|float|false
	 */
	public $uptake = 0;

	/**
	 * COD limits
	 *
	 * @var array
	 */
	public $cod_limits = array();

	/**
	 * Point of delivery option
	 *
	 * @var bool
	 */
	public $pod = false;

	/**
	 * Method name
	 */
	const METHOD_ID = 'chocante_blpaczka';

	/**
		* Default product weight (if not set)
		*/
	const DEFAULT_WEIGHT = 0.25;

	/**
	 * Object cache key
	 */
	const CACHE_KEY = 'chocante_blpaczka';

	/**
	 * Object cache TTL
	 */
	const CACHE_TTL       = 604800;
	const CACHE_TTL_SHORT = 300;

	/**
	 * Available couriers
	 */
	const COURIERS = array(
		'poczta'                    => 'Poczta Polska',
		'dpd'                       => 'DPD',
		'allegro_smart_dpd'         => 'DPD Allegro Smart',
		'ups'                       => 'UPS',
		'dhl'                       => 'DHL',
		'blp_cross_border'          => 'BLP Cross-Border',
		'blp_cross_border_eco'      => 'BLP Cross-Border Eco',
		'fedex'                     => 'FedEx',
		'fedex_rest'                => 'FedEx',
		'gls'                       => 'GLS',
		'hellman'                   => 'Hellmann',
		'inpost'                    => 'InPost',
		'orlen'                     => 'Orlen',
		'paczkomaty'                => 'InPost Paczkomat',
		'paczkomaty_allegro_smart'  => 'InPost Paczkomat Allegro Smart',
		'paczkomaty_eco'            => 'InPost Paczkomat Eco',
		'paczkomaty_to_door'        => 'InPost Paczkomat do drzwi',
		'poczta_ecommerce_envelope' => 'Poczta eCommerce Koperta',
		'allegro_smart_ecommerce'   => 'Allegro Smart eCommerce',
		'allegro_smart_poczta'      => 'Allegro Smart Poczta',
		'post_nord'                 => 'PostNord',
		'ambro_express'             => 'Ambro Express',
		'ambro_express_volume'      => 'Ambro Express',
		'euro_hermes'               => 'Eurohermes',
		'euro_hermes_sm'            => 'Eurohermes',
		'geis'                      => 'Geis',
		'geodis'                    => 'Geodis',
		'inpost_international'      => 'InPost International',
		'olza_logistic'             => 'OLZA Logistic',
		'packeta'                   => 'Packeta',
		'spring'                    => 'Spring',
		'meest'                     => 'Meest',
		'ups_rest_saver'            => 'UPS Saver',
		'ups_rest_standard'         => 'UPS Standard',
		'ups_rest_express'          => 'UPS Express',
		'ups_rest_express_plus'     => 'UPS Express plus',
		'ups_rest_expedited'        => 'UPS Expedited',
	);

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                   = self::METHOD_ID;
		$this->instance_id          = absint( $instance_id );
		$this->method_title         = 'BL Paczka';
		$this->method_description   = __( 'Shipping method with dynamic pricing based on BL Paczka API.', 'chocante' );
		$this->supports             = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
			'settings',
		);
		$this->instance_form_fields = array(
			'cod' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Cash on delivery', 'chocante' ),
				'default' => $this->cod ? 'yes' : 'no',
			),
			'pod' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Point of delivery', 'chocante' ),
				'default' => $this->pod ? 'yes' : 'no',
			),
			'eta' => array(
				'title'       => __( 'Time of delivery (days)', 'chocante' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => false,
				'placeholder' => __( 'e.g. 2-3', 'chocante' ),
			),
		);
		$this->form_fields          = array(
			'packages'   => array(
				'type'  => 'packages_table',
				'title' => __( 'Packages', 'chocante' ),
			),
			'fee'        => array(
				'title'       => __( 'Packaging fee', 'chocante' ),
				'type'        => 'price',
				'placeholder' => wc_format_localized_price( 0 ),
				'default'     => '0',
			),
			'cod_limits' => array(
				'type'  => 'cod_countries_table',
				'title' => __( 'COD limits', 'chocante' ),
			),
		);
		$this->cod                  = 'yes' === $this->get_option( 'cod' );
		$this->pod                  = 'yes' === $this->get_option( 'pod' );
		$this->title                = $this->get_method_label( $this->method_title );
		$this->cod_limits           = $this->get_option( 'cod_limits' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 99 );
		add_filter( 'woocommerce_shipping_method_add_rate', array( $this, 'add_delivery_time_to_rate' ), 10, 2 );
	}

	/**
	 * Get method instance display title based on selected options
	 *
	 * @param string $label Method name.
	 * @return string
	 */
	private function get_method_label( $label ) {
		if ( $this->cod ) {
			$label .= ' - ' . __( 'Cash on delivery', 'chocante' );
		}

		if ( $this->pod ) {
			$label .= ' - ' . __( 'Point of delivery', 'chocante' );
		}

		return $label;
	}

	/**
	 * Calculate the shipping costs.
	 *
	 * @param array $package Package of items from cart.
	 */
	public function calculate_shipping( $package = array() ) {
		$country_code = $package['destination']['country'];

		if ( $this->cod ) {
			$this->uptake = $this->calculate_uptake( $package['contents_cost'], $country_code );

			if ( false === $this->uptake ) {
				return;
			}
		}

		$cart_weight     = $this->get_cart_weight( $package );
		$shipping_option = $this->get_rate( $country_code, $cart_weight );

		if ( ! $shipping_option ) {
			return;
		}

		$rate = array(
			'id'            => $this->get_rate_id(),
			'label'         => $this->get_method_label( $shipping_option['courier'] ),
			'cost'          => $shipping_option['price'] + floatval( $this->get_option( 'fee' ) ),
			'package'       => $package,
			'delivery_time' => $this->get_option( 'eta' ),
			'meta_data'     => array(
				'courier' => $shipping_option['code'],
				'cod'     => $this->cod,
				'pod'     => $this->pod,
			),
		);

		$this->add_rate( $rate );
	}

	/**
	 * Get cart weight
	 *
	 * @param array $package Package of items from cart.
	 */
	private function get_cart_weight( $package = array() ) {
		$total_weight = 0;

		foreach ( $package['contents'] as $item ) {
			$product_weight = $item['data']->get_weight();

			if ( ! isset( $product_weight ) || empty( $product_weight ) ) {
				$product_weight = self::DEFAULT_WEIGHT;
			}

			$total_weight += (float) $product_weight * $item['quantity'];
		}

		return $total_weight;
	}

	/**
	 * Get shipping rate from cache
	 *
	 * @param string $country Country code.
	 * @param string $weight Cart weight.
	 * @return array
	 */
	private function get_rate( $country, $weight ) {
		$package_type = $this->get_package_type( $weight );
		$rate_hash    = md5( wp_json_encode( array( $country, ...$package_type, $this->cod, $this->pod ) ) );
		$valuation    = $this->get_shipping_valuation( $rate_hash, $country, $weight, $package_type );

		return $valuation;
	}

	/**
	 * Calculates the type and quantity of packages
	 *
	 * @param float $total_weight Cart weight.
	 * @return array
	 */
	private function get_package_type( $total_weight ) {
		$packages = $this->get_option( 'packages', array() );

		if ( empty( $packages ) ) {
			return array(
				'quantity' => ceil( $total_weight ),
				'side_x'   => 20,
				'side_y'   => 20,
				'side_z'   => 20,
			);
		}

		foreach ( $packages as $package ) {
			if ( $total_weight <= $package['weight'] ) {
				return array(
					'quantity' => 1,
					'side_x'   => $package['x'],
					'side_y'   => $package['y'],
					'side_z'   => $package['z'],
				);
			}
		}

		$largest    = end( $packages );
		$max_weight = floatval( $largest['weight'] );

		return array(
			'quantity' => $max_weight > 0 ? ceil( $total_weight / $max_weight ) : 1,
			'side_x'   => $largest['x'],
			'side_y'   => $largest['y'],
			'side_z'   => $largest['z'],
		);
	}

	/**
	 * Get data from cache
	 *
	 * @param string $key Request hash.
	 * @param string $country Country code.
	 * @param float  $weight Cart weight.
	 * @param array  $package Package type data.
	 * @return array
	 */
	private function get_shipping_valuation( $key, $country, $weight, $package ) {
		$object_cache = wp_using_ext_object_cache();
		$valuation    = $object_cache ? wp_cache_get( $key, self::CACHE_KEY ) : get_transient( self::CACHE_KEY . $key );

		if ( false === $valuation ) {
			$valuation = $this->fetch_valuation( $country, $weight, $package['side_x'], $package['side_y'], $package['side_z'], $package['quantity'], $this->uptake || 0, $this->pod );

			if ( false !== $valuation ) {
				if ( $object_cache ) {
					wp_cache_set( $key, $valuation, self::CACHE_KEY, $this->get_ttl() );
				} else {
					set_transient( self::CACHE_KEY . $key, $valuation, $this->get_ttl() );
				}
			}
		}

		return $valuation;
	}

	/**
	 * Fetch valuation
	 *
	 * @param string $country Country code.
	 * @param float  $weight Cart weight in kg.
	 * @param float  $side_x Package dimension x in cm.
	 * @param float  $side_y Package dimension y in cm.
	 * @param float  $side_z Package dimension z in cm.
	 * @param int    $quantity Number of packages.
	 * @param float  $uptake Order value for cash on delivery.
	 * @param bool   $pod Point of delivery.
	 */
	private function fetch_valuation( $country, $weight, $side_x, $side_y, $side_z, $quantity, $uptake, $pod ) {
		$valuation = get_valuation( $country, $weight, $side_x, $side_y, $side_z, $uptake, $pod );

		if ( false !== $valuation && $quantity > 1 ) {
			$valuation['price'] *= $quantity;
		}

		return $valuation;
	}

	/**
	 * Get caching TTL
	 */
	private function get_ttl() {
		return 'production' === wp_get_environment_type() ? self::CACHE_TTL : self::CACHE_TTL_SHORT;
	}

	/**
	 * Calculate uptake
	 *
	 * @param float  $cart_cost Cost of items in cart.
	 * @param string $country_code Shipping country.
	 * @return float|int|false
	 */
	private function calculate_uptake( $cart_cost, $country_code ) {
		$cod_limit = $this->get_cod_limit_for_country( $country_code );

		if ( ! $this->cod || ! isset( $cod_limit ) ) {
			return false;
		}

		$uptake           = 0;
		$country_currency = $cod_limit['currency'];
		$current_currency = get_currency();

		if ( $country_currency === $current_currency ) {
			$uptake = $cart_cost;
		} else {
			$shop_currencies = get_shop_currencies();

			if ( ! isset( $shop_currencies[ $country_currency ] ) ) {
				return false;
			}

			$currency_rate = floatval( $shop_currencies[ $country_currency ]['rate'] );
			$uptake        = $cart_cost * $currency_rate;
		}

		if ( $uptake > $cod_limit['limit'] ) {
			return false;
		}

		return $uptake;
	}

	/**
	 * Extract country COD limit from options
	 *
	 * @param string $country_code Country code.
	 * @return array
	 */
	private function get_cod_limit_for_country( $country_code ) {
		foreach ( $this->cod_limits as $row ) {
			if ( $row['country'] === $country_code ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * Display package table in admin
	 *
	 * @param string $key Form field name.
	 * @param array  $data From field value.
	 */
	public function generate_packages_table_html( $key, $data ) {
		$rows = $this->get_option( $key, array() );
		$tpl  = '<tr><td><input type="number" name="pkg_x[]" /></td><td><input type="number" name="pkg_y[]" /></td><td><input type="number" name="pkg_z[]" /></td><td><input type="text" name="pkg_weight[]" /></td><td><a href="#" class="remove_row">' . __( 'Remove' ) . '</a></td></tr>';
		ob_start();
		?>
		<tr>
			<th><?php echo esc_html( $data['title'] ); ?></th>
			<td>
				<table class="wc_input_table widefat chocante-blpaczka-admin">
					<thead><tr>
						<th><?php esc_html_e( 'Side X', 'chocante' ); ?> [cm]</th>
						<th><?php esc_html_e( 'Side Y', 'chocante' ); ?> [cm]</th>
						<th><?php esc_html_e( 'Side Z', 'chocante' ); ?> [cm]</th>
						<th><?php esc_html_e( 'Max. weight', 'chocante' ); ?> [kg]</th>
						<th></th>
					</tr></thead>
					<tbody>
					<?php foreach ( (array) $rows as $row ) : ?>
						<tr>
							<td><input type="number" name="pkg_x[]" value="<?php echo esc_attr( $row['x'] ); ?>" /></td>
							<td><input type="number" name="pkg_y[]" value="<?php echo esc_attr( $row['y'] ); ?>"/></td>
							<td><input type="number" name="pkg_z[]" value="<?php echo esc_attr( $row['z'] ); ?>"/></td>
							<td><input type="text" name="pkg_weight[]" value="<?php echo esc_attr( number_format_i18n( $row['weight'], 2 ) ); ?>"/></td>
							<td><a href="#" class="remove_row"><?php esc_html_e( 'Remove' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					<tfoot><tr><th colspan="5">
						<a href="#" class="chocante-add-row button" data-row="<?php echo esc_attr( $tpl ); ?>">
							<?php esc_html_e( 'Add package', 'chocante' ); ?>
						</a>
					</th></tr></tfoot>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate packages data
	 *
	 * @param string $key Form field name.
	 * @param array  $value From field data.
	 *
	 * phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	 */
	public function validate_packages_table_field( $key, $value ) {
		$fields = array(
			'x'      => 'pkg_x',
			'y'      => 'pkg_y',
			'z'      => 'pkg_z',
			'weight' => 'pkg_weight',
		);
		$count  = isset( $_POST['pkg_x'] ) ? count( $_POST['pkg_x'] ) : 0;
		$rows   = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$row = array();
			foreach ( $fields as $k => $field ) {
				$row[ $k ] = floatval( str_replace( ',', '.', $_POST[ $field ][ $i ] ?? 0 ) );
			}
			$rows[] = $row;
		}

		usort( $rows, fn( $a, $b ) => $a['weight'] <=> $b['weight'] );

		return $rows;
	}
	// phpcs:enable

	/**
	 * Display package table in admin
	 *
	 * @param string $key Form field name.
	 * @param array  $data From field value.
	 */
	public function generate_cod_countries_table_html( $key, $data ) {
		$rows       = $this->get_option( $key, array() );
		$countries  = WC()->countries->get_countries();
		$currencies = get_woocommerce_currencies();

		ob_start();
		?>
	<tr>
		<th><?php echo esc_html( $data['title'] ); ?></th>
		<td>
			<table class="wc_input_table widefat chocante-blpaczka-admin">
				<thead><tr>
					<th><?php esc_html_e( 'Country', 'chocante' ); ?></th>
					<th><?php esc_html_e( 'COD limit', 'chocante' ); ?></th>
					<th><?php esc_html_e( 'Currency', 'chocante' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody id="cod_countries_rows">
				<?php foreach ( (array) $rows as $row ) : ?>
					<tr>
						<td>
							<select name="cod_country[]">
								<?php foreach ( $countries as $code => $name ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $row['country'], $code ); ?>>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><input type="number" step="0.01" name="cod_limit[]" value="<?php echo esc_attr( (float) $row['limit'] ); ?>" /></td>
						<td>
							<select name="cod_currency[]">
								<?php foreach ( $currencies as $code => $name ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $row['currency'] ?? get_woocommerce_currency(), $code ); ?>>
										<?php echo esc_html( $code ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><a href="#" class="remove_row"><?php esc_html_e( 'Remove' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<tfoot><tr><th colspan="4">
					<a href="#" class="chocante-add-row button" data-row="<?php echo esc_attr( $this->get_cod_row_template( $countries, $currencies ) ); ?>">
						<?php esc_html_e( 'Add row', 'chocante' ); ?>
					</a>
				</th></tr></tfoot>
			</table>
		</td>
	</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get COD options table row
	 *
	 * @param array $countries Countries.
	 * @param array $currencies Currencies.
	 * @return string
	 */
	private function get_cod_row_template( $countries, $currencies ) {
		$country_options  = '';
		$currency_options = '';

		foreach ( $countries as $code => $name ) {
			$country_options .= '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
		}
		foreach ( $currencies as $code => $name ) {
			$selected          = selected( get_woocommerce_currency(), $code, false );
			$currency_options .= '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $code ) . '</option>';
		}

		$remove_label = esc_html_e( 'Remove' );

		return '<tr>'
		. '<td><select name="cod_country[]">' . $country_options . '</select></td>'
		. '<td><input type="number" step="0.01" name="cod_limit[]" value="0" /></td>'
		. '<td><select name="cod_currency[]">' . $currency_options . '</select></td>'
		. '<td><a href="#" class="remove_row">' . $remove_label . '</a></td>'
		. '</tr>';
	}

	/**
	 * Validate packages data
	 *
	 * @param string $key Form field name.
	 * @param array  $value From field data.
	 *
	 * phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	 */
	public function validate_cod_countries_table_field( $key, $value ) {
		$countries  = isset( $_POST['cod_country'] ) ? array_map( 'sanitize_text_field', $_POST['cod_country'] ) : array();
		$currencies = isset( $_POST['cod_currency'] ) ? array_map( 'sanitize_text_field', $_POST['cod_currency'] ) : array();
		$limits     = isset( $_POST['cod_limit'] ) ? array_map( 'floatval', $_POST['cod_limit'] ) : array();
		$rows       = array();
		foreach ( $countries as $i => $country ) {
			if ( $country ) {
				$rows[ $country ] = array(
					'country'  => $country,
					'currency' => $currencies[ $i ] ?? get_woocommerce_currency(),
					'limit'    => $limits[ $i ] ?? 0,
				);
			}
		}

		usort( $rows, fn( $a, $b ) => $a['country'] <=> $b['country'] );

		return $rows;
	}
	// phpcs:enable

	/**
	 * Add script to admin settings
	 */
	public function enqueue_admin_scripts() {
		wp_add_inline_script(
			'jquery',
			"
        jQuery(function($) {
            $(document).on('click', '.chocante-add-row', function(e) {
                e.preventDefault();
                var tpl = $(this).data('row');
                $(this).closest('table').find('tbody').append(tpl);
            });
            $(document).on('click', '.remove_row', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        });
    "
		);
		wp_add_inline_style(
			'woocommerce_admin_styles',
			'
				table.chocante-blpaczka-admin .remove_row { color: var(--wc-red); }
				table.chocante-blpaczka-admin tfoot th { font-weight: 400; }
				table.chocante-blpaczka-admin td { padding: 8px; }
			'
		);
	}

	/**
	 * Add delivery time to rate data
	 *
	 * @param WC_Shipping_Rate $rate Shipping rate.
	 * @param array            $args Rate args.
	 */
	public function add_delivery_time_to_rate( $rate, $args ) {
		if ( isset( $args['delivery_time'] ) ) {
			$rate->set_delivery_time( $args['delivery_time'] );
		}

		return $rate;
	}
}
