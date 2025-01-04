<?php
/**
 * Page content
 *
 * @package WordPress
 * @subpackage Chocante
 */

?>
<main role="main" class="wp-site-blocks is-layout-constrained">
	<?php get_template_part( 'template-parts/page', 'header-temp' ); ?>
	<?php the_content(); ?>
</main>