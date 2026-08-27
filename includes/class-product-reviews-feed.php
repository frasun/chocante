<?php
/**
 * Google Product Review feed
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante;

defined( 'ABSPATH' ) || exit;

/**
 * Generate feed for Google Product Reviews.
 */
class Product_Reviews_Feed {
	/**
	 * Feed directory path
	 */
	const FEEDS_DIR = '/uploads/feeds';

	/**
	 * Prepare review data.
	 *
	 * @return array
	 */
	public static function get_reviews() {
		$product_reviews = array();
		$comments        = get_comments(
			array(
				'post_type' => 'product',
				'status'    => 'approve',
				'type'      => 'review',
				'parent'    => 0,
			)
		);

		foreach ( $comments as $comment ) {
			$rating  = get_comment_meta( $comment->comment_ID, 'rating', true );
			$content = wp_strip_all_tags( $comment->comment_content );

			if ( empty( $rating ) || empty( $content ) ) {
				continue;
			}

			$is_verified   = (bool) get_comment_meta( $comment->comment_ID, 'verified', true );
			$is_registered = isset( $comment->user_id ) && 0 !== (int) $comment->user_id;
			$country       = get_comment_meta( $comment->comment_ID, 'country', true );
			$product       = wc_get_product( $comment->comment_post_ID );

			$review = array();

			$review[] = array(
				'@name'  => 'review_id',
				'@value' => $comment->comment_ID,
			);

			$name_node = array(
				'@name'  => 'name',
				'@value' => get_comment_author( $comment->comment_ID ),
			);

			if ( ! $is_verified && ! $is_registered ) {
				$name_node['@attributes'] = array( 'is_anonymous' => 'true' );
			}

			$review[] = array(
				'@name'  => 'reviewer',
				'@value' => array( $name_node ),
			);

			if ( ! empty( $country ) ) {
				$review[] = array(
					'@name'  => 'review_country',
					'@value' => $country,
				);
			}

			$review[] = array(
				'@name'  => 'review_timestamp',
				'@value' => gmdate( 'c', strtotime( $comment->comment_date_gmt ) ),
			);

			$review[] = array(
				'@name'  => 'content',
				'@value' => $content,
			);

			$review[] = array(
				'@name'       => 'review_url',
				'@value'      => get_comment_link( $comment ),
				'@attributes' => array( 'type' => 'group' ),
			);

			$review[] = array(
				'@name'  => 'ratings',
				'@value' => array(
					array(
						'@name'       => 'overall',
						'@value'      => $rating,
						'@attributes' => array(
							'min' => 1,
							'max' => 5,
						),
					),
				),
			);

			if ( $product ) {
				$product_value = array();

				if ( $product->get_sku() ) {
					$product_value[] = array(
						'@name'  => 'product_ids',
						'@value' => array(
							array(
								'@name'  => 'skus',
								'@value' => array(
									array(
										'@name'  => 'sku',
										'@value' => $product->get_sku(),
									),
								),
							),
						),
					);
				}

				$product_value[] = array(
					'@name'  => 'product_name',
					'@value' => $product->get_name(),
				);

				$product_value[] = array(
					'@name'  => 'product_url',
					'@value' => get_permalink( $comment->comment_post_ID ),
				);

				$review[] = array(
					'@name'  => 'products',
					'@value' => array(
						array(
							'@name'  => 'product',
							'@value' => $product_value,
						),
					),
				);
			}

			$review[] = array(
				'@name'  => 'is_verified_purchase',
				'@value' => $is_verified ? 'true' : 'false',
			);

			$product_reviews[] = array(
				'@name'  => 'review',
				'@value' => $review,
			);
		}

		return $product_reviews;
	}

	/**
	 * Get feed header
	 *
	 * @return array
	 */
	public static function get_feed() {
		$feed = array(
			'@name'       => 'feed',
			'@value'      => array(),
			'@attributes' => array(
				'xmlns:vc'                      => 'http://www.w3.org/2007/XMLSchema-versioning',
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'http://www.google.com/shopping/reviews/schema/product/2.4/product_reviews.xsd',
			),
		);

		$feed['@value'][] = array(
			'@name'  => 'version',
			'@value' => '2.4',
		);

		$publisher = array(
			'@name'  => 'publisher',
			'@value' => array(),
		);

		$publisher['@value'][] = array(
			'@name'  => 'name',
			'@value' => get_bloginfo( 'name' ),
		);

		if ( has_custom_logo() ) {
			$publisher['@value'][] = array(
				'@name'  => 'favicon',
				'@value' => wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ) ),
			);
		}

		$feed['@value'][] = $publisher;

		$feed['@value'][] = array(
			'@name'  => 'reviews',
			'@value' => self::get_reviews(),
		);

		return $feed;
	}

	/**
	 * Convert PHP array to XML
	 *
	 * @param array        $node XML node.
	 * @param \DOMDocument $dom XML document.
	 * @return \DOMElement
	 */
	public static function array_to_xml( $node, $dom ) {
		$element = $dom->createElement( $node['@name'] );

		if ( isset( $node['@attributes'] ) ) {
			foreach ( $node['@attributes'] as $attr => $attr_value ) {
				$element->setAttribute( $attr, (string) $attr_value );
			}
		}

		if ( is_array( $node['@value'] ) ) {
			foreach ( $node['@value'] as $child ) {
				$element->appendChild( self::array_to_xml( $child, $dom ) );
			}
		} else {
			$element->appendChild( $dom->createTextNode( (string) $node['@value'] ) );
		}

		return $element;
	}

	/**
	 * Build XML feed
	 *
	 * @param array $feed_data Feed data.
	 * @return \DOMDocument
	 */
	public static function build_xml_feed( $feed_data ) {
		$dom = new \DOMDocument( '1.0', 'UTF-8' );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom->formatOutput = true;
		$dom->appendChild( self::array_to_xml( $feed_data, $dom ) );

		return $dom;
	}

	/**
	 * Save XML file
	 *
	 * @param \DOMDocument $dom XML document.
	 * @return bool
	 */
	public static function save( $dom ) {
		wp_mkdir_p( WP_CONTENT_DIR . self::FEEDS_DIR );
		return (bool) $dom->save( WP_CONTENT_DIR . self::FEEDS_DIR . '/google-product-reviews.xml' );
	}
}
