<?php
/**
 * Product category recipe
 *
 * @package WordPress
 * @subpackage Chocante
 */

if ( ! isset( $args['data'] ) || empty( $args['data'] ) ) {
	return;
}
?>

<section class="product__details product__details--recipe">
	<header>
		<h4><?php echo esc_html_x( 'Recipe', 'product page', 'chocante' ); ?></h4>
	</header>
	<table>
		<?php foreach ( $args['data'] as $recipe ) : ?>
			<tr>
				<td><?php echo wp_kses_post( $recipe ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
</section>