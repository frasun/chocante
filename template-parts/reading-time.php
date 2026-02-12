<?php
/**
 * Single post reading time
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
?>

<?php if ( isset( $args['reading_time'] ) ) : ?>
	<div class="reading-time"><?php icon( 'clock' ); ?><span><?php echo esc_html_x( 'Reading time', 'reading time', 'chocante' ); ?>&nbsp;<strong><?php echo esc_html( $args['reading_time'] ); ?></strong></span></div>
<?php endif; ?>