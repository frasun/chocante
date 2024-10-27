<?php
/**
 * Product Search
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<button class="search-products__display" title="<?php esc_attr_e( 'Search products', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'woocommerce' ); ?>">	
	<?php Chocante::icon( 'search' ); ?>
</button>