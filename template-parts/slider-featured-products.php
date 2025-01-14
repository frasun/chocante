<?php
/**
 * Featured products slider
 *
 * @subpackage Chocante
 * @package WordPress
 */

defined( 'ABSPATH' ) || exit;
?>
<?php $fetured_products = Chocante_Product_Section::get_products( featured: true ); ?>

<?php if ( ! empty( $fetured_products ) ) : ?>
	<header class="post-slider-container featured-products alignfull">
		<section class="splide post-slider">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $fetured_products as $product ) : ?>
						<?php
						$post_object = get_post( $product->get_id() );
						setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
						echo '<li class="splide__slide">';
						get_template_part( 'template-parts/slide', 'product', array( 'onsale' => $product->is_on_sale() ) );
						echo '</li>';
						?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	</header>
	<?php wp_reset_postdata(); ?>
<?php endif; ?>