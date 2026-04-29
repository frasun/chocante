<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Layout\Product\get_product_image_ids;
use function Chocante\Layout\Product\display_product_gallery_image;

global $product;

if ( ! $product || ! $product instanceof WC_Product ) {
	return '';
}

$attachment_ids = get_product_image_ids( $product );
$alt_text       = _wp_specialchars( get_post_field( 'post_title', $product->get_id() ), ENT_QUOTES, 'UTF-8', true );
$image_size     = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
$full_size      = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
?>
<section class="woocommerce-product-gallery">
	<?php if ( count( $attachment_ids ) > 1 ) : ?>
		<div class="woocommerce-product-gallery__wrapper splide">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $attachment_ids as $key => $attachment_id ) : ?>
						<li class="splide__slide">
							<figure class="woocommerce-product-gallery__image">
								<?php display_product_gallery_image( $attachment_id, $alt_text, 0 === $key ); ?>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<ul class="woocommerce-product-gallery__thumbnails" role="list" aria-label="<?php esc_attr_e( 'Product gallery thumbnails', 'chocante' ); ?>">
			<?php
			foreach ( $attachment_ids as $key => $attachment_id ) {
				$aria_label = $key + 1 . ' / ' . count( $attachment_ids );
				echo '<li tabindex="0" aria-label="' . esc_attr( $aria_label ) . '">';
				echo wp_get_attachment_image(
					$attachment_id,
					'woocommerce_gallery_thumbnail',
					false,
					array(
						'alt'     => esc_attr( $alt_text ),
						'loading' => 'lazy',
					),
				);
				echo '</li>';
			}
			?>
		</ul>
	<?php else : ?>
		<div class="woocommerce-product-gallery__wrapper">
			<figure class="woocommerce-product-gallery__image">
				<?php
				if ( ! empty( $attachment_ids ) ) {
					display_product_gallery_image( $attachment_ids[0], $alt_text, true );
				} else {
					echo wp_kses_post( wc_placeholder_img( 'woocommerce_single' ) );
				}
				?>
			</figure>
		</div>
	<?php endif; ?>
</section>