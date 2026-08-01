<?php
/**
 * Mobile filter trigger
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<button id="openMobileFilters" data-wp-interactive="chocante/product-filters" data-wp-bind--hidden="!state.hasAvailableFilters" data-wp-init="callbacks.onInit">
	<?php esc_html_e( 'Filter', 'chocante-product-filters' ); ?>
	<span data-wp-bind--hidden="!state.hasActiveFilters" data-wp-text="state.activeFilters"></span>
</button>