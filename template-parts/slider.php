<?php
/**
 * Posts slider
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $args['posts']->found_posts > 1 ) : ?>
<section class="splide post-slider alignwide">
	<div class="splide__track">
		<ul class="splide__list">
			<?php
			$key = 0;
			while ( $args['posts']->have_posts() ) {
				$args['posts']->the_post();
				echo '<li class="splide__slide splash">';
				get_template_part(
					'template-parts/slide',
					'post',
					array(
						'first' => 0 === $key,
					)
				);
				echo '</li>';
				++$key;
			}
			?>
		</ul>
	</div>
	<ul class="splide__pagination"></ul>
</section>
<?php else :
	while ( $args['posts']->have_posts() ) {
		$args['posts']->the_post();
		get_template_part( 'template-parts/slide', 'post' );
	}
	endif;
	wp_reset_postdata();
?>