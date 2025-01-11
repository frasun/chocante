<?php
/**
 * Content header
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<header class="content-header">
	<div class="content-header__container">
		<?php do_action( 'chocante_before_content_header' ); ?>

		<h1 class="content-header__title"><?php the_title(); ?></h1>

		<?php if ( has_excerpt() ) : ?>
			<div class="content-header__lead"><?php the_excerpt(); ?></div>
		<?php endif; ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="content-header__thumbnail">
				<?php the_post_thumbnail( array( 1180, 1180 ) ); ?>
			</figure>
		<?php endif; ?>

		<?php do_action( 'chocante_after_content_header' ); ?>
	</div>
</header>