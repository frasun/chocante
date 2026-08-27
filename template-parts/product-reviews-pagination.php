<?php
/**
 * Product reviews pagination
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
use function Chocante\Layout\Common\spinner;
?>
<nav class="woocommerce-pagination">
	<?php echo spinner( 'data-wp-bind--hidden="!state.reviews.isLoading" hidden aria-hidden="true"' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
		<?php
			ob_start();
			icon( 'prev' );
			$prev_icon = ob_get_clean();
			$prev_link = get_previous_comments_link( $prev_icon );

			ob_start();
			icon( 'next' );
			$next_icon = ob_get_clean();
			$next_link = get_next_comments_link( $next_icon );
		?>
		<ul class="page-numbers">
			<?php if ( $prev_link ) : ?>
				<li><?php echo $prev_link; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php endif; ?>
			<?php if ( $next_link ) : ?>
				<li><?php echo $next_link; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>
</nav>
