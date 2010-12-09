<?php
/**
 * The loop that displays portfolio entries in place of the [portfolio] shortcode.
 *
 * @package WordPress
 * @subpackage webphysiology-portfolio plugin
 * @since webphysiology-portfolio 1.0.0
 */

/*  UPDATES

    1.1.0 - Added the ability to turn off the display of all detail data items should you want to store the values but not display the data to the user
    1.1.2 - Added apply_filters() method to content retrieved with get_the_content() as, unlike the_content() method,
            it does not apply_filters to the retrieved data, which results in any embedded [shortcodes] not being parsed
	
*/

global $loop;
global $wp_query;
global $portfolio_types;
global $portfolio_output;

$display_portfolio_type = get_option( 'webphysiology_portfolio_display_portfolio_type' );
$display_created_on = get_option( 'webphysiology_portfolio_display_createdate' );
$display_clientname = get_option( 'webphysiology_portfolio_display_clientname' );
$display_siteurl = get_option( 'webphysiology_portfolio_display_siteurl' );
$display_tech = get_option( 'webphysiology_portfolio_display_tech' );
$detail_labels = get_option( 'webphysiology_portfolio_display_labels' );
$portfolios_per_page = get_option( 'webphysiology_portfolio_items_per_page' );
$display_credit = get_option( 'webphysiology_portfolio_display_credit' );
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$type_label = $detail_labels["Type"];
$created_label = $detail_labels["Created"];
$client_label = $detail_labels["Client"];
$siteURL_label = $detail_labels["SiteURL"];
$tech_label = $detail_labels["Tech"];

// if the portfolio shortcode had no portfolio types defined
if ( $portfolio_types == '' ) {
	$loop = new WP_Query( array( 'post_type' => 'Portfolio', 'posts_per_page' => $portfolios_per_page, 'orderby' => 'meta_value_num', 'meta_key' => '_sortorder', 'order' => 'ASC', 'paged'=> $paged ) );
} else {
	$wp_query->query_vars['portfoliotype'] = $portfolio_types;
	$loop = new WP_Query( array( 'post_type' => 'Portfolio', 'portfoliotype' => $portfolio_types, 'posts_per_page' => $portfolios_per_page, 'orderby' => 'meta_value_num', 'meta_key' => '_sortorder', 'order' => 'ASC', 'paged'=> $paged ) );
}

// Display page navigation when applicable
nav_pages($loop, "top");

// set odd/even indicator for portfolio background highlighting
$odd = true;

if ( $loop->have_posts() ) : while ( $loop->have_posts() ) : $loop->the_post();

	if ($odd==true) {
		$post_class = 'portfolio_entry odd';
		$odd = false;
	} else {
		$post_class = 'portfolio_entry';
		$odd = true;
	}

	$portfolio_output .= '<div id="post-' . get_the_ID() . '" class="' . implode(" ", get_post_class($post_class)) . '">';
	$portfolio_output .= '    <div class="portfolio_page_img">';
	$portfolio_output .= '    	' . get_Loop_Site_Image();
	$portfolio_output .= '    </div>';

	$description = get_the_content();
	$type = get_post_meta(get_the_ID(), "_portfolio_type", true);
	$portfolio_type = get_term_by( 'slug', $type, 'portfolio_type' );
	if (isset($portfolio_type->name)) {
		$type = $portfolio_type->name;
	} else {
		$type = "";
	}
	$datecreate = get_post_meta(get_the_ID(), "_createdate", true);
	$client = get_post_meta(get_the_ID(), "_clientname", true);
	$technical_details = get_post_meta(get_the_ID(), "_technical_details", true);
	$siteurl = get_post_meta(get_the_ID(), "_siteurl", true);
	$sortorder = get_post_meta(get_the_ID(), "_sortorder", true);

	$portfolio_output .= '	<div class="portfolio_details">';
	$portfolio_output .= '        <div class="portfolio_title">';
	$portfolio_output .= '            ' . get_Loop_Portfolio_Title();
	$portfolio_output .= '        </div><!-- .entry-meta -->';
	
	if(!$description == '') {
		$description = apply_filters('the_content', $description);
		$description = str_replace(']]>', ']]>', $description);
		$portfolio_output .= '            <div class="portfolio_description"><div class="value">' . $description . '</div></div>';
	}
	
	$portfolio_output .= '		<div class="portfolio_meta">';
	
	if ((!$type == '') && ($display_portfolio_type == 'True')) {
		$portfolio_output .= '            <div class="portfolio_type""><div class="key">' . $type_label . ': </div><div class="value">' . $type . '</div></div>';
	}
	
	if ((!$datecreate == '') && ($display_created_on == 'True')) {
		$portfolio_output .= '            <div class="portfolio_datecreate"><div class="key">' . $created_label . ': </div><div class="value">' .$datecreate . '</div></div>';
	}
	
	if ((!$client == '') && ($display_clientname == 'True')) {
		$portfolio_output .= '            <div class="portfolio_client"><div class="key">' . $client_label . ': </div><div class="value">' .$client . '</div></div>';
	}
	
	if ((!$siteurl == '') && ($display_siteurl == 'True')) {
		$portfolio_output .= '            <div class="portfolio_siteurl"><div class="key">' . $siteURL_label . ': </div><div class="value"><a href="' . $siteurl . '">' . $siteurl . '</a></div></div>';
	}
	
	if ((!$technical_details == '') && ($display_tech == 'True')) {
		$portfolio_output .= '            <div class="portfolio_techdetails"><div class="key">' . $tech_label . ': </div><div class="value">' . $technical_details . '</div></div>';
	}
	
	$portfolio_output .= '            ' . wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'webphysiology_portfolio' ), 'after' => '</div>' ) );
	$portfolio_output .= '        </div>';
	$portfolio_output .= '    </div>';
	$portfolio_output .= '</div><!-- #post-## -->';
	
endwhile; else :
	
	$portfolio_output .= '<div id="post-0" class="post error404 not-found">';
    $portfolio_output .= '	<p>&nbsp;</p>';
	$portfolio_output .= '	<div class="entry-content">';
	$portfolio_output .= '		<p>' . __( 'Apologies, but no results were found for the requested portfolio records.', 'webphysiology_portfolio' ) . '</p>';
	$portfolio_output .= '	</div>';
	$portfolio_output .= '</div>';
	
endif;

// Credit link
if($display_credit == 'True') {
	$portfolio_output .= '<div id="portfolio_credit"><em>powered by <a href="http://webphysiology.com/redir/webphysiology-portfolio/">WEBphysiology Portfolio</a></em></div>';
}

// Display page navigation when applicable
nav_pages($loop, "bottom");
?>