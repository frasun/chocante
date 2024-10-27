<?php
/**
 * Product search form
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<aside class="search-products__form">
	<?php
	get_template_part( 'template-parts/modal-close' );
	get_product_search_form();
	?>
</aside>