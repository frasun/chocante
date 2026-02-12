<?php
/**
 * Widget settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Widgets;

defined( 'ABSPATH' ) || exit;

add_action( 'widgets_init', __NAMESPACE__ . '\register_sidebars' );

/**
 * Register widget areas
 */
function register_sidebars() {
	register_sidebar(
		array(
			'name'           => _x( 'Header - affix', 'admin', 'chocante' ),
			'id'             => 'header-affix',
			'description'    => _x( 'Widgets in this area will be sticky to the top of the page.', 'admin', 'chocante' ),
			'before_widget'  => '',
			'after_widget'   => '',
			'before_sidebar' => '<div class="site-header__affix">',
			'after_sidebar'  => '</div>',
		)
	);

	// Header Top Nav.
	register_sidebar(
		array(
			'name'           => _x( 'Header - top', 'admin', 'chocante' ),
			'id'             => 'header-top',
			'description'    => _x( 'Widgets in this area will be shown in the top part of header.', 'admin', 'chocante' ),
			'before_widget'  => '',
			'after_widget'   => '',
			'before_sidebar' => '<aside class="site-header__top">',
			'after_sidebar'  => '</aside>',
		)
	);

	register_sidebar(
		array(
			'name'           => _x( 'Mobile menu - after main menu', 'admin', 'chocante' ),
			'id'             => 'mobile-menu-after-nav',
			'description'    => _x( 'Widgets in this area will be shown in mobile menu after main navigation menu.', 'admin', 'chocante' ),
			'before_widget'  => '',
			'after_widget'   => '',
			'before_sidebar' => '<aside class="mobile-menu__after-nav">',
			'after_sidebar'  => '</aside>',
		)
	);

	register_sidebar(
		array(
			'name'           => _x( 'Mobile menu - top', 'admin', 'chocante' ),
			'id'             => 'mobile-menu-top',
			'description'    => _x( 'Widgets in this area will be shown in the top part of mobile menu.', 'admin', 'chocante' ),
			'before_widget'  => '',
			'after_widget'   => '',
			'before_sidebar' => '<div class="mobile-menu__top-sidebar">',
			'after_sidebar'  => '</div>',
		)
	);

	register_sidebar(
		array(
			'name'           => _x( 'Footer - bottom left', 'admin', 'chocante' ),
			'id'             => 'footer-bottom-left',
			'description'    => _x( 'Widgets in this area will be shown in the bottom left part of footer.', 'admin', 'chocante' ),
			'before_widget'  => '',
			'after_widget'   => '',
			'before_sidebar' => '<div class="site-footer__bottom-left">',
			'after_sidebar'  => '</div>',
		)
	);

	register_sidebar(
		array(
			'name'           => _x( 'Footer - bottom mobile', 'admin', 'chocante' ),
			'id'             => 'footer-bottom-mobile',
			'description'    => _x( 'Widgets in this area will be shown in the bottom right part of footer.', 'admin', 'chocante' ),
			'before_widget'  => '',
			'after_widget'   => '',
			'before_sidebar' => '<div class="site-footer__bottom-mobile">',
			'after_sidebar'  => '</div>',
		)
	);
}
