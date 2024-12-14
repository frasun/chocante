<?php
/**
 * Product section to display related, featured, cross-sells etc.
 *
 * @package     Chocante
 */

defined( 'ABSPATH' ) || exit;

$heading    = isset( $args['heading'] ) ? $args['heading'] : __( 'Related products', 'woocommerce' );
$subheading = isset( $args['subheading'] ) ? $args['subheading'] : null;
$cta_link   = isset( $args['cta_link'] ) ? $args['cta_link'] : null;
?>
<aside class="product-section product-section--<?php echo esc_attr( wp_rand() ); ?>"<?php Chocante_Product_Section::output_product_section_atts( $args ); ?>>
	<header class="product-section__header">
		<h3 class="product-section__heading">
			<?php if ( isset( $subheading ) ) : ?>		
				<span><?php echo esc_html( $subheading ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $heading ); ?>
		</h3>
		<?php if ( isset( $cta_link ) ) : ?>
			<a href="<?php echo esc_attr( $cta_link ); ?>">Zobacz wszystkie</a>
		<?php endif; ?>
	</header>
	<div class="product-section__spinner">
		<?php Chocante::spinner(); ?>
	</div>
</aside>
