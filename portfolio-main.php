<?php
/*
Plugin Name: WEBphysiology Portfolio
Plugin URI: http://webphysiology.com/redir/webphysiology-portfolio/
Description: Provides a clean Portfolio listing with image, details and portfolio type taxonomy.  A [portfolio] shortcode is used to include the portfolio on any page.
Version: 1.0.0
Author: Jeff Lambert
Author URI: http://webphysiology.com/redir/webphysiology-portfolio/author/
License: GPL2
*/

/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// TimThumb.php was utilized in order to resize the uploaded site images for display as "thumbnails" on the portfolio listing.
// 	Code was also "borrowed" from TimThumb for use in checking the existance of the specified image and replacing it with an "empty" image if it no longer existed.
//	  http://www.darrenhoyt.com/2008/04/02/timthumb-php-script-released/

// the "fancybox" jQuery code was utilized in this plugin in order to allow for a nicer, more modern display of the fullsize image.  http://fancybox.net/

// thanks also to http://www.webmaster-source.com/2010/01/08/using-the-wordpress-uploader-in-your-plugin-or-theme/ for getting me on the road to adding the upload image feature

// in addition to the WordPress Codex and support site, I picked up some good info while working on this plugin from the following Posts:
//     http://wptheming.com/2010/08/custom-metabox-for-post-type/
//     http://scribu.net/wordpress/custom-sortable-columns.html
//     http://shibashake.com/wordpress-theme/add-custom-post-type-columns



/**********
// ASTERISK - future tasks
// turn into a Class
// break functionality into separate scripts
// add ability to include / exlude multiple variations of Portfolio Types
**********/


// if the Ozh Admin Menu plugin is being used, add the JVHM icon to the menu portfolio menu item
function RegisterAdminIcon($hook) {
	if ( $hook == plugin_basename(__FILE__) && function_exists('plugins_url')) {
		return plugins_url('images/jvhm_pinwheel_bullet.png',plugin_basename(__FILE__));
	}
	return $hook;
}

// Nice icon for Admin Menu (requires Ozh Admin Drop Down Plugin)
add_filter('ozh_adminmenu_icon', 'RegisterAdminIcon');


// add links to the plugin list for the Portfolio plugin such that a user can get to Settings and other links from that screen
function RegisterPluginLinks($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$x = str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$links[] = '<a href="options-general.php?page=' . $x .'">' . __('Settings','Portfolio') . '</a>';
		$links[] = '<a href="http://webphysiology.com/redir/webphysiology-portfolio/support/">' . __('Support','sitemap') . '</a>';
		$links[] = '<a href="http://webphysiology.com/redir/webphysiology-portfolio/donate/">' . __('Donate','sitemap') . '</a>';
	}
	return $links;
}

// Additional links on the plugin page
add_filter('plugin_row_meta', 'RegisterPluginLinks',10,2);



//*************************************************//
//*************************************************//
//*************************************************//
//******  PORTFOLIO REGISTRATION CODE START  ******//
//*************************************************//
//*************************************************//
//*************************************************//

// Define and register the Portfolio custom post type
function portfolio_post_type_init() 
{
	$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'/images/jvhm_pinwheel_bullet.png';

	$labels = array(
		'name' => __('Portfolio', 'post type general name'),
		'singular_name' => __('Portfolio', 'post type singular name'),
		'add_new' => __('Add Portfolio', 'Portfolio'),
		'add_new_item' => __('Add Portfolio'),
		'edit_item' => __('Edit Portfolio'),
		'new_item' => __('New Portfolio'),
		'view_item' => __('View Portfolio'),
		'search_items' => __('Search Portfolios'),
		'not_found' =>  __('No Portfolios found'),
		'not_found_in_trash' => __('No Portfolios found in Trash'), 
		'parent_item_colon' => ''
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'query_var' => true,
		'rewrite' => false, // since we aren't pushing to single pages we don't need a re-write rule or permastructure
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => 5,
		'menu_icon' => $x,
		'supports' => array('title','editor','author'),
		'register_meta_box_cb' => 'add_portfolio_metaboxes',
		'taxonomies' => array('portfolio_type')
	); 
	
	register_post_type('Portfolio',$args);
}


// add "portfoliotype" into the recognized set of query variables
function portfolio_queryvars( $qvars )
{
  $qvars[] = 'portfoliotype';
  return $qvars;
}

// augment the JOIN if a Portfolio Type is part of the search
function portfolio_search_join( $join )
{
	global $wpdb, $wp_query;
	
	// if the portfolio type has been defined in the search vars
	if( isset( $wp_query->query_vars['portfoliotype'] )) {
		
		// if the JOIN statement currently is not empty append a 'LEFT OUTER JOIN'
		if (!empty($join)) $join .= " LEFT OUTER JOIN ";
		
		// add the join to the wp_postmeta table for meta records that are of a Portfolio Type
		$join .=  " " . $wpdb->prefix . "postmeta AS port ON (" . $wpdb->posts . ".ID = port.post_id AND port.meta_key = '_portfolio_type') ";
		
	}
	
	return $join;
}


// augment the WHERE clause if a Portfolio Type is part of the search
function portfolio_search_where( $where )
{
	global $wp_query;
	
	// if the portfolio type has been defined in the search vars
	if( isset( $wp_query->query_vars['portfoliotype'] )) {
		
		$types = get_query_var('portfoliotype');
		
		// if the WHERE statement currently is not empty append an 'AND'
		if (!empty($where)) $where .= " AND ";
		
		
		// * asterisk - my code follow-up flag *
		//  at some time in the future I hope to further the search conditions to allow for a mixed set of portfolios where
		//   some are defined to be included and others excluded.  for now one may specify one or more to include OR one to exclude
		
		
		// if the specified Portfolio Type does NOT lead with a '-', which would indicate a NOT EQUAL search
		if (substr($types, 0, 1) != '-' ) {
			$where .= " port.meta_value IN ('" . str_replace(',', "','", $types) . "')";
		} else {
			$where .= " IFNULL(port.meta_value,'BLAHBLAH') != '" . substr($types, 1) . "'";
		}
	}
	
	return $where;
}

add_filter('query_vars', 'portfolio_queryvars' );
add_filter('posts_join', 'portfolio_search_join' );
add_filter('posts_where', 'portfolio_search_where' );


// Define the Portfolio custom post type update messages
function portfolio_updated_messages( $messages ) {
	
	global $post, $post_ID;
	
	$messages['portfolio'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Portolios updated. <a href="%s">View portfolio</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Portfolio updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Portfolio restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Portfolio published. <a href="%s">View Portfolio</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Portfolio saved.'),
		8 => sprintf( __('Portfolio submitted. <a target="_blank" href="%s">Preview portfolio</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Portfolio scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview portfolio</a>'),
		  // translators: Publish box date format, see http://php.net/date
		  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Portfolio draft updated. <a target="_blank" href="%s">Preview portfolio</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);
	
	return $messages;
}


// Define the Portfolio edit form custom fields
function webphys_portfolio_edit_init() {
	
	global $post;
 
	// Noncename needed to verify where the data originated
	wp_nonce_field( 'portfolio_edit', 'portfoliometanonce' );
	
	// Gather any existing custom data for the Portfolio
	$portfolio_type = get_post_meta($post->ID, '_portfolio_type', true);
	$datecreate = get_post_meta($post->ID, '_createdate', true);
	$client = get_post_meta($post->ID, '_clientname', true);
	$technical_details = get_post_meta($post->ID, '_technical_details', true);
	$siteurl = get_post_meta($post->ID, '_siteurl', true);
	$imageurl = get_post_meta($post->ID, '_imageurl', true);
	$sortorder = get_post_meta($post->ID, '_sortorder', true);
	if ($sortorder=="") $sortorder = "-" . $post->ID;
 
	// Gather the list of Portfolio Types
	$portfolio_type_list = get_terms('portfolio_type', 'hide_empty=0'); 
 
 	// Build out the form fields
	
	echo '<p><label for="_portfolio_type">Select Portfolio Type: </label> ';

    echo '<select name="_portfolio_type" id="_portfolio_type">';
        echo '<!-- Display portfolio types as options -->';
            echo '<option class="portfolio_type_option" value=""';
            if( !count($portfolio_type_list) || is_wp_error($portfolio_type) || empty($portfolio_type)) echo 'selected>None</option>';
        foreach ($portfolio_type_list as $portfolio_item) {
            if ($portfolio_item->slug == $portfolio_type) {
                echo '<option class="portfolio_type_option" value="' . $portfolio_item->slug . '" selected>' . $portfolio_item->name . '</option>\n'; 
			} else {
                echo '<option class="portfolio_type_option" value="' . $portfolio_item->slug . '">' . $portfolio_item->name . '</option>\n';
			}
        }
    echo '</select></p>';   
    echo '<p><label for="_createdate">Enter Date Created: </label>';
	echo '<input type="text" id="_createdate" name="_createdate" value="' . $datecreate . '" class="code" />';
	echo ' note: this is freeform text and can take on whatever form you want (e.g., YYYY or MM/YYYY ...)</p>';
    echo '<p><label for="_clientname">Enter Client Name: </label>';
	echo '<input type="text" id="_clientname" name="_clientname" value="' . $client . '" class="widefat" /></p>';
    echo '<p><label for="_technical_details">Enter Technical Details: </label>';
	echo '<input type="text" id="_technical_details" name="_technical_details" value="' . $technical_details . '" class="widefat" /></p>';
    echo '<p><label for="_siteurl">Enter Portfolio Web Page URL: </label>';
	echo '<input type="text" id="_siteurl" name="_siteurl" value="' . $siteurl . '" class="widefat" /></p>';
    echo '<p><label for="_imageurl">Enter Portfolio Image URL: </label>';
	echo '<input id="upload_image_button" type="button" value="Upload Image" /><br />';
	echo '<input type="text" id="_imageurl" name="_imageurl" value="' . $imageurl . '" class="widefat shortbottom" /><br />';
	echo '<span class="attribute_instructions">Enter the URL for the portfolio image. Clicking "Insert into Post" from &lt;Upload Image&gt; will paste the inserted image\'s URL (take care what size is selected).</span></p>';
    echo '<p><label for="_sortorder">Enter site sort order: </label>';
	echo '<input type="text" id="_sortorder" name="_sortorder" value="' . $sortorder . '" class="code" /></p>';

}

/* Add the Portfolio custom fields (called as an argument of the custom post type registration) */
function add_portfolio_metaboxes() {
	add_meta_box('webphys_portfolio_edit_init', 'Portfolio Details', 'webphys_portfolio_edit_init', 'Portfolio', 'normal', 'high');
}

// Make certain the scripts and css necessary to support the file upload button are active
function portfolio_admin_scripts() {
    $x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'scripts/';
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_register_script('portfolio-image-upload', $x . 'file_uploader.js', array('jquery','media-upload','thickbox'));
	wp_enqueue_script('portfolio-image-upload');
}

function portfolio_admin_styles() {
    $x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'scripts/fancybox/';
	wp_register_style('lightbox_css', $x . '/jquery.fancybox-1.3.1.css');
	wp_enqueue_style('lightbox_css');
	
	wp_enqueue_style('thickbox');
}

if (is_admin()) {
//if (isset($_GET['post']) ) {
    add_action('admin_print_scripts', 'portfolio_admin_scripts');
    add_action('admin_print_styles', 'portfolio_admin_styles');
}


/* define the Portfolio ShortCode and set defaults for available arguments */
function portfolio_loop($atts, $content = null) {
	
	global $nav_spread;
	global $portfolio_types;
	global $portfolio_output;
	
	extract( shortcode_atts( array(
      'max_nav_spread' => 5,
	  'portfolio_type' => '',
      ), $atts ) );
	
	$nav_spread = $max_nav_spread;
	$portfolio_types = $portfolio_type;
	$portfolio_output = '';
	
	if ( !empty($content) ) {
		$portfolio_output = '<div class="portfolio_page_content">' . $content . '</div>';
	}
	
	$portfolio_output .= '<div id="portfolios" role="main">';
	include('loop-portfolio.php');
	$portfolio_output .= '</div><!-- #portfolios -->';
	
	return $portfolio_output;
	
}


// register the Portfolio custom post type and shortcode
add_action( 'init', 'portfolio_post_type_init' );
add_filter('post_updated_messages', 'portfolio_updated_messages');
add_shortcode('portfolio', 'portfolio_loop');


// define a custom Portfolio Type taxonomy and populate it
function create_portfolio_type_taxonomy() {
	
	if (!taxonomy_exists('portfolio_type')) {
		register_taxonomy('portfolio_type', 
						  'Portfolio',
						  array('hierarchical' => false, 'show_tagcloud' => false, 'label' => __('Portfolio Types'), 'query_var' => 'portfolio_type', 'rewrite' => array( 'slug' => 'portfolio_type')));
	 	
		// if there are no Portfolio Type terms, add a default term
		if (count(get_terms('portfolio_type', 'hide_empty=0')) == 0) {
			wp_insert_term('Default', 'portfolio_type');
		}
	}
}

// register the Portfolio Type taxonomy
add_action( 'init', 'create_portfolio_type_taxonomy', 0 );

/* Define Portfolio Plugin Activation process */
function portfolio_install() {
	
	// create new Portfolio plugin database field
	add_option("webphysiology_portfolio_display_createdate", 'True'); // This is the default value for whether to display the create date
	add_option("webphysiology_portfolio_items_per_page", '3'); // This is the default value for the number of portfolio items to display per page
	add_option("webphysiology_portfolio_display_credit", "True"); // This is the default value for whether to display a plugin publisher credit
	add_option("webphysiology_portfolio_delete_options", "False"); // This is the default value for whether to delete plugin options on plugin deactivation
	add_option("webphysiology_portfolio_delete_data", "False"); // This is the default value for whether to delete Portfolio data on plugin deactivation
	add_option("webphysiology_portfolio_use_css", 'True'); // This is the default value for the Portfolio CSS usage switch
	add_option("webphysiology_portfolio_overall_width", '660'); // This is the overall width of the portfolio listing
	add_option("webphysiology_portfolio_image_width", '200'); // This is the width to use on the portfolio image in the listing
	add_option("webphysiology_portfolio_header_color", '#004813'); // This is the h1 and h2 color
	add_option("webphysiology_portfolio_link_color", '#004813'); // This is the anchor link color
	add_option("webphysiology_portfolio_odd_stripe_color", '#eeeeee'); // This is the portfolio list odd row stripe background color
	add_option("webphysiology_portfolio_even_stripe_color", '#f5f5f5'); // This is the portfolio list even row stripe background color

}
register_activation_hook(__FILE__,'portfolio_install');

// smart jquery inclusion
if (!is_admin()) {
	wp_deregister_script('jquery');
	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"), false);
	wp_enqueue_script('jquery');
}

If (is_admin()) {
	// added in v1.0.0
	if(get_option( 'webphysiology_portfolio_display_credit' ) == "") {
		add_option("webphysiology_portfolio_display_credit", "True"); // This is the default value for whether to display a plugin publisher credit
	}
	if(get_option( 'webphysiology_portfolio_delete_options' ) == "") {
		add_option("webphysiology_portfolio_delete_options", "False"); // This is the default value for whether to delete plugin options on plugin deactivation
	}
	if(get_option( 'webphysiology_portfolio_delete_data' ) == "") {
		add_option("webphysiology_portfolio_delete_data", "False"); // This is the default value for whether to delete Portfolio data on plugin deactivation
	}
}


/* Define Portfolio Plugin De-activation process */
function portfolio_remove() {
	
	$deleteoptions = strtolower(get_option( 'webphysiology_portfolio_delete_options' ));
	$deletedata = strtolower(get_option( 'webphysiology_portfolio_delete_data' ));
	
	// if the option to delete options is set, delete the Portfolio options data
	//   note: Portfolio data cannot be deleted if options are not deleted too
	if ( $deleteoptions == "true" ) {
		
		/* Deletes the Portfolio plugin database field */
		delete_option('webphysiology_portfolio_display_createdate');
		delete_option('webphysiology_portfolio_items_per_page');
		delete_option('webphysiology_portfolio_display_credit');
		delete_option('webphysiology_portfolio_delete_options');
		delete_option('webphysiology_portfolio_delete_data');
		delete_option('webphysiology_portfolio_use_css');
		delete_option('webphysiology_portfolio_overall_width');
		delete_option('webphysiology_portfolio_image_width');
		delete_option('webphysiology_portfolio_header_color');
		delete_option('webphysiology_portfolio_link_color');
		delete_option('webphysiology_portfolio_odd_stripe_color');
		delete_option('webphysiology_portfolio_even_stripe_color');
		
	}
	
	// if the delete data option is set to delete, then delete the Portfolio records and Portfolio Type taxonomy records
	if ( $deletedata == "true" ) {
		
		// Gather the Portfolios
		$portfolios_to_delete = new WP_Query(array('post_type' => 'Portfolio', 'post_status' => 'any', 'orderby' => 'ID', 'order' => 'DESC'));
		
		// Loop through and delete the Portfolios
		if ( $portfolios_to_delete->have_posts() ) {
			while ( $portfolios_to_delete->have_posts() ) : $portfolios_to_delete->the_post();
				wp_delete_post( get_the_id(), true );
			endwhile;
		}

		// Gather the list of Portfolio Types
		$portfolio_type_list = get_terms('portfolio_type', 'hide_empty=0');
		
		// Loop thru the types and delete each one, the last will clear the taxonomy
		foreach ($portfolio_type_list as $portfolio_item) {
			wp_delete_term( $portfolio_item->term_id, 'portfolio_type' );
		}
	}

}
register_deactivation_hook( __FILE__, 'portfolio_remove' );


//*************************************************//
//*************************************************//
//*************************************************//
//*******  PORTFOLIO REGISTRATION CODE END  *******//
//*************************************************//
//*************************************************//
//*************************************************//




//*************************************************//
//*************************************************//
//*************************************************//
//***** PORTFOLIO ADMIN INTERFACE CODE START  *****//
//*************************************************//
//*************************************************//
//*************************************************//


//*************************************************//
//******* PORTFOLIO EDIT SCREEN CODE START  *******//
//*************************************************//

// Define the Save Metabox Data routine
function save_portfolio_meta($post_id, $post) {
	
	// verify this call is the result of a POST
	if ( empty($_POST) ) {
		return $post->ID;
	}
 
	// if the user isn't saving a portfolio
	if (strtolower($_POST['post_type']) != "portfolio") {
		return $post->ID;
	}
	
	// verify this came from our screen and with proper authorization, because save_post can be triggered at other times
	if ( !check_admin_referer('portfolio_edit','portfoliometanonce') ) {
		return $post->ID;
	}
 
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post->ID )) {
		return $post->ID;
	}
 
	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though.
 	
	$portfolio_meta['_portfolio_type'] = $_POST['_portfolio_type'];
	$portfolio_meta['_createdate'] = $_POST['_createdate'];
	$portfolio_meta['_clientname'] = $_POST['_clientname'];
	$portfolio_meta['_technical_details'] = $_POST['_technical_details'];
	$portfolio_meta['_siteurl'] = $_POST['_siteurl'];
	$portfolio_meta['_imageurl'] = $_POST['_imageurl'];
	if (!empty($_POST['_sortorder']) && (int)$_POST['_sortorder'] != 0 && is_numeric($_POST['_sortorder'])) {
		$portfolio_meta['_sortorder'] = $_POST['_sortorder'];
	} else {
		$portfolio_meta['_sortorder'] = -1*($post->ID);
	}
	
 
	// Add values of $portfolio_meta as custom fields
 
	foreach ($portfolio_meta as $key => $value) { // Cycle through the $portfolio_meta array!
		if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
			update_post_meta($post->ID, $key, $value);
		} else { // If the custom field doesn't have a value
			add_post_meta($post->ID, $key, $value);
		}
		if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
	}
 
}
 
// Add the Save Metabox Data
add_action('save_post', 'save_portfolio_meta', 1, 2); // save the custom fields
	
// remove the Porfolio Type tag sidebar widget from the Portfolio edit screen as the Portfolio Type dropdown manages this
function remove_post_custom_fields() {
	remove_meta_box( 'tagsdiv-portfolio_type' , 'Portfolio' , 'side' );
}
add_action( 'admin_menu' , 'remove_post_custom_fields' );

//*************************************************//
//******** PORTFOLIO EDIT SCREEN CODE END  ********//
//*************************************************//


//*************************************************//
//********* PORTFOLIO LISTING CODE START  *********//
//*************************************************//

/* Register the Portfolio columns to display in the Portfolio Admin listing */
function add_new_portfolio_columns($columns) {
	
	// note: columns in the listing are ordered in line with where they are created below
	$new_columns['title'] = _x('Portfolio Name', 'column name');
	$new_columns['_createdate'] = _x( 'Create Date', 'column name' );
	$new_columns['_clientname'] = _x( 'Client', 'column name' );
	$new_columns['_technical_details'] = _x( 'Technical Details', 'column name' );
	$new_columns['_siteurl'] = _x( 'Website URL', 'column name' );
	$new_columns['_portfolio_type'] = _x( 'Type', 'column name' );
	$new_columns['_sortorder'] = _x( 'Sort Order', 'column name' );
	$new_columns['date'] = _x('Create Date', 'column name');
	$new_columns['id'] = __('ID');

	return $new_columns;
}
add_filter('manage_edit-Portfolio_columns', 'add_new_portfolio_columns');


/* Define the data retrieval arguments for the Portfolio list columns */
function manage_portfolio_columns($column_name, $id) {
	global $wpdb;
	switch ($column_name) {
	case '_sortorder':
		echo get_post_meta( $id , '_sortorder' , true );
		break;
	case '_createdate':
		// Get the date the Portfolio was created, typically a year
		echo get_post_meta( $id , '_createdate' , true );
		break;
	case '_clientname':
		// Get the name of the client for whom the development was performed
		echo get_post_meta( $id , '_clientname' , true );
		break;
	case '_technical_details':
		// Get the technical details
		echo get_post_meta( $id , '_technical_details' , true );
		break;
	case '_siteurl':
		// Get the URL to the actual website
		echo get_post_meta( $id , '_siteurl' , true );
		break;
	case '_portfolio_type':
		// Get the Portfolio Type
		$type = get_post_meta( $id , '_portfolio_type' , true );
		$portfolio_type = get_term_by( 'slug', $type, 'portfolio_type' );
		if (isset($portfolio_type->name)) {
			echo $portfolio_type->name;
		} else {
			echo "";
		}
		break;
	case 'id':
		//Get the Post ID
		echo ($id);
		break;
	default:
		break;
	} // end switch
}
add_action('manage_posts_custom_column', 'manage_portfolio_columns', 10, 2);

//*************************************************//
//********** PORTFOLIO LISTING CODE END  **********//
//*************************************************//



//*************************************************//
//*************************************************//
//*************************************************//
//****** PORTFOLIO ADMIN INTERFACE CODE END  ******//
//*************************************************//
//*************************************************//
//*************************************************//


//*************************************************//
//*************************************************//
//*************************************************//
//******* PORTFOLIO PLUGIN ADMIN CODE START *******//
//*************************************************//
//*************************************************//
//*************************************************//


// if the current user is an administrator
if ( is_admin() ) {

	// add stylesheet link to the header of the Admin area
	function portfolio_admin_css() {
		$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'css/';
		wp_register_style('portfolio_admin_css', $x . 'portfolio_admin.css');
		wp_enqueue_style('portfolio_admin_css');
	}
	
	// Add Portfolio settings menu item
	function portolio_admin_menu() {
		add_options_page('WEBphysiology Portfolio', 'Portfolio', 'administrator', 'webphysiology-portfolio', 'portfolio_plugin_page');
	}
	
	add_action('init', 'portfolio_admin_css');
	add_action('admin_menu', 'portolio_admin_menu');
	
}


// define the Portfolio Plugin settings admin page
function portfolio_plugin_page() {

	echo '<div class="wrap portfolio-admin">';
    echo '	<div class="company_logo">';
    echo '        <a href="http://WEBphysiology.com/">&nbsp;</a>';
    echo '        <div id="icon-plugins" class="icon32"></div><h2>Portfolio Configuration</h2>';
    echo '    </div>';
    echo '    <div class="postbox-container">';
    echo '        <div class="metabox-holder">';
    echo '            <div class="meta-box-sortables">';

    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
	
    // variables for the field and option names
	$hidden_field_name = 'webphys_submit_hidden';
	$display_createdate = 'webphysiology_portfolio_display_createdate'; // default true
	$items_per_page = 'webphysiology_portfolio_items_per_page';  // default 3
	$display_credit = 'webphysiology_portfolio_display_credit'; // default true
	$delete_options = 'webphysiology_portfolio_delete_options'; // default false
	$delete_data = 'webphysiology_portfolio_delete_data'; // default false
	$use_css = 'webphysiology_portfolio_use_css'; // default true
	$overall_width = 'webphysiology_portfolio_overall_width'; // default is 660px
	$img_width = 'webphysiology_portfolio_image_width'; // default is 200px
	$header_color = 'webphysiology_portfolio_header_color'; // default is #004813
	$link_color = 'webphysiology_portfolio_link_color'; // default is #004813
	$odd_stripe_color = 'webphysiology_portfolio_odd_stripe_color'; // default is #eee
	$even_stripe_color = 'webphysiology_portfolio_even_stripe_color'; // default is #f5f5f5


    // See if the user has posted us some information.  If they did, this hidden field will be set to 'Y'.
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		
        // Read their posted value
		if ( isset($_POST[ $display_createdate ]) ) {
			$opt_val_display_createdate = $_POST[ $display_createdate ];
			if ($opt_val_display_createdate == "") $opt_val_display_createdate = "False";
		} else {
			$opt_val_display_createdate = "False";
		}
		$opt_val_items_per_page = $_POST[ $items_per_page ];
		if ( isset($_POST[ $display_credit ]) ) {
			$opt_val_display_credit = $_POST[ $display_credit ];
			if ($opt_val_display_credit == "") $opt_val_display_credit = "False";
		} else {
			$opt_val_display_credit = "False";
		}
		if ( isset($_POST[ $delete_options ]) ) {
			$opt_val_delete_options = $_POST[ $delete_options ];
			if ($opt_val_delete_options == "") $opt_val_delete_options = "False";
		} else {
			$opt_val_delete_options = "False";
		}
		if ( isset($_POST[ $delete_data ]) ) {
			$opt_val_delete_data = $_POST[ $delete_data ];
			if ($opt_val_delete_data == "") $opt_val_delete_data = "False";
		} else {
			$opt_val_delete_data = "False";
		}
		$opt_val_css = $_POST[ $use_css ];
		$opt_val_overall_width = $_POST[ $overall_width ];
		$opt_val_img_width = $_POST[ $img_width ];
		$opt_val_header_color = $_POST[ $header_color ];
		$opt_val_link_color = $_POST[ $link_color ];
		$opt_val_odd_stripe_color = $_POST[ $odd_stripe_color ];
		$opt_val_even_stripe_color = $_POST[ $even_stripe_color ];
		
		$validated = true;
		$validation_msg = __('settings saved.', 'Portfolio' );
		
		// do some validating on whether the resulting widths will work or not
		if (!is_numeric($opt_val_items_per_page)) {
			$validated = false;
			$validation_msg = __('settings NOT saved - items per page must be stated as a numeric value.', 'Portfolio' );
		} elseif (!is_numeric($opt_val_overall_width) || !is_numeric($opt_val_img_width)) {
			$validated = false;
			$validation_msg = __('settings NOT saved - widths must be stated as a numeric value.', 'Portfolio' );
		} elseif ($opt_val_img_width > $opt_val_overall_width) {
			$validated = false;
			$validation_msg = __('settings NOT saved - image width can\'t be wider than the overall width.', 'Portfolio' );
		} elseif ($opt_val_overall_width < 250) {
			$validated = false;
			$validation_msg = __('settings NOT saved - overall width cannot be narrower than 250 pixels.', 'Portfolio' );
		} elseif ( !check_admin_referer('portfolio_config', 'portolio-nonce') ) {
			$validated = false;
			$validation_msg = __('settings NOT saved - authentication error.', 'Portfolio' );
		}
		
		// if everything is still okay
		if ($validated) {
			
			// calculate other related element widths
			$overall_image_width = $opt_val_img_width + 20;
			$detail_width = $opt_val_overall_width - $overall_image_width - 30;
			$meta_value_width = $detail_width - 70;
			
			if ($meta_value_width < 100) {
				$too_wide_by = 100 - $meta_value_width;
				$image_width = $opt_val_img_width - $too_wide_by;
				$dtl_width = $opt_val_overall_width + $too_wide_by;
				$validated = false;
				$validation_msg = __('settings NOT saved - Reduce image width to ' . $image_width . ' pixels wide or set the overall width to at least ' . $dtl_width . ' pixels.', 'Portfolio' );
			} elseif ($detail_width < 170) {
				$too_narrow_by = (170 - $detail_width);
				$image_width = $opt_val_img_width + $too_narrow_by;
				$dtl_width = $opt_val_overall_width + $too_narrow_by;
				$validated = false;
				$validation_msg = __('settings NOT saved - too narrow by ' . $too_narrow_by . ' pixels, you need to set the image width to at least ' . $image_width . ' pixels or the overall width to at least ' . $dtl_width . ' pixels.', 'Portfolio' );
			}
		}
		
		// if the specified sizes are within tolerances
		if ( $validated ) {
			
			// Save the posted value in the database
			update_option( $display_createdate, $opt_val_display_createdate );
			update_option( $items_per_page, $opt_val_items_per_page );
			update_option( $display_credit, $opt_val_display_credit );
			update_option( $delete_options, $opt_val_delete_options );
			update_option( $delete_data, $opt_val_delete_data );
			update_option( $use_css, $opt_val_css );
			update_option( $overall_width, $opt_val_overall_width );
			update_option( $img_width, $opt_val_img_width );
			update_option( $header_color, $opt_val_header_color );
			update_option( $link_color, $opt_val_link_color );
			update_option( $odd_stripe_color, $opt_val_odd_stripe_color );
			update_option( $even_stripe_color, $opt_val_even_stripe_color );
			
		}
		
		if ($opt_val_display_createdate=="True" ) {$opt_val_display_createdate="checked";}
		if ($opt_val_css=="True" ) {$opt_val_css="checked";}
		if ($opt_val_display_credit=="True" ) {$opt_val_display_credit="checked";}
		if ($opt_val_delete_options=="True" ) {$opt_val_delete_options="checked";}
		if ($opt_val_delete_data=="True" ) {$opt_val_delete_data="checked";}
		
		// Put a settings updated message on the screen
		echo ('<div class="updated"><p><strong>' . $validation_msg . '</strong></p></div>');
		
	} else {
		
		// Read in existing option value from database
		$opt_val_display_createdate = get_option( $display_createdate );
		if ( $opt_val_display_createdate=="True" ) {$opt_val_display_createdate="checked";}
		$opt_val_items_per_page = get_option( $items_per_page );
		$opt_val_display_credit = get_option( $display_credit );
		if ( $opt_val_display_credit=="True" ) {$opt_val_display_credit="checked";}
		$opt_val_delete_options = get_option( $delete_options );
		if ( $opt_val_delete_options=="True" ) {$opt_val_delete_options="checked";}
		$opt_val_delete_data = get_option( $delete_data );
		if ( $opt_val_delete_data=="True" ) {$opt_val_delete_data="checked";}
		$opt_val_css = get_option( $use_css );
		if ( $opt_val_css=="True" ) {$opt_val_css="checked";}
		$opt_val_overall_width = get_option( $overall_width );
		$opt_val_img_width = get_option( $img_width );
		$opt_val_header_color = get_option( $header_color );
		$opt_val_link_color = get_option( $link_color );
		$opt_val_odd_stripe_color = get_option( $odd_stripe_color );
		$opt_val_even_stripe_color = get_option( $even_stripe_color );
		
	}
	
	echo "\n";
	echo '			<form action="" method="post" name="portolio-conf" id="portolio-conf">' . "\n" . '				';
	wp_nonce_field('portfolio_config', 'portolio-nonce');
	echo "\n";
	echo '				<input type="hidden" name="' . $hidden_field_name . '" value="Y">' . "\n";
	echo '				<input type="hidden" name="page_options" value="WEBphysiology_portolio_plugin_data" />' . "\n";
	echo '				<input type="hidden" value="' . get_option('version') . '" name="version"/>' . "\n";
	
	
	echo '				<div id="pluginsettings" class="postbox">' . "\n";
	echo '					<h3 class="hndle"><span>Portfolio Display Settings</span></h3>' . "\n";
	echo '					<div class="inside">' . "\n";
	echo '				    		<input type="checkbox" id="' . $display_createdate . '" name="' . $display_createdate . '" value="True" ' . $opt_val_display_createdate . '/><label for="' . $display_createdate . '">Display create date</label><br/>' . "\n";
	echo '				    		<label for="' . $items_per_page . '">Portfolio items per page:</label><input type="text" id="' . $items_per_page . '" name="' . $items_per_page . '" value="' . $opt_val_items_per_page . '"' . $opt_val_items_per_page . '/><br />' . "\n";
	echo '				    		<input type="checkbox" id="' . $display_credit . '" name="' . $display_credit . '" value="True" ' . $opt_val_display_credit . '/><label for="' . $display_credit . '">Display WEBphysiology credit and/or a donation would be nice (though neither is required).</label>' . "\n";
	echo '				  	</div>' . "\n";
	echo '				</div>' . "\n";
	echo '				<div id="pluginsettings" class="postbox">' . "\n";
	echo '					<h3 class="hndle"><span>Portfolio Styling</span></h3>' . "\n";
	echo '					<div class="inside">' . "\n";
	echo '						<input type="checkbox" id="' . $use_css . '" name="' . $use_css . '" value="True" ' . $opt_val_css . '/><label for="' . $use_css . '">Use Portfolio plugin CSS</label><br/>' . "\n";
	echo '						<label for="' . $overall_width . '">Portfolio List - overall width:</label><input type="text" id="' . $overall_width . '" name="' . $overall_width . '" value="' . $opt_val_overall_width . '" ' . $opt_val_overall_width . '/> pixels<br />' . "\n";
	echo '						<label for="' . $img_width . '">Portfolio List - image width:</label><input type="text" id="' . $img_width . '" name="' . $img_width . '" value="' . $opt_val_img_width . '" ' . $opt_val_img_width . '/> pixels<br />' . "\n";
	echo '						<label for="' . $header_color . '">Portfolio title color:</label><input type="text" id="' . $header_color . '" name="' . $header_color . '" value="' . $opt_val_header_color . '" ' . $opt_val_header_color . '/><span style="color:' . $opt_val_header_color . ';margin-left:10px;">title color</span><br />' . "\n";
	echo '						<label for="' . $link_color . '">Portfolio nav link color:</label><input type="text" id="' . $link_color . '" name="' . $link_color . '" value="' . $opt_val_link_color . '" ' . $opt_val_link_color . '/><span style="color:' . $opt_val_link_color . ';margin-left:10px;">link color</span><br />' . "\n";
	echo '						<label for="' . $odd_stripe_color . '">Portfolio odd stripe background color:</label><input type="text" id="' . $odd_stripe_color . '" name="' . $odd_stripe_color . '" value="' . $opt_val_odd_stripe_color . '" ' . $opt_val_odd_stripe_color . '/><span style="background-color:' . $opt_val_odd_stripe_color . ';margin-left:10px;">odd stripe color</span><br />' . "\n";
	echo '						<label for="' . $even_stripe_color . '">Portfolio even stripe background color:</label><input type="text" id="' . $even_stripe_color . '" name="' . $even_stripe_color . '" value="' . $opt_val_even_stripe_color . '" ' . $opt_val_even_stripe_color . '/><span style="background-color:' . $opt_val_even_stripe_color . ';margin-left:10px;">even stripe color</span><br/>' . "\n";
	echo '					</div>' . "\n";
	echo '				</div>' . "\n";
	echo '				<div id="pluginsettings" class="postbox">' . "\n";
	echo '					<h3 class="hndle"><span>Portfolio Deactivation Settings</span></h3>' . "\n";
	echo '					<div class="inside">' . "\n";
	echo '				    		<input type="checkbox" id="' . $delete_options . '" name="' . $delete_options . '" value="True" ' . $opt_val_delete_options . '/><label for="' . $delete_options . '">Delete Portfolio Option Settings</label><br/>' . "\n";
	echo '				    		<input type="checkbox" id="' . $delete_data . '" name="' . $delete_data . '" value="True" ' . $opt_val_delete_data . '/><label for="' . $delete_data . '">Delete Portfolio Records (includes Portfolio Types)</label>' . "\n";
	echo '				  	</div>' . "\n";
	echo '				</div>' . "\n";
	echo '				<div class="submit portfolio_button">' . "\n";
	echo '					<input type="submit" class="button-primary" name="Save" value="Save Portfolio Settings" id="submitbutton" />' . "\n";
	echo '					<input type="button" class="button" name="Default" value="Revert to Default Values" id="resetbutton" onClick="reset_to_default(this.form) . "\n";" />' . "\n";
	echo '				</div>' . "\n";
	echo '			</form>' . "\n";
	echo '			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="portfolio_donate">' . "\n";
	echo '				<input type="hidden" name="cmd" value="_s-xclick">' . "\n";
	echo '				<input type="hidden" name="hosted_button_id" value="G6YDH57GS9PCJ">' . "\n";
	echo '				<input style="background:none . "\n";border:none . "\n";" type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' . "\n";
	echo '				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">' . "\n";
	echo '			</form>' . "\n";
	echo '			</div> <!-- <div class="meta-box-sortables"> -->' . "\n";
	echo '		</div> <!-- <div class="metabox-holder"> -->' . "\n";
	echo '	</div> <!-- <div class="postbox-container"> -->' . "\n";
	echo '</div> <!-- <div class="wrap portfolio-admin"> -->' . "\n";
    
	echo '<script type="text/javascript">' . "\n";
    echo '	<!--' . "\n";
    echo '    function reset_to_default(settingsForm) {' . "\n";
    echo '    	settingsForm.' . $display_createdate . '.checked = true' . "\n";
    echo '    	settingsForm.' . $items_per_page . '.value = "3"' . "\n";
    echo '    	settingsForm.' . $display_credit . '.checked = true' . "\n";
    echo '    	settingsForm.' . $delete_options . '.checked = false' . "\n";
    echo '    	settingsForm.' . $delete_data . '.checked = false' . "\n";
    echo '    	settingsForm.' . $use_css . '.checked = true' . "\n";
    echo '    	settingsForm.' . $overall_width . '.value = "660"' . "\n";
    echo '    	settingsForm.' . $img_width . '.value = "200"' . "\n";
    echo '    	settingsForm.' . $header_color . '.value = "#004813"' . "\n";
    echo '    	settingsForm.' . $link_color . '.value = "#004813"' . "\n";
    echo '    	settingsForm.' . $odd_stripe_color . '.value = "#eeeeee"' . "\n";
    echo '    	settingsForm.' . $even_stripe_color . '.value = "#f5f5f5"' . "\n";
    echo '      settingsForm.submit()' . "\n";
    echo '    }' . "\n";
    echo '    //-->' . "\n";
    echo '</script>' . "\n";
}


//*************************************************//
//*************************************************//
//*************************************************//
//******** PORTFOLIO PLUGIN ADMIN CODE END ********//
//*************************************************//
//*************************************************//
//*************************************************//





//*************************************************//
//*************************************************//
//*************************************************//
//*****  PORTFOLIO USER INTERFACE CODE START  *****//
//*************************************************//
//*************************************************//
//*************************************************//


// Grab the Portfolio image for the current Portfolio in the loop

if ( ! function_exists( 'get_Loop_Site_Image' ) ) :
function get_Loop_Site_Image() {
	
    $full_size_img_url = get_post_meta(get_the_ID(), "_imageurl", true);
	
	$opt_val_img_width = get_option( 'webphysiology_portfolio_image_width' );
	
	if ($opt_val_img_width == "") {$opt_val_img_width = '150';}
	
	if (!empty($full_size_img_url)) {
		$img = clean_source($full_size_img_url);
		if (!file_exists($img)) {
			$full_size_img_url = "";
		}
	}
	
	if (!empty($full_size_img_url)) {
		
		$anchor_open = '<a href="' . $full_size_img_url . '" title="' . the_title_attribute( 'echo=0' ) . '" class="Portfolio-Link thickbox">';
		
		$path = $anchor_open . '<img src="' . plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $full_size_img_url . '&w=' . $opt_val_img_width . '&zc=1" alt="' . the_title_attribute('echo=0') . '" /></a>';
		
	} else {
		
		$path = '<img src="' . plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=images/empty_window.png&w=' . $opt_val_img_width . '&zc=1" alt="' . the_title_attribute('echo=0') . '" class="missing" />';
		
	}
	
	return $path;
}
endif;


// Grab the Portfolio title for the current Portfolio in the loop
if ( ! function_exists( 'get_Loop_Portfolio_Title' ) ) :
function get_Loop_Portfolio_Title() {
	
	global $portfolio_output;
	
	$portfolio_output .= '<h2>' . the_title_attribute('echo=0') . '</h2>';
}
endif;


if ( !is_admin() ) {
	
	/* Add the Portfolio Stylsheet to the <head> section of the page */
	/*  note: the user may override this option from the Portfolio settings page */
	function set_base_portfolio_css() {
		
		$portfolio_css_on = get_option('webphysiology_portfolio_use_css');
		
		if (is_wp_error($portfolio_css_on) || $portfolio_css_on=='True') {
			$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'css/';
			wp_register_style('portfolio_css', $x . 'portfolio.css');
			wp_enqueue_style('portfolio_css');
		}
		
	}
	add_action('init', 'set_base_portfolio_css');

}

function set_portfolio_css() {
	
	$overall_width = 'webphysiology_portfolio_overall_width'; // default is 660px
	$img_width = 'webphysiology_portfolio_image_width'; // default is 200px
	$header_color = 'webphysiology_portfolio_header_color'; // default is #004813
	$link_color = 'webphysiology_portfolio_link_color'; // default is #004813
	$odd_stripe_color = 'webphysiology_portfolio_odd_stripe_color'; // default is #eee
	$even_stripe_color = 'webphysiology_portfolio_even_stripe_color'; // default is #f5f5f5
	$opt_val_overall_width = get_option( $overall_width );
	$opt_val_img_width = get_option( $img_width );
	$opt_val_header_color = get_option( $header_color );
	$opt_val_link_color = get_option( $link_color );
	$opt_val_odd_stripe_color = get_option( $odd_stripe_color );
	$opt_val_even_stripe_color = get_option( $even_stripe_color );
	$overall_image_width = $opt_val_img_width + 20;
	$detail_width = $opt_val_overall_width - $overall_image_width - 30;
	$meta_value_width = $detail_width - 70;
	
	echo "\n";
	echo '<style type="text/css" id="webphysiology_portfolio_embedded_css">' . "\n";
	echo '    #portfolios {	' . "\n";
	echo '        width: ' . $opt_val_overall_width . 'px;' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_details {' . "\n";
	echo '        width: ' . $detail_width . 'px;' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_page_img {' . "\n";
	echo '        width: ' . $overall_image_width . 'px;' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_page_img img {' . "\n";
	echo '        width: ' . $opt_val_img_width . 'px;' . "\n";
	echo '        max-width: ' . $opt_val_img_width . 'px;' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_meta .value {' . "\n";
	echo '        width: ' . $meta_value_width . 'px;' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios h1, #portfolios h2 {' . "\n";
	echo '        color: ' . $opt_val_header_color . ';' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_nav a {' . "\n";
	echo '        color: ' . $opt_val_link_color . ';' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_entry {' . "\n";
	echo '        background-color: ' . $opt_val_even_stripe_color . ';' . "\n";
	echo '    }' . "\n";
	echo '    #portfolios .portfolio_entry.odd {' . "\n";
	echo '        background-color: ' . $opt_val_odd_stripe_color . ';' . "\n";
	echo '    }' . "\n";
	echo '</style>' . "\n";
	
	$portfolio_css_on = get_option('webphysiology_portfolio_use_css');
	
	if (is_wp_error($portfolio_css_on) || $portfolio_css_on=='True') {
		$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'css/';
		// note: wp_enqueue_style does not support conditional stylesheets at this time
		echo "\n";
		echo '<!--[if lte IE8]>' . "\n";
		echo '	<link rel="stylesheet" id="webphysiology_portfolio_ie_adjustment_css" type="text/css" href="' . $x . 'portfolio_lte_ie8.css" />' . "\n";
		echo '<![endif]-->' . "\n";
	}
}
add_action('wp_head', 'set_portfolio_css');


// add scripts and styling needed for the lightbox functionality
function jquery_lightbox_init () {
    $x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'scripts/fancybox/';
	wp_register_script('fancybox', $x . 'jquery.fancybox-1.3.1.pack.js', array('jquery'));
	wp_enqueue_script('fancybox');
	wp_register_script('mousewheel', $x . 'jquery.mousewheel-3.0.2.pack.js');
	wp_enqueue_script('mousewheel');
}

function jquery_lightbox_styles() {
    $x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'scripts/fancybox/';
	wp_register_style('lightbox_css', $x . 'jquery.fancybox-1.3.1.css');
	wp_enqueue_style('lightbox_css');
}

function fancy_script() {
	echo ( "\n" . '<script type="text/javascript">' . "\n");
	echo ( 'jQuery.noConflict();' . "\n");
	echo ( 'jQuery(document).ready(function() {' . "\n");
	echo ( '	jQuery("a.thickbox").fancybox({' . "\n");
	echo ( "		'overlayOpacity'	:	0.95," . "\n");
	echo ( "		'overlayColor'	:	'#333'," . "\n");
	echo ( "		'speedIn'	:	350," . "\n");
	echo ( "		'speedOut'	:	350," . "\n");
	echo ( "		'hideOnContentClick'	:	true," . "\n");
	echo ( "		'titleShow'	:	false" . "\n");
	echo ( "	});" . "\n");
	echo ( '});' . "\n");
	echo ( '</script>' . "\n");
}

if (!is_admin()) {
	add_action('init', 'jquery_lightbox_init');
	add_action('init', 'jquery_lightbox_styles');
	add_action('wp_head', 'fancy_script');
}


/* Build out the navigation elements for paging through the Portfolio pages */
function nav_pages($qryloop, $class) {
	
	global $nav_spread;
	global $portfolio_output;
	
	// get total number of pages in the query results
	$pages = $qryloop->max_num_pages;
	
	// if there is more than one page of Portfolio query results
	if($pages>1) {
		
		// get current page number
		intval(get_query_var('paged')) == 0 ? $curpage=1 : $curpage = intval(get_query_var('paged'));
		
		// determine the starting page number of the nav control
		if ($curpage-$nav_spread<-2) {
			$start = 1;
		} elseif ($curpage+$nav_spread>$pages) {
			if ($curpage-2<$pages-$nav_spread+1) {
				$start = $curpage-2;
			} else {
				$start = $pages-$nav_spread+1;
			}
		} else {
			$start = $curpage-2;
		}
		if ($start < 1) {
			$start = 1;
		}
		
		// set the ending page number of the nav control
		if ($start+$nav_spread-1<$pages) {
			$end = $start+$nav_spread-1;
		} else {
			$end = $pages;
		}
		
		// now build out the navigation page elements
		$portfolio_output .= '<div class="portfolio_nav ' . $class . '">';
		$portfolio_output .= '<ul>';
		
		if($start-1>1) {
			$portfolio_output .= '<li><a href="?paged=1">&laquo;</a></li>'; //next link
		}
		if($start>1) {
			$portfolio_output .= '<li><a href="?paged=' . ($start-1) . '">&lt;</a></li>'; //next link
		}
		
		for ($i=$start;$i<=$end;$i++) {
			if ($curpage!=$i) {
				$portfolio_output .= '<li><a href="?paged=' . $i .'"';
			} else {
				$portfolio_output .= '<li class="selected"><a href="?paged=' . $i .'" class="selected"';
			}
			$portfolio_output .= '>' . $i . '</a></li>';
		}
		
		if($end<$pages) {
			$portfolio_output .= '<li><a href="?paged=' . $i . '">&gt;</a></li>';
		}
		if($end<$pages-1) {
			$portfolio_output .= '<li><a href="?paged=' . $pages . '">&raquo;</a></li>';
		}
		$portfolio_output .= '</ul>';
		$portfolio_output .= '</div>';
		
	}
	
	return $portfolio_output;
}

/*
// RESERVED FOR POTENTIAL FUTURE USE - there is no single Portfolio page at this time
function use_portfolio_template() {
	
	if ( !( is_page('Portfolio') ) && (( !(get_post_type() == 'Portfolio') || is_404() )))  return;
	if ( is_page('Portfolio') ) {
		include('portfolioNEW.php');
		exit;
	} elseif ( is_single() ) {
		include('single-portfolio.php');
		exit;
	}
	
}
add_action('template_redirect', 'use_portfolio_template');
*/

//*************************************************//
//*************************************************//
//*************************************************//
//******  PORTFOLIO USER INTERFACE CODE END  ******//
//*************************************************//
//*************************************************//
//*************************************************//



//*************************************************//
//*************************************************//
//*************************************************//
//******  CODE "BORROWED" FROM TIMTHUMB.PHP  ******//
//*************************************************//
//*************************************************//
//*************************************************//


/**
 * tidy up the image source url
 *
 * @param <type> $src
 * @return string
 */
function clean_source ($src) {

	$host = str_replace ('www.', '', $_SERVER['HTTP_HOST']);
	$regex = "/^((ht|f)tp(s|):\/\/)(www\.|)" . $host . "/i";

	$src = preg_replace ($regex, '', $src);
	$src = strip_tags ($src);
    $src = check_external ($src);

    // remove slash from start of string
    if (strpos ($src, '/') === 0) {
        $src = substr ($src, -(strlen ($src) - 1));
    }

    // don't allow users the ability to use '../'
    // in order to gain access to files below document root
    $src = preg_replace ("/\.\.+\//", "", $src);

    // get path to image on file system
    $src = get_document_root ($src) . '/' . $src;

    return $src;

}

/**
 *
 * @global array $allowedSites
 * @param string $src
 * @return string
 */
function check_external ($src) {

    if (preg_match ('/http:\/\//', $src) == true) {

        $url_info = parse_url ($src);
		$fileDetails = pathinfo ($src);
		$ext = strtolower ($fileDetails['extension']);
		$filename = md5 ($src);
		$local_filepath = DIRECTORY_TEMP . '/' . $filename . '.' . $ext;

		if (!file_exists ($local_filepath)) {

			if (function_exists ('curl_init')) {

				$fh = fopen ($local_filepath, 'w');
				$ch = curl_init ($src);

				curl_setopt ($ch, CURLOPT_URL, $src);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt ($ch, CURLOPT_HEADER, 0);
				curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
				curl_setopt ($ch, CURLOPT_FILE, $fh);

				if (curl_exec ($ch) === FALSE) {
					if (file_exists ($local_filepath)) {
						unlink ($local_filepath);
					}
				}

				curl_close ($ch);
				fclose ($fh);
			}
		}
		$src = $local_filepath;
    }

    return $src;
}


/**
 *
 * @param <type> $src
 * @return string
 */
function get_document_root ($src) {

    // check for unix servers
    if (file_exists ($_SERVER['DOCUMENT_ROOT'] . '/' . $src)) {
        return $_SERVER['DOCUMENT_ROOT'];
    }

    // check from script filename (to get all directories to timthumb location)
    $parts = array_diff (explode ('/', $_SERVER['SCRIPT_FILENAME']), explode('/', $_SERVER['DOCUMENT_ROOT']));
    $path = $_SERVER['DOCUMENT_ROOT'];
    foreach ($parts as $part) {
        $path .= '/' . $part;
        if (file_exists($path . '/' . $src)) {
            return $path;
        }
    }

    // the relative paths below are useful if timthumb is moved outside of document root
    // specifically if installed in wordpress themes like mimbo pro:
    // /wp-content/themes/mimbopro/scripts/timthumb.php
    $paths = array (
        ".",
        "..",
        "../..",
        "../../..",
        "../../../..",
        "../../../../.."
    );

    foreach ($paths as $path) {
        if (file_exists($path . '/' . $src)) {
            return $path;
        }
    }

    // special check for microsoft servers
    if (!isset ($_SERVER['DOCUMENT_ROOT'])) {
        $path = str_replace ("/", "\\", $_SERVER['ORIG_PATH_INFO']);
        $path = str_replace ($path, "", $_SERVER['SCRIPT_FILENAME']);

        if (file_exists ($path . '/' . $src)) {
            return $path;
        }
    }

}

?>