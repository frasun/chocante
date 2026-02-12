<?php
/**
 * Product search form
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<aside class="search-products__form">
	<?php
	get_template_part( 'template-parts/modal-close' );

	if ( function_exists( 'get_product_search_form' ) ) {
		get_product_search_form();
	}
	?>
</aside>