<?php
/**
 * Single post
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

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
