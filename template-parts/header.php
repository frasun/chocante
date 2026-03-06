<?php
/**
 * Site header
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<header id="siteHeader" class="site-header">
	<?php
	if ( is_active_sidebar( 'header-affix' ) ) {
		dynamic_sidebar( 'header-affix' );
	}
	?>
	<div class="site-header__main" data-header-scroll="true">
	<div class="site-header__container">
		<?php get_template_part( 'template-parts/menu-top' ); ?>

		<div class="site-header__menu">
		<button class="site-header__toggle" aria-label="<?php esc_html_e( 'Toggle menu', 'chocante' ); ?>" aria-expanded="false" aria-controls="mobileMenu">
			<?php icon( 'menu-toggle' ); ?>
		</button>

		<?php
		if ( has_custom_logo() ) {
			the_custom_logo();
		}

		if ( has_nav_menu( 'chocante_menu_main' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'chocante_menu_main',
					'container'      => false,
					'link_before'    => '<span>',
					'link_after'     => '</span>',
					'menu_class'     => 'menu site-header__nav',
				)
			);
		}

		do_action( 'chocante_header_aside', 'site-header__aside' );
		?>
		</div>
	</div>
	</div>
</header>