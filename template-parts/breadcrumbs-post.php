<?php
/**
 * Single post breadcrumbs
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<nav class="content-header__breadcrumbs">
	<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php Chocante::icon( 'prev' ); ?><?php echo esc_html_x( 'Cacao Blog', 'blog', 'chocante' ); ?></a>
</nav>