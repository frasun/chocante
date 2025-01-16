<?php
/**
 * Content template
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<main role="main">
	<?php
	if ( $args['show_header'] ) {
		get_template_part( 'template-parts/content/content', 'header', array( 'class' => $args['header_class'] ) );
	}
	?>

	<div class="wp-site-blocks is-layout-constrained">
		<?php
		do_action( 'chocante_before_content' );
		the_content();
		do_action( 'chocante_after_content' );
		?>
	</div>
</main>

<?php do_action( 'chocante_after_main' ); ?>