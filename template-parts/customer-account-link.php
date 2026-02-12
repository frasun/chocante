<?php
/**
 * Customer account link
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

?>

<a class="customer-account-link" href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>" title="<?php esc_attr_e( 'My Account', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'My Account', 'woocommerce' ); ?>">
	<?php icon( 'account' ); ?>
</a>