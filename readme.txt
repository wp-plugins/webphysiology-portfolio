=== Plugin Name ===
Contributors: lambje
Donate link: http://webphysiology.com/redir/webphysiology-portfolio/donate/
Tags: portfolio,gallery,posts,post,custom post type,custom taxonomy,webphysiology
Requires at least: 3.0.0
Tested up to: 3.0.1
Stable tag: trunk

This plugin allows for the creation of an expanded-list styled page containing images and supporting detail, perfect for a portfolio presentation.

== Description ==

The WEBphysiology Portfolio plugin was built to provide a clean, current look in situations where an expanded list-style portfolio layout is appropriate. The plugin is implemented via a [shortcode] that supports specifying one-or-more portfolio types, all portfolio-types or all but one excluded portfolio type.

The plugin utilizes a Custom Post Type as well as a Custom Taxonomy. It provides a Settings page for specifying some customizable options, like the number of entries to display per page. It also allows one to turn off the provided CSS in place of implementing their own.

The Portfolio entry screen is highly customized to include just the items that make up a Portfolio entry. Attributes that aren’t populated will not be displayed on the end user interface. Attaching an image to a Portfolio entry also has been made relatively painless.

The end user interface can be adjusted somewhat using the Admin Settings values or via your own CSS. It also incorporates the TimThumb.php code in order to scale the images displayed in the list. The benefit here is to decrease the page weight while maintaining an acceptable quality image, plus the fact that you only need to load one image for use in the list thumbnail and expanded view. The end user interface also utilizes FancyBox to present the full-sized image in a lightbox fashion.


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

While my intention is to maintain this plugin such that it is defect free, this bit of work is not my number one priority.

== Screenshots ==

1. Portfolio Post edit screen
2. Portfolio Page edit screen showing [shortcode] implementation
3. Portfolio options screen

== Upgrade Notice ==

= 1.0.0 is the initial release =

== Changelog ==

= 1.0.0 =
* Initial release.

== Support ==

While I am open to input on defects and will try and correct them as soon as I can make time, please understand that this is side work and not my number one priority.  That said, I also use this plugin and am keen to ensure it provides the intended functionality.  As to requests for enhancements, feel free to make these but please don't be offended if I don't respond to your suggestions, of if my responses are delayed.