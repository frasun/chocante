<?php
/**
 * Product review form
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Layout\Common\spinner;

global $product;

$product_id = $product->get_id();
?>

<div
	id="review_form"
	class="woocommerce-Reviews__review-form"
	data-wp-init="callbacks.fetchForm"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	hidden
	data-wp-bind--hidden="!state.form.isVisible"
>
	<?php echo spinner( 'data-wp-bind--hidden="state.form.isFetched" aria-hidden="true"' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="review-form__fields" hidden data-wp-bind--hidden="!state.form.isFetched">
		<h3 class="review-form__title"><?php esc_html_e( 'Add a review', 'woocommerce' ); ?></h3>
		<form
			action="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>"
			method="post"
			class="review-form__field-wrapper"
			spellcheck="false"
			data-wp-on--reset="actions.resetRating"
			data-wp-on--submit="actions.submitReview"
			data-wp-bind--hidden="!state.form.config.showForm"
		>
			<div class="form-row" data-wp-bind--hidden="!state.form.config.authorRequired">
				<label for="author">
					<?php esc_html_e( 'First name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
				</label>
				<div class="woocommerce-input-wrapper">
					<input id="author" name="author" type="text" autocomplete="name" value="" size="30" data-wp-bind--required="state.form.config.authorRequired">
				</div>
			</div>
			<div class="form-row" data-wp-bind--hidden="!state.form.config.authorRequired">
				<label for="email">
					<?php esc_html_e( 'Email', 'woocommerce' ); ?>&nbsp;<span class="required" data-wp-bind--hidden="!state.form.config.authorRequired">*</span>
				</label>
				<div class="woocommerce-input-wrapper">
					<input id="email" name="email" type="email" autocomplete="email" value="" size="30" data-wp-bind--required="state.form.config.authorRequired">
				</div>
				<em><?php esc_html_e( 'Your email address will not be published.' ); ?></em>
			</div>
			<?php wc_get_template( 'template-parts/product-review-field.php' ); ?>
			<div class="form-submit">
				<input name="submit" type="submit" id="submit" class="submit" value="<?php esc_attr_e( 'Submit', 'woocommerce' ); ?>" data-wp-bind--disabled="state.form.isPostingReview" />
			</div>
			<p
				id="feedback"
				hidden
				data-wp-bind--hidden="!state.form.feedback.show"
				data-wp-watch="callbacks.renderFormFeedback"
				class="woocommerce-error"
				data-wp-class--woocommerce-success="state.form.feedback.status"
				data-wp-class--woocommerce-error="!state.form.feedback.status"
			></p>
		</form>
		<p class="woocommerce-verification-required" hidden data-wp-bind--hidden="!state.form.config.mustBeVerified">
			<?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ); ?>
		</p>
		<p class="must-log-in" hidden data-wp-bind--hidden="!state.form.config.mustLogIn">
			<?php
				$login_url = wc_get_page_permalink( 'myaccount' ) ?? wp_login_url( apply_filters( 'the_permalink', get_permalink( $product_id ), $product_id ) );
				/* translators: %s opening and closing link tags respectively */
				printf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'woocommerce' ), '<a href="' . esc_url( $login_url ) . '">', '</a>' );
			?>
		</p>
	</div>
</div>