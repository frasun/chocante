<?php
/**
 * Join FB group
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

$group_url = 'https://www.facebook.com/groups/kakaoceremonialne';
$image_url = get_stylesheet_directory_uri() . '/images/join-group.webp';
?>

<aside class="join-group">
	<img src="<?php echo esc_url( $image_url ); ?>" loading="lazy" alt="<?php echo esc_attr_x( 'Join the group', 'join group', 'chocante' ); ?>" class="join-group__image" />
	<div class="join-group__wrapper">
		<div class="join-group__content">
			<header class="join-group__header">
				<span class="join-group__subheading"><?php echo esc_html_x( 'Join the group', 'join group', 'chocante' ); ?></span>
				<h2 class="join-group__heading"><?php echo esc_html_x( 'Cacao at a discount', 'join group', 'chocante' ); ?></h2>
			</header>
			<p>
				<?php echo nl2br( esc_html_x( "Join the cacao group and return to the shop with a discount code. Buy ceremonial cacao, beans or cocoa butter at a better price. \n\nDiscount code within the posts.", 'join group', 'chocante' ) ); ?>
			</p>
			<a href="<?php echo esc_url( $group_url ); ?>" target="_blank"><?php echo esc_html_x( 'Join', 'join group', 'chocante' ); ?></a>
		</div>
	</div>
</aside>
