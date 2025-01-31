<?php
/**
 * Chocante Theme
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante class.
 */
class Chocante {
	/**
	 * Class constructor.
	 */
	public static function init() {
		// Setup.
		add_action( 'after_setup_theme', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'add_theme_support' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'register_nav_menus' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_sidebars' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );

		// Custom logo.
		add_action( 'after_setup_theme', array( __CLASS__, 'support_custom_logo' ) );
		add_filter( 'get_custom_logo_image_attributes', array( __CLASS__, 'set_custom_logo_attributes' ), 10, 2 );

		// Menu.
		// add_filter( 'nav_menu_css_class', array( __CLASS__, 'set_menu_item_class' ), 10, 2 );?
		// add_filter( 'nav_menu_submenu_css_class', array( __CLASS__, 'set_submenu_class' ) );?
		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( __CLASS__, 'output_mobile_menu' ), 20 );
			add_filter( 'nav_menu_item_title', array( __CLASS__, 'social_media_icons' ), 10, 3 );
		}

		// Footer.
		add_action( 'chocante_before_footer', array( __CLASS__, 'display_join_group' ) );

		// Images.
		add_action( 'after_setup_theme', array( __CLASS__, 'support_thumbnails' ) );
		if ( ! is_admin() ) {
			add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'set_image_lazy_loading' ) );
		}

		// WooCommerce.
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			require_once __DIR__ . '/class-chocante-woocommerce.php';
			Chocante_WooCommerce::init();
		}

		// Blog.
		add_action( 'pre_get_posts', array( __CLASS__, 'exclude_sticky_posts' ) );
		add_filter( 'excerpt_more', array( __CLASS__, 'set_excerpt_more' ) );
		if ( class_exists( 'Chocante_Product_Section' ) ) {
			add_action( 'chocante_after_main', array( __CLASS__, 'display_featured_products_slider_on_blog_page' ) );
		}

		// Post.
		add_filter( 'the_content', array( __CLASS__, 'display_post_header' ) );

		// Breadcrumbs.
		add_action( 'after_theme_setup', array( __CLASS__, 'support_rank_math_breadcrumbs' ) );
		add_action( 'chocante_before_content_header', array( __CLASS__, 'display_page_breadcrumbs' ) );

		// Editor.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );

		// Shortcodes.
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );
	}

	/**
	 * Load textdomain
	 */
	public static function load_textdomain() {
		load_theme_textdomain( 'chocante', get_theme_file_path( 'languages' ) );
	}

	/**
	 * WP features support
	 */
	public static function add_theme_support() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'post-formats', array( 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat' ) );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'block-template-parts' );
		add_theme_support( 'custom-line-height' );
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Log values to wp-content/debug.log
	 *
	 * @param mixed $data Value to log.
	 */
	public static function debug_log( $data ) {
		/* phpcs:disable */
		if ( true === WP_DEBUG ) {
			if(!isset($data)) {
				error_log('null');
			} elseif ( is_array( $data ) || is_object( $data ) ) {
				error_log( print_r( $data, true ) );
			} else {
				error_log( $data );
			}
		}
		/* phpcs:enable */
	}

	/**
	 * Register menu locations
	 */
	public static function register_nav_menus() {
		register_nav_menus(
			array(
				'chocante_menu_main'       => _x( 'Main menu', 'menu', 'chocante' ),
				'chocante_footer_chocante' => _x( 'Footer - Chocante', 'menu', 'chocante' ),
				'chocante_footer_products' => _x( 'Footer - Products', 'menu', 'chocante' ),
				'chocante_footer_shop'     => _x( 'Footer - Shop', 'menu', 'chocante' ),
				'chocante_footer_social'   => _x( 'Footer - Social media', 'menu', 'chocante' ),
				'chocante_footer_terms'    => _x( 'Footer - Terms', 'menu', 'chocante' ),
			)
		);
	}

	/**
	 * Add custom logo
	 */
	public static function support_custom_logo() {
		add_theme_support( 'custom-logo' );
	}

	/**
	 * Modify custom logo
	 *
	 * @param array $logo_atts Custom logo attributes.
	 * @param array $image_id Logo image ID.
	 * @return array
	 */
	public static function set_custom_logo_attributes( $logo_atts, $image_id ) {
		$logo = wp_get_attachment_metadata( $image_id );

		if ( $logo ) {
			$logo_atts['width']  = $logo['width'];
			$logo_atts['height'] = $logo['height'];
		}

		return $logo_atts;
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		// Common.
		$styles = include get_theme_file_path( 'build/chocante.asset.php' );

		wp_enqueue_style(
			'chocante-css',
			get_theme_file_uri( 'build/chocante.css' ),
			$styles['dependencies'],
			$styles['version'],
		);

		// style.css.
		wp_enqueue_style(
			'chocante-style',
			get_theme_file_uri( 'style.css' )
		);

		$scripts = include get_theme_file_path( 'build/chocante-scripts.asset.php' );

		wp_enqueue_script(
			'chocante-js',
			get_theme_file_uri( 'build/chocante-scripts.js' ),
			array_merge( $scripts['dependencies'], array( 'wc-cart-fragments', 'splide-js' ) ),
			$scripts['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Splide.
		wp_enqueue_script(
			'splide-js',
			'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js',
			array(),
			'1.4',
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Blog.
		if ( is_home() ) {
			$blog_styles = include get_theme_file_path( 'build/blog.asset.php' );

			wp_enqueue_style(
				'blog-css',
				get_theme_file_uri( 'build/blog.css' ),
				$blog_styles['dependencies'],
				$blog_styles['version'],
			);
		}

		// Single post.
		if ( is_singular( 'post' ) ) {
			$post_styles = include get_theme_file_path( 'build/single-post.asset.php' );

			wp_enqueue_style(
				'post-css',
				get_theme_file_uri( 'build/single-post.css' ),
				$post_styles['dependencies'],
				$post_styles['version'],
			);
		}
	}

	/**
	 * Register widget areas
	 */
	public static function register_sidebars() {
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

	/**
	 * Insert SVG icon
	 *
	 * @param string $filename Filename from /icons directory.
	 */
	public static function icon( $filename ) {
		$file = get_theme_file_path( "icons/icon-{$filename}.svg" );

		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	/**
	 * Insert spinner
	 */
	public static function spinner() {
		echo '<img src="' . esc_url( get_theme_file_uri( 'images/spinner-2x.gif' ) ) . '" alt="' . esc_attr_x( 'Loading', 'product slider', 'chocante' ) . '" class="spinner">';
	}

	/**
	 * Clean menu list item classes
	 *
	 * @param array $classes List of menu item classes.
	 * @return array
	 */
	public static function set_menu_item_class( $classes ) {
		return array_intersect( array( 'menu-item-has-children' ), $classes );
	}

	/**
	 * Clean sub-menu classes
	 *
	 * @param array $classes List of sub-menu classes.
	 * @return array
	 */
	public static function set_submenu_class( $classes ) {
		$classes = array();

		return $classes;
	}

	/**
	 * Output mobile menu
	 */
	public static function output_mobile_menu() {
		get_template_part( 'template-parts/mobile-menu' );
	}

	/**
	 * Display page title
	 */
	public static function get_page_title() {
		$title = '<h1>' . get_the_title() . '</h1>';

		echo wp_kses_post( apply_filters( 'chocante_page_title', $title, get_the_title() ) );
	}

	/**
	 * Add lazy loading to images.
	 *
	 * @param array $atts Image attributes.
	 * @return array
	 */
	public static function set_image_lazy_loading( $atts ) {
		$atts['loading'] = 'lazy';

		return $atts;
	}

	/**
	 * Display join Facebook group section
	 */
	public static function display_join_group() {
		get_template_part( 'template-parts/join', 'group' );
	}

	/**
	 * Display social media icons instead of title
	 *
	 * @param string   $title     The menu item's title.
	 * @param WP_Post  $menu_item The current menu item object.
	 * @param stdClass $args      An object of wp_nav_menu() arguments.
	 * @return string
	 */
	public static function social_media_icons( $title, $menu_item, $args ) {
		if ( 'chocante_footer_social' === $args->theme_location ) {
			ob_start();
			self::icon( $menu_item->post_name );
			$icon = ob_get_clean();

			if ( ! empty( $icon ) ) {
				return $icon;
			}
		}

		return $title;
	}

	/**
	 * Support post thumbnails
	 */
	public static function support_thumbnails() {
		add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Exclude sticky posts from main blog query
	 *
	 * @param WP_Query $query Query.
	 */
	public static function exclude_sticky_posts( $query ) {
		if ( $query->is_home() && $query->is_main_query() ) {
			$query->set( 'post__not_in', get_option( 'sticky_posts' ) );
		}
	}

	/**
	 * Change the excerpt more string
	 */
	public static function set_excerpt_more() {
		return '&hellip;';
	}

	/**
	 * Display featured products on blog home page
	 */
	public static function display_featured_products_slider_on_blog_page() {
		if ( is_home() ) {
			self::display_featured_products_slider();
		}
	}

	/**
	 * Display featured products slider
	 */
	public static function display_featured_products_slider() {
		Chocante_Product_Section::class::display_product_section(
			array(
				'heading'    => _x( 'Featured products', 'product slider', 'chocante' ),
				'subheading' => _x( 'Learn more about our offer', 'product slider', 'chocante' ),
				'cta_link'   => wc_get_page_permalink( 'shop' ),
			)
		);
	}

	/**
	 * Use Rank Math breadcrumbs
	 */
	public static function support_rank_math_breadcrumbs() {
		add_theme_support( 'rank-math-breadcrumbs' );
	}

	/**
	 * Display page breadcrumbs
	 */
	public static function display_page_breadcrumbs() {
		if ( is_page_template( 'page-templates/temp.php' ) && function_exists( 'rank_math_the_breadcrumbs' ) ) {
			rank_math_the_breadcrumbs();
		} elseif ( is_singular( 'post' ) ) {
			get_template_part( 'template-parts/breadcrumbs', 'post' );
		}
	}

	/**
	 * Add assets to Gutenberg editor
	 */
	public static function enqueue_editor_assets() {
		// Editor specific.
		$editor_styles = include get_theme_file_path( 'build/editor.asset.php' );

		wp_enqueue_style(
			'chocante-editor-css',
			get_theme_file_uri( 'build/editor.css' ),
			$editor_styles['dependencies'],
			$editor_styles['version'],
		);

		$ediotr_scripts = include get_theme_file_path( 'build/editor-scripts.asset.php' );

		wp_enqueue_script(
			'chocante-editor-js',
			get_theme_file_uri( 'build/editor-scripts.js' ),
			array(
				'wp-blocks',
				'wp-dom-ready',
				'wp-edit-post',
			),
			$ediotr_scripts['version'],
			true
		);
	}

	/**
	 * Display single post content header
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function display_post_header( $content ) {
		if ( is_singular( 'post' ) && in_the_loop() && is_main_query() ) {
			ob_start();
			get_template_part( 'template-parts/post-header', '', array( 'content' => $content ) );
			return ob_get_clean() . $content;
		}

		return $content;
	}

	/**
	 * Return estimated content reading time
	 *
	 * @param string $content Text content.
	 * @param int    $words_per_minute Number of words per minute.
	 * @return float
	 */
	public static function get_reading_time( $content, $words_per_minute = 200 ) {
		$total_words = count( preg_split( '~[^\p{L}\p{N}\']+~u', wp_strip_all_tags( $content ) ) );

		return floor( $total_words / $words_per_minute );
	}

	/**
	 * Add shortcodes for template parts
	 */
	public static function add_shortcodes() {
		// [chocante_post_slider] shortcode.
		add_shortcode(
			'chocante_post_slider',
			function () {
				ob_start();
				get_template_part( 'template-parts/post-slider' );
				return ob_get_clean();
			}
		);
	}
}
