<?php
/**
 * Page footer
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

do_action( 'chocante_before_footer' );
?>

<footer class="site-footer">
	<?php if ( has_nav_menu( 'chocante_footer_products' ) || has_nav_menu( 'chocante_footer_chocante' ) || has_nav_menu( 'chocante_footer_shop' ) ) : ?>
		<div class="site-footer__nav">
			<?php if ( has_nav_menu( 'chocante_footer_products' ) ) : ?>
				<nav class="site-footer__nav-menu site-footer__nav-menu--products">
					<h4 class="site-footer__nav-header"><?php echo esc_html_x( 'Products', 'footer nav', 'chocante' ); ?></h4>
					<?php
						wp_nav_menu(
							array(
								'theme_location' => 'chocante_footer_products',
								'container'      => false,
							)
						);
					?>
				</nav>
			<?php endif; ?>
			<?php if ( has_nav_menu( 'chocante_footer_chocante' ) ) : ?>
				<nav class="site-footer__nav-menu site-footer__nav-menu--chocante">
					<h4 class="site-footer__nav-header"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h4>
					<?php
						wp_nav_menu(
							array(
								'theme_location' => 'chocante_footer_chocante',
								'container'      => false,
							)
						);
					?>
				</nav>
			<?php endif; ?>
			<?php if ( has_nav_menu( 'chocante_footer_shop' ) ) : ?>
				<nav class="site-footer__nav-menu site-footer__nav-menu--shop">
					<h4 class="site-footer__nav-header"><?php echo esc_html_x( 'Shop', 'footer nav', 'chocante' ); ?></h4>
					<?php
						wp_nav_menu(
							array(
								'theme_location' => 'chocante_footer_shop',
								'container'      => false,
							)
						);
					?>
				</nav>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( is_active_sidebar( 'footer-bottom-left' ) || is_active_sidebar( 'footer-bottom-mobile' ) || has_nav_menu( 'chocante_footer_social' ) ) : ?>
		<div class="site-footer__bottom">
			<?php
			if ( is_active_sidebar( 'footer-bottom-left' ) ) {
				dynamic_sidebar( 'footer-bottom-left' );
			}

			wp_nav_menu(
				array(
					'theme_location' => 'chocante_footer_social',
					'container'      => false,
				)
			);

			if ( is_active_sidebar( 'footer-bottom-mobile' ) ) {
				dynamic_sidebar( 'footer-bottom-mobile' );
			}
			?>
		</div>
	<?php endif; ?>

	<aside class="site-footer__copy">
		<div class="site-footer__copy-container">
			<div class="site-footer__copy-text">
				&copy; <strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong> 2020 – <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html_x( 'All Rights Reserved', 'footer', 'chocante' ); ?>
			</div>
			<?php
			if ( has_nav_menu( 'chocante_footer_terms' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'chocante_footer_terms',
						'container'      => false,
					)
				);
			}
			?>
		</div>
	</aside>
</footer>

<?php wp_footer(); ?>
</body>
</html>