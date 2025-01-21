<?php
/**
 * Additional description on product page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

$post_content = get_the_content();

if ( empty( $post_content ) ) {
	return;
}
?>

<section class="product__description">
	<?php echo wp_kses_post( apply_filters( 'the_content', $post_content ) ); ?>
</section>