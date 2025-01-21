<?php
/**
 * Product stock status
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="infobox__stock">
	<?php Chocante::icon( 'box' ); ?>

	<div>
		<h6><?php echo esc_html_x( 'In stock', 'infobox', 'chocante' ); ?></h6>
		<?php chocante_stock_quantity_display(); ?>
	</div>
</div>