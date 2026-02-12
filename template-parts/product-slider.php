<?php
/**
 * Products Slider
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

if ( $args['products'] ) : ?>
	<section class="splide posts-slider" data-aria='<?php echo wp_json_encode( $args['labels'] ); ?>'>
		<div class="splide__track">
			<ul class="splide__list products">
				<?php do_action( 'chocante_product_section_loop' ); ?>
				<?php foreach ( $args['products'] as $product ) : ?>
						<?php
						$post_object = get_post( $product->get_id() );
						setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
						wc_get_template_part( 'content', 'product' );
						?>
				<?php endforeach; ?>
			</ul>
			<?php wp_reset_postdata(); ?>
			<nav class="splide__arrows">
				<button class="splide__arrow splide__arrow--prev">
					<?php icon( 'prev' ); ?>
				</button>
				<button class="splide__arrow splide__arrow--next">
					<?php icon( 'next' ); ?>
				</button>
			</nav>
		</div>
		<ul class="splide__pagination"></ul>
	</section>
<?php else : ?>
	<section class="product-section__empty">
		<p><?php esc_html_e( 'No products found', 'woocommerce' ); ?></p>
	</section>
<?php endif; ?>
