<?php
/**
 * Single post breadcrumbs
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

?>

<nav class="content-header__breadcrumbs">
	<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php icon( 'prev' ); ?><?php echo esc_html_x( 'Cacao Blog', 'blog', 'chocante' ); ?></a>
</nav>