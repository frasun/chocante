<?php
/**
 * Section with icon and information
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;

$icon    = isset( $args['icon'] ) ? $args['icon'] : null;
$heading = isset( $args['heading'] ) ? $args['heading'] : null;
$content = isset( $args['content'] ) ? $args['content'] : null;
?>

<div>
	<?php
	if ( isset( $icon ) ) {
		icon( $icon );
	}
	?>

	<div>
		<?php
		if ( isset( $heading ) ) {
			echo '<h6>' . wp_kses_post( $heading ) . '</h6>';
		}

		if ( isset( $content ) ) {
			echo '<p>' . wp_kses_post( $content ) . '</p>';
		}
		?>
	</div>
</div>