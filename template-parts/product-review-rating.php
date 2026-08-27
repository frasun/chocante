<?php
/**
 * Product review rarting field
 *
 * @see: Automattic\WooCommerce\Internal\OrderReviews\StarRating
 * @see: woocommerce/templates/order/star-rating.php
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

$labels         = array(
	1 => __( 'Very poor', 'woocommerce' ),
	2 => __( 'Not that bad', 'woocommerce' ),
	3 => __( 'Average', 'woocommerce' ),
	4 => __( 'Good', 'woocommerce' ),
	5 => __( 'Perfect', 'woocommerce' ),
);
$rating_labels  = (array) apply_filters( 'woocommerce_review_order_rating_labels', $labels );
$reversed       = array_reverse( $rating_labels, true );
$context_labels = array();

foreach ( $reversed as $rating => $label ) {
	$context_labels[] = array(
		'value'     => $rating,
		'label'     => $label,
		/* translators: 1: numeric star rating 2: label text e.g. "Good" */
		'ariaLabel' => sprintf( esc_html__( '%1$d out of 5 stars: %2$s', 'woocommerce' ), (int) $rating, esc_html( $label ) ),
		'inputId'   => 'product-rating-' . $rating,
	);
}

$required       = wc_review_ratings_required();
$rating_context = array(
	'labels'        => $context_labels,
	'required'      => $required,
	'selected'      => null,
	'selectedLabel' => null,
);
?>

<div class="form-row">
	<p id="product-rating-label" class="form-label"><?php esc_html_e( 'Your rating', 'woocommerce' ); ?><?php echo wp_kses_post( $required ? '&nbsp;' . wp_required_field_indicator() : '' ); ?></p>
	<?php
		$server_context = wp_interactivity_data_wp_context( $rating_context );
		$rating_field   = <<<HTML
			<div
				class="woocommerce-star-rating"
				role="radiogroup"
				aria-labelledby="product-rating-label"
				aria-describedby="product-rating-caption"
				data-wp-interactive="chocante/product-rating"
				{$server_context}
			>
				<div class="woocommerce-star-rating__stars">
					<template data-wp-each--rating="context.labels" data-wp-each-key="context.rating.value">
						<input
							class="woocommerce-star-rating__input"
							type="radio"
							name="rating"
							data-wp-bind--id="context.rating.inputId"
							data-wp-bind--value="context.rating.value"
							data-wp-bind--required="context.required"
							data-wp-watch="callbacks.onSelectedChange"
							data-wp-on--keydown="actions.setSelectedWithKeyboard"
							data-wp-on--change="actions.setSelectedLabel"
						/>
						<label class="woocommerce-star-rating__star" data-wp-bind--for="context.rating.inputId">
							<span class="screen-reader-text" data-wp-text="context.rating.ariaLabel"></span>
						</label>
					</template>
				</div>
				<span id="product-rating-caption" class="woocommerce-star-rating__caption" aria-live="polite" data-wp-text="context.selectedLabel"></span>
			</div>
		HTML;
		echo wp_interactivity_process_directives( $rating_field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>