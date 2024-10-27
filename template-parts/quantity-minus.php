<?php
/**
 * Quantiny minus button
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<button class="quantity__minus" title="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>">
	<?php Chocante::icon( 'minus' ); ?>
</button>