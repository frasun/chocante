<?php
/**
 * Translation settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Translations;

defined( 'ABSPATH' ) || exit;

use const Chocante\Layout\ACF\ACF_PRODUCT_TITLE;
use const Chocante\Layout\ACF\ACF_PRODUCT_TYPE;
use function Chocante\Woo\get_variation_name;

add_filter( 'chocante_product_section_script_data', __NAMESPACE__ . '\add_language_to_product_section_script' );

// Translation blocks.
const TRANSLATION_BLOCK = 'translation-block';

add_filter( 'render_block_core/paragraph', __NAMESPACE__ . '\set_translation_block' );
add_filter( 'render_block_core/list-item', __NAMESPACE__ . '\set_translation_block' );
add_filter( 'render_block_core/heading', __NAMESPACE__ . '\set_translation_block' );
add_filter( 'woocommerce_short_description', __NAMESPACE__ . '\set_translation_blocks' );
add_filter( 'chocante_shop_product_category_description', __NAMESPACE__ . '\set_translation_blocks', 20 );
add_filter( 'the_content', __NAMESPACE__ . '\set_translation_blocks' );
add_filter( 'the_excerpt', __NAMESPACE__ . '\set_translation_blocks' );
add_filter( 'woocommerce_attribute', __NAMESPACE__ . '\split_product_attributes', 10, 3 );

// Exclude from translation.
const NO_TRANSLATE_ATTR = 'data-no-translation';

add_filter( 'chocante_product_variation_name', __NAMESPACE__ . '\no_translate_units' );
add_filter( 'chocante_acf_product_data_value', __NAMESPACE__ . '\no_translate_units' );
add_filter( 'chocante_product_variation_name', __NAMESPACE__ . '\no_translate_percentages' );
add_filter( 'chocante_acf_product_data_value', __NAMESPACE__ . '\no_translate_percentages' );
add_filter( 'chocante_common_breadcrumbs_wrap_before', __NAMESPACE__ . '\no_translate_breadcrums_aria' );
add_filter( 'chocante_shop_breadcrumbs', __NAMESPACE__ . '\no_translate_breadcrums' );
add_filter( 'trp_no_translate_selectors', __NAMESPACE__ . '\no_translate_selectors' );

// Exclude gettext from processing.
add_filter( 'trp_skip_gettext_processing', __NAMESPACE__ . '\no_process_gettext_domains', 10, 4 );

// Exclude from auto translation.
const NO_AUTO_TRANSLATE_ATTR = 'data-no-auto-translation';

add_filter( 'chocante_acf_product_title', __NAMESPACE__ . '\no_auto_translate_product_name', 10, 2 );
add_filter( 'trp_no_auto_translate_selectors', __NAMESPACE__ . '\no_auto_translate_selectors' );

// Language switcher.
add_action( 'init', __NAMESPACE__ . '\add_language_switcher_shortcode' );

// Plugin i18n.
add_filter( 'woocommerce_available_variation', __NAMESPACE__ . '\i18n_woo_variation' );
add_filter( 'woocommerce_cart_shipping_method_full_label', __NAMESPACE__ . '\i18n_shipping_method', 10, 2 );
add_filter( 'cwginstock_default_values', __NAMESPACE__ . '\i18n_cwg' );
add_filter( 'cwginstock_localization_array', __NAMESPACE__ . '\i18n_cwg_script' );
add_filter( 'rmp_custom_strings', __NAMESPACE__ . '\i18n_rmp' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\i18n_paczkomaty', 20 );

// Woo e-mails.
add_filter( 'woocommerce_order_item_name', __NAMESPACE__ . '\translate_email_order_item_name', 40, 2 );
add_filter( 'woocommerce_get_order_item_totals', __NAMESPACE__ . '\translate_email_order_totals', 10, 2 );

/**
 * Add current language information to product section script
 *
 * @param array $script_data Script localization data.
 * @return array
 */
function add_language_to_product_section_script( $script_data ) {
	$script_data['lang'] = get_locale();

	return $script_data;
}

/**
 * Set translation block on the element so that it is translated as a whole with text formatting and links
 *
 * @param string $block_content The block content.
 * @return string
 */
function set_translation_block( $block_content ) {
	$tags = new \WP_HTML_Tag_Processor( $block_content );

	$tags->next_tag();
	$tags->add_class( TRANSLATION_BLOCK );

	return $tags->get_updated_html();
}

/**
 * Set translation blocks in the content from classic editor.
 *
 * @param string $content The block content.
 * @return string
 */
function set_translation_blocks( $content ) {
	$tags               = new \WP_HTML_Tag_Processor( $content );
	$translation_blocks = array( 'P', 'LI', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' );

	while ( $tags->next_tag( array( 'tag_closer' => 'skip' ) ) ) {
		if ( in_array( $tags->get_tag(), $translation_blocks, true ) ) {
			$tags->add_class( TRANSLATION_BLOCK );
		}
	}

	return $tags->get_updated_html();
}

/**
 * Wrap percentage values in no translate elements
 *
 * @param string $text Original string.
 * @return string
 */
function no_translate_percentages( $text ) {
	$pattern = '/(\d+[.,]?\d*)(?:\/(\d+[.,]?\d*))?%/i';

	return preg_replace_callback(
		$pattern,
		function ( $matches ) {
			$result = format_localized_decimal( $matches[1] );

			if ( ! empty( $matches[2] ) ) {
				$result .= '/' . format_localized_decimal( $matches[2] );
			}

			return no_translate_wrap( $result . '%' );
		},
		$text
	);
}

/**
 * Wrap unit values in no translate elements, ensuring space between value and unit
 *
 * @param string $text Original string.
 * @return string
 */
function no_translate_units( $text ) {
	$units   = array( 'g', 'kg', 'mg', 'kcal', 'kJ', 'l', 'ml' );
	$pattern = '/(\d+[.,]?\d*)(\s?)(' . implode( '|', $units ) . ')(?![a-zA-Z0-9])/i';

	return preg_replace_callback(
		$pattern,
		function ( $matches ) {
			$formatted = format_localized_decimal( $matches[1] );
			return no_translate_wrap( $formatted . ' ' . $matches[3] );
		},
		$text
	);
}

/**
 * Format decimal value using current locale
 *
 * @param string $value Value to format.
 * @return string
 */
function format_localized_decimal( $value ) {
	$locale = get_locale();
	setlocale( LC_NUMERIC, $locale . '.UTF-8', $locale );
	$locale_info = localeconv();
	$decimal_sep = $locale_info['decimal_point'] ?? '.';

	$normalized = str_replace( ',', '.', $value );
	return str_replace( '.', $decimal_sep, $normalized );
}

/**
 * Wrap in no translate span
 *
 * @param string $text Content.
 * @return string
 */
function no_translate_wrap( $text ) {
	return sprintf( '<span %s>%s</span>', esc_attr( NO_TRANSLATE_ATTR ), esc_html( $text ) );
}

/**
 * Exclude selectors from translation
 *
 * @param string[] $selectors_array Array of HTML selectors.
 * @return string[]
 */
function no_translate_selectors( $selectors_array ) {
	$excluded = array(
		'img', // Images.
		'.woocommerce-Price-amount', // Price.
		'.woocommerce-Price-currencySymbol', // Price currency.
		'[name="attribute_pa_waga"]', // Variation selector.
		'.quantity', // Quantity input.
		'.sku', // Product SKU.
		'.badge--bio', // Product BIO badge.
		'.woocommerce-product-attributes-item--weight .woocommerce-product-attributes-item__value', // Product weight.
		'.woocommerce-product-attributes-item--attribute_pa_brand .woocommerce-product-attributes-item__value', // Product brand.
		'.tax-pa_brand .woocommerce-products-header__title.page-title', // Product brand page title.
		'label[for^="pa_brand"]', // Product brand filter.
		'.product-quantity', // Product quantity.
		'.woocommerce-product-gallery__wrapper', // Product gallery.
		'.woocommerce-product-attributes-item--attribute_pa_gatunek .woocommerce-product-attributes-item__value', // Product origin.
		'.woocommerce-product-attributes-item--attribute_pa_gatunek-kakao .woocommerce-product-attributes-item__value', // Product origin.
		'.tax-pa_gatunek .woocommerce-products-header__title page-title', // Product origin page title.
		'.tax-pa_gatunek-kakao .woocommerce-products-header__title page-title', // Product origin page title.
		'label[for^="pa_gatunek"]', // Product origin filter.
		'label[for^="product_tag"]', // Product tag filter.
		'.product_meta .tagged_as a', // Product tags.
		'.shipping-calculator-form', // Shipping calculator.
		'#menu-social-media', // Social media links.
		'.site-footer__copy-text', // Footer copy.
		'.remove_from_cart_button', // Mini-cart remove button.
		'.woocommerce-multi-currency', // Currency switcher.
		'.trp-block-container', // Language switcher.
		'.order-number', // Order number.
		'address', // Addresses.
		'[class*="username"]', // Username.
		'[class*="email"]', // Username.
		'.woocommerce-orders-table__cell-order-total-value', // Orders table total.
		'label[for*="globkurier"]', // Globkurier carrier names.
		'#chocante-product-filters [data-filter="product_cat"] legend', // Product filters: product category label.
		'#chocante-product-filters [data-filter="product_tag"] legend', // Product filters: product tag label.
		'[data-country]', // Free shipping: country name.
		'[name="billing_country"]', // Country select.
		'[name="shipping_country"]', // Country select.
		'[name="billing_state"]', // State select.
		'[name="shipping_state"]', // State select.
		'a[href^="mailto:]"', // Email links.
		'a[href^="tel:]"', // Phone links.
		'.shop-name', // Shop name.
		'.rmp-rating-widget__icons', // Post rating icons.
		'.wp-block-safe-svg-svg-icon', // SVG icon block.
		'.woocommerce-bacs-bank-details', // Bank account details.
		'sup.fn', // Footnotes.
		'[data-fn]', // Footnotes.
		'.wp-block-footnotes', // Links in footnotes.
		'meta[property="og:url"]',
		'meta[property="og:site_name"]',
		'meta[property^="og:image"]',
		'meta[name="twitter:card"]',
		'meta[name="twitter:image"]',
		'.no-translate', // Custom class to use in the editor.
	);

	return array_merge( $selectors_array, $excluded );
}

/**
 * Exclude selectors from translation
 *
 * @param string[] $selectors_array Array of HTML selectors.
 * @return string[]
 */
function no_auto_translate_selectors( $selectors_array ) {
	$excluded = array(
		'.wp-block-footnotes', // Footnotes.
	);

	return array_merge( $selectors_array, $excluded );
}

/**
 * Don't translate aria-label of breadcrumb element
 */
function no_translate_breadcrums_aria() {
	return '<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb" ' . NO_TRANSLATE_ATTR . '-aria-label>';
}

/**
 * Don't translate brands in shop breadcrumbs
 *
 * @param string $breadcrumbs_html Shop breadcrumbs element.
 * @return string
 */
function no_translate_breadcrums( $breadcrumbs_html ) {
	if ( is_tax( 'pa_brand' ) ) {
		$translate_attr = NO_TRANSLATE_ATTR;
	}

	if ( is_product() ) {
		$translate_attr = NO_AUTO_TRANSLATE_ATTR;
	}

	if ( isset( $translate_attr ) ) {
		$last_span_pos = strrpos( $breadcrumbs_html, '<span>' );

		if ( false !== $last_span_pos ) {
			return substr_replace( $breadcrumbs_html, '<span ' . $translate_attr . '>', $last_span_pos, 6 );
		}
	}

	return $breadcrumbs_html;
}

/**
 * Split product taxonomy terms in product attributes section
 *
 * @param string        $attribute_value Attribute display value.
 * @param \WC_Attribute $attribute Attribute object.
 * @param array         $values All attribute values.
 * @return array
 */
function split_product_attributes( $attribute_value, $attribute, $values ) {
	if ( $attribute->is_taxonomy() && count( $values ) > 1 ) {
		$attribute_taxonomy = $attribute->get_taxonomy_object();

		if ( ! $attribute_taxonomy->attribute_public ) {
			$wrapped = array_map(
				function ( $v ) {
					return '<span>' . esc_html( $v ) . '</span>';
				},
				$values
			);

			return implode( ', ', $wrapped );
		}
	}

	return $attribute_value;
}

/**
 * Exclude gettext domains from processing
 *
 * @param bool   $process Whether to process the string.
 * @param string $translation Translation.
 * @param string $text Original text.
 * @param string $domain Text domain.
 * @return bool
 */
function no_process_gettext_domains( $process, $translation, $text, $domain ) {
	$allowed_domains = array(
		'chocante',
		'woocommerce',
		'chocante-free-shipping',
		'chocante-product-filters',
		'chocante-vat-eu',
		'chocante-omnibus',
		'back-in-stock-notifier-for-woocommerce',
		'woocommerce-paczkomaty-inpost',
		'rate-my-post',
	);

	if ( ! in_array( $domain, $allowed_domains, true ) ) {
		return true;
	}

	return $process;
}

/**
 * Don't auto-translate product names
 *
 * @param string      $title Product name HTML.
 * @param string|null $name Product name.
 * @return string
 */
function no_auto_translate_product_name( $title, $name = null ) {
	$display_name = $title;

	if ( isset( $name ) ) {
		$display_name = $name;
	}

	return sprintf( '<span %s>%s</span>', NO_AUTO_TRANSLATE_ATTR, $display_name );
}

/**
 * Display langauge swticher
 */
function add_language_switcher_shortcode() {
	// [chocante_language_switcher] shortcode.
	add_shortcode(
		'chocante_language_switcher',
		function () {
			if ( ! apply_filters( 'trp_allow_tp_to_run', true ) ) {
				return;
			}

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			global $TRP_LANGUAGE;

			$languages = trp_custom_language_switcher();

			if ( count( $languages ) < 2 || ! isset( $languages[ $TRP_LANGUAGE ] ) ) {
				return;
			}

			$current = $languages[ $TRP_LANGUAGE ];
			unset( $languages[ $TRP_LANGUAGE ] );

			ob_start();
			get_template_part(
				'template-parts/language',
				'switcher',
				array(
					'current'   => $current,
					'languages' => $languages,
				)
			);

			return ob_get_clean();
		}
	);
}

/**
 * Expose CWG translations to TranslatePress
 *
 * @param array $default_strings Default messages.
 * @return array
 */
function i18n_cwg( $default_strings ) {
	$default_strings['form_title']             = '';
	$default_strings['name_placeholder']       = '';
	$default_strings['form_placeholder']       = '';
	$default_strings['button_label']           = '';
	$default_strings['empty_error_message']    = '';
	$default_strings['invalid_email_error']    = '';
	$default_strings['success_sub_subject']    = '';
	$default_strings['success_sub_message']    = '';
	$default_strings['instock_mail_subject']   = '';
	$default_strings['instock_mail_message']   = '';
	$default_strings['success_subscription']   = '';
	$default_strings['already_subscribed']     = '';
	$default_strings['empty_name_message']     = '';
	$default_strings['invalid_phone_error']    = '';
	$default_strings['phone_number_too_short'] = '';
	$default_strings['phone_number_too_long']  = '';

	return $default_strings;
}

/**
 * Expose CWG dynamic strings to TranslatePress
 * Needs to be registered in the default site language
 *
 * @param array $script_data Script localization data.
 * @return array
 */
function i18n_cwg_script( $script_data ) {
	if ( ! function_exists( 'trp_translate' ) ) {
		return;
	}

	$script_data['empty_name']    = trp_translate( 'Podaj imię', null, false );
	$script_data['empty_email']   = trp_translate( 'Podaj adres e-mail', null, false );
	$script_data['invalid_email'] = trp_translate( 'Adres e-mail ma nieprawidłowy format', null, false );

	return $script_data;
}

/**
 * Expose Rate My Post feedback messages to TranslatePress
 * Needs to be registered in the default site language
 *
 * @param array $custom_strings Plugin strings.
 * @return array
 */
function i18n_rmp( $custom_strings ) {
	if ( ! function_exists( 'trp_translate' ) ) {
		return;
	}

	$custom_strings['afterVote'] = trp_translate( 'Dziękujemy za ocenę!', null, false );

	return $custom_strings;
}

/**
 * Expose WooCommerce Paczkomaty Inpost script strings to TranslatePress
 */
function i18n_paczkomaty() {
	if ( ! class_exists( 'WPDesk_Paczkomaty_Plugin' ) || ! function_exists( 'trp_translate' ) ) {
		return;
	}

	$i18n_data = array(
		'lang' => array(
			'loading_more'  => trp_translate( 'Więcej...', null, false ),
			'no_results'    => trp_translate( 'Brak paczkomatów.', null, false ),
			'searching'     => trp_translate( 'Szukam paczkomatów...', null, false ),
			'error_loading' => trp_translate( 'Wyszukiwanie w toku...', null, false ),
			'placeholder'   => trp_translate( 'Wpisz miasto lub miasto, ulica.', null, false ),
			'min_chars'     => trp_translate( 'Wpisz minimum % znaki.', null, false ),
			'nomachine'     => trp_translate( 'Paczkomat nie istnieje', null, false ),
		),
	);

	wp_add_inline_script(
		'paczkomaty_front',
		'Object.assign(window.paczkomaty, ' . wp_json_encode( $i18n_data ) . ');',
		'after'
	);
}

/**
 * Expose variation description used in JS variations form
 *
 * @param array $variation_data Variation data.
 * @return array
 */
function i18n_woo_variation( $variation_data ) {
	if ( function_exists( 'trp_translate' ) ) {
		$variation_description                   = $variation_data['variation_description'];
		$variation_data['variation_description'] = trp_translate( $variation_description, null, false );
	}

	return $variation_data;
}

/**
 * Expose shipping method name to TrasnlatePress
 *
 * @param string           $label Shipping method label.
 * @param  WC_Shipping_Rate $method Shipping method rate data.
 * @return string
 */
function i18n_shipping_method( $label, $method ) {
	$raw_label = $method->get_label();

	return str_replace( $raw_label, '<span>' . $raw_label . '</span>', $label );
}

/**
 * Translate product names in email
 *
 * @param string         $item_name_html Display name of line item.
 * @param \WC_Order_Item $item Order line item.
 * @return string
 */
function translate_email_order_item_name( $item_name_html, $item ) {
	if ( ! function_exists( 'trp_translate' ) ) {
		return $item_name_html;
	}

	global $TRP_EMAIL_ORDER;

	if ( ! $TRP_EMAIL_ORDER ) {
		return $item_name_html;
	}

	$order_id = $item->get_order_id();
	$language = trp_woo_hpos_get_post_meta( $order_id, 'trp_language', true );

	if ( ! $language ) {
		return $item_name_html;
	}

	if ( class_exists( 'ACF' ) ) {
		$id                 = $item->get_product_id();
		$product_short_name = get_field( ACF_PRODUCT_TITLE, $id );
		$product_type       = get_field( ACF_PRODUCT_TYPE, $id );

		if ( $product_short_name ) {
			$item_name_acf = trp_translate( $product_short_name, $language, false );

			if ( $product_type ) {
				$item_name_acf .= ' ' . trp_translate( $product_type, $language, false );
			}

			$variation_name = get_variation_name( $item->get_product() );

			if ( $variation_name ) {
				$item_name_acf .= ' - ' . $variation_name;
			}

			return $item_name_acf;
		}
	}

	return trp_translate( wp_strip_all_tags( $item_name_html ), $language, false );
}

/**
 * Translate order totals in email
 *
 * @param array     $totals Order totals rows.
 * @param \WC_Order $order Order object.
 * @return array
 */
function translate_email_order_totals( $totals, $order ) {
	global $TRP_EMAIL_ORDER;

	if ( ! $TRP_EMAIL_ORDER ) {
		return $totals;
	}

	$language = trp_woo_hpos_get_post_meta( $order->get_id(), 'trp_language', true );

	error_log( print_r( $language, true ) );

	if ( ! $language ) {
		return $totals;
	}

	foreach ( $totals as $key => &$row ) {
		if ( 'shipping' === $key ) {
			$row['value'] = trp_translate( $row['value'], $language, false );
			$row['meta']  = trp_translate( $row['meta'], $language, false );
		}

		if ( 'payment_method' === $key ) {
			$row['value'] = trp_translate( $row['value'], $language, false );
		}
	}

	return $totals;
}
