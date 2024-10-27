<?php
/**
 * Close modal button
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<button data-close-modal aria-label="<?php esc_attr_e( 'Close' ); ?>">
	<?php Chocante::icon( 'close' ); ?>
</button>