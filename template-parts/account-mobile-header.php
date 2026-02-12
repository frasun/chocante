<?php
/**
 * Account content header
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

?>

<header class="woocommerce-MyAccount-content__header">
	<button class="woocommerce-MyAccount-content__back-button">
		<?php icon( 'prev' ); ?>
	</button>
	<h2 class="page-title"><?php echo esc_html( $args['page_title'] ); ?></h2>
</header>