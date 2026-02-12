<?php
/**
 * Close modal button
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<button data-close-modal aria-label="<?php esc_attr_e( 'Close' ); ?>" aria-controls="mobileMenu">
	<?php icon( 'close' ); ?>
</button>
