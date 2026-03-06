<?php
/**
 * Currency redirect page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
	<html>
		<head>
			<script>
				const valid = <?php echo wp_json_encode( $args['currencies'] ); ?>;
				const url = new URL(window.location.href);
				const currency = url.searchParams.get('wmc-currency');

				if ( valid.includes( currency ) ) {
					const d = new Date();
					d.setTime( d.getTime() + 86400000 );
					document.cookie = 'wmc_current_currency=' + currency + ';expires=' + d.toUTCString() + ';path=/';
					document.cookie = 'wmc_current_currency_old=' + currency + ';expires=' + d.toUTCString() + ';path=/';
				}

				url.searchParams.delete('wmc-currency');
				window.location.href = url.href;
			</script>
		</head>
	<body>Redirecting...</body>
</html>