<?php
/**
 * Quantiny plus button
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<button class="quantity__plus" title="<?php esc_attr_e( 'Add', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Add', 'woocommerce' ); ?>">
	<?php icon( 'plus' ); ?>
</button>