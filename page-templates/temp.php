<?php
/**
 * Template Name: [temporary] Default
 *
 * @todo: Chocante - Bricks - move to page.php
 * @package WordPress
 * @subpackage Chocante
 */

get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'page' );
		do_action( 'chocante_after_main' );
	endwhile;
endif;
get_footer();
