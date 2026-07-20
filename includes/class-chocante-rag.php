<?php
/**
 * REST API route for RAG product database
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\RAG;

use WC_DateTime;

use const Chocante\Layout\ACF\ACF_PRODUCT_DETAILS;
use const Chocante\Layout\ACF\ACF_PRODUCT_DETAILS_LABEL;
use const Chocante\Layout\ACF\ACF_PRODUCT_DETAILS_VALUE;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Register endpoint for RAG
 */
class Chocante_RAG {
	/**
	 * API route namespace
	 *
	 * @var string
	 */
	private $namespace;

	/**
	 * API resource name
	 *
	 * @var string
	 */
	private $resource_name;

	/**
	 * GET endpoint response schema
	 *
	 * @var array
	 */
	private $schema;


	/**
	 * Create class
	 */
	public function __construct() {
		$this->namespace     = 'chocante';
		$this->resource_name = 'v1/rag_data';
	}

	/**
	 * Registet API routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items_rest' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_items_args(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Authorize request
	 *
	 * @param \WP_REST_Request $request Current request.
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Get our sample schema for a post.
	 *
	 * @return array The sample schema for a post
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema' => 'https://json-schema.org/draft/2020-12',
			'title'   => 'rag_data',
			'type'    => 'array',
			'items'   => array(
				'type'       => 'object',
				'properties' => array(
					'id'            => array(
						'description' => 'Product ID',
						'type'        => 'integer',
					),
					'sku'           => array(
						'description' => 'Product SKU',
						'type'        => 'string',
					),
					'title'         => array(
						'description' => 'Product name',
						'type'        => 'string',
					),
					'permalink'     => array(
						'description' => 'Product page URL',
						'type'        => 'string',
						'format'      => 'uri',
					),
					'image'         => array(
						'description' => 'Product image URL',
						'type'        => 'string',
						'format'      => 'uri',
					),
					'last_modified' => array(
						'description' => 'Last updated',
						'type'        => 'string',
						'format'      => 'date',
					),
					'embed_text'    => array(
						'description' => 'Properties used in semantic search',
						'type'        => 'object',
						'properties'  => array(
							'short_description' => array(
								'description' => 'Product description (short)',
								'type'        => 'string',
							),
							'description'       => array(
								'description' => 'Product description',
								'type'        => 'string',
							),
							'categories'        => array(
								'description' => 'Product categories',
								'type'        => 'array',
								'items'       => array( 'type' => 'string' ),
							),
							'details'           => array(
								'description' => 'Product detailed information',
								'oneOf'       => array(
									array( 'type' => 'string' ),
									array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
					'metadata'      => array(
						'description' => 'Properties used in exact search',
						'type'        => 'object',
						'properties'  => array(
							'tags'   => array(
								'description' => 'Product tags',
								'type'        => 'array',
								'items'       => array( 'type' => 'string' ),
							),
							'weight' => array(
								'description' => 'Available product weight variants (kg)',
								'type'        => 'array',
								'items'       => array( 'type' => 'number' ),
							),
							'brand'  => array(
								'description' => 'Product brand',
								'type'        => 'string',
							),
						),
					),
				),
			),
		);

		return $this->schema;
	}

	/**
	 * GET endpoint arguments
	 */
	public function get_items_args() {
		$args = array();

		$post_status = array( 'publish', 'draft', 'trash' );

		$args['page'] = array(
			'description'       => 'Number of page of results',
			'type'              => 'integer',
			'validate_callback' => fn ( $value ) => is_numeric( $value ) && (int) $value > 0,
			'sanitize_callback' => 'absint',
			'default'           => 1,
		);

		$args['per_page'] = array(
			'description'       => 'Number of results per page',
			'type'              => 'integer',
			'validate_callback' => fn ( $value ) => is_numeric( $value ) && (int) $value > 0 && (int) $value <= 100,
			'sanitize_callback' => 'absint',
			'default'           => 10,
		);

		$args['modified_after'] = array(
			'description'       => 'Include only modified after date',
			'type'              => 'string',
			'validate_callback' => fn( $value ) => (bool) rest_parse_date( $value ),
			'sanitize_callback' => 'sanitize_text_field',
		);

		$args['include'] = array(
			'description'       => 'Include only products with IDS',
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'validate_callback' => function ( $value ) {
				$ids = explode( ',', $value );
				foreach ( $ids as $id ) {
					if ( ! is_numeric( $id ) ) {
						return false;
					}
				}
				return true;
			},
			'sanitize_callback' => fn( $value ) => array_map( 'absint', explode( ',', $value ) ),
		);

		$args['status'] = array(
			'description'       => 'Include only products with publish status',
			'type'              => 'string',
			'default'           => 'publish',
			'enum'              => $post_status,
			'validate_callback' => fn( $value ) => in_array( $value, $post_status, true ),
			'sanitize_callback' => 'sanitize_text_field',
		);

		return $args;
	}

	/**
	 * Get items for endpoint
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items_rest( $request ) {
		$args = array(
			'limit'  => $request->get_param( 'per_page' ),
			'page'   => $request->get_param( 'page' ),
			'status' => $request->get_param( 'status' ),
		);

		if ( ! empty( $request->get_param( 'include' ) ) ) {
			$args['include'] = $request->get_param( 'include' );
		}

		if ( ! empty( $request->get_param( 'modified_after' ) ) ) {
			$args['date_modified'] = '>' . $request->get_param( 'modified_after' );
		}

		return $this->get_items( $args );
	}

	/**
	 * Get items
	 *
	 * @param array $args Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $args ) {
		$data = array();

		$products = wc_get_products( $args );

		if ( empty( $products ) ) {
			return rest_ensure_response( $data );
		}

		foreach ( $products as $product ) {
			$data[] = $this->prepare_item_for_response( $product );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param \WC_Product $product Product object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function prepare_item_for_response( $product ) {
		return array(
			'id'            => $product->get_id(),
			'sku'           => $product->get_sku(),
			'title'         => $product->get_title(),
			'permalink'     => $product->get_permalink(),
			'image'         => $this->get_image_url( $product ),
			'last_modified' => $this->get_last_modified_string( $product ),
			'embed_text'    => array(
				'short_description' => sanitize_text_field( $product->get_short_description() ),
				'description'       => sanitize_text_field( $product->get_description() ),
				'categories'        => $this->get_taxonomy_string( $product->get_category_ids(), 'product_cat' ),
				'details'           => $this->get_details( $product ),
			),
			'metadata'      => array(
				'tags'   => $this->get_taxonomy_string( $product->get_tag_ids(), 'product_tag' ),
				'weight' => $this->get_weight_variants( $product ),
				'brand'  => $this->get_brand( $product ),
			),
		);
	}

	/**
	 * Get last modified date string
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function get_last_modified_string( $product ) {
		$last_modified = new WC_DateTime( $product->get_date_modified() );
		return $last_modified->__toString();
	}

	/**
	 * Get product image URL
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function get_image_url( $product ) {
		if ( $product->get_image_id() ) {
			$image_id = $product->get_image_id();
		} elseif ( $product->get_parent_id() ) {
			$parent   = wc_get_product( $product->get_parent_id() );
			$image_id = $parent->get_image_id();
		} else {
			$image_id = get_option( 'woocommerce_placeholder_image', 0 );
		}

		return wp_get_attachment_image_url( $image_id );
	}

	/**
	 * Get product categories
	 *
	 * @param array  $term_ids Taxonomy term ids.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private function get_taxonomy_string( $term_ids, $taxonomy ) {
		$term_names = array();

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $taxonomy );

			if ( is_wp_error( $term ) || ! isset( $term ) ) {
				continue;
			}

			$term_names[] = $term->name;
		}

		return implode( ', ', $term_names );
	}

	/**
	 * Get product weight variants
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function get_weight_variants( $product ) {
		if ( $product->is_type( 'simple' ) ) {
			return array( (float) $product->get_weight() );
		}

		$variations = $product->get_children();
		$weight     = array();

		foreach ( $variations as $variation ) {
			$variant  = wc_get_product( $variation );
			$weight[] = (float) $variant->get_weight();
		}

		return $weight;
	}

	/**
	 * Get product details - attributes & other data
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	private function get_details( $product ) {
		$taxonomies = array( 'pa_smak', 'pa_gatunek-kakao' );
		$terms      = wp_get_object_terms( $product->get_id(), $taxonomies );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$attributes = array_map( fn( $term ) => $term->name, $terms );

		if ( class_exists( 'ACF' ) ) {
			$field_name     = ACF_PRODUCT_DETAILS;
			$acf_attributes = get_field( $field_name, $product->get_id() );

			if ( $acf_attributes ) {
				foreach ( $acf_attributes as $attribute ) {
					$attributes[] = $attribute[ ACF_PRODUCT_DETAILS_LABEL ] . ': ' . $attribute[ ACF_PRODUCT_DETAILS_VALUE ];
				}
			}
		}

		return $attributes;
	}

	/**
	 * Get product brand
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	private function get_brand( $product ) {
		$terms = wp_get_object_terms( $product->get_id(), 'pa_brand' );

		if ( is_wp_error( $terms ) ) {
			return '';
		}

		return implode( ', ', array_map( fn( $term ) => $term->name, $terms ) );
	}
}

/**
 * Register RAG REST route
 */
add_action(
	'rest_api_init',
	function () {
		$controller = new Chocante_RAG();
		$controller->register_routes();
	}
);

/**
 * Register RAG ability
 */
add_action(
	'wp_abilities_api_init',
	function () {
		$controller = new Chocante_RAG();

		wp_register_ability(
			'chocante/get-rag-products',
			array(
				'label'               => 'Get RAG product data',
				'description'         => 'Retrieve product data to use in RAG db',
				'category'            => 'site',
				'input_schema'        => array(
					'oneOf' => array(
						array(
							'type'       => 'object',
							'properties' => $controller->get_items_args(),
						),
						array( 'type' => 'null' ),
					),
				),
				'output_schema'       => $controller->get_item_schema(),
				'execute_callback'    => function ( $input ) use ( $controller ) {
					$args = array(
						'limit'  => $input['per_page'] ?? 10,
						'page'   => $input['page'] ?? 1,
						'status' => $input['status'] ?? 'publish',
					);

					if ( ! empty( $input['include'] ) ) {
						$args['include'] = $input['include'];
					}

					if ( ! empty( $input['modified_after'] ) ) {
						$args['date_modified'] = '>' . $input['modified_after'];
					}

					$response = $controller->get_items( $args );
					return $response->get_data();
				},
				'permission_callback' => fn () => current_user_can( 'read' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}
);
