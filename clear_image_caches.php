<?php
/**
 * This file contains code to work with an Ajax call to clear out all the image caches
 *
 * @package WordPress
 * @subpackage webphysiology-portfolio plugin
 * @since webphysiology-portfolio 1.3.1
 */

/*  UPDATES

	1.4.2 - added a call to a new function to clear out thumbnails created within the uploads directory
	      - removed the clearing of the imageresizer/cache directory as it was removed
	
*/


if ( ! session_id() ) {
	session_start();
}

if ( isset($_SESSION['cache']) ) {
	
	global $have_wpload;
	
	$have_wpload = false;
	
	// get this file's directory
	$dir = dirname(__FILE__) . '/';
	
	// define the path to the wp-load.php file
	$load = str_replace("wp-content/plugins/webphysiology-portfolio/","",$dir) . 'wp-load.php';
	
	if ( file_exists( $load ) ) {
		require_once($load);
		$have_wpload = true;
	}
	
	$dir = $_SESSION['cache'] . "file_functions.php";
	
	include_once($dir);
	
	$dir = str_replace("clear_image_caches.php","",__FILE__)."/temp";
	cleardir($dir);
	
//	$dir = str_replace("clear_image_caches.php","",__FILE__)."/scripts/imageresizer/cache";
//	cleardir($dir);
	
	$dir = str_replace("clear_image_caches.php","",__FILE__)."/scripts/stw/cache";
	cleardir($dir);
	
	// clear out re-sized images created within the uploads directory
	webphys_portfolio_delete_uploaded_images();
	
	check_temp_dir();
	
	echo "Image Caches Cleared";
	
} else {
	
	echo "Unable to Clear Image Caches";
	
}

// delete thumbnail images that were created within the uploads directories
function webphys_portfolio_delete_uploaded_images() {
	
	global $wpdb;
	global $have_wpload;
	
	if ( ! $have_wpload ) { return; }
	
	$upload = wp_upload_dir();
	$upload_url = $upload[baseurl];
	$upload_dir = $upload[basedir];
	$plugins_dir = str_replace("/uploads","/plugins/webphysiology-portfolio/",$upload_dir);
	$width = get_option('webphysiology_portfolio_image_width');
	$delete_files = "";
	
	$sql = $wpdb->prepare ("SELECT	DISTINCT meta_value
			FROM	$wpdb->postmeta sp
			WHERE	meta_key = '_imageurl'
			AND		meta_value LIKE %s
			AND		EXISTS (	SELECT	1
								FROM	$wpdb->postmeta ssp
								WHERE	sp.post_id = sp.post_id
								AND		ssp.meta_key = '_webphys_portfolio_type')
			UNION
			SELECT	option_value 'meta_value'
			FROM	$wpdb->options
			WHERE	option_name = 'webphysiology_portfolio_missing_image_url'
			ORDER BY meta_value", $upload[baseurl] . "%");
	
	$images = $wpdb->get_col($sql);
	
	if ($images) {
		
		foreach ($images as $image) {
			
			if ( $image != "images/empty_window.png" ) {
			
				// grab the path to the thumbnail image that matches the current Portfolio image width
				$img = str_ireplace($upload_url,$upload_dir,str_ireplace(array(".png",".jpg",".gif"),"",$image) . "-" . $width . "*");
				
			} else {
				
				$img = $plugins_dir . str_ireplace(array(".png",".jpg",".gif"),"",$image) . "-" . $width . "*";
				
			}
			
			webphys_portfolio_delete_files($img);
			
		}
	}
	
	return $delete_files;
}

?>