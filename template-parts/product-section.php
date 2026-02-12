<?php
/**
 * Product section to display related, featured, cross-sells etc.
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Layout\Common\spinner;
?>
<section <?php echo esc_attr( isset( $args['section_id'] ) ? 'id="' . $args['section_id'] . '"' : '' ); ?> class="product-section product-section--<?php echo esc_attr( wp_rand() ); ?><?php echo esc_attr( $args['section_class'] ); ?>"<?php echo esc_attr( $args['filters'] ); ?>>
	<header class="product-section__header">
		<h3 class="product-section__heading">
			<?php if ( isset( $args['subheading'] ) ) : ?>
				<span><?php echo esc_html( $args['subheading'] ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $args['heading'] ); ?>
		</h3>
		<a href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
	</header>
	<?php if ( ! empty( $args['content'] ) ) : ?>
	<div class="product-section__description">
		<p><?php echo wp_kses_post( $args['content'] ); ?></p>
	</div>
	<?php endif; ?>
	<div class="product-section__spinner">
		<?php echo wp_kses_post( spinner() ); ?>
	</div>
</section>
