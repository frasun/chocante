<?php
/**
 * Products diet info
 *
 * @package     Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $args['data'] ) || empty( $args['data'] ) ) {
	return;
}

?>
<aside class="product__diet-info">
	<?php
	foreach ( $args['data'] as $icon ) {
		echo wp_get_attachment_image( $icon['id'], 'full' );
	}
	?>
</aside>