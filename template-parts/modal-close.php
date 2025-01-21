<?php
/**
 * Close modal button
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<button data-close-modal aria-label="<?php esc_attr_e( 'Close' ); ?>">
	<?php Chocante::icon( 'close' ); ?>
</button>