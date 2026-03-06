<?php
/**
 * Product stock status
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<div class="infobox__stock">
	<?php icon( 'box' ); ?>

	<div>
		<h6><?php echo esc_html_x( 'In stock', 'infobox', 'chocante' ); ?></h6>
		<?php do_action( 'chocante_product_stock' ); ?>
	</div>
</div>