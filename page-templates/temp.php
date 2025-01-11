<?php
/**
 * Template Name: [temporary] Default
 *
 * @todo: Chocante - Bricks - move to page.php
 * @package WordPress
 * @subpackage Chocante
 */

$page_header = isset( $args['page_header'] ) ? $args['page_header'] : locate_template( 'template-parts/content/content-header.php' );

get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content/content', get_post_type(), array( 'page_header' => $page_header ) );
	endwhile;
endif;
get_footer();
