<?php
/**
 * The template to display the reviewers meta data (name, verified owner, review date)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/review-meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Location\get_country_name;

global $comment;

$verified                    = wc_review_is_from_verified_owner( $comment->comment_ID );
$comment_author_country_code = get_comment_meta( $comment->comment_ID, 'country', true );
$comment_author_country      = get_country_name( $comment_author_country_code );
$is_review                   = 'review' === $comment->comment_type;
?>

<p class="meta">
<?php if ( '0' === $comment->comment_approved ) : ?>
	<em class="woocommerce-review__awaiting-approval"><?php esc_html_e( 'Your review is awaiting approval', 'woocommerce' ); ?></em>
<?php else : ?>
	<time class="woocommerce-review__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( get_comment_date( wc_date_format() ) ); ?></time>
	<?php if ( $is_review && isset( $comment_author_country ) ) : ?>
		<span>&nbsp;&middot;&nbsp;</span><span><?php echo esc_html( $comment_author_country ); ?></span>
	<?php endif; ?>
	<?php if ( $is_review && 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $verified ) : ?>
		<span>&nbsp;&middot;&nbsp;</span><span class="woocommerce-review__verified"><?php echo esc_html__( 'verified owner', 'woocommerce' ); ?></span>
	<?php endif; ?>
	<?php if ( $is_review ) : ?>
		<span>&nbsp;&middot;&nbsp;</span><span>ID: <?php echo esc_html( $comment->comment_ID ); ?></span>
	<?php endif; ?>
<?php endif; ?>
</p>
