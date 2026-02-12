<?php
/**
 * Featured products slider
 *
 * @subpackage Chocante
 * @package WordPress
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Layout\ProductSection\get_products;

?>
<?php
	$fetured_products = get_products( featured: true );
?>

<?php if ( ! empty( $fetured_products ) ) : ?>
	<header class="post-slider-container featured-products alignfull wp-site-blocks is-layout-constrained has-global-padding">
		<section class="splide post-slider alignwide">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $fetured_products as $key => $product ) : ?>
						<?php
						$post = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
						setup_postdata( $post );
						echo '<li class="splide__slide splash">';
						get_template_part(
							'template-parts/slide',
							'product',
							array(
								'onsale' => $product->is_on_sale(),
								'first'  => 0 === $key,
							)
						);
						echo '</li>';
						wp_reset_postdata();
						?>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php if ( count( $fetured_products ) > 1 ) : ?>
				<ul class="splide__pagination"></ul>
			<?php endif; ?>
		</section>
	</header>
<?php endif; ?>