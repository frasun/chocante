<?php
/**
 * Single post content template
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<article>
	<?php
	get_template_part( 'template-parts/content/content-header', '', array( 'class' => 'has-background' ) );

	Chocante_Product_Section::display_product_section(
		array(
			'heading'    => _x( 'Learn more about our offer', 'product slider', 'chocante' ),
			'subheading' => _x( 'Featured products', 'product slider', 'chocante' ),
			'cta_link'   => wc_get_page_permalink( 'shop' ),
		)
	);
	?>

	<div class="wp-site-blocks is-layout-constrained has-global-padding">
		<?php the_content(); ?>
	</div>
</article>

<?php
$category = get_term_by( 'slug', 'kakao-ceremonialne', 'product_cat' );

if ( false !== $category ) {
	Chocante_Product_Section::display_product_section(
		array(
			'heading'    => $category->name,
			'subheading' => _x( 'Learn more about our offer', 'product slider', 'chocante' ),
			'cta_link'   => esc_url( get_category_link( $category->term_id ) ),
			'category'   => array( apply_filters( 'wpml_object_id', $category->term_id, 'product_cat' ) ),
		)
	);
}
?>