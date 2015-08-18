<?php
/*
	Plugin Name: Replace Featured Image with Video
	Plugin URI: http://www.purpleturtle.pro
	Description: Replace the featured image in your WordPress post or page with an embedded video (eg Youtube, Dailymotion and more)
	Version: 2.1
	Author: Purple Turtle Productions
	Author URI: http://www.w3bdesign.ca/
*/

//***********************************************************
// Add box to Post page (Admin side)
//***********************************************************
if ( is_admin() ) {
	add_action( 'save_post',  'save_related_video_item' );
	add_action( 'add_meta_boxes', 'add_fv_box_fields' );
}

function add_fv_box_fields() {
	add_meta_box( 'docs_list', __( 'Featured Video', 'related-video' ), 'admin_fv_box_html', 'post', 'side' );
}

function admin_fv_box_html() {

	global $post;

	$rel_video_url = get_post_meta( $post->ID, '_related-video-url', true );
	$rel_video = get_post_meta( $post->ID, '_related-video', true );

	echo '
	<div>Video URL:</div>
	<div><input type="text" name="videoEmbedURL" id="videoEmbedURL" class="widefat" value="' . $rel_video_url . '" onchange="setVideoURL()" /></div>
	<div><p>Video embed code:</p>
		<textarea name="rel_video" id="videoEmbedCode" class="widefat" style="height:220px;">' . $rel_video . '</textarea>
		<input type="hidden" name="docs_check_nonce" id="docs_check_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />
	</div>';

}

//***********************************************************
// Add JavaScript to Admin side
//***********************************************************
add_action( 'admin_enqueue_scripts', 'register_relatedvideo_scripts' );

function register_relatedvideo_scripts() {

	wp_enqueue_script( 'jquery' );

	wp_register_script( 'related-video-script', plugins_url( 'js/pt-page-featured-video.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'related-video-script' );

}

//***********************************************************
// When User Clicks Save Post...
//***********************************************************
function save_related_video_item(){

	global $post;

	if (!isset($post_id) || ($post_id == null))
		$post_id = $post->ID;

	// security check, return the post id if access denied
	if (isset($_POST['docs_check_nonce']) && !wp_verify_nonce( $_POST['docs_check_nonce'], plugin_basename(__FILE__) ))
		return $post_id;

	// return post id and bypass autosaves as well
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;

	// checking the current user permissions ....
	if ( $_POST['post_type'] == 'page' ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	if (isset($_POST['fvLicenseKey']) )
		update_option('fv_licensekey', $_POST['fvLicenseKey']);

	if ( isset($_POST['rel_video']) ) {
		// clean all doc meta, we are going to add all current meta again ...
		delete_post_meta($post_id, '_related-video');
		delete_post_meta($post_id, '_related-video-url');

		if (strlen($_POST['videoEmbedURL']) == 0) {
			$videoEmbedURL = $_POST['videoEmbedURL'];
			add_post_meta( $post_id, '_related-video-url', $videoEmbedURL );

			$rel_video = $_POST['rel_video'];
			add_post_meta( $post_id, '_related-video', $rel_video );
		}
	}

}

//***********************************************************
// Replace the video
//***********************************************************
function filter_featured_image_to_video( $html, $post_id = 0 ) {

	global $post;
	if ( isset($post) && !empty($post) ) {
		$x = get_post_meta( $post->ID, '_related-video', true);
		if (strlen($x) > 0)
			return $x;
	}

	return $html;

}

if (!is_admin())
	add_filter( 'post_thumbnail_html', 'filter_featured_image_to_video' );



?>
