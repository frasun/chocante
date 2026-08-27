<?php
/**
 * Display single product reviews (comments)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product-reviews.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Translations\format_localized_decimal;
use function Chocante\Assets\icon;

global $product;

if ( ! comments_open() ) {
	return;
}

$rating_count = $product->get_rating_count();
$review_count = $product->get_review_count();
$average      = $product->get_average_rating();

?>
<section id="reviews" class="woocommerce-Reviews" data-wp-interactive="chocante/product-reviews">
	<header class="woocommerce-Reviews__header">
		<h2 class="woocommerce-Reviews__title"><?php esc_html_e( 'Reviews', 'woocommerce' ); ?></h2>
		<?php if ( $rating_count > 0 ) : ?>
			<button class="woocommerce-Reviews__add" data-wp-on--click="actions.showReviewForm"><?php echo esc_html__( 'Add a review', 'woocommerce' ); ?></button>
		<?php endif; ?>
	</header>
	<div class="woocommerce-Reviews__wrapper" data-wp-interactive="chocante/product-reviews" data-wp-router-region="chocante/product-reviews">
		<div class="woocommerce-Reviews__reviews">
			<?php if ( $rating_count > 0 ) : ?>
				<aside class="woocommerce-Reviews__rating">
					<h6 class="woocommerce-Reviews__rating-average"><?php echo esc_html( format_localized_decimal( round( $average, 1 ) ) ); ?></h6>
					<?php echo wc_get_rating_html( $average, $rating_count ); // phpcs:ignore ?>
					<p class="woocommerce-Reviews__rating-count"><?php printf( _n( '%s customer review', '%s customer reviews', $review_count, 'woocommerce' ), esc_html( $review_count ) ); // phpcs:ignore?></p>
					<button class="woocommerce-Reviews__add" data-wp-on--click="actions.showReviewForm"><?php echo esc_html__( 'Add a review', 'woocommerce' ); ?></button>
				</aside>
			<?php endif; ?>
			<div id="comments" class="woocommerce-Reviews__comments" data-wp-class--loaded="state.reviews.hasLoaded">
				<?php if ( have_comments() ) : ?>
					<ol class="woocommerce-Reviews__comments-list">
						<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
					</ol>
				<?php else : ?>
					<div class="woocommerce-noreviews">
						<figure><?php icon( 'bookmark-star' ); ?></figure>
						<p><?php esc_html_e( 'There are no reviews yet.', 'woocommerce' ); ?></p>
						<button class="button" data-wp-on--click="actions.showReviewForm"><?php echo esc_html__( 'Add a review', 'woocommerce' ); ?></button>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php get_template_part( 'template-parts/product-reviews', 'pagination' ); ?>
	</div>
	<?php get_template_part( 'template-parts/product-review', 'form' ); ?>
</section>
