<?php
/**
 * Account navigation header with user data
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

global $current_user;
?>

<header class="woocommerce-MyAccount-navigation__header">
	<figure class="woocommerce-MyAccount-navigation__header-icon"><?php icon( 'account' ); ?></figure>
	<span class="woocommerce-MyAccount-navigation__username"><?php echo esc_html( $current_user->user_login ); ?></span>
	<span class="woocommerce-MyAccount-navigation__email"><?php echo esc_html( $current_user->user_email ); ?></span>
</header>