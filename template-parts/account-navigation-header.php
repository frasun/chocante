<?php
/**
 * Account navigation header with user data
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

global $current_user;
?>

<header class="woocommerce-MyAccount-navigation__header">
	<figure class="woocommerce-MyAccount-navigation__header-icon"><?php Chocante::icon( 'account' ); ?></figure>
	<span class="woocommerce-MyAccount-navigation__username"><?php echo esc_html( $current_user->user_login ); ?></span>
	<span class="woocommerce-MyAccount-navigation__email"><?php echo esc_html( $current_user->user_email ); ?></span>
</header>