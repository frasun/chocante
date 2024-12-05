<?php
/**
 * Additional product information
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<aside class="infobox">
	<?php
	/**
	 * Stock status
	 */
	if ( function_exists( 'chocante_stock_quantity_get_text' ) ) {
		get_template_part( 'template-parts/info', 'stock' );
	}

	/**
	 * Fast shipping
	 */
	get_template_part( 'template-parts/info', 'shipping' );

	do_action( 'chocante_delivery_info' );
	?>
</aside>
