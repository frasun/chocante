<?php
/**
 * Mini Cart
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<a class="customer-account-link" href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>" title="<?php esc_attr_e( 'My Account', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'My Account', 'woocommerce' ); ?>">
	<?php Chocante::icon( 'account' ); ?>
</a>