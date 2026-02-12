<?php
/**
 * Settings for flat rate shipping.
 *
 * @package WooCommerce\Classes\Shipping
 */

defined( 'ABSPATH' ) || exit;

$settings = array(
	'title'                 => array(
		'title'       => __( 'Name', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This name will be shown when in case of Globurier API error.', 'chocante' ),
		'default'     => 'Globkurier',
		'placeholder' => __( 'e.g. Standard national', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'transport_code'        => array(
		'title'   => _x( 'Transport type', 'globkurier', 'chocante' ),
		'type'    => 'select',
		'default' => 'road',
		'class'   => 'wc-enhanced-select',
		'options' => array(
			self::TRANSPORT_CODE_ROAD => _x( 'Road', 'globkurier', 'chocante' ),
			self::TRANSPORT_CODE_AIR  => _x( 'Air', 'globkurier', 'chocante' ),
		),
	),
	'default_rate'          => array(
		'title'             => _x( 'Default rate', 'globkurier', 'chocante' ),
		'type'              => 'text',
		'class'             => 'wc-shipping-modal-price',
		'placeholder'       => wc_format_localized_price( 0 ),
		'sanitize_callback' => array( $this, 'sanitize_default_rate' ),
		'description'       => __( 'This rate will be shown when in case of Globurier API error.', 'chocante' ),
		'desc_tip'          => true,
	),
	'default_delivery_time' => array(
		'title'       => _x( 'Default delivery time (days)', 'globkurier', 'chocante' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'This delivery time will be shown when in case of Globurier API error or missing delivery time entry.', 'chocante' ),
		'desc_tip'    => true,
	),
);

return $settings;
