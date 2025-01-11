<?php
/**
 * Single post content header
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<header class="single-post__header">
	<?php
	get_template_part(
		'template-parts/reading-time',
		'',
		array(
			// translators: reading time in minutes.
			'reading_time' => sprintf( esc_html_x( '%d minute(s)', 'reading time', 'chocante' ), esc_html( Chocante::get_reading_time( $args['content'] ) ) ),
		)
	);

	if ( shortcode_exists( 'ratemypost-result' ) ) {
		echo do_shortcode( '[ratemypost-result]' );
	}
	?>
</header>