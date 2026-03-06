<?php
/**
 * Currency switcher
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $args['items'] ) ) {
	return;
}
?>

<div class="switcher switcher--currency" data-no-translation>
	<span class="switcher__current"><?php echo esc_html( $args['current'] ); ?></span>
	<div class="switcher__items">
		<?php foreach ( $args['items'] as $item ) : ?>
			<?php $link_href = $args['is_js'] ? '#' : "/?wmc-currency={$item}"; ?>
			<a rel="nofollow" class="wmc-currency-redirect" href="<?php echo esc_url( $link_href ); ?>" data-currency="<?php echo esc_attr( $item ); ?>">
				<?php echo wp_kses_post( $item ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
