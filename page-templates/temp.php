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
		get_template_part(
			'template-parts/content/content',
			get_post_type(),
			array(
				'header_class' => isset( $args['header_class'] ) ? $args['header_class'] : null,
				'show_header'  => isset( $args['show_header'] ) ? $args['show_header'] : true,
			)
		);
	endwhile;
endif;
get_footer();
