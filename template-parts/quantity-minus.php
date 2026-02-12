<?php
/**
 * Quantiny minus button
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<button class="quantity__minus" title="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Remove', 'woocommerce' ); ?>">
	<?php icon( 'minus' ); ?>
</button>