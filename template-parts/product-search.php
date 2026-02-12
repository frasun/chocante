<?php
/**
 * Product Search
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<button class="search-products__display" title="<?php esc_attr_e( 'Search products', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'woocommerce' ); ?>">
	<?php icon( 'search' ); ?>
</button>