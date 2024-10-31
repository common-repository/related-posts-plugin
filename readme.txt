=== Related Posts Plugin ===
Contributors: mitchoyoshitaka
Author: EscalateSEO
Author URI: http://www.escalateseo.com
Plugin URI: http://www.escalateseo.com
Tags: related, posts, post, pages, page, RSS, feed, feeds
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.2.0

Display a list of related entries on your site and feeds based on a unique algorithm. Templating allows customization of the display.

== Description ==

Related Posts Plugin (RPP) gives you a list of posts and/or pages related to the current entry, introducing the reader to other relevant content on your site. Key features include:

1. **An advanced and versatile algorithm**: Using a customizable algorithm considering post titles, content, tags, and categories, RPP calculates a "match score" for each pair of posts on your blog. You choose the threshold limit for relevance and you get more related posts if there are more related posts and less if there are less.
2. **Templating**: **New in 3.0!** The [RPP templating system] puts you in charge of how your posts are displayed.
3. **Caching**: **Improved in 3.2!** RPP organically caches the related posts data as your site is visited, greatly improving performance.
4. **Related posts in RSS feeds**: Display related posts in your RSS and Atom feeds with custom display options.
5. **Disallowing certain tags or categories**: You can choose certain tags or categories as disallowed, meaning any page or post with such tags or categories will not be served up by the plugin.
6. **Related posts and pages**: Puts you in control of pulling up related posts, pages, or both.

This plugin requires PHP 5 and MySQL 4.1 or greater.

== Installation ==

= Auto display on your website =

1. Copy the folder `related-posts-plugin` into the directory `wp-content/plugins/` and (optionally) the sample templates inside `rpp-templates` folder into your active theme.

2. Activate the plugin.

= Auto display in your feeds =

Make sure the "display related posts in feeds" option is turned on if you would like to show related posts in your RSS and Atom feeds. The "display related posts in feeds" option can be used regardless of whether you auto display them on your website (and vice versa).

= Widget =

Related posts can also be displayed as a widget. Go to the Design > Widgets options page and add the Related Posts widget. The widget will only be displayed on single entry (permalink) pages. The widget can be used even if the "auto display" option is turned off.

= Custom display through templates =

New in version 3.0, RPP allows the advanced user with knowledge of PHP to customize the display of related posts using a templating mechanism. More information is available [in this tutorial](http://www.escalateseo.comblog/projects/rpp-3-templates/).

= Manual installation =

For advanced users with knowledge of PHP, there is also an [advanced manual installation option](http://www.escalateseo.com/code/rpp/manual-installation/).

== Frequently Asked Questions ==

If your question isn't here, ask your own question at the forums.

= How can I move the related posts display? =

If you do not want to show the Related Posts display in its default position (right below the post content), first go to RPP options and turn off the "automatically display" option in the "website" section. If you would like to instead display it in your sidebar and you have a widget-aware theme, RPP provides a Related Posts widget which you can add under "Appearance" > "Widgets".

If you would like to add the Related Posts display elsewhere, follow these directions: (*Knowledge of PHP and familiarity with editing your WordPress theme files is required.*)

Edit your relevant theme file (most likely something like `single.php`) and add the PHP code `related_posts();` within [The Loop](http://codex.wordpress.org/The_Loop) where you want to display the related posts.

This method can also be used to display RPP on pages other than single-post displays, such as on archive pages. There is a little more information on the [advanced manual installation page](http://www.escalateseo.com/).

= Does RPP slow down my blog/server? =

A little bit, yes. However, RPP 3.0 introduced a new caching mechanism which greatly reduces the hit of the computationally intensive relatedness computation. In addition, *I highly recommend all RPP users use a page-caching plugin, such as [WP-SuperCache](http://ocaoimh.ie/wp-super-cache/).*

If you find that the RPP database calls are still too database-intensive, try the following:

* turning off "cross relate posts and pages";
* turning on "show only previous posts";
* not considering tags and/or categories in the Relatedness formula;
* not excluding any tags and/or categories in The Pool.

All of these can improve database performance.

If you are in the process of looking for a hosting provider whose databases will not balk under RPP, I personally have had great success with [MediaTemple](http://www.mediatemple.net/go/order/?refdom=mitcho.com).

= Every page just says "no related posts"! What's up with that? =

Most likely you have "no related posts" right now as the default "match threshold" is too high. Here's what I recommend to find an appropriate match threshold: first, lower your match threshold in the RPP prefs to something very low, like 1. Most likely the really low threshold will pull up many posts that aren't actually related (false positives), so look at some of your posts' related posts and their match scores. This will help you find an appropriate threshold. You want it lower than what you have now, but high enough so it doesn't have many false positives.

= How do I turn off the match score next to the related posts? =

The match score display is only for administrators... you can log out of `wp-admin` and check out the post again and you will see that the score is gone.

If you would like more flexibility in changing the display of your related posts, please see the [templating tutorial](http://www.escalateseo.comblog/projects/rpp-3-templates/).

= I use DISQUS for comments. I can't access the RPP options page! =

The DISQUS plugin loads some JavaScript voodoo which is interacting in weird ways with the AJAX in RPP's options page. You can fix this by going to the DISQUS plugin advanced settings and turning on the "Check this if you have a problem with comment counts not showing on permalinks" option.

= I use DISQUS for comments. My RSS feed is now invalid and cannot be parsed by some clients! =

The DISQUS plugin loads some JavaScript voodoo when related posts are displayed, even in the RSS feed. You can fix this by going to the DISQUS plugin advanced settings and turning on the "Check this if you have a problem with comment counts not showing on permalinks" option.

= I get a PHP error saying "Cannot redeclare `related_posts()`" =

You most likely have another related posts plugin activated at the same time. Please disactivate those other plugins first before using RPP.

= I turned off one of the relatedness criteria (titles, bodies, tags, or categories) and now every page says "no related posts"! =

This has to do with the way the "match score" is computed. Every entry's match score is the weighted sum of its title-score, body-score, tag-score, and category-score. If you turn off one of the relatedness criteria, you will no doubt have to lower your match threshold to get the same number of related entries to show up. Alternatively, you can consider one of the other criteria "with extra weight".

It is recommended that you tweak your match threshold whenever you make changes to the "makeup" of your match score (i.e., the settings for the titles, bodies, tags, and categories items).

= Are there any plugins that are incompatible with RPP? =

Aside from the DISQUS plugin (see above), currently the only known incompatibility is [with the SEO_Pager plugin](http://wordpress.org/support/topic/267966) and the [Pagebar 2](http://www.elektroelch.de/hacks/wp/pagebar/) plugin. Users of SEO Pager are urged to turn off the automatic display option in SEO Pager and instead add the code manually. There are reports that the [WP Contact Form III plugin and Contact Form Plugin](http://wordpress.org/support/topic/392605) may also be incompatible with RPP. Other related posts plugins, obviously, may also be incompatible.

Please submit similar bugs by starting a new thread on [the Wordpress.org forums](http://wordpress.org).

= Does RPP work with full-width characters or languages that don't use spaces between words? =

RPP works fine with full-width (double-byte) characters, assuming your WordPress database is set up with Unicode support. 99% of the time, if you're able to write blog posts with full-width characters and they're displayed correctly, RPP will work on your blog.

However, RPP does have difficulty with languages that don't place spaces between words (Chinese, Japanese, etc.). For these languages, the "consider body" and "consider titles" options in the "Relatedness options" may not be very helpful. Using only tags and categories may work better for these languages.

= Things are weird after I upgraded. =

I highly recommend you disactivate RPP, replace it with the new one, and then reactivate it.

= Can I clear my cache? =

Yes, you can clear the cache by going to your RPP settings page ("Related Posts (RPP)") in your admin interface, and adding `&action=flush` to the URL and reloading the page. RPP will begin the process of organically rebuilding your cache.