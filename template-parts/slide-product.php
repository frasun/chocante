<?php
/**
 * Post slide
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

$product           = wc_get_product( get_the_ID() );
$product_link      = get_permalink( $product->get_id() );
$product_category  = apply_filters( 'chocante_featured_products_category', null, $product->get_id() );
$product_name      = apply_filters( 'chocante_featured_products_title', get_the_title(), $product->get_id() );
$product_thumbnail = apply_filters( 'chocante_featured_products_thumbnail', $product->get_image_id(), $product->get_id() );
?>
<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile is-image-fill-element has-white-color">
	<div class="wp-block-media-text__content">
		<?php if ( isset( $product_category ) ) : ?>
			<h5 class="wp-block-heading splash__subtitle"><?php echo esc_html( $product_category ); ?></h5>
		<?php endif; ?>
		<h2 class="wp-block-heading has-white-color splash__title splash__title--large" style="text-decoration: underline;">
			<a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
		</h2>
		<div class="wp-block-buttons alignfull is-content-justification-left is-layout-flex wp-block-buttons-is-layout-flex splash__grow">
			<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-inverted">
				<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html_x( 'Buy now', 'product loop', 'chocante' ); ?></a>
			</div>
			<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-inverted-outline">
				<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Go to shop', 'woocommerce' ); ?></a>
			</div>
		</div>
		<?php do_action( 'chocante_featured_products_content', $product->get_id() ); ?>
	</div>
	<?php if ( isset( $product_thumbnail ) ) : ?>
		<figure class="wp-block-media-text__media">
			<a href="<?php echo esc_url( $product_link ); ?>" class="splash__thumbnail">
				<?php
				if ( $args['onsale'] ) {
					woocommerce_show_product_sale_flash();
				}

				echo wp_get_attachment_image(
					$product_thumbnail,
					'medium_large',
					false,
					array(
						'fetchpriority' => isset( $args['first'] ) ? 'high' : 'low',
						'loading'       => isset( $args['first'] ) ? 'eager' : 'lazy',
					)
				);
				?>
			</a>
		</figure>
	<?php endif; ?>
</div>