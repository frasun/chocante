<?php
/**
 * Content template
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( is_single() ) : ?>
	<article>
<?php else : ?>
	<main role="main">
<?php endif; ?>

		<?php
		if ( $args['page_header'] ) {
			require_once $args['page_header'];
		}
		?>

		<div class="wp-site-blocks is-layout-constrained">
			<?php
			do_action( 'chocante_before_content' );
			the_content();
			do_action( 'chocante_after_content' );
			?>
		</div>

<?php if ( is_single() ) : ?>
	</article>
<?php else : ?>
	</main>
<?php endif; ?>

<?php do_action( 'chocante_after_main' ); ?>