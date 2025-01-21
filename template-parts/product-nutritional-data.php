<?php
/**
 * Product nutritional data
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $args['data'] ) || empty( $args['data'] ) ) {
	return;
}

?>
<section class="product__details">
	<header>
		<h4><?php echo esc_html_x( 'Nutritional data', 'product page', 'chocante' ); ?></h4>
		<span><?php echo esc_html_x( 'Per 100 g', 'product page', 'chocante' ); ?></span>
	</header>
	<table>
		<?php foreach ( $args['data'] as $data ) : ?>
			<tr>
				<th><?php echo wp_kses_post( $data['label'] ); ?></th>
				<td><?php echo wp_kses_post( $data['value'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
</section>