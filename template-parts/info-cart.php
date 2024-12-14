<?php
/**
 * Additional delivery information
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<aside class="infobox">
	<?php
		/**
		 * Safe shopping
		 */
		// translators: Safe shopping heading.
		$heading = _x( 'Safe shopping', 'infobox', 'chocante' );
		// translators: Safe shopping content.
		$content = _x( 'If the product we supply is faulty or damaged, you can return the goods and we will give you a refund or send you a new product.', 'infobox', 'chocante' );
		get_template_part(
			'template-parts/info',
			'section',
			array(
				'icon'    => 'cart',
				'heading' => $heading,
				'content' => $content,
			)
		);

		/**
		 * Fast shipping
		 */
		get_template_part( 'template-parts/info', 'shipping' );

		do_action( 'chocante_delivery_info' );
		?>
</aside>
