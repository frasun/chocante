<?php
/**
 * Mobile filter trigger
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<button id="openMobileFilters" data-wp-interactive="chocante/shop" data-wp-bind--hidden="chocante/product-filters::!state.hasAvailableFilters" data-wp-init="callbacks.shopInit" data-wp-on--click="actions.openMobileFiltes">
	<?php esc_html_e( 'Filter', 'chocante-product-filters' ); ?>
	<span data-wp-bind--hidden="chocante/product-filters::!state.hasActiveFilters" data-wp-text="chocante/product-filters::state.activeFilters"></span>
</button>