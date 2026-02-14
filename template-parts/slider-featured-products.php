<?php
/**
 * Featured products slider
 *
 * @subpackage Chocante
 * @package WordPress
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Layout\ProductSection\get_products;

$fetured_products = get_products( featured: true );
$has_products     = ! empty( $fetured_products );

if ( ! $has_products ) {
	return;
}

// Load block styles.
wp_enqueue_style( 'wp-block-media-text' );
wp_enqueue_style( 'wp-block-buttons' );
wp_enqueue_style( 'wp-block-button' );
?>

<header class="post-slider-container featured-products alignfull wp-site-blocks is-layout-constrained has-global-padding">
	<section class="splide post-slider alignwide">
		<div class="splide__track">
			<ul class="splide__list">
				<?php
				foreach ( $fetured_products as $key => $product ) {
					$post = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
					setup_postdata( $post );
					$args = array( 'onsale' => $product->is_on_sale() );

					if ( 0 === $key ) {
						$args['first'] = true;
					}

					echo '<li class="splide__slide splash">';
					get_template_part( 'template-parts/slide', 'product', $args );
					echo '</li>';
					wp_reset_postdata();
				}
				?>
			</ul>
		</div>
		<?php if ( count( $fetured_products ) > 1 ) : ?>
			<ul class="splide__pagination"></ul>
		<?php endif; ?>
	</section>
</header>