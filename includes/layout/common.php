<?php
/**
 * Layout hooks - common
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Common;

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

// Breadcrumbs.
add_action( 'chocante_before_content_header', __NAMESPACE__ . '\display_page_breadcrumbs' );
add_filter( 'woocommerce_breadcrumb_defaults', __NAMESPACE__ . '\modify_breadcrumbs' );
add_filter( 'rank_math/frontend/breadcrumb/args', __NAMESPACE__ . '\modify_breadcrumbs' );

// Layout.
add_action( 'chocante_header', __NAMESPACE__ . '\display_header' );
add_action( 'chocante_header_aside', __NAMESPACE__ . '\display_header_actions' );
add_action( 'chocante_before_footer', __NAMESPACE__ . '\display_join_group' );
add_action( 'chocante_footer', __NAMESPACE__ . '\display_footer' );
add_action( 'chocante_footer', __NAMESPACE__ . '\output_mobile_menu', 20 );
add_action( 'chocante_footer', __NAMESPACE__ . '\output_product_search', 30 );

// Cart & product page.
add_action( 'woocommerce_before_quantity_input_field', __NAMESPACE__ . '\display_remove_quantity_button' );
add_action( 'woocommerce_after_quantity_input_field', __NAMESPACE__ . '\display_add_quantity_button', 20 );
add_filter( 'woocommerce_quantity_input_type', __NAMESPACE__ . '\set_quantity_input_type' );

// Free shipping.
add_action( 'chocante_delivery_info', __NAMESPACE__ . '\display_free_delivery_info' );

// Product search.
add_action( 'pre_get_product_search_form', __NAMESPACE__ . '\display_product_search_title' );
add_filter( 'get_product_search_form', __NAMESPACE__ . '\display_product_search_icon' );

// Product loop.
add_filter( 'woocommerce_loop_add_to_cart_link', __NAMESPACE__ . '\add_to_cart_button', 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', __NAMESPACE__ . '\add_to_cart_text', 10, 2 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 50 );
add_action( 'woocommerce_before_shop_loop_item_title', __NAMESPACE__ . '\add_loop_item_info_open', 30 );
add_action( 'woocommerce_after_shop_loop_item_title', __NAMESPACE__ . '\add_loop_item_info_close', 20 );
add_action( 'woocommerce_after_shop_loop_item', __NAMESPACE__ . '\add_loop_item_info_close', 30 );

// Product & archive page.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
add_action( 'woocommerce_before_main_content', __NAMESPACE__ . '\open_main_element' );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );
add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\close_main_element', 60 );

// Comments / Reviews.
add_filter( 'wp_list_comments_args', __NAMESPACE__ . '\fix_comments_ordering' );
add_action( 'pre_get_comments', __NAMESPACE__ . '\set_comments_query_defaults' );
add_filter( 'get_page_of_comment_query_args', __NAMESPACE__ . '\fix_comment_page_order' );
add_filter( 'previous_comments_link_attributes', __NAMESPACE__ . '\aria_prev_comments' );
add_filter( 'next_comments_link_attributes', __NAMESPACE__ . '\aria_next_comments' );
add_filter( 'comment_form_fields', __NAMESPACE__ . '\reorder_comment_form_fields' );
add_filter( 'woocommerce_review_gravatar_size', __NAMESPACE__ . '\set_avatar_size' );
add_filter( 'get_comment_author', __NAMESPACE__ . '\get_comment_author', 10, 3 );

// Admin.
add_filter( 'show_admin_bar', __NAMESPACE__ . '\hide_admin_bar' );

/**
 * Display account link, mini-cart & product search actions in header
 *
 * @param string $container_id Container CSS class.
 */
function display_header_actions( $container_id ) {
	if ( $container_id ) {
		echo '<aside class="' . esc_attr( $container_id ) . '">';
	}

	get_template_part( 'template-parts/mini-cart' );
	get_template_part( 'template-parts/customer-account-link' );
	get_template_part( 'template-parts/product-search' );

	if ( $container_id ) {
		echo '</aside>';
	}
}

/**
 * Display join Facebook group section
 */
function display_join_group() {
	get_template_part( 'template-parts/join', 'group' );
}

/**
 * Display page breadcrumbs
 */
function display_page_breadcrumbs() {
	if ( is_page_template( 'page-templates/temp.php' ) && function_exists( 'rank_math_the_breadcrumbs' ) ) {
		rank_math_the_breadcrumbs();
	} elseif ( is_singular( 'post' ) ) {
		get_template_part( 'template-parts/breadcrumbs', 'post' );
	}
}

/**
 * Modify breadcrumbs settings
 *
 * @param array $args Breadcrumbs args.
 * @return array
 */
function modify_breadcrumbs( $args ) {
	$args['wrap_before'] = apply_filters( 'chocante_common_breadcrumbs_wrap_before', '<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">' );
	$args['wrap_after']  = '</nav>';
	$args['before']      = '<span>';
	$args['after']       = '</span>';
	$args['delimiter']   = ''; // Woo breadcrumbs.
	$args['separator']   = ''; // RankMath breadcrumbs.

	return $args;
}

/**
 * Return spinner image
 *
 * @param string $atts HTML attributes.
 * @return string
 */
function spinner( $atts = '' ) {
	return sprintf( '<img src="%s" alt="%s" class="spinner" loading="lazy" %s />', esc_url( get_theme_file_uri( 'images/spinner-2x.gif' ) ), esc_attr_x( 'Loading', 'product slider', 'chocante' ), $atts );
}

/**
 * Output product search form
 */
function output_product_search() {
	get_template_part( 'template-parts/product-search', 'form' );
}

/**
 * Output mobile menu
 */
function output_mobile_menu() {
	get_template_part( 'template-parts/mobile-menu' );
}

/**
 * Add title to product search
 */
function display_product_search_title() {
	echo '<h4 class="search-products__title">' . esc_html__( 'Search products', 'woocommerce' ) . '</h4>';
}

/**
 * Add title to product search
 *
 * @param string $form Search form HTML.
 * @return string
 */
function display_product_search_icon( $form ) {
	ob_start();
	echo '<div class="search-products__icon">';
	icon( 'search' );
	echo '</div>';

	return str_replace( '</form>', ob_get_clean() . '</form>', $form );
}

/**
 * Display quantity buttons in cart
 */
function display_add_quantity_button() {
	get_template_part( 'template-parts/quantity', 'plus' );
}

/**
 * Display quantity buttons in cart
 */
function display_remove_quantity_button() {
	get_template_part( 'template-parts/quantity', 'minus' );
}

/**
 * Always set quantity input to type="number"
 */
function set_quantity_input_type() {
	return 'number';
}

/**
 * Display free delivery infor
 */
function display_free_delivery_info() {
	if ( ! class_exists( 'Chocante_Free_Shipping' ) ) {
		return;
	}

	get_template_part(
		'template-parts/info',
		'section',
		array(
			'icon'    => 'shipping',
			'content' => \Chocante_Free_Shipping::instance()->display_free_shipping_info( true ),
		)
	);
}

/**
 * Output product loop item content wrapper open
 */
function add_loop_item_info_open() {
	echo '<div class="woocommerce-loop-product__info-wrapper">';
	echo '<div class="woocommerce-loop-product__info">';
}

/**
 * Output product loop item content wrapper close
 */
function add_loop_item_info_close() {
	echo '</div>';
}

/**
 * Replace add to cart link with button
 *
 * @param string      $link Add to cart url.
 * @param \WC_Product $product Product object.
 * @return string
 */
function add_to_cart_button( $link, $product ) {
	return '<button class="button">' . esc_html( $product->add_to_cart_text() ) . '</button>';
}

/**
 * Replace add to cart text when product is out of stock
 *
 * @param string      $text Add to cart text.
 * @param \WC_Product $product Product object.
 * @return string
 */
function add_to_cart_text( $text, $product ) {
	return $product->is_in_stock() ? _x( 'Buy now', 'product loop', 'chocante' ) : __( 'Read more', 'woocommerce' );
}

/**
 * Open <main> element
 */
function open_main_element() {
	echo '<main role="main">';
}

/**
 * Close <main> element
 */
function close_main_element() {
	echo '</main>';
}

/**
 * Display product badges
 */
function show_product_badge() {
	get_template_part( 'template-parts/product', 'badge' );
}

/**
 * Hide admin bar for users who cannot edit posts
 *
 * @param bool $show_admin_bar Whether the admin bar should be shown. Default false.
 * @return bool
 */
function hide_admin_bar( $show_admin_bar ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return false;
	}

	return $show_admin_bar;
}

/**
 * Display site header
 */
function display_header() {
	get_template_part( 'template-parts/header' );
}

/**
 * Display site footer
 */
function display_footer() {
	get_template_part( 'template-parts/footer' );
}

/**
 * Setup review list options
 *
 * @see: Chocante\Layout\Common\set_comments_ordering
 *
 * @param string|array $args Formatting options.
 * @return string|array
 */
function fix_comments_ordering( $args ) {
	$args['reverse_top_level'] = isset( $args['reverse_top_level'] ) ? ! $args['reverse_top_level'] : ( 'asc' === get_option( 'comment_order' ) );

	return $args;
}

/**
 * Add WCAG directives to previous comments navigation link (newer comments)
 *
 * @param string $atts Link attributes.
 * @return string
 */
function aria_prev_comments( $atts ) {
	$atts .= sprintf( ' aria-label="%s"', __( 'Newer comments' ) );
	return $atts;
}

/**
 * Add WCAG directives to next comments navigation link (older comments)
 *
 * @param string $atts Link attributes.
 * @return string
 */
function aria_next_comments( $atts ) {
	$atts .= sprintf( ' aria-label="%s"', __( 'Older comments' ) );
	return $atts;
}

/**
 * Reorder comment form fields
 *
 * @param array $comment_fields The comment fields.
 * @return array
 */
function reorder_comment_form_fields( $comment_fields ) {
	$fields = array();

	if ( isset( $comment_fields['author'] ) ) {
		$fields['author'] = $comment_fields['author'];
	}

	if ( isset( $comment_fields['email'] ) ) {
		$fields['email'] = $comment_fields['email'];
	}

	$fields['comment'] = $comment_fields['comment'];

	return $fields;
}

/**
 * Set comment query according to admin discussion settings
 *
 * @param \WP_Comment_Query $query Current instance of WP_Comment_Query (passed by reference).
 */
function set_comments_query_defaults( $query ) {
	// Ordering.
	$query->query_vars['order'] = 'DESC';

	// Pagination.
	if ( ! empty( $query->query_vars['number'] ) ) {
		return;
	}

	if ( ! get_option( 'page_comments' ) ) {
		return;
	}

	$per_page = (int) get_option( 'comments_per_page' );

	if ( $per_page < 1 ) {
		return;
	}

	if ( ! empty( $query->query_vars['number'] ) ) {
		$query->query_vars['number'] = $per_page;
	}

	if ( empty( $query->query_vars['offset'] ) ) {
		$query->query_vars['offset'] = 0;
	}
}

/**
 * Fix calculating commetns page used in deeplinking according to comments DESC order
 *
 * @see Chocante\Layout\Common\set_comments_query_defaults
 *
 * @param array $args Array of WP_Comment_Query arguments.
 * @return array
 */
function fix_comment_page_order( $args ) {
	if ( isset( $args['date_query'][0]['before'] ) ) {
		$args['date_query'][0]['after'] = $args['date_query'][0]['before'];
		unset( $args['date_query'][0]['before'] );
	}

	return $args;
}

/**
 * Set user avatar size in px
 *
 * @return string
 */
function set_avatar_size() {
	return '32';
}

/**
 * Display comment/review author name
 *
 * @param string      $comment_author The comment author's username.
 * @param string      $comment_id     The comment ID as a numeric string.
 * @param \WP_Comment $comment        The comment object.
 * @return string
 */
function get_comment_author( $comment_author, $comment_id, $comment ) {
	if ( is_admin() ) {
		return $comment_author;
	}

	$display_name = get_comment_meta( $comment_id, 'author_display_name', true );

	if ( ! empty( $display_name ) ) {
		return $display_name;
	}

	if ( 'comment' !== $comment->comment_type ) {
		return $comment_author;
	}

	$user = ! empty( $comment->user_id ) ? get_userdata( $comment->user_id ) : false;

	if ( $user ) {

		if ( user_can( $user, 'moderate_comments' ) ) {
			$comment_author = get_bloginfo( 'name' );
		} else {
			$comment_author = $user->first_name;
		}
	}

	return $comment_author;
}
