<?php
/**
 * Product review commet field
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( wc_review_ratings_enabled() ) {
	wc_get_template( 'template-parts/product-review-rating.php' );
}
?>
<div class="form-row">
	<label for="comment"><?php esc_html_e( 'Your review', 'woocommerce' ); ?>&nbsp;<span class="required" data-wp-bind--hidden="state.form.config.verifiedBuyer">*</span></label>
	<div class="woocommerce-input-wrapper">
		<textarea
			id="comment"
			name="comment"
			cols="45"
			rows="8"
			placeholder="<?php esc_attr_e( 'Share your experience with this product...', 'woocommerce' ); ?>"
			data-wp-bind--required="!state.form.config.verifiedBuyer"></textarea>
	</div>
</div>