<?php
/**
 * Product tags
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\ProductTags;

defined( 'ABSPATH' ) || exit;

// Admin.
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\scripts' );
add_action( 'product_tag_add_form_fields', __NAMESPACE__ . '\add_thumbnail_field' );
add_action( 'product_tag_edit_form_fields', __NAMESPACE__ . '\edit_thumbnail_field', 10, 1 );
add_action( 'created_term', __NAMESPACE__ . '\thumbnail_field_save', 10, 1 );
add_action( 'edit_term', __NAMESPACE__ . '\thumbnail_field_save', 10, 1 );
add_filter( 'woocommerce_sortable_taxonomies', __NAMESPACE__ . '\sort_tags' );
add_filter( 'manage_edit-product_tag_columns', __NAMESPACE__ . '\columns' );
add_filter( 'manage_product_tag_custom_column', __NAMESPACE__ . '\column', 10, 3 );

/**
 * Enqueue scripts.
 *
 * @return void
 */
function scripts() {
	$screen = get_current_screen();

	if ( in_array( $screen->id, array( 'edit-product_tag' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}
}

/**
 * Tag thumbnails.
 */
function add_thumbnail_field() {
	global $woocommerce;
	?>
	<div class="form-field">
	<label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label>
	<div id="product_tag_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60px" height="60px" /></div>
	<div style="line-height:60px;">
		<input type="hidden" id="product_tag_thumbnail_id" name="product_tag_thumbnail_id" />
		<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
		<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
	</div>
	<script type="text/javascript">

		jQuery(function(){
		// Only show the "remove image" button when needed
		if ( ! jQuery('#product_tag_thumbnail_id').val() ) {
			jQuery('.remove_image_button').hide();
		}

		// Uploading files
		var file_frame;

		jQuery(document).on( 'click', '.upload_image_button', function( event ){

			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( file_frame ) {
			file_frame.open();
			return;
			}

			// Create the media frame.
			file_frame = wp.media.frames.downloadable_file = wp.media({
			title: '<?php echo esc_js( __( 'Choose an image', 'woocommerce' ) ); ?>',
			button: {
				text: '<?php echo esc_js( __( 'Use image', 'woocommerce' ) ); ?>',
			},
			multiple: false
			});

			// When an image is selected, run a callback.
			file_frame.on( 'select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();

			jQuery('#product_tag_thumbnail_id').val( attachment.id );
			jQuery('#product_tag_thumbnail img').attr('src', attachment.url );
			jQuery('.remove_image_button').show();
			});

			// Finally, open the modal.
			file_frame.open();
		});

		jQuery(document).on( 'click', '.remove_image_button', function( event ){
			jQuery('#product_tag_thumbnail img').attr('src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>');
			jQuery('#product_tag_thumbnail_id').val('');
			jQuery('.remove_image_button').hide();
			return false;
		});
		});

	</script>
	<div class="clear"></div>
	</div>
	<?php
}

/**
 * Edit thumbnail field row.
 *
 * @param WP_Term $term     Current taxonomy term object.
 */
function edit_thumbnail_field( $term ) {
	global $woocommerce;

	$image        = '';
	$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $thumbnail_id ) {
		$image = wp_get_attachment_url( $thumbnail_id );
	}
	if ( empty( $image ) ) {
		$image = wc_placeholder_img_src();
	}
	?>
	<tr class="form-field">
	<th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label></th>
	<td>
		<div id="product_tag_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /></div>
		<div style="line-height:60px;">
		<input type="hidden" id="product_tag_thumbnail_id" name="product_tag_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
		<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
		<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
		</div>
		<script type="text/javascript">

		jQuery(function(){

			// Only show the "remove image" button when needed
			if ( ! jQuery('#product_tag_thumbnail_id').val() )
			jQuery('.remove_image_button').hide();

			// Uploading files
			var file_frame;

			jQuery(document).on( 'click', '.upload_image_button', function( event ){

			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( file_frame ) {
				file_frame.open();
				return;
			}

			// Create the media frame.
			file_frame = wp.media.frames.downloadable_file = wp.media({
				title: '<?php echo esc_js( __( 'Choose an image', 'woocommerce' ) ); ?>',
				button: {
				text: '<?php echo esc_js( __( 'Use image', 'woocommerce' ) ); ?>',
				},
				multiple: false
			});

			// When an image is selected, run a callback.
			file_frame.on( 'select', function() {
				attachment = file_frame.state().get('selection').first().toJSON();

				jQuery('#product_tag_thumbnail_id').val( attachment.id );
				jQuery('#product_tag_thumbnail img').attr('src', attachment.url );
				jQuery('.remove_image_button').show();
			});

			// Finally, open the modal.
			file_frame.open();
			});

			jQuery(document).on( 'click', '.remove_image_button', function( event ){
			jQuery('#product_tag_thumbnail img').attr('src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>');
			jQuery('#product_tag_thumbnail_id').val('');
			jQuery('.remove_image_button').hide();
			return false;
			});
		});

		</script>
		<div class="clear"></div>
	</td>
	</tr>
	<?php
}

/**
 * Saves thumbnail field.
 *
 * @param int $term_id Term ID.
 *
 * @return void
 */
function thumbnail_field_save( $term_id ) {
	if ( isset( $_POST['product_tag_thumbnail_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_term_meta( $term_id, 'thumbnail_id', absint( $_POST['product_tag_thumbnail_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
}

/**
 * Sort tags function.
 *
 * @param array $sortable Sortable array.
 */
function sort_tags( $sortable ) {
	$sortable[] = 'product_tag';
	return $sortable;
}

/**
 * Columns function.
 *
 * @param mixed $columns Columns.
 */
function columns( $columns ) {
	if ( empty( $columns ) ) {
		return $columns;
	}

	$new_columns          = array();
	$new_columns['cb']    = $columns['cb'];
	$new_columns['thumb'] = __( 'Image', 'woocommerce' );
	unset( $columns['cb'] );
	$columns = array_merge( $new_columns, $columns );
	return $columns;
}

/**
 * Column function.
 *
 * @param mixed $columns Columns.
 * @param mixed $column Column.
 * @param mixed $id ID.
 */
function column( $columns, $column, $id ) {
	if ( 'thumb' === $column ) {
		global $woocommerce;

		$image        = '';
		$thumbnail_id = get_term_meta( $id, 'thumbnail_id', true );

		if ( $thumbnail_id ) {
			$image = wp_get_attachment_url( $thumbnail_id );
		}
		if ( empty( $image ) ) {
			$image = wc_placeholder_img_src();
		}

		$columns .= '<img src="' . $image . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';

	}
	return $columns;
}

/**
 * Helper function :: wc_get_tag_thumbnail_url function.
 *
 * @param  int    $tag_id Tag ID.
 * @param  string $size Thumbnail image size.
 * @return string
 */
function get_tag_thumbnail_url( $tag_id, $size = array( 56, 56 ) ) {
	$thumbnail_id = get_term_meta( $tag_id, 'thumbnail_id', true );

	if ( $thumbnail_id ) {
		$thumb_src = wp_get_attachment_image_src( $thumbnail_id, $size );
	}

	return ! empty( $thumb_src ) ? current( $thumb_src ) : '';
}

/**
 * Display product diet icons
 *
 * @param int $product_id Product tags taxonomy terms.
 */
function display_diet_icons( $product_id ) {
	$icons = array();
	$tags  = get_product_tags( $product_id );

	foreach ( $tags as $tag ) {
		$thumbnail = get_tag_thumbnail_url( $tag->term_id );

		if ( ! $thumbnail ) {
			continue;
		}

		$icons[] = array(
			'thumbnail' => $thumbnail,
			'name'      => $tag->name,
		);
	}

	if ( ! empty( $icons ) ) {
		get_template_part( 'template-parts/product', 'diet-tags', array( 'product_tags' => $icons ) );
	}
}

/**
 * Display product diet icons on product page
 */
function display_diet_icons_product_page() {
	global $product;

	display_diet_icons( $product->get_id() );
}

/**
 * Get featured product diet icons
 *
 * @param int $product_id Product ID.
 * @return array
 */
function get_product_tags( $product_id ) {
	$icons = wc_get_product_terms( $product_id, 'product_tag' );

	return $icons;
}