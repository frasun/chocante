<?php
/**
 * Abilities / MCP settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Abilities;

add_action( 'wp_abilities_api_init', __NAMESPACE__ . '\retrieve_posts' );

/**
 * Ability to retireve posts
 */
function retrieve_posts() {
	wp_register_ability(
		'chocante/get-posts',
		array(
			'label'               => 'Get Posts',
			'description'         => 'Retrieve WordPress posts with optional filtering',
			'category'            => 'site',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'numberposts' => array(
						'type'        => 'integer',
						'description' => 'Number of posts to retrieve',
						'default'     => 5,
						'minimum'     => 1,
						'maximum'     => 100,
					),
					'post_status' => array(
						'type'        => 'string',
						'description' => 'Post status to filter by',
						'enum'        => array( 'publish', 'draft', 'private' ),
						'default'     => 'publish',
					),
				),
			),
			'output_schema'       => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'ID'           => array( 'type' => 'integer' ),
						'post_title'   => array( 'type' => 'string' ),
						'post_content' => array( 'type' => 'string' ),
						'post_date'    => array( 'type' => 'string' ),
					),
				),
			),
			'execute_callback'    => function ( $input ) {
				$args = array(
					'numberposts' => $input['numberposts'] ?? 5,
					'post_status' => $input['post_status'] ?? 'publish',
				);

				return array_map(
					function ( $post ) {
						return array(
							'ID'           => $post->ID,
							'post_title'   => $post->post_title,
							'post_content' => $post->post_content,
							'post_date'    => $post->post_date,
						);
					},
					get_posts( $args )
				);
			},
			'permission_callback' => function () {
				return current_user_can( 'read' );
			},
			'meta'                => array(
				'mcp' => array(
					'public' => true,
					'type'   => 'tool',
				),
			),
		)
	);
}
