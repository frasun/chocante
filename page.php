<?php
/**
 * Page
 *
 * @package Chocante
 */

get_header();

$bricks_data = Bricks\Helpers::get_bricks_data( get_the_ID(), 'content' );

if ( $bricks_data ) {
	Bricks\Frontend::render_content( $bricks_data );
} elseif ( have_posts() ) {
	while ( have_posts() ) :
		the_post();
		if ( is_cart() || is_account_page() || is_checkout() ) { ?>
		<main role="main">
			<?php the_content(); ?>
			<?php if ( is_cart() ) : ?>
				<?php do_action( 'chocante_after_cart' ); ?>
			<?php endif; ?>
		</main>
			<?php
		} else {
			get_template_part( 'template-parts/page' );
		}
	endwhile;
}

get_footer();
