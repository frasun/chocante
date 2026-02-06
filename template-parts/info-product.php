<?php
/**
 * Additional product information
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<aside class="infobox">
	<?php
	/**
	 * Stock status
	 */
	get_template_part( 'template-parts/info', 'stock' );

	/**
	 * Fast shipping
	 */
	get_template_part( 'template-parts/info', 'shipping' );

	do_action( 'chocante_delivery_info' );
	?>
</aside>
