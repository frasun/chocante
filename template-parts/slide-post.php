<?php
/**
 * Post slide
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile is-image-fill-element has-white-color">
	<div class="wp-block-media-text__content">
		<h5 class="wp-block-heading splash__subtitle"><?php echo esc_html_x( 'Cacao Blog', 'blog', 'chocante' ); ?></h5>
		<h2 class="wp-block-heading has-white-color splash__title">
			<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
		</h2>
		<p class="splash__grow"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<div class="wp-block-buttons alignfull is-content-justification-left is-layout-flex wp-block-buttons-is-layout-flex">
			<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-inverted">
				<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html_x( 'Read more', 'blog', 'chocante' ); ?></a>
			</div>
		</div>
	</div>
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="wp-block-media-text__media">
			<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>" class="splash__thumbnail">
				<?php
				the_post_thumbnail(
					'woocommerce_single',
					array(
						'fetchpriority' => true === $args['first'] ? 'high' : 'low',
						'loading'       => true === $args['first'] ? 'eager' : 'lazy',
					)
				);
				?>
			</a>
		</figure>
	<?php endif; ?>
</div>