<?php
/**
 * 404 page
 *
 * @package WordPress
 * @subpackage Chocante
 */

get_header(); ?>

<div class="empty-screen">
	<figure>
		<?php Chocante::icon( 'error' ); ?>
	</figure>
	<h1 class="page-title">
		<?php echo esc_html_x( 'Page not found', '404', 'chocante' ); ?>
	</h1>
	<a href="<?php echo esc_url( get_bloginfo( 'url' ) ); ?>" class="button button--sm"><?php echo esc_html_x( 'Go to homepage', 'thankyou', 'chocante' ); ?></a>
</div>

<?php get_footer(); ?>
