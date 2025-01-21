<?php
/**
 * Post slide
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="post">
	<h5 class="post__heading"><?php echo esc_html_x( 'Cacao Blog', 'blog', 'chocante' ); ?></h5>
	<?php the_title( '<h2 class="post__title">', '</h2>' ); ?>
	<p class="post__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
	<div class="post__cta">
		<a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_html_x( 'Read more', 'blog', 'chocante' ); ?></a>
	</div>
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="post__thumbnail">
			<?php the_post_thumbnail( array( 350, 350 ) ); ?>
		</figure>
	<?php endif; ?>
</div>