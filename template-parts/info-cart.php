<?php
/**
 * Additional delivery information
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;
?>

<aside class="cart-info">
	<?php
		/**
		 * Safe shopping
		 */
		// translators: Safe shopping heading.
		$heading = __( 'Safe shopping', 'chocante' );
		// translators: Safe shopping content.
		$content = __( 'If the product we supply is faulty or damaged, you can return the goods and we will give you a refund or send you a new product.', 'chocante' );
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
		// translators: Fast shipping heading.
		$heading = __( 'Fast shipping', 'chocante' );
		// translators: Fast shipping content.
		$content = __( 'Order before 11 a.m. and we will ship it the same day.', 'chocante' );
		get_template_part(
			'template-parts/info',
			'section',
			array(
				'icon'    => 'clock',
				'heading' => $heading,
				'content' => $content,
			)
		);

		do_action( 'chocante_delivery_info' );
		?>
</aside>
