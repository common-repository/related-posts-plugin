<?php
/*
Plugin Name: Related Posts Plugin
Plugin URI: http://www.escalateseo.com
Description: Returns a list of related entries based on a unique algorithm for display on your blog and RSS feeds. A templating feature allows customization of the display.
Version: 1.2.0
Author: EscalateSEO
Author URI: http://www.escalateseo.com
*/

// set $rpp_debug
if (isset($_REQUEST['rpp_debug']))
  $rpp_debug = true;

define('RPP_VERSION','1.2.0');
define('RPP_DIR',dirname(__FILE__));

require_once(RPP_DIR.'/includes.php');
require_once(RPP_DIR.'/related-functions.php');
require_once(RPP_DIR.'/template-functions.php');

// New in 3.2: load RPP cache engine
// By default, this is tables, which uses custom db tables.
// Use postmeta instead and avoid custom tables by adding the following to wp-config:
//   define('RPP_CACHE_TYPE', 'postmeta');
if (!defined('RPP_CACHE_TYPE'))
	define('RPP_CACHE_TYPE', 'tables');
global $rpp_cache, $rpp_storage_class;
require_once(RPP_DIR . '/cache-' . RPP_CACHE_TYPE . '.php');
// For PHP 4, we have to pass this object by reference:
$GLOBALS['rpp_cache'] =& new $rpp_storage_class;

register_activation_hook(__FILE__,'rpp_activate');
load_plugin_textdomain('rpp', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)).'/lang',dirname(plugin_basename(__FILE__)).'/lang');

// setup admin
add_action('admin_menu','rpp_admin_menu');
// new in 3.3: properly enqueue scripts for admin:
add_action( 'admin_enqueue_scripts', 'rpp_admin_enqueue' );
// new in 3.3: set default meta boxes to show:
add_filter( 'default_hidden_meta_boxes', 'rpp_default_hidden_meta_boxes', 10, 2 );

// automatic display hooks:
add_filter('the_content','rpp_default',1200);
add_filter('the_content_rss','rpp_rss',600);
add_filter('the_excerpt_rss','rpp_rss_excerpt',600);

// new in 2.0: add as a widget
add_action('widgets_init', 'widget_rpp_init');
// new in 3.0: add meta box
add_action( 'admin_menu', 'rpp_add_metabox');

// update cache on save
add_action('save_post','rpp_save_cache');

// new in 3.2: update cache on delete
add_action('delete_post','rpp_delete_cache');
// new in 3.2.1: handle post_status transitions
add_action('transition_post_status','rpp_status_transition', 10, 3);

// sets the score override flag.
add_action('parse_query','rpp_set_score_override_flag');

// new in 3.3: include BlogGlue meta box
if ( file_exists( RPP_DIR . '/blogglue.php' ) )
	include_once( RPP_DIR . '/blogglue.php' );

if (isset($relateit)) return false;

require_once(dirname(__FILE__) . '/rpp.class.php');

$relateit = new RelatedWP();

add_action('wp_footer', array($relateit, 'wp_footer'));