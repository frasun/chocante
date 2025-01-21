<?php
/**
 * Blog page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main role="main">
	<?php
		$sticky_posts = new WP_Query(
			array(
				'post_type' => 'post',
				'post__in'  => get_option( 'sticky_posts' ),
			)
		);
		?>

	<?php if ( $sticky_posts->have_posts() ) : ?>
		<header class="post-slider-container">
			<?php
			get_template_part(
				'template-parts/slider',
				null,
				array(
					'posts' => $sticky_posts,
				)
			);
			?>
		</header>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<section class="posts">
			<?php if ( $sticky_posts->have_posts() ) : ?>
				<h2 class="page-title"><?php echo esc_html_x( 'Other articles', 'blog', 'chocante' ); ?></h2>
			<?php endif; ?>
			<div class="posts__loop">
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<a class="post" href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( array( 350, 350 ), array( 'class' => 'post__thumbnail' ) );
						}
						the_title( '<h3 class="post__title">', '</h3>' );
						?>
					</a>
				<?php endwhile; ?>
			</div>
			<?php
			ob_start();
			Chocante::icon( 'prev' );
			$prev_icon = ob_get_clean();

			ob_start();
			Chocante::icon( 'next' );
			$next_icon = ob_get_clean();

			the_posts_pagination(
				array(
					'prev_text' => $prev_icon,
					'next_text' => $next_icon,
				)
			);
			?>
		</section>
	<?php endif; ?>
</main>
<?php do_action( 'chocante_after_main' ); ?>
<?php
get_footer();
