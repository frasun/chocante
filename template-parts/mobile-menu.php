<?php
/**
 * Mobile menu
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<nav class="mobile-menu">
	<header class="mobile-menu__top">
		<?php
		get_template_part( 'template-parts/modal-close' );

		if ( is_active_sidebar( 'mobile-menu-top' ) ) {
			dynamic_sidebar( 'mobile-menu-top' );
		}
		?>
	</header>
	<?php
	if ( has_nav_menu( 'chocante_menu_main' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'chocante_menu_main',
				'container'      => false,
				'link_before'    => '<span>',
				'link_after'     => '</span>',
				'menu_class'     => 'menu mobile-menu__nav',
			)
		);
	}

	if ( is_active_sidebar( 'mobile-menu-after-nav' ) ) {
		dynamic_sidebar( 'mobile-menu-after-nav' );
	}
	?>
</nav>
