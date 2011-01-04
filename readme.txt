=== Plugin Name ===
Contributors: lambje
Donate link: http://webphysiology.com/redir/webphysiology-portfolio/donate/
Tags: portfolio,gallery,posts,post,custom post type,custom taxonomy,webphysiology
Requires at least: 3.0.0
Tested up to: 3.0.4
Stable tag: 1.1.4

Allows for the creation of an expanded-list styled or a grid-style page containing images and supporting detail, perfect for a portfolio presentation.

== Description ==

The WEBphysiology Portfolio plugin was built to provide a clean, current look in situations where an expanded list-style or grid-style portfolio layout is appropriate. The plugin is implemented via a [shortcode] that supports specifying one-or-more portfolio types, all portfolio-types or all but one excluded portfolio type.

The plugin utilizes a Custom Post Type as well as a Custom Taxonomy. It provides a Settings page for specifying some customizable options, like the number of entries to display per page. It also allows one to turn off the provided CSS in place of implementing their own.

The Portfolio entry screen is highly customized to include just the items that make up a Portfolio entry. Attributes that aren’t populated will not be displayed on the end user interface. Attaching an image to a Portfolio entry also has been made relatively painless.

The end user interface can be adjusted using the Portfolio (Admin) Settings values or via your own CSS. It also incorporates the TimThumb.php code in order to scale the images displayed in the portfolio. The benefit here is to decrease the page weight while maintaining an acceptable quality image, plus the fact that you only need to load one image for use in the portfolio thumbnail and expanded view. The end user interface also utilizes FancyBox to present the full-sized image in a litebox when you click on the image or you can go to a URL on an image click.

== Installation ==

This section describes how to install the plugin and get it working.

1. Extract the WEBphysiology Portfolio ZIP file and place the `webphysiology-portfolio` folder into the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the necessary Portfolio Types via it's menu under the Portfolio section
4. Add one or more Portfolios
5. Place the [portfolio] shortcode into the content area of your "portfolio" page

== Frequently Asked Questions ==

= Specifying a 'portfolio_type' in the shortcode is not working. Why? =

Double-check that the code you are specifying is the correct one.  For example, if you've used the same Portfolio Type as a Tag, chances are the slug on your portfolio type had a number appended, even though you didn't type it in when you added the type.

= Where can I get more details on using this plugin? =

More detailed information can be found here: http://webphysiology.com/plugins/webphysiology-portfolio-plugin/.

= How can I get support? =

My intention, at a minimum, is to maintain this plugin such that it is defect free.  For more info visit our <a href="http://webphysiology.com/plugins/webphysiology-portfolio-plugin/">WEBphysiology Portfolio plugin page</a>.  If after reviewing the details here, and perhaps checking out the videos, you still aren't finding the answer you're looking for, use our support system to log a ticket.

= I get a "File Not Found" error when I try and install from WordPress plugin area =

This happens on occasion.  Running the install a second time typically results in a successful install.

= The styling of my Portfolio is not reflecting the changes I made in the Portfolio options.  Why? =

The styling behavior of the WEBphysiology Portfolio can vary from theme to theme.  The reason for this is that the theme's styling can trump the Portfolio's styling depending upon where the styling of one or the other falls within the styling hierarchy.  If this is happening you'll have to adjust your theme's styling to allow for the portfolio's styling to work.

== Screenshots ==

1. Portfolio Page Frontend User Interface
2. Portfolio Post edit screen
3. Portfolio Page edit screen showing [shortcode] implementation
4. Portfolio options screen

== Upgrade Notice ==

= 1.0.0 is the initial release =

== Changelog ==

= 1.1.4 =
* Fixed a bug where the plugin credit could not be turned off.  Oops
= 1.1.3 =
* Added ability to suppress the display of the portfolio title and portfolio description
* Added the ability to display Portfolio items in a grid style
* Cleaned up the Admin interface
* Cleaned up some CSS styling issues
= 1.1.2 =
* Added apply_filters() to data retrieved with get_the_content() as that method does not include this, unlike the standard the_content() method
= 1.1.1 =
* Bug fix - a form tag around the color selector was keeping the Portfolio Settings submit button from firing on Windows machines
= 1.1.0 =
* Added a color picker to the Admin styling area to make color selections quicker
* Added the ability to change the detail data labels and their width
* Added the ability to turn off the display of all detail data items should you want to store the values but not display them
* Added the ability to navigate to the specified "site" URL when you click on the thumbnail as opposed to opening up a larger image in a litebox
* Added the ability to specify a missing image URL as opposed to using the plugin provided image
* Fixed potential issue where embedded STYLE was still being included when NOT using WEBphysiology Portfolio CSS
= 1.0.2 =
* Added support for WEBphysiology 80% opacity within IE
* CSS adjustments
* Updated thumbnail retrieval to change the image URL passed to timthumb to exlude the path up through the wp_content directory
= 1.0.1 =
* Minor adjustments to release (first pluginitis)
= 1.0.0 =
* Initial release.

== Support ==

*** NOTE: If you get a "File Not Found" during installation from the WordPress site, simply run the installation again.

I will do my best to correct any reported defects as soon as I can make time, but please understand that this is side work. That said, I also use this plugin and am keen to ensure it provides the intended functionality. As to requests for enhancements, feel free to make these. I'll do my best to respond to your requests and, for those requests that I feel would benefit the majority of users, I'll get them on the enhancement list. I can't say just how quickly these would be implemented but funding the request would definitely move it up in the queue.
...