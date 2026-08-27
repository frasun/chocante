<?php
/**
 * WooCommerce settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Woo;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\Scheduler;
use Chocante\Product_Reviews_Feed;

const REVIEW_REQUEST_LIMIT = 2;
const REVIEW_REQUEST_META  = '_chocante_review_request_count';

// Modify price display.
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\set_price_display_modify' );
add_action( 'chocante_product_section_loop', __NAMESPACE__ . '\set_price_display_modify' );
add_filter( 'woocommerce_get_price_suffix', __NAMESPACE__ . '\add_price_suffix', 10, 4 );
add_filter( 'woocommerce_format_price_range', __NAMESPACE__ . '\modify_price_range', 10, 3 );
add_filter( 'woocommerce_variable_price_html', __NAMESPACE__ . '\add_price_range_prefix', 10, 2 );

// Product search.
add_filter( 'get_product_search_form', __NAMESPACE__ . '\change_product_search_action' );
add_action( 'template_redirect', __NAMESPACE__ . '\redirect_product_search' );
add_filter( 'query_vars', __NAMESPACE__ . '\register_product_search_var' );
add_action( 'parse_query', __NAMESPACE__ . '\use_product_search_var' );
add_filter( 'get_search_query', __NAMESPACE__ . '\use_product_search_in_query' );

// Shipping methods.
add_filter( 'woocommerce_shipping_methods', __NAMESPACE__ . '\add_shipping_methods' );
add_filter( 'woocommerce_cart_shipping_method_full_label', __NAMESPACE__ . '\display_delivery_time', 20, 2 );

// Gift wrapper.
add_filter( 'tgpc_wc_gift_wrapper_icon_html', __NAMESPACE__ . '\disable_gift_wrapper_icon_in_admin' );
add_filter( 'tgpc_wc_gift_wrapper_checkout_label', __NAMESPACE__ . '\display_gift_wrapper_label', 10, 3 );

// Product reviews.
add_action( 'comment_post', __NAMESPACE__ . '\add_metadata_to_product_review' );
add_action( 'woocommerce_review_order_submitted', __NAMESPACE__ . '\add_metadata_to_order_review', 10, 2 );
add_filter( 'preprocess_comment', __NAMESPACE__ . '\ajax_set_comment_type', 1 );
add_action( 'wp_ajax_submit_review', __NAMESPACE__ . '\submit_review' );
add_action( 'wp_ajax_nopriv_submit_review', __NAMESPACE__ . '\submit_review' );
add_filter( 'allow_empty_comment', __NAMESPACE__ . '\allow_rating_only_for_verified_buyers', 10, 2 );
add_action( 'updated_comment_meta', __NAMESPACE__ . '\sync_rating_after_review_update', 10, 3 );
add_filter( 'woocommerce_product_reviews_list_table_prepare_items_args', __NAMESPACE__ . '\admin_search_reviews_by_id' );
add_action( Scheduler::ACTION_HOOK, __NAMESPACE__ . '\reschedule_order_review_request' );
add_action( 'wp_update_comment_count', __NAMESPACE__ . '\schedule_product_reviews_feed' );
add_action( 'chocante_generate_product_reviews_feed', __NAMESPACE__ . '\generate_product_reviews_feed' );

/**
 * Fix PHP notice in widgets page
 *
 * @link https://github.com/WordPress/gutenberg/issues/33576#issuecomment-883690807
 */
remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );

/**
 * Set global variable to modify price display
 */
function set_price_display_modify() {
	global $chocante_display_price_modify;
	$chocante_display_price_modify = true;
}

/**
 * Add variation suffix to product price
 *
 * @param string      $suffix System price suffix.
 * @param \WC_Product $product Product object.
 */
function add_price_suffix( $suffix, $product ) {
	global $chocante_display_price_modify;

	if ( ! $chocante_display_price_modify ) {
		return $suffix;
	}

	if ( $product instanceof \WC_Product_Variable ) {
		$visible_variations = $product->get_visible_children();

		if ( empty( $visible_variations ) ) {
			return $suffix;
		}

		$variation_id   = reset( $visible_variations );
		$variation_name = get_variation_name( wc_get_product( $variation_id ) );

		if ( $variation_name ) {
			$variation_display_name = apply_filters( 'chocante_product_variation_name', $variation_name );
			$suffix                .= " <small class='woocommerce-price-suffix'>/ {$variation_display_name}</small>";
		}
	}

	return $suffix;
}

/**
 * Format price range display on product listing
 *
 * @param string $price Product price html.
 * @param string $from Price range from value.
 */
function modify_price_range( $price, $from ) {
	global $chocante_display_price_modify;

	if ( ! $chocante_display_price_modify ) {
		return $price;
	}

	return wc_price( $from );
}

/**
 * Add prefix to price range
 *
 * @param string               $price_html Privce element.
 * @param \WC_Product_Variable $product Product object.
 * @return string
 */
function add_price_range_prefix( $price_html, $product ) {
	global $chocante_display_price_modify;

	if ( ! $chocante_display_price_modify ) {
		return $price_html;
	}

	$prices = $product->get_variation_prices( true );

	if ( empty( $prices['price'] ) ) {
		return $price_html;
	}

	$min_price = current( $prices['price'] );
	$max_price = end( $prices['price'] );

	if ( $min_price === $max_price ) {
		return $price_html;
	}

	return _x( 'From', 'price range prefix', 'chocante' ) . ' ' . $price_html;
}

/**
 * Add Globkurier to shipping methods
 *
 * @param array $shipping_methods Shipping methods.
 * @return array
 */
function add_shipping_methods( $shipping_methods ) {
	$shipping_methods['globkurier']        = 'Globkurier_Shipping';
	$shipping_methods['chocante_blpaczka'] = 'BLPaczka_Shipping';

	return $shipping_methods;
}

/**
 * Disable gift wrapping icon in admin
 *
 * @return string
 */
function disable_gift_wrapper_icon_in_admin() {
	return '';
}

/**
 * Modify gift wrapper checkbox label
 *
 * @param string $label The input label as html.
 * @param string $label_icon The html of the icon.
 * @param string $label_text The escaped text of the label.
 * @return string
 */
function display_gift_wrapper_label( $label, $label_icon, $label_text ) {
	return $label_text;
}

/**
 * Has postcode validation
 *
 * @see: WC_Validation::is_postcode
 *
 * @param string $country Country code.
 * @return bool
 */
function has_postcode_validation( $country ) {
	$can_validate = array( 'AT', 'BE', 'CH', 'HU', 'NO', 'BA', 'BR', 'DE', 'DK', 'ES', 'FI', 'EE', 'FR', 'IT', 'GB', 'IE', 'IN', 'JP', 'PT', 'PR', 'US', 'CA', 'PL', 'CZ', 'SE', 'SK', 'NL', 'SI', 'LI' );
	return in_array( $country, $can_validate, true );
}

/**
 * Gets display label of the first variation term
 *
 * @param \WC_Product_Variation $product Variation product object.
 * @return string|false
 */
function get_variation_name( $product ) {
	if ( ! $product instanceof \WC_Product_Variation ) {
		return false;
	}

	$variation_attributes = $product->get_variation_attributes( false );
	$variation_term       = get_term_by( 'slug', reset( $variation_attributes ), array_key_first( $variation_attributes ) );

	if ( ! $variation_term ) {
		return false;
	}

	$variation_name = apply_filters( 'chocante_product_variation_name', $variation_term->name );

	return $variation_name;
}

/**
 * Additional hook in WooCommerce breadcrumbs
 */
function display_shop_breadcrumbs() {
	ob_start();

	woocommerce_breadcrumb();
	$breadcrumbs = apply_filters( 'chocante_shop_breadcrumbs', ob_get_clean() );

	echo wp_kses_post( $breadcrumbs );
}

/**
 * Display order line item quantity
 *
 * @param \WC_Product $product Product object.
 * @param int         $quantity Quantity.
 * @return string
 */
function get_order_item_quantity( $product, $quantity ) {
	$quantity_label = sprintf( '&times; %s', $quantity );
	$variation_name = get_variation_name( $product );

	if ( $variation_name ) {
		$quantity_label = sprintf( '%s &times; %s', $quantity, $variation_name );
	}

	return '<span class="product-quantity">' . $quantity_label . '</span>';
}

/**
 * Modify product search form - use shop url and filter_query param
 *
 * @param string $form Search form html.
 * @return string
 */
function change_product_search_action( $form ) {
	$shop_url = wc_get_page_permalink( 'shop' );
	$form     = str_replace(
		'action="' . esc_url( home_url( '/' ) ) . '"',
		'action="' . esc_url( $shop_url ) . '"',
		$form
	);

	$form = str_replace(
		'name="s"',
		'name="filter_query"',
		$form
	);

	return $form;
}

/**
 * Redirect product search to shop path
 */
function redirect_product_search() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['s'] ) ) {

		$shop_url = wc_get_page_permalink( 'shop' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );

		$redirect_url = add_query_arg(
			array(
				'filter_query' => rawurlencode( $search_query ),
				'post_type'    => 'product',
			),
			$shop_url
		);

		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}
}

/**
 * Register new query var for product search
 *
 * @param string[] $vars The array of allowed query variable names.
 * @return string[]
 */
function register_product_search_var( $vars ) {
	$vars[] = 'filter_query';
	return $vars;
}

/**
 * Use product search query var to search
 *
 * @param \WP_Query $query The WP_Query instance (passed by reference).
 */
function use_product_search_var( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
		if ( isset( $query->query_vars['filter_query'] ) && ! empty( $query->query_vars['filter_query'] ) ) {
			$query->set( 's', $query->query_vars['filter_query'] );
			$query->is_search = true;
		}
	}
}

/**
 * Use product search param in search query
 *
 * @param mixed $query Contents of the search query variable.
 * @return mixed
 */
function use_product_search_in_query( $query ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['filter_query'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET['filter_query'] ) );
	}

	return $query;
}

/**
 * Display delivery time for shipping methods
 *
 * @param string            $label Shipping method label HTML.
 * @param \WC_Shipping_Rate $method Shipping method instance.
 * @return string
 */
function display_delivery_time( $label, $method ) {
	$delivery_time = $method->get_delivery_time();

	if ( ! empty( $delivery_time ) ) {
		$delivery_time_text = sprintf( '%s %s', $delivery_time, __( 'days', 'woocommerce' ) );
		return $label . '<small class="chocante-delivery-time">' . esc_html( $delivery_time_text ) . '</small>';
	}

	return $label;
}

/**
 * Add meta to product review
 *
 * @param int $comment_id Comment ID.
 */
function add_metadata_to_product_review( $comment_id ) {
	$comment = get_comment( $comment_id );

	if ( 'product' !== get_post_type( $comment->comment_post_ID ) || 'review' !== $comment->comment_type ) {
		return;
	}

	$customer = wc()->customer;

	add_comment_meta( $comment_id, 'country', $customer->get_billing_country(), true );

	$name = $customer->get_billing_first_name();

	if ( ! empty( $name ) ) {
		add_comment_meta( $comment_id, 'author_display_name', $name, true );
	}
}

/**
 * Add meta to order review
 *
 * @see Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler::process_rows
 *
 * @param \WC_Order $order   The order.
 * @param array     $results Per-row outcomes — see `SubmissionHandler::process_rows()`.
 */
function add_metadata_to_order_review( $order, $results ) {
	foreach ( $results as $review ) {
		$comment_id = $review['comment_id'];

		if ( ! $comment_id ) {
			continue;
		}

		add_comment_meta( $comment_id, 'author_display_name', $order->get_billing_first_name(), true );
		add_comment_meta( $comment_id, 'country', $order->get_billing_country(), true );
	}
}

/**
 * Set comment type as 'review'. Needed for ajax submission.
 *
 * @see WC_Comments::update_comment_type
 *
 * @param array $comment_data Comment data.
 * @return array
 */
function ajax_set_comment_type( $comment_data ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'submit_review' === $_POST['action'] ) {
		$comment_data['comment_type'] = 'review';
	}

	return $comment_data;
}

/**
 * Submit product review
 */
function submit_review() {
	if ( ! check_ajax_referer( 'chocante_product_review' ) ) {
		wp_send_json_error(
			array(
				'message' => 'invalid_nonce',
			),
			401
		);
	}

	/**
	 * Add rating validation
	 *
	 * @see WC_Comments::check_comment_rating
	 */
	if ( wc_review_ratings_enabled() && wc_review_ratings_required() && empty( $_POST['rating'] ) ) {
		wp_send_json_error(
			array(
				'message' => 'require_rating',
			),
			400
		);
	}

	/**
	 * Needed to work in AJAX
	 *
	 * @see WC_Comments::validate_product_review_verified_owners
	 */
	add_filter( 'wp_die_ajax_handler', __NAMESPACE__ . '\ajax_handle_unverfied_reviews' );

	$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );

	remove_filter( 'wp_die_ajax_handler', __NAMESPACE__ . '\ajax_handle_unverfied_reviews' );

	if ( is_wp_error( $comment ) ) {
		$error = $comment->get_error_code();

		wp_send_json_error(
			array(
				'message' => $error,
			),
			get_error_status( $error )
		);
	}

	$message  = 'comment_save_success';
	$location = get_comment_link( $comment );

	if ( 'unapproved' === wp_get_comment_status( $comment ) ) {
		$message = 'comment_save_unapproved';

		if ( ! empty( $comment->comment_author_email ) ) {
			$location = add_query_arg(
				array(
					'unapproved'      => $comment->comment_ID,
					'moderation-hash' => wp_hash( $comment->comment_date_gmt ),
				),
				$location
			);
		}
	}

	wp_send_json_success(
		array(
			'message'    => $message,
			'redirectTo' => $location,
		)
	);
}

/**
 * Get review error status
 *
 * @param string $code Error code.
 * @return int
 */
function get_error_status( $code ) {
	switch ( $code ) {
		case 'comment_reply_to_unapproved_comment':
		case 'comment_closed':
		case 'comment_on_draft':
		case 'not_logged_in':
			$status = 403;
			break;
		case 'comment_id_not_found':
		case 'comment_on_trash':
		case 'comment_on_password_protected':
			$status = 404;
			break;
		case 'require_name_email':
		case 'require_valid_email':
		case 'require_valid_comment':
		case 'comment_content_column_length':
		case 'comment_author_column_length':
		case 'comment_author_email_column_length':
		case 'comment_author_url_column_length':
			$status = 400;
			break;
		case 'comment_duplicate':
			$status = 409;
			break;
		case 'comment_flood':
			$status = 429;
			break;
		case 'comment_save_error':
		default:
			$status = 500;
	}

	return $status;
}

/**
 * Handle unverfied reviews error inside AJAX product review submission
 */
function ajax_handle_unverfied_reviews() {
	remove_filter( 'wp_die_ajax_handler', __NAMESPACE__ . '\ajax_handle_unverfied_reviews' );

	return function ( $message, $title = '', $args = array() ) {
		wp_send_json_error( array( 'message' => $message ), $args['code'] ?? 500 );
	};
}

/**
 * Filters whether an empty comment should be allowed.
 *
 * @since 5.1.0
 *
 * @param bool  $allow_empty_comment Whether to allow empty comments. Default false.
 * @param array $commentdata         Array of comment data to be sent to wp_insert_comment().
 */
function allow_rating_only_for_verified_buyers( $allow_empty_comment, $commentdata ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! ( wp_doing_ajax() && isset( $_POST['action'] ) && 'submit_review' === $_POST['action'] ) ) {
		return $allow_empty_comment;
	}

	if ( ! ( wc_review_ratings_enabled() && wc_review_ratings_required() ) ) {
		return $allow_empty_comment;
	}

	if ( wc_customer_bought_product( '', $commentdata['user_id'], $commentdata['comment_post_ID'] ) ) {
		return true;
	}

	return $allow_empty_comment;
}

/**
 * Refresh avg. product rating after review update
 *
 * @param int    $meta_id     ID of updated metadata entry.
 * @param int    $comment_id   ID of the object metadata is for.
 * @param string $meta_key    Metadata key.
 */
function sync_rating_after_review_update( $meta_id, $comment_id, $meta_key ) {
	if ( 'rating' !== $meta_key ) {
		return;
	}

	$comment = get_comment( $comment_id );

	if ( ! $comment ) {
		return;
	}

	wp_update_comment_count_now( $comment->comment_post_ID );
}

/**
 * Add ability in admin to search by review ID
 *
 * @param array $args Comment query args.
 * @return array
 */
function admin_search_reviews_by_id( $args ) {
	if ( ! empty( $args['search'] ) && is_numeric( $args['search'] ) ) {
		$args['comment__in'] = array( (int) $args['search'] );
		unset( $args['search'] );
	}

	return $args;
}

/**
 * Re-schedule customer order review request email
 *
 * @see Automattic\WooCommerce\Internal\OrderReviews\Scheduler::handle_woocommerce_order_status_completed
 *
 * @param int $order_id Order ID.
 */
function reschedule_order_review_request( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( ! $order instanceof \WC_Order ) {
		return;
	}

	$review_request_count = $order->get_meta( REVIEW_REQUEST_META );
	if ( ! $review_request_count ) {
		$order->update_meta_data( REVIEW_REQUEST_META, 1 );
		$order->save();
		return;
	}

	if ( $review_request_count >= REVIEW_REQUEST_LIMIT ) {
		return;
	}

	$mailer = WC()->mailer();
	if ( ! $mailer ) {
		return;
	}

	$emails         = $mailer->get_emails();
	$email_template = $emails['WC_Email_Customer_Review_Request'] ?? null;

	$email = $email_template instanceof \WC_Email_Customer_Review_Request ? $email_template : null;

	if ( null === $email || ! $email->is_enabled() ) {
		return;
	}

	$should_send = (bool) apply_filters( 'woocommerce_should_send_review_request', true, $order );
	if ( ! $should_send ) {
		return;
	}

	if ( ! ItemEligibility::has_actionable_items( $order ) ) {
		return;
	}

	$when = time() + $email->get_delay_seconds();
	as_schedule_single_action( $when, Scheduler::ACTION_HOOK, array( $order_id ) );

	$order->update_meta_data( Scheduler::SCHEDULED_META_KEY, (string) $when );
	$order->update_meta_data( REVIEW_REQUEST_META, $review_request_count + 1 );
	$order->save();
}

/**
 * Schedule product reviews generation
 *
 * @param int $post_id Comment post ID.
 */
function schedule_product_reviews_feed( $post_id ) {
	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! wp_next_scheduled( 'chocante_generate_product_reviews_feed' ) ) {
		wp_schedule_single_event( time() + 30, 'chocante_generate_product_reviews_feed' );
	}
}

/**
 * Generate product reviews feed
 */
function generate_product_reviews_feed() {
	$feed = Product_Reviews_Feed::get_feed();
	$dom  = Product_Reviews_Feed::build_xml_feed( $feed );

	Product_Reviews_Feed::save( $dom );
}
