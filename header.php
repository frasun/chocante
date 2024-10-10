<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
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

	<header>
		<aside>
			<div>Waluty</div>
			<div>Języki</div>
		</aside>
		<div>Logo</div>
		<div>Menu</div>
		<div>Menu górne</div>
		<div>Menu ikonki</div>
	</header>