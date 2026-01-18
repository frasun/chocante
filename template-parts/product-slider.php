<?php
/**
 * Products Slider
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( $args['products'] ) : ?>
	<section class="splide product__slider" data-aria='<?php echo wp_json_encode( $args['labels'] ); ?>'>
		<div class="splide__track">
			<ul class="splide__list products">
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
					<?php Chocante::icon( 'prev' ); ?>
				</button>
				<button class="splide__arrow splide__arrow--next">
					<?php Chocante::icon( 'next' ); ?>
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
