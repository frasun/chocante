<?php
/**
 * Products Slider
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<section class="product__details">
	<h4><?php echo esc_html_x( 'Product Details', 'product page', 'chocante' ); ?></h4>
	<?php wc_display_product_attributes( $product ); ?>
</section>