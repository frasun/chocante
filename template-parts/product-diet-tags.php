<?php
/**
 * Product Tags
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="product__diet-info">
	<?php
	foreach ( $args['product_tags'] as $ptag ) {
			// Handle different width - inconsistency in uploaded icons.
			printf( "<img src='%s' alt='%s' class='diet-info__tag-thumbnail' height='%s' loading='lazy' />", esc_url( $ptag['thumbnail'] ), esc_attr( $ptag['name'] ), esc_attr( 56 ) );
	}
	?>
</aside>
