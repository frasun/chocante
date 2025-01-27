<?php
/**
 * Settings for flat rate shipping.
 *
 * @package WooCommerce\Classes\Shipping
 */

defined( 'ABSPATH' ) || exit;

$settings = array(
	'title'      => array(
		'title'       => __( 'Name', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'woocommerce' ),
		'default'     => 'Globkurier',
		'placeholder' => __( 'e.g. Standard national', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'tax_status' => array(
		'title'   => __( 'Tax status', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'taxable',
		'options' => array(
			'taxable' => __( 'Taxable', 'woocommerce' ),
			'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
		),
	),
);

return $settings;
