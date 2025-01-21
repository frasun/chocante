<?php
/**
 * Page template
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;


get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post(); ?>
	<main role="main">
		<?php
		do_action( 'chocante_before_content' );
		the_content();
		do_action( 'chocante_after_content' );
		?>
	</main>
		<?php
		do_action( 'chocante_after_main' );
	endwhile;
endif;
get_footer();
