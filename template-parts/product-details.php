<?php
/**
 * Products Slider
 *
 * @package     Chocante
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<section class="product__details">
	<h4><?php esc_html_e( 'Product Details', 'chocante' ); ?></h4>
	<?php wc_display_product_attributes( $product ); ?>
</section>