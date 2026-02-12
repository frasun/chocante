<?php
/**
 * Product stock status
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

global $product;
?>

<div class="infobox__stock">
	<?php icon( 'box' ); ?>

	<div>
		<h6><?php echo esc_html_x( 'In stock', 'infobox', 'chocante' ); ?></h6>
		<?php echo wp_kses_post( wc_get_stock_html( $product ) ); ?>
	</div>
</div>