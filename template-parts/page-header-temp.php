<?php
/**
 * Page header
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<header class="page-header alignwide has-global-padding">
	<div class="page-header__container">
		<?php do_action( 'chocante_page_header_before_title' ); ?>

		<h1 class="page-header__title"><?php the_title(); ?></h1>

		<?php if ( has_excerpt() ) : ?>
			<div class="page-header__lead"><?php the_excerpt(); ?></div>
		<?php endif; ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="page-header__thumbnail">
				<?php the_post_thumbnail( array( 1180, 1180 ) ); ?>
			</figure>
		<?php endif; ?>
	</div>
</header>