<?php
/**
 * Product stock status
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="infobox__stock">
	<?php Chocante::icon( 'box' ); ?>
  
	<div>
		<h6><?php esc_html_e( 'In stock', 'chocante' ); ?></h6>
		<?php chocante_stock_quantity_display(); ?>
	</div>
</div>