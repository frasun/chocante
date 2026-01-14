<?php
/**
 * Displayed when no products are found matching the current query
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/no-products-found.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="woocommerce-no-products-found empty-screen">
	<p><?php esc_html_e( 'No products were found matching your selection.', 'woocommerce' ); ?></p>
	<?php if ( ! is_search() && ( woocommerce_products_will_display() || ( function_exists( 'chocante_has_product_filters' ) && chocante_has_product_filters() ) ) ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'reset', 'true', strtok( get_pagenum_link(), '?' ) ) ); ?>" class="button"><?php esc_html_e( 'Reset filters', 'chocante-product-filters' ); ?></a>
		<span><?php echo esc_html_x( 'or', 'no products found', 'chocante' ); ?></span>
	<?php endif; ?>
	<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="button button--sm"><?php esc_html_e( 'Go to shop', 'woocommerce' ); ?></a>
</div>