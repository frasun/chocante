<?php
/**
 * Delivery point selection
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use const Chocante\Layout\Checkout\DELIVERY_POINT;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
global $TRP_LANGUAGE;

$user_locale          = $TRP_LANGUAGE ?? get_user_locale();
$language_code        = locale_get_primary_language( $user_locale );
$shipping_postcode    = WC()->customer->get_shipping_postcode();
$shipping_destination = WC()->countries->get_formatted_address(
	array(
		'country'  => WC()->customer->get_shipping_country(),
		'postcode' => WC()->customer->get_shipping_postcode(),
	),
	', '
);

if ( ! empty( $shipping_postcode ) ) {
	$points_label = sprintf( '%s: <strong>%s</strong>', __( 'Showing results for', 'chocante' ), $shipping_destination );
} else {
	$points_label = __( 'Enter postal code to see available delivery points', 'chocante' );
}

$delivery_point = WC()->session->get( DELIVERY_POINT );
?>
<address class="chocante-delivery-point">
	<small class="chocante-delivery-point__label"><?php echo wp_kses_post( $points_label ); ?></small>
	<?php if ( ! empty( $shipping_postcode ) ) : ?>
	<select id="chocante-delivery-point-<?php esc_attr_e( $args['instance_id'] ); ?>" name="chocante-delivery-point-<?php esc_attr_e( $args['instance_id'] ); ?>" data-language="<?php echo esc_attr( $language_code ); ?>" data-courier-code="<?php echo esc_attr( $args['courier'] ); ?>" disabled data-placeholder-ready="<?php esc_attr_e( 'Select delivery point', 'chocante' ); ?>" data-placeholder-fetching="<?php esc_attr_e( 'Fetching point data...', 'chocante' ); ?>" data-placeholder-empty="<?php esc_attr_e( 'No points available', 'chocante' ); ?>">
		<option></option>
		<option selected disabled><?php esc_attr_e( 'Fetching point data...', 'chocante' ); ?></option>
	</select>
	<div class="chocante-delivery-point__info">
		<?php if ( isset( $delivery_point ) ) : ?>
			<small><?php esc_html_e( 'Selected delivery point:', 'chocante' ); ?></small>
			<p><?php echo esc_html( $delivery_point['info'] ); ?></p>
		<?php else : ?>
			<p class="chocante-delivery-point__empty"><?php esc_html_e( 'No delivery point selected', 'chocante' ); ?></p>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</address>