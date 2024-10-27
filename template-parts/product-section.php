<?php
/**
 * Product section to display related, featured, cross-sells etc.
 *
 * @package     Chocante
 */

defined( 'ABSPATH' ) || exit;

$section_type = $args['type'] ? " product-section--{$args['type']}" : '';
?>
<aside class="product-section<?php echo esc_attr( $section_type ); ?>">
	<?php $heading = apply_filters( 'chocante_product_section_heading', __( 'Related products', 'woocommerce' ) ); ?>
	<?php if ( $heading ) : ?>
	<h3 class="product-section__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>
	<div class="product-section__spinner">
		<?php Chocante::spinner(); ?>
	</div>
</aside>
