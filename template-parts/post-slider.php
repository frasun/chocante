<?php
/**
 * Products Slider
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

$blog_posts = get_posts(
	array(
		'numberposts'      => 12,
		'post_type'        => 'post',
		'orderby'          => 'rand',
		'suppress_filters' => false,
	)
);

if ( ! empty( $blog_posts ) ) : ?>
	<section class="splide posts-slider blog__slider">
		<div class="splide__track">
			<ul class="splide__list products">
				<?php foreach ( $blog_posts as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<?php setup_postdata( $post ); ?>
					<li class="splide__slide">
						<a class="post" href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( array( 350, 350 ), array( 'class' => 'post__thumbnail' ) );
							}
							the_title( '<h3 class="post__title">', '</h3>' );
							?>
						</a>
					</li>
					<?php wp_reset_postdata(); ?>
				<?php endforeach; ?>
			</ul>
			<nav class="splide__arrows">
				<button class="splide__arrow splide__arrow--prev">
					<?php icon( 'prev' ); ?>
				</button>
				<button class="splide__arrow splide__arrow--next">
					<?php icon( 'next' ); ?>
				</button>
			</nav>
		</div>
	</section>
<?php else : ?>
	<section class="product-section__empty">
		<p><?php esc_html_e( 'No products found', 'woocommerce' ); ?></p>
	</section>
<?php endif; ?>
