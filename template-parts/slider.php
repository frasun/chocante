<?php
/**
 * Posts slider
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $args['posts']->found_posts > 1 ) : ?>
<section class="splide post-slider">
	<div class="splide__track">
		<ul class="splide__list">
			<?php
			while ( $args['posts']->have_posts() ) {
				$args['posts']->the_post();
				echo '<li class="splide__slide">';
				get_template_part( 'template-parts/slide', 'post' );
				echo '</li>';
			}
			?>
		</ul>
	</div>
</section>
<?php else :
	while ( $args['posts']->have_posts() ) {
		$args['posts']->the_post();
		get_template_part( 'template-parts/slide', 'post' );
	}
	endif;
	wp_reset_postdata();
?>