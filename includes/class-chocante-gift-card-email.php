<?php
/**
 * Gift card email
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

use const Chocante\GiftCards\GIFT_CARD_COUPON;

if ( ! class_exists( 'Chocante_Gift_Card_Email', false ) ) :
	/**
	 * Email handler.
	 */
	class Chocante_Gift_Card_Email extends \WC_Email {
		/**
		 * Create class
		 */
		public function __construct() {
			$this->id             = 'gift_card_email';
			$this->title          = __( 'Gift card', 'chocante' );
			$this->description    = __( 'Sent when an order contains gift card products.', 'chocante' );
			$this->template_base  = get_theme_file_path() . '/woocommerce/';
			$this->template_html  = 'emails/gift-card.php';
			$this->template_plain = 'emails/plain/gift-card.php';
			$this->customer_email = true;

			// Trigger.
			add_action( 'chocante_gift_card_notification', array( $this, 'trigger' ) );

			parent::__construct();

			add_filter( 'woocommerce_email_preview_dummy_order', array( $this, 'extened_order_preview' ), 10, 2 );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '{site_title} - Your Gift Card codes', 'chocante' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your Gift Cards', 'chocante' );
		}

		/**
		 * Send email
		 *
		 * @param int $order_id Order ID.
		 */
		public function trigger( $order_id ) {
			$this->setup_locale();

			$this->object    = wc_get_order( $order_id );
			$this->recipient = $this->object->get_billing_email();

			if ( ! $this->object ) {
				$this->restore_locale();
				return;
			}

			$this->send_notification();

			$this->restore_locale();
		}

		/**
		 * Get email html
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'user_display_name'  => $this->object->get_billing_first_name(),
					'gift_cards'         => $this->get_gift_cards( $this->object ),
					'additional_content' => $this->get_additional_content(),
					'email'              => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Get email plain
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'user_display_name'  => $this->object->get_billing_first_name(),
					'gift_cards'         => $this->get_gift_cards( $this->object ),
					'additional_content' => $this->get_additional_content(),
					'email'              => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Happy shopping!', 'chocante' );
		}

		/**
		 * Modify the email object before rendering the preview to add additional data.
		 *
		 * @param \WC_Order $order The dummy order object.
		 * @param string    $email_type The email type to preview.
		 */
		public function extened_order_preview( $order, $email_type ) {
			if ( 'Chocante_Gift_Card_Email' !== $email_type ) {
				return $order;
			}

			foreach ( $order->get_items() as $item ) {
				$item->add_meta_data( GIFT_CARD_COUPON, 'PREVIEW-CODE-1234' );
			}

			return $order;
		}

		/**
		 * Get gift card data
		 *
		 * @param \WC_Order $order Order object.
		 * @return array
		 */
		private function get_gift_cards( $order ) {
			$gift_cards = array();

			foreach ( $order->get_items() as $order_item ) {
				$order_gift_cards = $order_item->get_meta( GIFT_CARD_COUPON, false );

				if ( ! empty( $order_gift_cards ) ) {
					$gift_cards = array_merge( $gift_cards, $order_gift_cards );
				}
			}

			return $gift_cards;
		}
	}
endif;

return new Chocante_Gift_Card_Email();
