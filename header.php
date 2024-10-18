<?php
/**
 * Site Header
 *
 * @package Chocante
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> chocante>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<?php do_action( 'bricks_meta_tags' ); ?>
	<?php wp_head(); ?>
</head>

<?php
do_action( 'bricks_body' );

do_action( 'bricks_before_site_wrapper' );

do_action( 'bricks_before_header' );

do_action( 'render_header' );

do_action( 'bricks_after_header' );
?>

<header class="site-header">
	<div class="container">
		<?php get_template_part( 'template-parts/menu-top' ); ?>		

		<div class="site-header__menu">
			<button class="site-header__toggle">
				<?php Chocante::icon( 'menu-toggle' ); ?>
			</button>

			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			}

			if ( has_nav_menu( 'chocante_menu_main' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'chocante_menu_main',
						'container'      => false,
						'link_before'    => '<span>',
						'link_after'     => '</span>',
						'menu_class'     => 'menu site-header__nav',
					)
				);
			}

			do_action( 'chocante_header_aside', 'site-header__aside' );
			?>
		</div>
	</div>
</header>