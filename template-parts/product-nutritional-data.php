<?php
/**
 * Product nutritional data
 *
 * @package     Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $args['data'] ) || empty( $args['data'] ) ) {
	return;
}

?>
<section class="product__details">
	<header>
		<h4><?php esc_html_e( 'Nutritional data', 'chocante' ); ?></h4>
		<span><?php esc_html_e( 'Per 100 g', 'chocante' ); ?></span>
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