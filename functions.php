<?php
/**
 * Chocante theme functions
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme settings.
 */
require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/class-assets-handler.php';
require_once __DIR__ . '/includes/assets.php';
require_once __DIR__ . '/includes/widgets.php';
require_once __DIR__ . '/includes/menu.php';
require_once __DIR__ . '/includes/blocks.php';
require_once __DIR__ . '/includes/media.php';
require_once __DIR__ . '/includes/currency.php';
require_once __DIR__ . '/includes/translations.php';
require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/plugins.php';

/**
 * WooCommerce settings.
 */
require_once __DIR__ . '/includes/woocommerce/woo.php';
require_once __DIR__ . '/includes/woocommerce/product-tags.php';
require_once __DIR__ . '/includes/woocommerce/shipping/class-globkurier-shipping.php';

/**
 * Layout.
 */
require_once __DIR__ . '/includes/layout/common.php';
require_once __DIR__ . '/includes/layout/blog.php';
require_once __DIR__ . '/includes/layout/product-section.php';
require_once __DIR__ . '/includes/layout/shop.php';
require_once __DIR__ . '/includes/layout/product.php';
require_once __DIR__ . '/includes/layout/account.php';
require_once __DIR__ . '/includes/layout/cart.php';
require_once __DIR__ . '/includes/layout/checkout.php';
require_once __DIR__ . '/includes/layout/acf.php';

/**
 * WooCommerce function overrides.
 */
use function Chocante\Layout\Common\show_product_badge;

/** Display product badges */
function woocommerce_show_product_sale_flash() {
	show_product_badge();
}

/** Display product badges in the loop */
function woocommerce_show_product_loop_sale_flash() {
	show_product_badge();
}
