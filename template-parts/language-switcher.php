<?php
/**
 * Langauge switcher
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="switcher switcher--language" data-no-translation>
	<span class="switcher__current"><?php echo esc_html( $args['current']['short_language_name'] ); ?></span>
	<div class="switcher__items">
		<?php foreach ( $args['languages'] as $item ) : ?>
			<a href="<?php echo esc_url( $item['current_page_url'] ); ?>"><?php echo esc_html( $item['short_language_name'] ); ?></a>
		<?php endforeach; ?>
	</div>
</div>
