<?php
/*
Plugin Name: WEBphysiology Portfolio
Plugin URI: http://webphysiology.com/redir/webphysiology-portfolio/
Description: Provides a clean Portfolio listing with image, details and portfolio type taxonomy.  A [portfolio] shortcode is used to include the portfolio on any page.
Version: 1.2.4
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

/*  UPDATES

	1.2.4 - * added shortcode parameter "id" that allows for the ability to encapsulate a portfolio within a <div> of a specified id
			* added shortcode parameter "per_page" that allows for the ability to override the options setting specifying the number of portfolios to display per page
			* added shortcode parameter "thickbox" that allows for the ability to override the options setting specifying the image click behavior 
			* added shortcode parameter "credit" that allows for the ability to override the options setting specifying whether to display the plugin credit
			* found that some of the shortcode variables were not being cleared when a shortcode was used more than once on a given page - fixed
			* added support for thickbox pop-ups to display Youtube and Vimeo videos along with pulling back the thumbnail associated with them
			* moved plugin Options up within the Portfolio menu section and also moved the "Settings" link on the plugins page from the description area to under the plugin title
			* updated the custom post type from "Portfolio" to "webphys_portfolio" because v3.1 doesn't like caps and also to avoid contention with other plugins
			* started transition of shortcode from [portfolio] to [webphysiology_portfolio]
			* added important release notes to the Portfolio Options page
	1.2.3 - * added code to trap for autosave and quick edit saves such that custom Portfolio save script does not execute and, in the case of the
	          quick edit save, keep it from completing
			* removed the "view" option within the Portfolio admin listing as there is no individual Portfolio view
			* updated Portfolio Listing column labels to those set in the Portfolio options
			* updated Portfolio Listing to hide Portfolio Types as QuickEdit does not utilize a select list
			* removed the "preview" button from the Portfolio edit screen
			* changed the ShrinkTheWeb secret key input to a type Password to mask the value
			* added environment check to ensure the current host meets the minimum requirements of this plugin
			* added a setting to allow clicks on links to open in a new tab (target="_blank")
	1.2.2 - * removed the forcing of the sort field to be numeric and added an option to sort alphabetically (by turning off "sort numerically")
	1.2.1 - * made some changes to the navigation control, nav_pages(), as it wasn't always accurately drawn
	        * removed an errant character from a line of code
			* added note to Portfolio edit screen when ShrinkTheWeb is used to let the user know that entering an image URL will override the use of ShrinkTheWeb for that Portfolio
	1.2.0 - * added support for ShrinkTheWeb.com
	        * am removing the empty "temp" directory from the plugin package and replacing it with code that will create it should it not exist
	        * updated the portfolio_search_where() function to handle any amount of included and excluded portfolio types and in any order
			* updated nav control code to handle multiple [portfolio] shortcodes being used on one page
			* updated embedded CSS to include new "webphysiology_portfolio" Class, which is identical to what the "portfolios" ID was. this was
			  necessary as the ID was invalid in that using the [portfolio] shortcode more than once on a page would result in duplicate IDs.
			  The first [portfolio] shortcode will still use the "portfolios" ID but subsequent ones will have a number appended to the ID and
			  at some point the #portfolios entries in the CSS files will be removed, defaulting to just the "webphysiology_portfolio" class
	1.1.5 - * updated nav_pages() method as it wasn't working when pretty permalinks were not being utilized
	        * enhanced nav control method so that it doesn't have to rebuild for the bottom nav, it just uses what was built for the top nav
			* updated code to allow for portfolio images that are hosted on sites other than the current site
	1.1.4 - fixed a bug where the plugin credit could not be turned off
	1.1.3 - added grid styling and ability to turn off portfolio title and description
    1.1.2 - updated included loop-portfolio.php file
    1.1.1 - Bug fix - a form tag around the color selector was keeping the Portfolio Settings submit button from firing on Windows machines, so, removed it as it was unnecessary
    1.1.0 - several changes to this script were made in this release, including the following:
			* Added a color picker to the Admin styling area to make color selections quicker
			* Added the ability to change the detail data labels and their width
			* Added the ability to turn off the display of all detail data items should you want to store the values but not display them
			* Added the ability to navigate to the specified "site" URL when you click on the thumbnail as opposed to opening up a larger image in a litebox
			* Added the ability to specify a missing image URL as opposed to using the plugin provided image
			* Fixed potential issue where embedded STYLE was still being included when NOT using WEBphysiology Portfolio CSS
    1.0.2 - changed image url passed to timthumb.php to exclude the content directory from the path as
    		some installs were having problems with this
    
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
// image gallery widget or shortcode
// add sort order to quick edit
**********/


// ASTERISK = make certain to update these as appropriate with new releases //

define ( 'WEBPHYSIOLOGY_VERSION', '1.2.4' );
define ( 'WEBPHYSIOLOGY_DB_VERSION', '3.1' );
define ( 'WEBPHYSIOLOGY_PORTFOLIO_WP_PAGE', basename($_SERVER['PHP_SELF']) );

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
	
	$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'images/jvhm_pinwheel_bullet.png';

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
		'show_in_menu' => true,
		'query_var' => true,
		'rewrite' => false, // since we aren't pushing to single pages we don't need a re-write rule or permastructure.
							// if we were it would look something like 'rewrite' => array("slug" => "portfolio")
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => 5,
		'menu_icon' => $x,
		'show_in_nav_menus' => true,
		'supports' => array('title','editor','author'),
		'register_meta_box_cb' => 'add_portfolio_metaboxes',
		'taxonomies' => array('portfolio_type')
	); 
	
	register_post_type('webphys_portfolio',$args);
	
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
	if ( !empty( $wp_query->query_vars['portfoliotype'] )) {
		
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
	// this code should not be running within the admin
	if (!is_admin()) {
		
		global $wp_query;
		
		// if the portfolio type has been defined in the search vars
		if ( !empty( $wp_query->query_vars['portfoliotype'] )) {
			
			// clear out our portfolio type buckets
			$IN = "";
			$OUT = "";
			
			$types = get_query_var('portfoliotype');
			
			// place the portfolio types into an array so that it is easier to process them
			$ptypes = explode(",",$types);
			
			// loop through the portfolio array
			foreach ($ptypes as $value) {
				
				// if the portfolio type is not lead by a minus sign then add it to the IN bucket
				if (substr($value, 0, 1) != '-') {
					if ( !empty($IN) ) $IN .= ",";
					$IN .= $value;
				} else { // otherwise, add it to the OUT bucket
					if ( !empty($OUT) ) $OUT .= ",";
					$OUT .= substr($value, 1);
				}
			}
			
			// if some of the portfolio types were flagged for inclusion then add an IN() clause
			if ( !empty($IN) ) {
				if (!empty($where)) $where .= " AND ";
				$where .= " port.meta_value IN ('" . str_replace(',', "','", $IN) . "')";
			}
			
			// if some of the portfolio types were flagged for exclusion then add a NOT IN() clause
			if ( !empty($OUT) ) {
				if (!empty($where)) $where .= " AND ";
				$where .= " port.meta_value NOT IN ('" . str_replace(',', "','", $OUT) . "')";
			}
			
		}
		
	} // (!is_admin())
	
	return $where;
}

add_filter('query_vars', 'portfolio_queryvars' );
add_filter('posts_join', 'portfolio_search_join' );
add_filter('posts_where', 'portfolio_search_where' );


// Define the Portfolio custom post type update messages
function portfolio_updated_messages( $messages ) {
	
	global $post, $post_ID;
	
	$messages['webphys_portfolio'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Portolios updated.'),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Portfolio updated.'),
		5 => isset($_GET['revision']) ? sprintf( __('Portfolio restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Portfolio published.'),
		7 => __('Portfolio saved.'),
		8 => __('Portfolio submitted.'),
		9 => sprintf( __('Portfolio scheduled for: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Portfolio draft updated.'),
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
	
	$detail_labels = get_option( 'webphysiology_portfolio_display_labels' );
	$type = $detail_labels["Type"];
	$created = $detail_labels["Created"];
	$clientname = $detail_labels["Client"];
	$siteURL = $detail_labels["SiteURL"];
	$tech = $detail_labels["Tech"];
	$stwcomments = '';
	$sortcomments = "";
	//inline-edit-tags
	if ( strtolower( get_option('webphysiology_portfolio_use_stw')) == 'true' ) {
		$stwcomments = '</span><br /><span class="attribute_instructions"><strong>note</strong>: entering an image path will override the use of ShrinkTheWeb.com. Review "<a href="http://webphysiology.com/plugins/webphysiology-portfolio-plugin/#options" title="WEBphysiology Portfolio Documentation" target="_blank">Use ShrinkTheWeb.com</a>" option</span><br /><span class="attribute_instructions">documentation for more details.';
	}
	if ( strtolower( get_option('webphysiology_portfolio_sort_numerically')) != 'true' ) {
		$sortcomments = '<br /><span class="attribute_instructions"><strong>note</strong>: you are sorting alphanumerically</span>';
	}
	
	// hide the Portfolio edit screen Preview button
	echo "\n" . '<style type="text/css" id="webphysiology_portfolio_hide_preview_css">' . "\n" . '	#preview-action { display: none; } ' . "\n" . '</style>' . "\n";

	echo '<p><label for="_portfolio_type">Select Portfolio Type (' . $type . '): </label> ';

    echo '<select name="_portfolio_type" id="_portfolio_type">';
        echo '<!-- Display portfolio types as options -->';
            echo '<option class="portfolio_type_option" value=""';
            if ( !count($portfolio_type_list) || is_wp_error($portfolio_type) || empty($portfolio_type) ) echo 'selected>None</option>';
        foreach ($portfolio_type_list as $portfolio_item) {
            if ($portfolio_item->slug == $portfolio_type) {
                echo '<option class="portfolio_type_option" value="' . $portfolio_item->slug . '" selected>' . $portfolio_item->name . '</option>\n'; 
			} else {
                echo '<option class="portfolio_type_option" value="' . $portfolio_item->slug . '">' . $portfolio_item->name . '</option>\n';
			}
        }
    echo '</select></p>';
    echo '<p class="tallbottom"><label for="_createdate">Enter Date Created (' . $created . '): </label>';
	echo '<input type="text" id="_createdate" name="_createdate" value="' . $datecreate . '" class="code shortbottom" />';
	echo '<br /><span class="attribute_instructions">note: this is freeform text and can take on whatever form you want (e.g., YYYY or MM/YYYY ...)</span></p>';
    echo '<p><label for="_clientname">Enter Client Name (' . $clientname . '): </label>';
	echo '<input type="text" id="_clientname" name="_clientname" value="' . $client . '" class="widefat" /></p>';
    echo '<p><label for="_technical_details">Enter Technical Details (' . $tech . '): </label>';
	echo '<input type="text" id="_technical_details" name="_technical_details" value="' . $technical_details . '" class="widefat" /></p>';
    echo '<p><label for="_siteurl">Enter Portfolio Web Page URL (' . $siteURL . '): </label>';
	echo '<input type="text" id="_siteurl" name="_siteurl" value="' . $siteurl . '" class="widefat" /></p>';
    echo '<p><label for="_imageurl">Enter Portfolio Image URL: </label>';
	echo '<input id="upload_portfolio_image_button" type="button" value="Upload Image" /><br />';
	echo '<input type="text" id="_imageurl" name="_imageurl" value="' . $imageurl . '" class="widefat shortbottom" /><br />';
	echo '<span class="attribute_instructions">Enter the URL for the portfolio image. Clicking "Insert into Post" from &lt;Upload Image&gt; will paste the inserted image\'s URL.' . $stwcomments . '</span></p>';
    echo '<p><label for="_sortorder">Enter Site Sort Order: </label>';
	echo '<input type="text" id="_sortorder" name="_sortorder" value="' . $sortorder . '" class="code" />';
	echo '<input type="hidden" name="autosave_quickedit_check" value="true" />'. $sortcomments . '</p>';

}

/* Add the Portfolio custom fields (called as an argument of the custom post type registration) */
function add_portfolio_metaboxes() {
	add_meta_box('webphys_portfolio_edit_init', 'Portfolio Details', 'webphys_portfolio_edit_init', 'webphys_portfolio', 'normal', 'high');
}

// Make certain the scripts and css necessary to support the file upload button are active
function portfolio_admin_scripts() {
	
	global $post;
	
	$continue = "False";
	
	// don't include the media upload script if we are not on a portfolio edit page, otherwise,
	// the standard image upload will be hijacked and not work on other post and page admin pages
	if (!empty($post)) {
		if (strtolower($post->post_type) == "webphys_portfolio") {
			$continue = "True";
		}
	}
	
	if ($continue == "True") {
		
		$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'scripts/';
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_register_script('portfolio-image-upload', $x . 'file_uploader.js', array('jquery','media-upload','thickbox'));
		wp_enqueue_script('portfolio-image-upload');
		
	}
}

function portfolio_admin_styles() {
	
	global $post;
	
	$continue = "False";
	
	// don't include the media upload script if we are not on a portfolio edit page, otherwise,
	// the standard image upload will be hijacked and not work on other post and page admin pages
	if (!empty($post)) {
		if (strtolower($post->post_type) == "webphys_portfolio") {
			$continue = "True";
		}
	}
	
	if ($continue == "True") {
		
		$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'scripts/fancybox/';
		wp_register_style('lightbox_css', $x . '/jquery.fancybox-1.3.1.css');
		wp_enqueue_style('lightbox_css');
		
		wp_enqueue_style('thickbox');
		
	}
}

if ( is_admin() ) {
	add_action('admin_print_scripts', 'portfolio_admin_scripts');
	add_action('admin_print_styles', 'portfolio_admin_styles');
}

/* define the Portfolio ShortCode and set defaults for available arguments */
function portfolio_loop($atts, $content = null) {
	
	global $for;
	global $portfolio_types;
	global $click_behavior;
	global $portfolio_output;
	global $num_per_page;
	global $display_the_credit;
	
	clear_globals();
	
	$max_nav_spread = '';
	$portfolio_type = '';
	$on_click = get_option('webphysiology_portfolio_image_click_behavior');
	$showme_the_credit = get_option('webphysiology_portfolio_display_credit');
	
	extract( shortcode_atts( array(
      'max_nav_spread' => 5,
	  'portfolio_type' => '',
	  'thickbox' => $on_click,
	  'id' => '',
	  'per_page' => '',
      'credit' => $showme_the_credit), $atts ) );
	
	if ( ( strtolower($thickbox) == 'true' ) || ( $thickbox == 1 ) || ( strtolower($thickbox) == 'litebox' ) ) {
		$click_behavior = "litebox";
	} else {
		$click_behavior = "nav2page";
	}
	
	if ( ( strtolower($credit) == 'true' ) || ( $credit == 1 ) ) {
		$display_the_credit = "True";
	} else {
		$display_the_credit = "False";
	}
	
	$for = $max_nav_spread;
	$portfolio_types = $portfolio_type;
	
	if ( !empty($per_page) && is_numeric($per_page) ) {
		$num_per_page = $per_page;
	} else {
		$num_per_page = get_option('webphysiology_portfolio_items_per_page');
	}
	
	if ( !empty($id) ) {
		$portfolio_output = '<div id="' . $id . '">';
	}
	
	if ( !empty($content) ) {
		$portfolio_output .= '<div class="portfolio_page_content">' . $content . '</div>';
	}
	
	include('loop-portfolio.php');
	
	if ( !empty($id) ) {
		$portfolio_output .= '</div>';
	}
	
	return $portfolio_output;
	
}


/* clear out the shortcode values otherwise they get re-used if more than one shortcode is used per page */
function clear_globals() {
	
	global $wp_query;
	global $for;
	global $portfolio_types;
	global $click_behavior;
	global $portfolio_output;
	global $num_per_page;
	global $display_the_credit;
	
	$wp_query->query_vars['portfoliotype'] = '';
	$for = '';
	$portfolio_types = '';
	$click_behavior = '';
	$portfolio_output = '';
	$num_per_page = '';
	$display_the_credit = '';
	
}

// register the Portfolio custom post type and shortcode
add_action( 'init', 'portfolio_post_type_init' );
add_filter('post_updated_messages', 'portfolio_updated_messages');
add_shortcode('portfolio', 'portfolio_loop');
add_shortcode('webphysiology_portfolio', 'portfolio_loop'); /* asterisk - remove in a later version */


// define a custom Portfolio Type taxonomy and populate it
function create_portfolio_type_taxonomy() {
	
	if (!taxonomy_exists('portfolio_type')) {
		register_taxonomy('portfolio_type', 
						  'webphys_portfolio',
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
	
	$return = get_option('webphysiology_portfolio_version');
	if ( empty($return) || ($return != '1.2.4') ) {
		add_option("webphysiology_portfolio_version", WEBPHYSIOLOGY_VERSION);
		add_option("webphysiology_portfolio_database_version", WEBPHYSIOLOGY_DB_VERSION);
		add_option("webphysiology_portfolio_display_portfolio_title", 'True'); // This is the default value for whether to display the Portfolio Title
		add_option("webphysiology_portfolio_display_portfolio_desc", 'True'); // This is the default value for whether to display the Portfolio Description
		add_option("webphysiology_portfolio_display_portfolio_type", 'True'); // This is the default value for whether to display the Portfolio Type
		add_option("webphysiology_portfolio_display_createdate", 'True'); // This is the default value for whether to display the create date
		add_option("webphysiology_portfolio_display_clientname", 'True'); // This is the default value for whether to display the client name
		add_option("webphysiology_portfolio_display_siteurl", 'True'); // This is the default value for whether to display the site URL
		add_option("webphysiology_portfolio_display_tech", 'True'); // This is the default value for whether to display the technical data
		add_option("webphysiology_portfolio_missing_image_url", 'images/empty_window.png'); // This is the default value for the missing image url
		add_option("webphysiology_portfolio_allowed_image_sites","flickr.com,picasa.com,blogger.com,wordpress.com,img.youtube.com"); // This is the default value for the allowed image sites
		add_option("webphysiology_portfolio_use_stw", 'False'); // This is the default value for whether to display images using ShrinkTheWeb.com
		add_option("webphysiology_portfolio_stw_ak", ""); // This is the default value for the ShrinkTheWeb.com Access Key
		add_option("webphysiology_portfolio_stw_sk", ""); // This is the default value for the ShrinkTheWeb.com Security Key
		add_option("webphysiology_portfolio_image_click_behavior", 'litebox'); // This is the default value for whether to display the image in a thickbox or navigate to the associated site
		add_option("webphysiology_portfolio_anchor_click_behavior", 'False'); // This is the default value for whether to open links in a new window
		add_option("webphysiology_portfolio_label_width", "60"); // This is the default value for the label width
		add_option("webphysiology_portfolio_display_labels", array("Type" => "Type", "Created" => "Created", "Client" => "For", "SiteURL" => "Site", "Tech" => "Tech")); // This is the default values for the field labels on the site UI
		add_option("webphysiology_portfolio_items_per_page", '3'); // This is the default value for the number of portfolio items to display per page
		add_option("webphysiology_portfolio_sort_numerically", 'True'); // This is the default value for whether to sort numerically off the sort column
		add_option("webphysiology_portfolio_display_credit", "True"); // This is the default value for whether to display a plugin publisher credit
		add_option("webphysiology_portfolio_gridstyle", "False"); // This is the default value for whether to display portfolio items in a grid format
		add_option("webphysiology_portfolio_gridcolor", "#eeeeee"); // This is the default value for the grid background color
		add_option("webphysiology_portfolio_delete_options", "False"); // This is the default value for whether to delete plugin options on plugin deactivation
		add_option("webphysiology_portfolio_delete_data", "False"); // This is the default value for whether to delete Portfolio data on plugin deactivation
		add_option("webphysiology_portfolio_use_css", 'True'); // This is the default value for the Portfolio CSS usage switch
		add_option("webphysiology_portfolio_overall_width", '660'); // This is the overall width of the portfolio listing
		add_option("webphysiology_portfolio_image_width", '200'); // This is the width to use on the portfolio image in the listing
		add_option("webphysiology_portfolio_header_color", '#004813'); // This is the h1 and h2 color
		add_option("webphysiology_portfolio_link_color", '#004813'); // This is the anchor link color
		add_option("webphysiology_portfolio_odd_stripe_color", '#eeeeee'); // This is the portfolio list odd row stripe background color
		add_option("webphysiology_portfolio_even_stripe_color", '#f9f9f9'); // This is the portfolio list even row stripe background color
	}
}
register_activation_hook(__FILE__,'portfolio_install');

// smart jquery inclusion
if ( (!is_admin()) || ( WEBPHYSIOLOGY_PORTFOLIO_WP_PAGE == 'edit.php' ) ) {
	wp_deregister_script('jquery');
	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"), false);
	wp_enqueue_script('jquery');
}


function set_admin_message($message) {
	update_option('webphysiology_portfolio_message', $msg);
}


If (is_admin()) {
	
	$message = get_option('webphysiology_portfolio_message');
	if ( ! empty($message) ) {
		echo ('<div class="error"><p><strong>' . $message . '.</strong></p></div>');
		update_option('webphysiology_portfolio_message', '');
	}
	
	// ASTERISK = make certain to update this with new releases //
	// check the most recently added option, if it doesn't exist then pass down through all of them and add any that are missing
	$return = get_option('webphysiology_portfolio_message');

	if ( empty($return) ) {
		
		// added in v1.2.4
		$return = get_option('webphysiology_portfolio_message');
		if ( empty($return) ) {
			add_option('webphysiology_portfolio_message', '');
		}
		
		$return = get_option('webphysiology_portfolio_database_version');
		if ( empty($return) ) {
			
			add_option("webphysiology_portfolio_database_version", WEBPHYSIOLOGY_DB_VERSION);
			update_database('1.2.4');
			
		}
		$return = get_option('webphysiology_portfolio_version');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_version", WEBPHYSIOLOGY_VERSION);
		}
		
		// added in v1.2.3
		$return = get_option('webphysiology_portfolio_anchor_click_behavior');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_anchor_click_behavior", 'False'); // This is the default value for whether to open links in a new tab
		}
		// added in v1.2.2
		$return = get_option('webphysiology_portfolio_sort_numerically');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_sort_numerically", 'True'); // This is the default value for whether to sort numerically off the sort column
		}
		// added in v1.2.0
		check_temp_dir(); // check to see that the temp directory exists, as this is needed when images from different domains are utilized
		$return = get_option('webphysiology_portfolio_use_stw');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_use_stw", 'False'); // This is the default value for whether to display images using ShrinkTheWeb.com
		}
		$return = get_option('webphysiology_portfolio_stw_ak');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_stw_ak", ""); // This is the default value for the ShrinkTheWeb.com Access Key
		}
		$return = get_option('webphysiology_portfolio_stw_sk');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_stw_sk", ""); // This is the default value for the ShrinkTheWeb.com Security Key
		}
		// added in v1.1.5
		$return = get_option('webphysiology_portfolio_allowed_image_sites');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_allowed_image_sites", "flickr.com,picasa.com,blogger.com,wordpress.com,img.youtube.com"); // This is the default value for the allowed image sites
		}
		// added in v1.1.3
		$return = get_option('webphysiology_portfolio_display_portfolio_title');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_portfolio_title", 'True'); // This is the default value for whether to display the Portfolio Title
		}
		$return = get_option('webphysiology_portfolio_display_portfolio_desc');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_portfolio_desc", 'True'); // This is the default value for whether to display the Portfolio Description
		}
		$return = get_option('webphysiology_portfolio_gridstyle');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_gridstyle", "False"); // This is the default value for whether to display the portfolio in a grid style
		}
		$return = get_option('webphysiology_portfolio_gridcolor');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_gridcolor", "#eeeeee"); // This is the default value for the grid background color
		}
		// added in v1.0.3
		$return = get_option('webphysiology_portfolio_display_clientname');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_clientname", 'True'); // This is the default value for whether to display the client name
		}
		$return = get_option('webphysiology_portfolio_display_siteurl');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_siteurl", 'True'); // This is the default value for whether to display the site URL
		}
		$return = get_option('webphysiology_portfolio_display_tech');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_tech", 'True'); // This is the default value for whether to display the technical data
		}
		$return = get_option('webphysiology_portfolio_label_width');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_label_width", "60"); // This is the default value for the detail label width
		}
		$return = get_option('webphysiology_portfolio_display_labels');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_labels", array("Type" => "Type","Created" => "Created","Client" => "For","SiteURL" => "Site","Tech" => "Tech")); // This is the default values for the field labels on the site UI
		}
		$return = get_option('webphysiology_portfolio_missing_image_url');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_missing_image_url", "images/empty_window.png"); // This is the default value for the missing image path
		}
		$return = get_option('webphysiology_portfolio_image_click_behavior');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_image_click_behavior", 'litebox'); // This is the default value for whether to display the image in a thickbox or navigate to the associated site
		}
		// added in v1.0.0
		$return = get_option('webphysiology_portfolio_display_credit');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_display_credit", "True"); // This is the default value for whether to display a plugin publisher credit
		}
		$return = get_option('webphysiology_portfolio_delete_options');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_delete_options", "False"); // This is the default value for whether to delete plugin options on plugin deactivation
		}
		$return = get_option('webphysiology_portfolio_delete_data');
		if ( empty($return) ) {
			add_option("webphysiology_portfolio_delete_data", "False"); // This is the default value for whether to delete Portfolio data on plugin deactivation
		}
		
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
		delete_option("webphysiology_portfolio_database_version");
		delete_option('webphysiology_portfolio_display_portfolio_title');
		delete_option('webphysiology_portfolio_display_portfolio_desc');
		delete_option('webphysiology_portfolio_display_portfolio_type');
		delete_option('webphysiology_portfolio_display_createdate');
		delete_option('webphysiology_portfolio_display_clientname');
		delete_option('webphysiology_portfolio_display_siteurl');
		delete_option('webphysiology_portfolio_display_tech');
		delete_option('webphysiology_portfolio_missing_image_url');
		delete_option('webphysiology_portfolio_allowed_image_sites');
		delete_option('webphysiology_portfolio_use_stw');
		delete_option('webphysiology_portfolio_stw_ak');
		delete_option('webphysiology_portfolio_stw_sk');
		delete_option('webphysiology_portfolio_image_click_behavior');
		delete_option('webphysiology_portfolio_anchor_click_behavior');
		delete_option('webphysiology_portfolio_label_width');
		delete_option('webphysiology_portfolio_display_labels');
		delete_option('webphysiology_portfolio_items_per_page');
		delete_option('webphysiology_portfolio_sort_numerically');
		delete_option('webphysiology_portfolio_display_credit');
		delete_option('webphysiology_portfolio_gridstyle');
		delete_option('webphysiology_portfolio_gridcolor');
		delete_option('webphysiology_portfolio_use_css');
		delete_option('webphysiology_portfolio_overall_width');
		delete_option('webphysiology_portfolio_image_width');
		delete_option('webphysiology_portfolio_header_color');
		delete_option('webphysiology_portfolio_link_color');
		delete_option('webphysiology_portfolio_odd_stripe_color');
		delete_option('webphysiology_portfolio_even_stripe_color');
		delete_option('webphysiology_portfolio_delete_options');
		delete_option('webphysiology_portfolio_delete_data');
		
	}
	
	// if the delete data option is set to delete, then delete the Portfolio records and Portfolio Type taxonomy records
	if ( $deletedata == "true" ) {
		
		// Gather the Portfolios
		$portfolios_to_delete = new WP_Query(array('post_type' => 'webphys_portfolio', 'post_status' => 'any', 'orderby' => 'ID', 'order' => 'DESC'));
		
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
	
	// if the save was initiated by an autosave or a quick edit, exit out as the Portfolio fields being updated here may get over written or hang the save
	if (!isset($_POST['autosave_quickedit_check'])) {
		return $post->ID;
	}
	
	// verify this call is the result of a POST
	if ( empty($_POST) ) {
		return $post->ID;
	}
 
	// if the user isn't saving a portfolio
	if (strtolower($_POST['post_type']) != "webphys_portfolio") {
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
	if (!empty($_POST['_sortorder'])) {
		$portfolio_meta['_sortorder'] = $_POST['_sortorder'];
	} else {
		$portfolio_meta['_sortorder'] = -1*($post->ID);
	}
	
 
	// Add values of $portfolio_meta as custom fields
 
	foreach ($portfolio_meta as $key => $value) { // Cycle through the $portfolio_meta array!
		if ( $post->post_type == 'revision' ) return; // Don't store custom data twice
		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		if (get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
			update_post_meta($post->ID, $key, $value);
		} else { // If the custom field doesn't have a value
			add_post_meta($post->ID, $key, $value);
		}
		if (!$value) delete_post_meta($post->ID, $key); // Delete if blank
	}
 
}
 
// Add the Save Metabox Data
add_action('save_post', 'save_portfolio_meta', 1, 2); // save the custom fields
	
// remove the Porfolio Type tag sidebar widget from the Portfolio edit screen as the Portfolio Type dropdown manages this
// also remove author dropdown list as this really doesn't apply to Portfolios
function remove_post_custom_fields() {
	remove_meta_box( 'tagsdiv-portfolio_type' , 'webphys_portfolio' , 'side' );
	remove_meta_box( 'authordiv' , 'webphys_portfolio' , 'content' );
}
if (is_admin()) {
	add_action( 'admin_menu' , 'remove_post_custom_fields' );
}

//*************************************************//
//******** PORTFOLIO EDIT SCREEN CODE END  ********//
//*************************************************//


//*************************************************//
//********* PORTFOLIO LISTING CODE START  *********//
//*************************************************//

/* Register the Portfolio columns to display in the Portfolio Admin listing */
function add_new_portfolio_columns($columns) {
	
	// hide the Portfolio edit screen Preview button
	echo "\n" . '<style type="text/css" id="webphysiology_portfolio_hide_preview_css">' . "\n" . '	.row-actions { width: 120%; } ' . "\n" . '	label.inline-edit-tags { display: none !important; } ' . "\n" . '</style>' . "\n";
	$detail_labels = get_option( 'webphysiology_portfolio_display_labels' );
	$type = $detail_labels["Type"];
	$createdate = $detail_labels["Created"];
	$clientname = $detail_labels["Client"];
	$siteURL = $detail_labels["SiteURL"];
	$tech = $detail_labels["Tech"];
	
	if (empty($type)) {
		$type = 'Type';
	}
	if (empty($createdate)) {
		$createdate = 'Create Date';
	}
	if (empty($clientname)) {
		$clientname = 'Client';
	}
	if (empty($siteURL)) {
		$siteURL = 'Website URL';
	}
	if (empty($tech)) {
		$tech = 'Technical Details';
	}
	
	// perhaps update column names (e.g., _clientname) to make them more unique (e.g., _webphys_clientname)
	
	// note: columns in the listing are ordered in line with where they are created below
	$new_columns['title'] = _x('Portfolio Name', 'column name');
	$new_columns['_createdate'] = _x( $createdate, 'column name' );
	$new_columns['_clientname'] = _x( $clientname, 'column name' );
	$new_columns['_technical_details'] = _x( $tech, 'column name' );
	$new_columns['_siteurl'] = _x( $siteURL, 'column name' );
	$new_columns['_portfolio_type'] = _x( $type, 'column name' );
	$new_columns['_sortorder'] = _x( 'Sort Order', 'column name' );
	$new_columns['date'] = _x('Create Date', 'column name');
	$new_columns['id'] = __('ID');

	return $new_columns;
}


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
		if (!empty($portfolio_type->name)) {
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

//removes view from portfolio list
function remove_quick_edit( $actions ) {
	global $post;
    if( $post->post_type == 'webphys_portfolio' ) {
//		unset($actions['inline hide-if-no-js']);
		unset($actions['view']);
	}
	
    return $actions;
	
}

if (is_admin()) {
	add_filter('manage_edit-webphys_portfolio_columns', 'add_new_portfolio_columns');
	add_action('manage_posts_custom_column', 'manage_portfolio_columns', 10, 2);
	add_filter('post_row_actions','remove_quick_edit',10,2);
}

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

	// Add stylesheet link to the header of the Admin area
	function portfolio_admin_css() {
		$file = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'css/portfolio_admin.css';
		wp_register_style('portfolio_admin_css', $file);
		wp_enqueue_style('portfolio_admin_css');
		wp_enqueue_script('farbtastic');
		
		$file = str_replace('wp-content/plugins', "wp-admin/css/",WP_PLUGIN_URL) . 'farbtastic.css';
		wp_register_style('farbtastic_css', $file);
		wp_enqueue_style('farbtastic_css');
	}
	
	// Add Portfolio Options menu item
	function portolio_admin_menu() {
		
		add_submenu_page('edit.php?post_type=webphys_portfolio', 'WEBphysiology Portfolio Options', 'Options', 'manage_options', 'webphysiology-portfolio', 'portfolio_plugin_page' );
		
	}
	
	// Add plugin Settings link
	function add_plugin_settings_link($links) {
		$x = str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$settings_link = '<a href="edit.php?post_type=webphys_portfolio&page=' . $x .'">' . __('Settings','Portfolio') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	$plugin = plugin_basename(__FILE__);
	
	add_action('init', 'portfolio_admin_css');
	add_action('admin_menu', 'portolio_admin_menu');
	add_filter( 'plugin_action_links_' . $plugin, 'add_plugin_settings_link' );
}

// define the Portfolio Plugin settings admin page
function portfolio_plugin_page() {
	
    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
		wp_die( __('Your user account does not have sufficient privileges to manage Portfolio options.') );
    }
	
	echo '<div class="wrap portfolio-admin">';
    echo '	<div class="company_logo">';
    echo '        <a href="http://WEBphysiology.com/">&nbsp;</a>';
    echo '        <div id="icon-plugins" class="icon32"></div><h2>Portfolio Options</h2>';
    echo '    </div>';
    echo '    <div class="postbox-container">';
    echo '        <div class="metabox-holder">';
    echo '            <div class="meta-box-sortables">';

    // variables for the field and option names
	$hidden_field_name = 'webphys_submit_hidden';
	$display_portfolio_title = 'webphysiology_portfolio_display_portfolio_title'; // default true
	$display_portfolio_desc = 'webphysiology_portfolio_display_portfolio_desc'; // default true
	$display_portfolio_type = 'webphysiology_portfolio_display_portfolio_type'; // default true
	$display_createdate = 'webphysiology_portfolio_display_createdate'; // default true
	$display_clientname = 'webphysiology_portfolio_display_clientname'; // default true
	$display_siteurl = 'webphysiology_portfolio_display_siteurl'; // default true
	$display_tech = 'webphysiology_portfolio_display_tech'; // default true
	$missing_img_url = 'webphysiology_portfolio_missing_image_url'; // default images/empty_window.png
	$allowed_sites = 'webphysiology_portfolio_allowed_image_sites'; // default flickr.com,picasa.com,blogger.com,wordpress.com,img.youtube.com
	$use_stw = 'webphysiology_portfolio_use_stw'; // default false
	$stw_ak = 'webphysiology_portfolio_stw_ak'; // default ""
	$stw_sk = 'webphysiology_portfolio_stw_sk'; // default ""
	$img_click_behavior = 'webphysiology_portfolio_image_click_behavior'; // default litebox
	$target = 'webphysiology_portfolio_anchor_click_behavior'; // default False
	$check_openlitebox = '';
	$check_nav2page = '';
	$label_width = 'webphysiology_portfolio_label_width'; // default 60
	$display_labels = 'webphysiology_portfolio_display_labels'; // default array("Type" => "Type","Created" => "Created","Client" => "For","SiteURL" => "Site","Tech" => "Tech")
	$items_per_page = 'webphysiology_portfolio_items_per_page';  // default 3
	$sort_numerically = 'webphysiology_portfolio_sort_numerically'; // default true
	$display_credit = 'webphysiology_portfolio_display_credit'; // default true
	$gridstyle = 'webphysiology_portfolio_gridstyle'; // default false
	$gridcolor = 'webphysiology_portfolio_gridcolor'; // default #eee
	$use_css = 'webphysiology_portfolio_use_css'; // default true
	$overall_width = 'webphysiology_portfolio_overall_width'; // default is 660px
	$img_width = 'webphysiology_portfolio_image_width'; // default is 200px
	$header_color = 'webphysiology_portfolio_header_color'; // default is #004813
	$link_color = 'webphysiology_portfolio_link_color'; // default is #004813
	$odd_stripe_color = 'webphysiology_portfolio_odd_stripe_color'; // default is #eee
	$even_stripe_color = 'webphysiology_portfolio_even_stripe_color'; // default is #f9f9f9
	$delete_options = 'webphysiology_portfolio_delete_options'; // default false
	$delete_data = 'webphysiology_portfolio_delete_data'; // default false


    // See if the user has posted us some information.  If they did, this hidden field will be set to 'Y'.
    if ( !empty($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		
		if ($_POST["reset_form"] == "False") {
		
			// Read their posted value
			if ( !empty($_POST[ $display_portfolio_title ]) ) {
				$opt_val_display_portfolio_title = $_POST[ $display_portfolio_title ];
			} else {
				$opt_val_display_portfolio_title = "False";
			}
			if ( !empty($_POST[ $display_portfolio_desc ]) ) {
				$opt_val_display_portfolio_desc = $_POST[ $display_portfolio_desc ];
			} else {
				$opt_val_display_portfolio_desc = "False";
			}
			if ( !empty($_POST[ $display_portfolio_type ]) ) {
				$opt_val_display_portfolio_type = $_POST[ $display_portfolio_type ];
			} else {
				$opt_val_display_portfolio_type = "False";
			}
			if ( !empty($_POST[ $display_createdate ]) ) {
				$opt_val_display_createdate = $_POST[ $display_createdate ];
			} else {
				$opt_val_display_createdate = "False";
			}
			if ( !empty($_POST[ $display_clientname ]) ) {
				$opt_val_display_clientname = $_POST[ $display_clientname ];
			} else {
				$opt_val_display_clientname = "False";
			}
			if ( !empty($_POST[ $display_siteurl ]) ) {
				$opt_val_display_siteurl = $_POST[ $display_siteurl ];
			} else {
				$opt_val_display_siteurl = "False";
			}
			if ( !empty($_POST[ $display_tech ]) ) {
				$opt_val_display_tech = $_POST[ $display_tech ];
			} else {
				$opt_val_display_tech = "False";
			}
			if (!empty($_POST[ $img_click_behavior ])) {
				$opt_val_img_click_behavior = $_POST[ $img_click_behavior ];
			} else {
				$opt_val_img_click_behavior = 'litebox';
			}
			if ( !empty($_POST[ $target ]) ) {
				$opt_val_target = $_POST[ $target ];
			} else {
				$opt_val_target = "False";
			}
			$opt_val_label_width = $_POST[ $label_width ];
			$opt_val_display_labels["Type"] = $_POST[ $display_labels . '_Type' ];
			$opt_val_display_labels["Created"] = $_POST[ $display_labels . '_Created' ];
			$opt_val_display_labels["Client"] = $_POST[ $display_labels . '_Client' ];
			$opt_val_display_labels["SiteURL"] = $_POST[ $display_labels . '_SiteURL' ];
			$opt_val_display_labels["Tech"] = $_POST[ $display_labels . '_Tech' ];
			if (!empty($_POST[ $missing_img_url ])) {
				$opt_val_missing_img_url = $_POST[ $missing_img_url ];
			} else {
				$opt_val_missing_img_url = 'images/empty_window.png';
			}
			$opt_val_allowed_sites = $_POST[ $allowed_sites ];
			if ( !empty($_POST[ $use_stw ]) ) {
				$opt_val_use_stw = $_POST[ $use_stw ];
			} else {
				$opt_val_use_stw = "False";
			}
			$opt_val_stw_ak = $_POST[ $stw_ak ];
			$opt_val_stw_sk = $_POST[ $stw_sk ];
			$opt_val_items_per_page = $_POST[ $items_per_page ];
			if (!empty($_POST[ $sort_numerically ])) {
				$opt_val_sort_numerically = $_POST[ $sort_numerically ];
			} else {
				$opt_val_sort_numerically = 'False';
			}			
			if ( !empty($_POST[ $display_credit ]) ) {
				$opt_val_display_credit = $_POST[ $display_credit ];
			} else {
				$opt_val_display_credit = "False";
			}
			if (!empty($_POST[ $gridstyle ])) {
				$opt_val_gridstyle = $_POST[ $gridstyle ];
			} else {
				$opt_val_gridstyle = "False";
			}
			$opt_val_gridcolor = $_POST[ $gridcolor ];
			$opt_val_css = $_POST[ $use_css ];
			$opt_val_overall_width = $_POST[ $overall_width ];
			$opt_val_img_width = $_POST[ $img_width ];
			$opt_val_header_color = $_POST[ $header_color ];
			$opt_val_link_color = $_POST[ $link_color ];
			$opt_val_odd_stripe_color = $_POST[ $odd_stripe_color ];
			$opt_val_even_stripe_color = $_POST[ $even_stripe_color ];
			if ( !empty($_POST[ $delete_options ]) ) {
				$opt_val_delete_options = $_POST[ $delete_options ];
			} else {
				$opt_val_delete_options = "False";
			}
			if ( !empty($_POST[ $delete_data ]) ) {
				$opt_val_delete_data = $_POST[ $delete_data ];
			} else {
				$opt_val_delete_data = "False";
			}
			
			$validated = true;
			$validation_msg = __('settings saved', 'Portfolio' );
			
			// do some validating on whether the resulting widths will work or not
			if (!is_numeric($opt_val_items_per_page)) {
				$validated = false;
				$validation_msg = __('settings NOT saved - items per page must be stated as a numeric value.', 'Portfolio' );
			} elseif (!is_numeric($opt_val_overall_width) || !is_numeric($opt_val_img_width) || !is_numeric($opt_val_label_width)) {
				$validated = false;
				$validation_msg = __('settings NOT saved - widths must be stated as a numeric value.', 'Portfolio' );
			} elseif ($opt_val_img_width > $opt_val_overall_width) {
				$validated = false;
				$validation_msg = __('settings NOT saved - image width can\'t be wider than the overall width.', 'Portfolio' );
			} elseif ($opt_val_overall_width < 250) {
				$validated = false;
				$validation_msg = __('settings NOT saved - overall width cannot be narrower than 250 pixels.', 'Portfolio' );
			} elseif ( ($opt_val_use_stw == "True") && ( (empty($opt_val_stw_ak)) || (empty($opt_val_stw_sk)) ) ) {
				$validated = false;
				$validation_msg = __('settings NOT saved - ShrinkTheWeb.com settings are incomplete.', 'Portfolio' );
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
			
		} else {  //if ($_POST["reset_form"] == "True") {
			
			// Reset to default settings
			$opt_val_display_portfolio_title = "True";
			$opt_val_display_portfolio_desc = "True";
			$opt_val_display_portfolio_type = "True";
			$opt_val_display_createdate = "True";
			$opt_val_display_clientname = "True";
			$opt_val_display_siteurl = "True";
			$opt_val_display_tech = "True";
			$opt_val_missing_img_url = "images/empty_window.png";
			$opt_val_allowed_sites = "flickr.com,picasa.com,blogger.com,wordpress.com,img.youtube.com";
			$opt_val_use_stw = "False";
			$opt_val_stw_ak = "";
			$opt_val_stw_sk = "";
			$opt_val_img_click_behavior = "litebox";
			$opt_val_label_width = "60";
			$opt_val_display_labels = array("Type" => "Type", "Created" => "Created", "Client" => "For", "SiteURL" => "Site", "Tech" => "Tech");
			$opt_val_items_per_page = "3";
			$opt_val_sort_numerically = "True";
			$opt_val_display_credit = "True";
			$opt_val_gridstyle = "False";
			$opt_val_gridcolor = "#eeeeee";
			$opt_val_css = "True";
			$opt_val_overall_width = "660";
			$opt_val_img_width = "200";
			$opt_val_header_color = "#004813";
			$opt_val_link_color = "#004813";
			$opt_val_odd_stripe_color = "#eeeeee";
			$opt_val_even_stripe_color = "#f9f9f9";
			$opt_val_delete_options = "False";
			$opt_val_delete_data = "False";
			$validation_msg = __('successfully reset to default values', 'Portfolio' );
			$validated = true;
			
		}
		
		// if the specified sizes are within tolerances
		if ( $validated ) {
			
			// Save the posted value in the database
			update_option( $display_portfolio_title, $opt_val_display_portfolio_title );
			update_option( $display_portfolio_desc, $opt_val_display_portfolio_desc );
			update_option( $display_portfolio_type, $opt_val_display_portfolio_type );
			update_option( $display_createdate, $opt_val_display_createdate );
			update_option( $display_clientname, $opt_val_display_clientname );
			update_option( $display_siteurl, $opt_val_display_siteurl );
			update_option( $display_tech, $opt_val_display_tech );
			update_option( $missing_img_url, $opt_val_missing_img_url );
			update_option( $allowed_sites, $opt_val_allowed_sites );
			update_option( $use_stw, $opt_val_use_stw );
			update_option( $stw_ak, $opt_val_stw_ak );
			update_option( $stw_sk, $opt_val_stw_sk );
			update_option( $img_click_behavior, $opt_val_img_click_behavior );
			update_option( $target, $opt_val_target );
			update_option( $label_width, $opt_val_label_width );
			update_option( $display_labels, $opt_val_display_labels );
			update_option( $items_per_page, $opt_val_items_per_page );
			update_option( $sort_numerically, $opt_val_sort_numerically );
			update_option( $display_credit, $opt_val_display_credit );
			update_option( $gridstyle, $opt_val_gridstyle );
			update_option( $gridcolor, $opt_val_gridcolor );
			update_option( $use_css, $opt_val_css );
			update_option( $overall_width, $opt_val_overall_width );
			update_option( $img_width, $opt_val_img_width );
			update_option( $header_color, $opt_val_header_color );
			update_option( $link_color, $opt_val_link_color );
			update_option( $odd_stripe_color, $opt_val_odd_stripe_color );
			update_option( $even_stripe_color, $opt_val_even_stripe_color );
			update_option( $delete_options, $opt_val_delete_options );
			update_option( $delete_data, $opt_val_delete_data );
			
		}
		
		// Put a settings updated message on the screen
		echo ('<div class="updated"><p><strong>' . $validation_msg . '</strong></p></div>');
		
	} else {
		
		// Read in existing option value from database
		$opt_val_display_portfolio_title = get_option( $display_portfolio_title );
		$opt_val_display_portfolio_desc = get_option( $display_portfolio_desc );
		$opt_val_display_portfolio_type = get_option( $display_portfolio_type );
		$opt_val_display_createdate = get_option( $display_createdate );
		$opt_val_display_clientname = get_option( $display_clientname );
		$opt_val_display_siteurl = get_option( $display_siteurl );
		$opt_val_display_tech = get_option( $display_tech );
		$opt_val_missing_img_url = get_option( $missing_img_url );
		$opt_val_allowed_sites = get_option( $allowed_sites );
		$opt_val_use_stw = get_option( $use_stw );
		$opt_val_stw_ak = get_option( $stw_ak );
		$opt_val_stw_sk = get_option( $stw_sk );
		$opt_val_img_click_behavior = get_option( $img_click_behavior );
		$opt_val_target = get_option( $target );
		$opt_val_label_width = get_option( $label_width );
		$opt_val_display_labels = get_option( $display_labels );
		$opt_val_items_per_page = get_option( $items_per_page );
		$opt_val_sort_numerically = get_option( $sort_numerically );
		$opt_val_display_credit = get_option( $display_credit );
		$opt_val_gridstyle = get_option( $gridstyle );
		$opt_val_gridcolor = get_option( $gridcolor );
		$opt_val_css = get_option( $use_css );
		$opt_val_overall_width = get_option( $overall_width );
		$opt_val_img_width = get_option( $img_width );
		$opt_val_header_color = get_option( $header_color );
		$opt_val_link_color = get_option( $link_color );
		$opt_val_odd_stripe_color = get_option( $odd_stripe_color );
		$opt_val_even_stripe_color = get_option( $even_stripe_color );
		$opt_val_delete_options = get_option( $delete_options );
		$opt_val_delete_data = get_option( $delete_data );
		
	}
	
	if ($opt_val_display_portfolio_title=="True" ) {$opt_val_display_portfolio_title="checked";}
	if ($opt_val_display_portfolio_desc=="True" ) {$opt_val_display_portfolio_desc="checked";}
	if ($opt_val_display_portfolio_type=="True" ) {$opt_val_display_portfolio_type="checked";}
	if ($opt_val_display_createdate=="True" ) {$opt_val_display_createdate="checked";}
	if ($opt_val_display_clientname=="True" ) {$opt_val_display_clientname="checked";}
	if ($opt_val_display_siteurl=="True" ) {$opt_val_display_siteurl="checked";}
	if ($opt_val_display_tech=="True" ) {$opt_val_display_tech="checked";}
	if ($opt_val_use_stw=="True" ) {$opt_val_use_stw="checked";} else {$opt_val_use_stw="";}
	if ($opt_val_img_click_behavior == "litebox") { $check_openlitebox = 'checked'; } else { $check_nav2page = 'checked'; }
	if ($opt_val_target=="True" ) {$opt_val_target="checked";}
	if ($opt_val_sort_numerically=="True" ) {$opt_val_sort_numerically="checked";}
	if ($opt_val_css=="True" ) {$opt_val_css="checked";}
	if ($opt_val_display_credit=="True" ) {$opt_val_display_credit="checked";}
	if ($opt_val_gridstyle=="True" ) {$opt_val_gridstyle="checked";}
	if ($opt_val_delete_options=="True" ) {$opt_val_delete_options="checked";}
	if ($opt_val_delete_data=="True" ) {$opt_val_delete_data="checked";}
	
	echo "\n";
	echo '<script type="text/javascript">' . "\n";
	echo '	<!--' . "\n";
	echo '	jQuery(document).ready(function() {' . "\n";
	echo '		jQuery("#colorpicker").farbtastic("#colorselector")' . "\n";
	echo '	});' . "\n";
	echo '	-->' . "\n";
	echo '</script>' . "\n";
	echo "\n";
	echo '			<form action="" method="post" name="portolio-conf" id="portolio-conf">' . "\n" . '				';
	wp_nonce_field('portfolio_config', 'portolio-nonce');
	echo "\n";
	echo '				<input type="hidden" name="' . $hidden_field_name . '" value="Y">' . "\n";
	echo '				<input type="hidden" name="page_options" value="WEBphysiology_portolio_plugin_data" />' . "\n";
	echo '				<input type="hidden" value="' . get_option('version') . '" name="version"/>' . "\n";
	echo portfolio_version_alert(WEBPHYSIOLOGY_VERSION);
	echo '				<div id="pluginsettings" class="postbox">' . "\n";
	echo '					<h3 class="hndle"><span>Portfolio Display Settings</span></h3>' . "\n";
	echo '					<div class="inside">' . "\n";
	echo '						<input type="checkbox" id="' . $display_portfolio_title . '" name="' . $display_portfolio_title . '" value="True" ' . $opt_val_display_portfolio_title . '/><label for="' . $display_portfolio_title . '">Display portfolio title</label><br/>' . "\n";
	echo '						<input type="checkbox" id="' . $display_portfolio_desc . '" name="' . $display_portfolio_desc . '" value="True" ' . $opt_val_display_portfolio_desc . '/><label for="' . $display_portfolio_desc . '">Display portfolio description</label><br />' . "\n";
	echo '						<div class="display_and_label">' . "\n";
	echo '							<div class="display_attrib">' . "\n";
	echo '								<input type="checkbox" id="' . $display_portfolio_type . '" name="' . $display_portfolio_type . '" value="True" ' . $opt_val_display_portfolio_type . '/><label for="' . $display_portfolio_type . '">Display portfolio type</label><br/>' . "\n";
	echo '							</div>' . "\n";
	echo '							<div class="label_attrib">' . "\n";
	echo '								<label for="' . $display_labels . '_Type">Portfolio type label:</label><input type="text" id="' . $display_labels . '_Type" name="' . $display_labels . '_Type" value="' . $opt_val_display_labels["Type"] . '" /><br />' . "\n";
	echo '							</div>' . "\n";
	echo '						</div>' . "\n";
	echo '						<div class="display_and_label">' . "\n";
	echo '							<div class="display_attrib">' . "\n";
	echo '								<input type="checkbox" id="' . $display_createdate . '" name="' . $display_createdate . '" value="True" ' . $opt_val_display_createdate . '/><label for="' . $display_createdate . '">Display date created</label><br/>' . "\n";
	echo '							</div>' . "\n";
	echo '							<div class="label_attrib">' . "\n";
	echo '								<label for="' . $display_labels . '_Created">Date created label:</label><input type="text" id="' . $display_labels . '_Created" name="' . $display_labels . '_Created" value="' . $opt_val_display_labels["Created"] . '" /><br />' . "\n";
	echo '							</div>' . "\n";
	echo '						</div>' . "\n";
	echo '						<div class="display_and_label">' . "\n";
	echo '							<div class="display_attrib">' . "\n";
	echo '								<input type="checkbox" id="' . $display_clientname . '" name="' . $display_clientname . '" value="True" ' . $opt_val_display_clientname . '/><label for="' . $display_clientname . '">Display client name</label><br/>' . "\n";
	echo '							</div>' . "\n";
	echo '							<div class="label_attrib">' . "\n";
	echo '								<label for="' . $display_labels . '_Client">Client name label:</label><input type="text" id="' . $display_labels . '_Client" name="' . $display_labels . '_Client" value="' . $opt_val_display_labels["Client"] . '" /><br />' . "\n";
	echo '							</div>' . "\n";
	echo '						</div>' . "\n";
	echo '						<div class="display_and_label">' . "\n";
	echo '							<div class="display_attrib">' . "\n";
	echo '								<input type="checkbox" id="' . $display_siteurl . '" name="' . $display_siteurl . '" value="True" ' . $opt_val_display_siteurl . '/><label for="' . $display_siteurl . '">Display portfolio web page</label><br/>' . "\n";
	echo '							</div>' . "\n";
	echo '							<div class="label_attrib">' . "\n";
	echo '								<label for="' . $display_labels . '_SiteURL">Portfolio web page label:</label><input type="text" id="' . $display_labels . '_SiteURL" name="' . $display_labels . '_SiteURL" value="' . $opt_val_display_labels["SiteURL"] . '" /><br />' . "\n";
	echo '							</div>' . "\n";
	echo '						</div>' . "\n";
	echo '						<div class="display_and_label">' . "\n";
	echo '							<div class="display_attrib">' . "\n";
	echo '								<input type="checkbox" id="' . $display_tech . '" name="' . $display_tech . '" value="True" ' . $opt_val_display_tech . '/><label for="' . $display_tech . '">Display technical details</label><br/>' . "\n";
	echo '							</div>' . "\n";
	echo '							<div class="label_attrib">' . "\n";
	echo '								<label for="' . $display_labels . '_Tech">Technical details label:</label><input type="text" id="' . $display_labels . '_Tech" name="' . $display_labels . '_Tech" value="' . $opt_val_display_labels["Tech"] . '" /><br />' . "\n";
	echo '							</div>' . "\n";	
	echo '						</div>' . "\n";	
	echo '						<label for="' . $label_width . '">Label width:</label><input type="text" id="' . $label_width . '" name="' . $label_width . '" value="' . $opt_val_label_width . '" class="webphysiology_portfolio_small_input" /> pixels<br />' . "\n";
	echo '						<label for="' . $missing_img_url . '">Missing image URL:</label><input type="text" id="' . $missing_img_url . '" name="' . $missing_img_url . '" value="' . $opt_val_missing_img_url . '" class="half_input shortbottom" /><br /><span class="attribute_instructions">note: url should be relative to this plugin\'s directory, be in the uploads directory (e.g., /uploads/2010/11/missing.jpg) or be the full URL path</span><br class="tallbottom" />' . "\n";
	echo '						<label for="' . $allowed_sites . '">Allowed image sites:</label><input type="text" id="' . $allowed_sites . '" name="' . $allowed_sites . '" value="' . $opt_val_allowed_sites . '" class="half_input shortbottom" /><br /><span class="attribute_instructions">note: add allowed domain separated with commas (e.g., flickr.com,picasa.com,blogger.com,wordpress.com,img.youtube.com)</span><br class="tallbottom" />' . "\n";
	echo '								<input type="checkbox" id="' . $use_stw . '" name="' . $use_stw . '" value="True" ' . $opt_val_use_stw . ' /><label for="' . $use_stw . '" class="half_input shortbottom">Use ShrinkTheWeb.com</label>&nbsp;&nbsp;&nbsp;' . "\n";
	echo '							<label for="' . $stw_ak . '">Access key:</label><input type="text" id="' . $stw_ak . '" name="' . $stw_ak . '" value="' . $opt_val_stw_ak . '" />&nbsp;&nbsp;&nbsp;' . "\n";
	echo '							<label for="' . $stw_sk . '">Secret key:</label><input type="password" id="' . $stw_sk . '" name="' . $stw_sk . '" value="' . $opt_val_stw_sk . '" /><br />' . "\n";
	echo '						<span class="attribute_instructions">Get your own <a href="http://www.shrinktheweb.com">Website Preview from ShrinkTheWeb</a></span><br class="tallbottom"/>' . "\n";
	echo '						<label for="' . $img_click_behavior . '">Image click behavior: </label><input type="radio" name="' . $img_click_behavior . '" value="litebox" ' .  $check_openlitebox . ' /> Open fullsize image in a thickbox&nbsp;&nbsp;<input type="radio" name="' . $img_click_behavior . '" value="nav2page" ' . $check_nav2page . ' /> Navigate to the portfolio web page URL<br/>' . "\n";
	echo '								<input type="checkbox" id="' . $target . '" name="' . $target . '" value="True" ' . $opt_val_target . '/><label for="' . $target . '">Open links in a new tab (target="_blank")</label><br/>' . "\n";
	echo '						<span class="attribute_instructions">Commonly accepted practice is to NOT open links in a new tab or window</span><br class="tallbottom"/>' . "\n";
	echo '						<label for="' . $items_per_page . '">Portfolio items per page:</label><input type="text" id="' . $items_per_page . '" name="' . $items_per_page . '" value="' . $opt_val_items_per_page . '" class="webphysiology_portfolio_small_input" /><br />' . "\n";
	echo '						<input type="checkbox" id="' . $sort_numerically . '" name="' . $sort_numerically . '" value="True" ' . $opt_val_sort_numerically . '/><label for="' . $sort_numerically . '">Sort numerically</label><br/>' . "\n";
	echo '						<input type="checkbox" id="' . $display_credit . '" name="' . $display_credit . '" value="True" ' . $opt_val_display_credit . '/><label for="' . $display_credit . '">Display WEBphysiology credit and/or a donation would be nice (though neither is required).</label>' . "\n";
	echo '					</div>' . "\n";
	echo '				</div>' . "\n";
	echo '				<div id="pluginsettings" class="postbox">' . "\n";
	echo '					<h3 class="hndle"><span>Portfolio Styling</span></h3>' . "\n";
	echo '					<div class="inside" style="clear:both;overflow:hidden;">' . "\n";
	echo '						<div class="portfolio_admin_style">' . "\n";
	echo '							<input type="checkbox" id="' . $use_css . '" name="' . $use_css . '" value="True" ' . $opt_val_css . '/><label for="' . $use_css . '">Use Portfolio plugin CSS</label><br/>' . "\n";
	echo '								<input type="checkbox" id="' . $gridstyle . '" name="' . $gridstyle . '" value="True" ' . $opt_val_gridstyle . '/><label for="' . $gridstyle . '">Use Grid Style layout</label>&nbsp;&nbsp;&nbsp;' . "\n"; //<br/>' . "\n";
	echo '									<label for="' . $gridcolor . '">Grid background color:</label><input type="text" id="' . $gridcolor . '" name="' . $gridcolor . '" value="' . $opt_val_gridcolor . '" class="webphysiology_portfolio_small_input" /><br />' . "\n";
	echo '							<label for="' . $overall_width . '">Portfolio List - overall width:</label><input type="text" id="' . $overall_width . '" name="' . $overall_width . '" value="' . $opt_val_overall_width . '" class="webphysiology_portfolio_small_input" /> pixels<br />' . "\n";
	echo '							<label for="' . $img_width . '">Portfolio List - image width:</label><input type="text" id="' . $img_width . '" name="' . $img_width . '" value="' . $opt_val_img_width . '" class="webphysiology_portfolio_small_input" /> pixels<br /><span class="attribute_instructions">note: if you use the Grid Style layout, this is your overall cell width</span><br class="tallbottom" />' . "\n";
	echo '							<label for="' . $header_color . '">Portfolio Title color:</label><input type="text" id="' . $header_color . '" name="' . $header_color . '" value="' . $opt_val_header_color . '" class="webphysiology_portfolio_small_input" /><span style="color:' . $opt_val_header_color . ';margin-left:10px;">title color</span><br />' . "\n";
	echo '							<label for="' . $link_color . '">Portfolio Nav color:</label><input type="text" id="' . $link_color . '" name="' . $link_color . '" value="' . $opt_val_link_color . '" class="webphysiology_portfolio_small_input" /><span style="color:' . $opt_val_link_color . ';margin-left:10px;">link color</span><br /><span class="attribute_instructions">note: this is the color of the page navigation numbers</span><br class="tallbottom" />' . "\n";
	echo '							<label for="' . $odd_stripe_color . '">Portfolio odd stripe background color:</label><input type="text" id="' . $odd_stripe_color . '" name="' . $odd_stripe_color . '" value="' . $opt_val_odd_stripe_color . '" class="webphysiology_portfolio_small_input" /><span style="background-color:' . $opt_val_odd_stripe_color . ';margin-left:10px;">odd stripe color</span><br />' . "\n";
	echo '							<label for="' . $even_stripe_color . '">Portfolio even stripe background color:</label><input type="text" id="' . $even_stripe_color . '" name="' . $even_stripe_color . '" value="' . $opt_val_even_stripe_color . '" class="webphysiology_portfolio_small_input" /><span style="background-color:' . $opt_val_even_stripe_color . ';margin-left:10px;">even stripe color</span><br/>' . "\n";
	echo '						</div>' . "\n";
	echo '						<div id="color-selection-helper"">' . "\n";
	echo '							<p>color selection helper</p>' . "\n";
	echo '							<div id="colorpicker"></div>' . "\n";
	echo '							<input type="text" id="colorselector" name="colorselector" value="#cccccc" />' . "\n";
	echo '						</div>' . "\n";
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
	echo '					<input type="button" class="button" name="Default" value="Revert to Default Values" id="resetbutton" onClick="reset_to_default(this.form)" />' . "\n";
	echo '				</div>' . "\n";
	echo '				<input id="reset_form" name="reset_form" type="hidden" value="False" />' . "\n";
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
	echo '		settingsForm.reset_form.value = "True"' . "\n";
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
function showImage($label, $src)
{
    if (!$src) {
        $path = ""; // use some default image
	}
	
	return $path;
}

if ( ! function_exists( 'get_Loop_Site_Image' ) ) :
function get_Loop_Site_Image() {
	
	global $click_behavior;
	
	require_once("scripts/stw/stw.php");
	
	$anchor_open = '';
	$anchor_close = '';
	$class = '';
//	$img_click_behavior = get_option( 'webphysiology_portfolio_image_click_behavior' );
	$target = get_option( 'webphysiology_portfolio_anchor_click_behavior' );
    $full_size_img_url = get_post_meta(get_the_ID(), "_imageurl", true);
	$img_url = str_replace(content_url(), "", $full_size_img_url);
    $site_url = get_post_meta(get_the_ID(), "_siteurl", true);
	
	$opt_val_img_width = get_option( 'webphysiology_portfolio_image_width' );
	
	if ( empty($opt_val_img_width) ) { $opt_val_img_width = '150'; }
	
	if ( empty($target) || ($target == "False") ) {
		$target = '';
	} else {
		$target = ' target="_blank"';
	}
	
	// If using ShrinkTheWeb, no image URL is specified and a site URL is assigned
	if ( empty($img_url) && (strtolower(get_option( 'webphysiology_portfolio_use_stw' )) == "true") && !empty($site_url) ) {
		$non_stw_full_size_img_url = $full_size_img_url;
		$full_size_img_url = AppSTW::getScaledThumbnail($site_url, 640, 480);
	} elseif ( ! empty($full_size_img_url) ) {  // else if an image URL was assigned
		$non_stw_full_size_img_url = $full_size_img_url;
	}
	
	if ( !empty($full_size_img_url) ) {
		
		$img_url = clean_source($full_size_img_url);
		
		// if there was an issue with the image url
		if ( empty($img_url) ) { $full_size_img_url = ""; }
		
		// if the image url was cleaned and not cleared, check that it really exists
		if ( ($img_url != $full_size_img_url) && ( !empty($img_url)) ) {
			if (!file_exists(dirname ( __FILE__ ) . '/' . $img_url)) {
				if (!file_exists($img_url)) {
					$full_size_img_url = "";
				}
			}
		}
	}
	
	// if the image was not specified or was cleared due to issues, use the default empty image
	if ( empty($full_size_img_url) ) {
		
		$img_url = get_option( 'webphysiology_portfolio_missing_image_url' );
		
		if ( empty($img_url) ) {
			$img_url = 'images/empty_window.png';
		}
		if ( !empty($img_url) ) {
			$class = ' class="missing"';
		}
	}
	
	$supported_video = is_supported_video($site_url);
	
	if ( ($click_behavior == 'litebox') && ( !empty($full_size_img_url) || $supported_video ) ) {
		
		if ( $supported_video ) {
			
			$fbclass = get_video_class($site_url);
			$anchor_open = '<a class="Portfolio-Link' . $fbclass . '" href="' . $site_url . '" title="' . the_title_attribute( 'echo=0' ) . '"' . $target . '>';
			
			if ( empty($non_stw_full_size_img_url) ) {
				$img_html = get_Video_Thumbnail($site_url, $img_url, $opt_val_img_width);
			} else {
				$img_html = esc_attr(plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $img_url . '&w=' . $opt_val_img_width . '&zc=1');
			};
			
		} else {
			
			$anchor_open = '<a class="Portfolio-Link thickbox" href="' . $full_size_img_url . '" title="' . the_title_attribute( 'echo=0' ) . '"' . $target . '>';
			$img_html = esc_attr(plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $img_url . '&w=' . $opt_val_img_width . '&zc=1');
			
		}
		
		$anchor_close = '</a>';
			
	} elseif ( ( $click_behavior == 'nav2page' ) && ( $supported_video ) ) {
		
		$anchor_open = '<a href="' . $site_url . '" title="' . the_title_attribute( 'echo=0' ) . '" class="Portfolio-Link"' . $target . '>';
		$anchor_close = '</a>';
		
		if ( empty($non_stw_full_size_img_url) ) {
			$img_html = get_Video_Thumbnail($site_url, $img_url, $opt_val_img_width);
		} else {
			$img_html = esc_attr(plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $img_url . '&w=' . $opt_val_img_width . '&zc=1');
		};
		
	} elseif ( ( $click_behavior == 'nav2page' ) && ( !empty($site_url) ) ) {
		
		$anchor_open = '<a href="' . $site_url . '" title="' . the_title_attribute( 'echo=0' ) . '" class="Portfolio-Link"' . $target . '>';
		$anchor_close = '</a>';
		$img_html = esc_attr(plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $img_url . '&w=' . $opt_val_img_width . '&zc=1');
		
	} elseif ( ! empty($img_url) ) {
		
		$img_html = esc_attr(plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $img_url . '&w=' . $opt_val_img_width . '&zc=1');
		
	}
	
	if ( ! empty($img_html) ) {
		$path = $anchor_open . '<img src="' . $img_html . '" alt="' . the_title_attribute('echo=0') . '"' . $class . ' width="' . $opt_val_img_width . '" />' . $anchor_close;
	} else {
		$path = $anchor_open . 'no image' . $anchor_close;
	}
	
	return $path;
}
endif;

// Check to see if the specified portfolio URL is a supportedd video URL
if ( ! function_exists( 'is_supported_video' ) ) :

function is_supported_video($siteurl) {
	
	// Currently supported video formats: Vimeo and YouTube
	
	if ( ( strpos($siteurl, 'vimeo.com/') !== false ) || ( strpos($siteurl, 'youtube.com/watch') !== false ) ) {
		return true;
	} else {
		return false;
	}
}

endif;

// Determine the class to assign to the anchor tag based upon the video provider
if ( ! function_exists( 'get_video_class' ) ) :

function get_video_class($siteurl) {
	if ( strpos($siteurl, 'vimeo') !== false ) {
		$fbclass = ' vimeo';
	} elseif ( strpos($siteurl, 'youtube.com/watch') !== false ) {
		$fbclass = ' youtube';
	} else {
		$fbclass = '';
	}
	return $fbclass;
}

endif;

// Get the thumbnail for the specified video
if ( ! function_exists( 'get_Video_Thumbnail' ) ) :

function get_Video_Thumbnail($vid, $stw, $img_width) {
	
	if ( strpos($vid, 'vimeo.com/') !== false ) {
		$thumb = str_replace("http://vimeo.com/","",$vid);
		if ( is_numeric($thumb) ) {
			$url = 'http://vimeo.com/api/v2/video/' . $thumb . '.php';
			$contents = @file_get_contents($url);
			$array = @unserialize(trim($contents));
			$img_url = $array[0]['thumbnail_large'];
		}
	} elseif ( strpos($vid, 'youtube.com/watch') !== false ) {
		$thumb = str_replace("www.youtube.com/watch?v=","i.ytimg.com/vi/",$vid);
		if ( strpos($thumb, "&feature=player_embedded") !== false ) {
			$img_url = str_replace("&feature=player_embedded","/hqdefault.jpg",$thumb);
		} else {
			$img_url = $thumb . "/hqdefault.jpg";
		}
	} else {
		$img_url = $vid;
	}
	
	if ( empty($img_url) && !empty($stw) ) {
		if ( empty($img_width) ) {
			$img_width = get_option( 'webphysiology_portfolio_image_width' );
		}
		$img_url = esc_attr(plugin_dir_url(__FILE__) . 'scripts/thumb/timthumb.php?src=' . $stw . '&w=' . $img_width . '&zc=1');
	}
	
	return $img_url;
	
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
	
	$gridstyle = 'webphysiology_portfolio_gridstyle'; // default false
	$gridcolor = 'webphysiology_portfolio_gridcolor'; // default #eee
	$overall_width = 'webphysiology_portfolio_overall_width'; // default is 660px
	$img_width = 'webphysiology_portfolio_image_width'; // default is 200px
	$meta_key_width = 'webphysiology_portfolio_label_width'; // default is 60px
	$header_color = 'webphysiology_portfolio_header_color'; // default is #004813
	$link_color = 'webphysiology_portfolio_link_color'; // default is #004813
	$odd_stripe_color = 'webphysiology_portfolio_odd_stripe_color'; // default is #eee
	$even_stripe_color = 'webphysiology_portfolio_even_stripe_color'; // default is #f9f9f9
	$opt_val_gridstyle = get_option( $gridstyle );
	$opt_val_gridcolor = get_option( $gridcolor );
	$opt_val_overall_width = get_option( $overall_width );
	$opt_val_img_width = get_option( $img_width );
	$opt_val_meta_key_width = get_option( $meta_key_width );
	$opt_val_header_color = get_option( $header_color );
	$opt_val_link_color = get_option( $link_color );
	$opt_val_odd_stripe_color = get_option( $odd_stripe_color );
	$opt_val_even_stripe_color = get_option( $even_stripe_color );
	$portfolio_css_on = get_option('webphysiology_portfolio_use_css');
	
	$overall_image_width = $opt_val_img_width + 20;
	if ($opt_val_gridstyle != 'True') {
		$detail_width = $opt_val_overall_width - $overall_image_width - 30;
		$meta_value_width = $detail_width - ($opt_val_meta_key_width + 4);
		$class = '.portfolio_details';
	} else {
		$detail_width = $overall_image_width - 10;
		$meta_value_width = $detail_width - ($opt_val_meta_key_width + 4);
		$class = '.portfolio_entry';
	}
	$grid_image_width = $detail_width - 10;
	
	$embedded_css = "\n" .
					'<style type="text/css" id="webphysiology_portfolio_embedded_css">' . "\n" .
					'    #portfolios, .webphysiology_portfolio {	' . "\n" .
					'        width: ' . $opt_val_overall_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios ' . $class . ',' . "\n" .
					'    .webphysiology_portfolio ' . $class . ' {' . "\n" .
					'        width: ' . $detail_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_page_img,' . "\n" .
					'    .webphysiology_portfolio .portfolio_page_img {' . "\n" .
					'        width: ' . $overall_image_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios .grid .portfolio_page_img,' . "\n" .
					'    .webphysiology_portfolio .grid .portfolio_page_img {' . "\n" .
					'        width: ' . $detail_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_page_img img,' . "\n" .
					'    .webphysiology_portfolio .portfolio_page_img img {' . "\n" .
					'        width: ' . $opt_val_img_width . 'px;' . "\n" .
					'        max-width: ' . $opt_val_img_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_meta .key,' . "\n" .
					'    .webphysiology_portfolio .portfolio_meta .key {' . "\n" .
					'    	width: ' . $opt_val_meta_key_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_meta .value,' . "\n" .
					'    .webphysiology_portfolio .portfolio_meta .value {' . "\n" .
					'        width: ' . $meta_value_width . 'px;' . "\n" .
					'    }' . "\n" .
					'    #portfolios ul.grid,' . "\n" .
					'    .webphysiology_portfolio ul.grid {' . "\n" .
					'    	background-color: ' . $opt_val_gridcolor . ';' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_title h1, #portfolios .portfolio_title h2,' . "\n" .
					'    .webphysiology_portfolio .portfolio_title h1, .webphysiology_portfolio .portfolio_title h2 {' . "\n" .
					'        color: ' . $opt_val_header_color . ';' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_nav a,' . "\n" .
					'    .webphysiology_portfolio .portfolio_nav a {' . "\n" .
					'        color: ' . $opt_val_link_color . ';' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_entry,' . "\n" .
					'    .webphysiology_portfolio .portfolio_entry {' . "\n" .
					'        background-color: ' . $opt_val_even_stripe_color . ';' . "\n" .
					'    }' . "\n" .
					'    #portfolios .portfolio_entry.odd,' . "\n" .
					'    .webphysiology_portfolio .portfolio_entry.odd {' . "\n" .
					'        background-color: ' . $opt_val_odd_stripe_color . ';' . "\n" .
					'    }' . "\n" .
					'</style>' . "\n";
	
	if (is_wp_error($portfolio_css_on) || $portfolio_css_on=='True') {
		
		echo $embedded_css;
		
		$x = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'css/';
		// note: wp_enqueue_style does not support conditional stylesheets at this time
		
		echo "\n";
		echo '<!--[if lte IE 8]>' . "\n";
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
	echo ("\n");
	echo ( 'jQuery(document).ready(function() {' . "\n");
	echo ("\n");
	echo ( '	jQuery("a.thickbox").fancybox({' . "\n");
	echo ( "		'overlayOpacity'	:	0.95," . "\n");
	echo ( "		'overlayColor'	:	'#333'," . "\n");
	echo ( "		'transitionIn'	: 'fade'," . "\n");
	echo ( "		'transitionOut'	: 'fade'," . "\n");
	echo ( "		'speedIn'	:	350," . "\n");
	echo ( "		'speedOut'	:	350," . "\n");
	echo ( "		'hideOnContentClick'	:	true," . "\n");
	echo ( "		'href' : this.src," . "\n");
	echo ( "		'showCloseButton'	: true," . "\n");
	echo ( "		'titleShow'	:	false" . "\n");
	echo ( "	});" . "\n");
	echo ("\n");
	echo ( '	jQuery("a.alert_text").click(function() {' . "\n");
	echo ( '		jQuery.fancybox({' . "\n");
	echo ( "			'overlayOpacity'		: 0.95," . "\n");
	echo ( "			'overlayColor'			: '#333'," . "\n");
	echo ( "			'speedIn'				: 350," . "\n");
	echo ( "			'speedOut'				: 350," . "\n");
	echo ( "			'hideOnContentClick'	: false," . "\n");
	echo ( "			'transitionIn'			: 'fade'," . "\n");
	echo ( "			'transitionOut'			: 'fade'," . "\n");
	echo ( "			'autoDimension'			: false," . "\n");
	echo ( "			'width'					: 480," . "\n");
	echo ( "			'height'				: 'auto'," . "\n");
	echo ( "			'href'					: this.href," . "\n");
	echo ( "			'scrolling'				: 'yes'," . "\n");
	echo ( "			'showCloseButton'		: true," . "\n");
	echo ( "			'titleShow'				: false" . "\n");
	echo ( "		});" . "\n");
	echo ( "		return false;" . "\n");
	echo ( "	});" . "\n");
	echo ("\n");
	echo ( '	jQuery("a.vimeo").click(function() {' . "\n");
	echo ( '		jQuery.fancybox({' . "\n");
	echo ( "			'overlayOpacity'	: 0.95," . "\n");
	echo ( "			'overlayColor'	: '#333'," . "\n");
	echo ( "			'speedIn'		: 350," . "\n");
	echo ( "			'speedOut'		: 350," . "\n");
	echo ( "			'padding'		: 10," . "\n");
	echo ( "			'autoScale'		: false," . "\n");
	echo ( "			'transitionIn'	: 'fade'," . "\n");
	echo ( "			'transitionOut'	: 'fade'," . "\n");
	echo ( "			'title'			: this.title," . "\n");
	echo ( "			'titleShow'		: false," . "\n");
	echo ( "			'showCloseButton'	: true," . "\n");
	echo ( "			'width'			: 680," . "\n");
	echo ( "			'height'		: 495," . "\n");
	echo ( "			'href'			: this.href.replace(new RegExp(\"([0-9])\",\"i\"),'moogaloop.swf?clip_id=$1')," . "\n");
	echo ( "			'type'			: 'swf'" . "\n");
	echo ( "		});" . "\n");
	echo ( "		return false;" . "\n");
	echo ( "	});" . "\n");
	echo ("\n");
	echo ( '	jQuery("a.youtube").click(function() {' . "\n");
	echo ( '		jQuery.fancybox({' . "\n");
	echo ( "			'overlayOpacity'	: 0.95," . "\n");
	echo ( "			'overlayColor'	: '#333'," . "\n");
	echo ( "			'speedIn'		: 350," . "\n");
	echo ( "			'speedOut'		: 350," . "\n");
	echo ( "			'padding'		: 10," . "\n");
	echo ( "			'autoScale'		: false," . "\n");
	echo ( "			'transitionIn'	: 'fade'," . "\n");
	echo ( "			'transitionOut'	: 'fade'," . "\n");
	echo ( "			'title'			: this.title," . "\n");
	echo ( "			'titleShow'		: false," . "\n");
	echo ( "			'showCloseButton'	: true," . "\n");
	echo ( "			'width'			: 680," . "\n");
	echo ( "			'height'		: 495," . "\n");
	echo ( "			'href'			: this.href.replace(new RegExp(\"watch\\\?v=\", \"i\"), 'v/')," . "\n");
	echo ( "			'type'			: 'swf'," . "\n");
	echo ( "			'swf'			: {" . "\n");
	echo ( "			   	 'wmode'		: 'transparent'," . "\n");
	echo ( "				'allowfullscreen'	: 'false'" . "\n");
	echo ( "			}" . "\n");
	echo ( "		});" . "\n");
	echo ( "		return false;" . "\n");
	echo ( "	});" . "\n");
	echo ("\n");
	echo ( '});' . "\n");
	echo ("\n");
	echo ( '</script>' . "\n");
}

if ( (!is_admin()) || ( WEBPHYSIOLOGY_PORTFOLIO_WP_PAGE == 'edit.php' ) ) {
	add_action('init', 'jquery_lightbox_init');
	add_action('init', 'jquery_lightbox_styles');
}
if ( !is_admin() ) {
	add_action('wp_head', 'fancy_script');
}
if ( WEBPHYSIOLOGY_PORTFOLIO_WP_PAGE == 'edit.php' ) {
	add_action('admin_head', 'fancy_script');
}


/* Build out the navigation elements for paging through the Portfolio pages */
function nav_pages($qryloop, $pageurl, $class) {
	
	global $for;
	global $portfolio_output;
	global $navcontrol;
	
	// get total number of pages in the query results
	$pages = $qryloop->max_num_pages;
	
	// if there is more than one page of Portfolio query results
	if ($pages > 1) {
		
		// if this is the bottom nav then there is no point in rebuilding everything, just take what we
		// built for the top nav and put it in the bottom nav <div>
		if ( ($class == "bottom") && ( !empty($navcontrol) ) ) {
			$portfolio_output .= '<div class="portfolio_nav ' . $class . '">' . $navcontrol . '</div>';
			$navcontrol = array();
			return $portfolio_output;
		}
		
		// if the user is not using pretty permalinks, then the nav page reference is a second parameter
		// 1.1.5 - also building out a full URL to the particular page
		if ( strpos($pageurl, "?page_id=") > 0 ) {
			$paged = $pageurl . "&paged=";
		} else {
			$paged = $pageurl . "?paged=";
		}		
		
		// get current page number
		intval(get_query_var('paged')) == 0 ? $curpage=1 : $curpage = intval(get_query_var('paged'));
		
		// determine the starting page number of the nav control
		
		// figure out where to start and end the nav control numbering as well as what arrow elements we need on each end, if any
		$start = $curpage - round(($for/2),0) + 1;
		if ( ($start + $for) > $pages ) { $start = $pages - $for + 1; }
		if ($start < 1) { $start = 1; }
		if ( ($start + $for) > $pages ) { $for = $pages - $start + 1; }
		$before = 0;
		if ($start > 2) {
			$before = 2;
		} elseif ($start > 1) {
			$before = 1;
		}
		$after = $pages - ($start + $for - 1);
		if ($after > 2) {
			$after = 2;
		} elseif ( $after < 0) {
			$after = 0;
		}		
		
		// now build out the navigation page control elements
		$nav = '<ul>';
		if ($before == 1) {
			$nav .= '<li><a href="' . $paged . ($start - 1) . '">&lt;</a></li>';
		} elseif ($before == 2) {
			$nav .= '<li><a href="' . $paged . '1">&laquo;</a></li>';
			$nav .= '<li><a href="' . $paged . ($start - 1) . '">&lt;</a></li>';
		}
		for ($i=$start;$i<=($start+$for-1);$i++) {
			if ($curpage!=$i) {
				$nav .= '<li><a href="' . $paged . $i .'"';
			} else {
				$nav .= '<li class="selected"><a href="' . $paged . $i .'" class="selected"';
			}
			$nav .= '>' . $i . '</a></li>';
		}
		if ($after == 1) {
			$nav .= '<li><a href="' . $paged . ($start + $for) . '">&gt;</a></li>';
		} elseif ($after == 2) {
			$nav .= '<li><a href="' . $paged . ($start + $for) . '">&gt;</a></li>';
			$nav .= '<li><a href="' . $paged . $pages . '">&raquo;</a></li>';
		}
		$nav .= '</ul>';
		
		$portfolio_output .= '<div class="portfolio_nav ' . $class . '">' . $nav . '</div>';
		
		if ($class == "top") {
			$navcontrol = $nav;
		}
		
	}
	
	return $portfolio_output;
}

/*
// RESERVED FOR POTENTIAL FUTURE USE - there is no single Portfolio page at this time
function use_portfolio_template() {
	
	if ( !( is_page('Portfolio') ) && (( !(get_post_type() == 'webphys_portfolio') || is_404() )))  return;
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
	
	$orig_src = "";
	
	// if the image file is on the current server, grab the path as we'll be setting it back to this if all is good

	if (strpos(strtoupper($src),strtoupper($_SERVER['HTTP_HOST'])) > 0) {
		$orig_src = $src;
	}
	
	$host = str_replace ('www.', '', $_SERVER['HTTP_HOST']);
	$regex = "/^((ht|f)tp(s|):\/\/)(www\.|)" . $host . "/i";
	$src = preg_replace ($regex, '', $src);
	$src = strip_tags ($src);
	$src = check_external ($src);
	
	if ( empty($src) ) {return $src;}
	
    // remove slash from start of string
    if (strpos ($src, '/') === 0) {
        $src = substr ($src, -(strlen ($src) - 1));
    }
	
    // don't allow users the ability to use '../'
    // in order to gain access to files below document root
    $src = preg_replace ("/\.\.+\//", "", $src);
	
	// get path to image on file system
	if (substr($src,0,4) != 'temp') {
    	$src = get_document_root ($src) . '/' . $src;
	}
	
	if ( !empty($orig_src) ) {
		if (file_exists($src)) {
			$src = $orig_src;
		} else {
			$src = "";
		}
	}
	
    return $src;

}

/**
 * @param string $src
 * @return string
 */
function check_external ($src) {

	// external domains that are allowed to be displayed on your website
	$allowedSites = explode (",", get_option( 'webphysiology_portfolio_allowed_image_sites' ));
	
	$error = false;
	
    if (preg_match ('/http:\/\//', $src) == true) {

        $url_info = parse_url ($src);

        $isAllowedSite = false;
        foreach ($allowedSites as $site) {
			$site = '/' . addslashes ($site) . '/';
            if (preg_match ($site, $url_info['host']) == true) {
                $isAllowedSite = true;
            }
		}

		if ($isAllowedSite) {
			
			$fileDetails = pathinfo ($src);
			$ext = strtolower ($fileDetails['extension']);

			$filename = md5 ($src);
			$newsrc = 'temp/' . $filename . '.' . $ext;
			
			$local_filepath = dirname ( __FILE__ ) . '/' . $newsrc;
			
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
						display_error ('error reading file ' . $src . ' from remote host: ' . curl_error($ch));
						$error = true;
					}

					curl_close ($ch);
					fclose ($fh);

                } else {

					if (!$img = file_get_contents($src)) {
						display_error('remote file for ' . $src . ' can not be accessed. It is likely that the file permissions are restricted');
						$error = true;
					}

					if (file_put_contents ($local_filepath, $img) == FALSE) {
						display_error ('error writing temporary file');
						$error = true;
					}

				}

				if (!file_exists($local_filepath)) {
					display_error('local file for ' . $src . ' can not be created');
					$error = true;
				}

			}
			
			if ((!$error) && ($newsrc!="")) {
				$src = $newsrc;
			} else {
				$src = "";
			}

		} else {

			display_error('remote host "' . $url_info['host'] . '" not allowed');
			$src = "";

		}

    }

    return $src;

}

function check_temp_dir() {
	
	// define the temp folder's local path
	$tempdir = dirname ( __FILE__ ) . '/temp';
	
	// make sure temp directory exists. if it doesn't, create it
	if (!file_exists ($tempdir)) {
		// give 777 permissions so that developer can overwrite
		// files created by web server user
		mkdir ($tempdir);
		chmod ($tempdir, 0755);
	}
	
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

function display_error ($errorString = '') {

	echo '<pre>' . htmlentities($errorString) . '</pre>';

}



// check that the current environment supports the WEBphysiology Portfolio plugin

function portfolio_requirements_message() {
	
    global $wpdb;
	
	if (empty($portfolio_rqmts_checked)) {
		
		if (empty($top_message_head) && empty($message) && empty($message_head)) {
			
			$is_php_valid = version_compare(phpversion(), '5.0.0', '>');
			$is_mysql_valid = version_compare($wpdb->db_version(), '5.0.0', '>');
			$is_wp_valid = version_compare(get_bloginfo("version"), '3.0.0', '>');
			$meets_requirements = ($is_php_valid && $is_mysql_valid && $is_wp_valid);
			$class = $meets_requirements ? "update-message" : "error";
			
			if ( !$meets_requirements ) {
	
				$top_message_head = "<div class='error' style='margin:5px; padding:3px; text-align:left; width:93%; margin-bottom: 15px;'>";
		
				$message = "Your host setup is not compatible with WEBphysiology Portfolio. The following items must be upgraded:<br/> ";
		
				if(!$is_php_valid){
					$message .= " - <strong>PHP</strong> (Current version: " .  phpversion() . ", Required: 5.0)<br/> ";
				}
		
				if(!$is_mysql_valid){
					$message .= " - <strong>MySql</strong> (Current version: " .  $wpdb->db_version() . ", Required: 5.0)<br/> ";
				}
		
				if(!$is_wp_valid){
					$message .= " - <strong>Wordpress</strong> (Current version: " .  get_bloginfo("version") . ", Required: 3.0)<br/> ";
				}
		
				$message .= "</div>";
				
				echo $top_message_head . $message;
				
			}
		}
	}

}

if ( WEBPHYSIOLOGY_PORTFOLIO_WP_PAGE == "plugins.php" ) {
    add_action('after_plugin_row_webphysiology-portfolio/portfolio-main.php', 'portfolio_requirements_message');
}

function update_database($ver) {
	
	global $wpdb;
	
	if ( $ver = '1.2.4' ) {
		/* update post types from "Portfolio" to "webphys_portfolio" */
		$wpdb->query("UPDATE $wpdb->posts SET post_type = 'webphys_portfolio' WHERE post_type = 'Portfolio'");
		
		$x = str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$settings_link = '<a href="edit.php?post_type=webphys_portfolio&page=' . $x .'" style="background:none;margin:0;padding:0;float:none;width:auto;height:auto;">' . __('Portfolio Options page','Portfolio') . '</a>';
		$msg = 'Please read the important WEBphysiology Portfolio Release Notes available on the ' . $settings_link ;
		
		set_admin_message($msg);
	}
}

function portfolio_version_alert($alert_ver) {
	
	$html = "";
	
	if ( $alert_ver = '1.2.4' ) {
		$html  = '<div id="pluginsettings" class="postbox">' . "\n";
		$html .= '	<h3 class="hndle"><span>Portfolio Release Notes</span></h3>' . "\n";
		$html .= '	<div class="inside" style="padding: 10px 10px;">' . "\n";
		$html .= '		<a class="alert_text" href="#version_release_notes">IMPORTANT Version 1.2.4 release notes</a>' . "\n";

		$html .= '		<div style="display: none;">' . "\n";
		$html .= '		<div id="version_release_notes" name="version_release_notes">' . "\n";
		$html .= '			<h3 style="font-size:1.4em;text-align: center;">WEBphysiology Portfolio Plugin - Version 1.2.4 Release Notifications</h3>' . "\n";
		$html .= '			<p style="font-weight:bold; color:red;text-align:center;">!!! PLEASE NOTE A FEW CHANGES THAT YOU NEED TO BE AWARE OF AS SOME DEPRECATION WILL BE COMING SOON  !!!<br /></p>' . "\n";
		$html .= "			<p>1) To proactively try and avoid future plugin contentions, the shortcode will be changing from [portfolio] to [webphysiology_portfolio].  The later is currently available. The former will go away in the near future, so, update your portoflio pages with the new code.</p>" . "\n";
		$html .= '			<p>2) If you are doing custom CSS work on the portfolio, be aware that back in version 1.2.0 we noted that the standard CSS that comes with the plugin was having the ID "#portfolio" selector changed to the class ".webphysiology_portfolio" selector.  The ID selector will be removed from the CSS in the next release.  You will not be affected if you have not customized any backend CSS.  If you have, just make certain you are not using "#portfolio".</p>' . "\n";
		$html .= '			<p>3) The WEBphysiology Portfolio settings have been moved out from under the Admin "Settings" menu and relabeled. The plugin configuration options are now labeled "Options" and are located under the Portfolio menu block.</p>' . "\n";
		$html .= '			<p style="font-size:1.2em;font-weight:bold;margin-top:10px;">1.2.4 Enhancements to be aware of:</p>' . "\n";
		$html .= '			<p>1) The custom post type has been changed from "Portfolio" to "webphys_portfolio". Reason #1 is that WP v3.1 has disallowed the use of uppercase characters in the custom post type name, which broke the plugin.  "webphys_" also was added to proactively try and avoid any contentions with other plugins and code.  When you upgraded to v1.2.4 of this plugin the Portfolio Post data was automagically updated to the new custom post type value "webphys_portfolio".</p>' . "\n";
		$html .= "			<p>2) Four new shortcode parameters have been added to allow for additional functionality:</p>" . "\n";
		$html .= '			<p style="margin-left:10px;"><span style="font-weight:bold;">id</span> : this string parameter allows you to specify a &lt;div&gt; ID that will wrap the data returned by the shortcode. This will provide the ability to style a given instance of the shortcode differently from another instance.<br />' . "\n";
		$html .= '			      <span style="font-weight:bold;">per_page</span> : this numeric parameter, if specified, will override the Option setting and allow you to, on a particular instance of the shortcode, specify how many portfolio items will be included per page for that instance of the shortcode.<br />' . "\n";
		$html .= '			      <span style="font-weight:bold;">thickbox</span> : this boolean (true/false) parameter will let you override the Option setting, allowing you to open items in a thickbox or direct the click to the specified URL<br />' . "\n";
		$html .= '			      <span style="font-weight:bold;">credit</span> : this boolean (true/false) parameter will let you override the Option setting, allowing you to only display the plugin credit where you want to. specific reason for this parm is to allow you, in instances where you have more than one [webphysiology_portfolio] shortcode on a page, to just display the credit on one instance.</p>' . "\n";
		$html .= "			<p>3) YouTube and Vimeo are now supported within the Fancybox thickbox interface.  If you enter a Portfolio Web Page URL for a video hosted on one of these sites, and you have set the WEBphysiology Portfolio options to display the image in a thickbox, then the video will be displayed in the thickbox as opposed to sending you to Vimeo/Youtube.  The required format for these URLs are as follows:</p>" . "\n";
		$html .= '			<p style="margin-left:10px;"><span style="font-weight:bold;">Youtube</span>:  http://www.youtube.com/watch?v=<span style="font-style:italic;">071KqJu7WVo</span><br />' . "\n";
		$html .= '			      <span style="font-weight:bold;">Vimeo</span>:  http://vimeo.com/<span style="font-style:italic;">16756306</span><br /></p>' . "\n";
		$html .= "			<p>For a complete list of changes refer to the Readme.txt file in the WEBphysiology Portfolio plugin directory.</p>" . "\n";
		$html .= "		</div>" . "\n";
		$html .= "		</div>" . "\n";


		$html .= "	</div>" . "\n";
		$html .= "</div>" . "\n";
	}
	
	return $html;
	
}
?>