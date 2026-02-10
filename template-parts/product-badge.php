<?php
/**
 * Product badges
 *
 * @package     WordPress
 * @subpackage  Chocante
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $product;

$is_on_sale = $product->is_on_sale();
$is_bio     = has_term( 'BIO', 'product_tag', $product->get_id() );

if ( ! $is_on_sale && ! $is_bio ) {
	return;
}
?>

<div class="badge__container">
<?php
if ( $is_on_sale ) {
	echo wp_kses_post( apply_filters( 'woocommerce_sale_flash', sprintf( '<span class="onsale">%s</span>', esc_html__( 'Sale!', 'woocommerce' ) ), $post, $product ) );
}
if ( $is_bio ) {
	echo '<span class="badge badge--bio">BIO</span>';
}
?>
</div>
