<?php

require_once(RPP_DIR.'/magic.php');
require_once(RPP_DIR.'/keywords.php');
require_once(RPP_DIR.'/intl.php');
require_once(RPP_DIR.'/services.php');

if ( !defined('WP_CONTENT_URL') )
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

global $rpp_value_options, $rpp_binary_options, $rpp_clear_cache_options;
// here's a list of all the options RPP uses (except version), as well as their default values, sans the rpp_ prefix, split up into binary options and value options. These arrays are used in updating settings (options.php) and other tasks.
$rpp_value_options = array(
	'threshold' => 5,
	'limit' => 5,
	'template_file' => '', // new in 2.2
	'excerpt_length' => 10,
	'recent_number' => 12,
	'recent_units' => 'month',
	'before_title' => '<li>',
	'after_title' => '</li>',
	'before_post' => ' <small>',
	'after_post' => '</small>',
	'before_related' => '<p>'.__('Related posts:','rpp').'</p><ol>',
	'after_related' => '</ol>',
	'no_results' => '<p>'.__('No related posts.','rpp').'</p>',
	'order' => 'score DESC',
	'rss_limit' => 3,
	'rss_template_file' => '', // new in 2.2
	'rss_excerpt_length' => 10,
	'rss_before_title' => '<li>',
	'rss_after_title' => '</li>',
	'rss_before_post' => ' <small>',
	'rss_after_post' => '</small>',
	'rss_before_related' => '<p>'.__('Related posts:','rpp').'</p><ol>',
	'rss_after_related' => '</ol>',
	'rss_no_results' => '<p>'.__('No related posts.','rpp').'</p>',
	'rss_order' => 'score DESC',
	'title' => '2',
	'body' => '2',
	'categories' => '1', // changed default in 3.3
	'tags' => '2',
	'distags' => '',
	'discats' => '');
$rpp_binary_options = array(
	'past_only' => true,
	'show_excerpt' => false,
	'recent_only' => false, // new in 3.0
	'use_template' => false, // new in 2.2
	'rss_show_excerpt' => false,
	'rss_use_template' => false, // new in 2.2
	'show_pass_post' => false,
	'cross_relate' => false,
	'auto_display' => true,
	'rss_display' => false, // changed default in 3.1.7
	'rss_excerpt_display' => true,
	'promote_rpp' => false,
	'rss_promote_rpp' => false);
// These are options which, when updated, will trigger a clearing of the cache
$rpp_clear_cache_options = array(
	'distags','discats','show_pass_post','recent_only','threshold','title','body','categories',
	'tags');

function rpp_enabled() {
	global $wpdb, $rpp_cache;
	if ($rpp_cache->is_enabled() === false)
		return false;
	$indexdata = $wpdb->get_results("show index from $wpdb->posts");
	foreach ($indexdata as $index) {
		if ($index->Key_name == 'rpp_title')
			return true;
	}
	return false;
}

function rpp_activate() {
	global $rpp_version, $wpdb, $rpp_binary_options, $rpp_value_options, $rpp_cache;
	foreach (array_keys($rpp_value_options) as $option) {
		if (get_option("rpp_$option") === false)
			add_option("rpp_$option",$rpp_value_options[$option] . ' ');
	}
	foreach (array_keys($rpp_binary_options) as $option) {
		if (get_option("rpp_$option") === false)
			add_option("rpp_$option",$rpp_binary_options[$option]);
	}

	$wpdb->get_results("show index from $wpdb->posts where Key_name='rpp_title'");
	if (!$wpdb->num_rows)
		$wpdb->query("ALTER TABLE $wpdb->posts ADD FULLTEXT `rpp_title` ( `post_title` )");

	$wpdb->get_results("show index from $wpdb->posts where Key_name='rpp_content'");
	if (!$wpdb->num_rows)
		$wpdb->query("ALTER TABLE $wpdb->posts ADD FULLTEXT `rpp_content` ( `post_content` )");
	
	if (!rpp_enabled()) {
		// If we are still not enabled, run the cache abstraction's setup method.
		$rpp_cache->setup();
		// If we're still not enabled, give up.
		if (!rpp_enabled())
			return 0;
	}
	
	if (!get_option('rpp_version')) {
		add_option('rpp_version',RPP_VERSION);	
		rpp_version_info(true);
	} else {
		rpp_upgrade_check();
	}

	return 1;
}

function rpp_myisam_check() {
	global $wpdb;
	$tables = $wpdb->get_results("show table status like '{$wpdb->posts}'");
	foreach ($tables as $table) {
		if ($table->Engine == 'MyISAM') return true;
		else return $table->Engine;
	}
	return 'UNKNOWN';
}

function rpp_upgrade_check() {
	$last_version = get_option('rpp_version');
	if (version_compare(RPP_VERSION, $last_version) === 0)
		return;

	global $rpp_value_options, $rpp_binary_options, $rpp_cache;

	foreach (array_keys($rpp_value_options) as $option) {
		if (get_option("rpp_$option") === false)
			add_option("rpp_$option",$rpp_value_options[$option].' ');
	}
	foreach (array_keys($rpp_binary_options) as $option) {
		if (get_option("rpp_$option") === false)
			add_option("rpp_$option",$rpp_binary_options[$option]);
	}

	$rpp_cache->upgrade($last_version);

	rpp_version_info(true);

	update_option('rpp_version',RPP_VERSION);
}

function rpp_admin_menu() {
	$hook = add_options_page(__('Related Posts (RPP)','rpp'),__('Related Posts (RPP)','rpp'), 'manage_options', 'rpp', 'rpp_options_page');
	add_action("load-$hook",'rpp_load_thickbox');
	// new in 3.3: load options page sections as metaboxes
	include('options-meta-boxes.php');
	// new in 3.0.12: add settings link to the plugins page
	add_filter('plugin_action_links', 'rpp_settings_link', 10, 2);
}

// since 3.3
function rpp_admin_enqueue() {
	global $current_screen;
	if (is_object($current_screen) && $current_screen->id == 'settings_page_rpp') {
		wp_enqueue_script( 'postbox' );
		wp_enqueue_style( 'rpp_options', plugins_url( 'options.css', __FILE__ ), array(), RPP_VERSION );
	}
}

function rpp_settings_link($links, $file) {
  $this_plugin = dirname(plugin_basename(__FILE__)) . '/rpp.php';
  if($file == $this_plugin) {
    $links[] = '<a href="options-general.php?page=rpp">' . __('Settings', 'rpp') . '</a>';
  }
  return $links;
}

function rpp_load_thickbox() {
	wp_enqueue_script( 'thickbox' );
	if (function_exists('wp_enqueue_style')) {
		wp_enqueue_style( 'thickbox' );
	}
}

function rpp_options_page() {
	// for proper metabox support:
	require(RPP_DIR.'/options.php');
}

function widget_rpp_init() {
  register_widget('RPP_Widget');
}

// vaguely based on code by MK Safi
// http://msafi.com/fix-related-posts-plugin-rpp-widget-and-add-it-to-the-sidebar/
class RPP_Widget extends WP_Widget {
  function RPP_Widget() {
    parent::WP_Widget(false, $name = __('Related Posts (RPP)','rpp'));
  }

  function widget($args, $instance) {
  	global $post;
    if (!is_singular())
      return;

    extract($args);

		$type = ($post->post_type == 'page' ? array('page') : array('post'));
		if (rpp_get_option('cross_relate'))
			$type = array('post','page');

    $title = apply_filters('widget_title', $instance['title']);
    echo $before_widget;
		if ( !$instance['use_template'] ) {
			echo $before_title;
			if ($title)
				echo $title;
			else
				_e('Related Posts (RPP)','rpp');
			echo $after_title;
    }
		echo rpp_related($type,$instance,false,false,'widget');
    echo $after_widget;
  }

  function update($new_instance, $old_instance) {
		// this starts with default values.
		$instance = array( 'promote_rpp' => 0, 'use_template' => 0 );
		foreach ( $instance as $field => $val ) {
			if ( isset($new_instance[$field]) )
				$instance[$field] = 1;
		}
		if ($instance['use_template']) {
			$instance['template_file'] = $new_instance['template_file'];
			$instance['title'] = $old_instance['title'];
		} else {
			$instance['template_file'] = $old_instance['template_file'];
			$instance['title'] = $new_instance['title'];
		}
    return $instance;
  }

  function form($instance) {
    $title = esc_attr($instance['title']);
    $template_file = $instance['template_file'];
    ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

			<?php // if there are RPP templates installed...
				if (count(glob(STYLESHEETPATH . '/rpp-template-*.php'))): ?>

				<p><input class="checkbox" id="<?php echo $this->get_field_id('use_template'); ?>" name="<?php echo $this->get_field_name('use_template'); ?>" type="checkbox" <?php checked($instance['use_template'], true) ?> /> <label for="<?php echo $this->get_field_id('use_template'); ?>"><?php _e("Display using a custom template file",'rpp');?></label></p>
				<p id="<?php echo $this->get_field_id('template_file_p'); ?>"><label for="<?php echo $this->get_field_id('template_file'); ?>"><?php _e("Template file:",'rpp');?></label> <select name="<?php echo $this->get_field_name('template_file'); ?>" id="<?php echo $this->get_field_id('template_file'); ?>">
					<?php foreach (glob(STYLESHEETPATH . '/rpp-template-*.php') as $template): ?>
					<option value='<?php echo htmlspecialchars(basename($template))?>'<?php echo (basename($template)==$template_file)?" selected='selected'":'';?>><?php echo htmlspecialchars(basename($template))?></option>
					<?php endforeach; ?>
				</select><p>

			<?php endif; ?>

        <p><input class="checkbox" id="<?php echo $this->get_field_id('promote_rpp'); ?>" name="<?php echo $this->get_field_name('promote_rpp'); ?>" type="checkbox" <?php checked($instance['images'], true) ?> /> <label for="<?php echo $this->get_field_id('promote_rpp'); ?>"><?php _e("Help promote Related Posts Plugin?",'rpp'); ?></label></p>

				<script type="text/javascript">
				jQuery(function() {
					function ensureTemplateChoice() {
						if (jQuery('#<?php echo $this->get_field_id('use_template'); ?>').attr('checked')) {
							jQuery('#<?php echo $this->get_field_id('title'); ?>').attr('disabled',true);
							jQuery('#<?php echo $this->get_field_id('template_file_p'); ?>').show();
						} else {
							jQuery('#<?php echo $this->get_field_id('title'); ?>').attr('disabled',false);
							jQuery('#<?php echo $this->get_field_id('template_file_p'); ?>').hide();
						}
					}
					jQuery('#<?php echo $this->get_field_id('use_template'); ?>').change(ensureTemplateChoice);
					ensureTemplateChoice();
				});
				</script>

    <?php
  }
}


function rpp_default($content) {
	global $wpdb, $post;

	if (is_feed())
		return rpp_rss($content);

	$type = ($post->post_type == 'page' ? array('page') : array('post'));
	if (rpp_get_option('cross_relate'))
		$type = array('post','page');

	if (rpp_get_option('auto_display') and is_single())
		return $content . rpp_related($type,array(),false,false,'website');
	else
		return $content;
}

function rpp_rss($content) {
	global $wpdb, $post;

	$type = ($post->post_type == 'page' ? array('page') : array('post'));
	if (rpp_get_option('cross_relate'))
		$type = array('post','page');

	if (rpp_get_option('rss_display'))
		return $content.rpp_related($type,array(),false,false,'rss');
	else
		return $content;
}

function rpp_rss_excerpt($content) {
	global $wpdb, $post;

	$type = ($post->post_type == 'page' ? array('page') : array('post'));
	if (rpp_get_option('cross_relate'))
		$type = array('post','page');

	if (rpp_get_option('rss_excerpt_display') && rpp_get_option('rss_display'))
		return $content.clean_pre(rpp_related($type,array(),false,false,'rss'));
	else
		return $content;
}

// Used only in demo mode
if (!defined('LOREMIPSUM'))
	define('LOREMIPSUM','Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Cras tincidunt justo a urna. Ut turpis. Phasellus convallis, odio sit amet cursus convallis, eros orci scelerisque velit, ut sodales neque nisl at ante. Suspendisse metus. Curabitur auctor pede quis mi. Pellentesque lorem justo, condimentum ac, dapibus sit amet, ornare et, erat. Quisque velit. Etiam sodales dui feugiat neque suscipit bibendum. Integer mattis. Nullam et ante non sem commodo malesuada. Pellentesque ultrices fermentum lectus. Maecenas hendrerit neque ac est. Fusce tortor mi, tristique sed, cursus at, pellentesque non, dui. Suspendisse potenti.');

function rpp_excerpt($content,$length) {
  $content = strip_tags( (string) $content );
	preg_replace('/([,;.-]+)\s*/','\1 ',$content);
	return implode(' ',array_slice(preg_split('/\s+/',$content),0,$length)).'...';
}

function rpp_set_option($option,$value) {
	global $rpp_value_options, $rpp_clear_cache_options, $rpp_cache;
	if (array_search($option,array_keys($rpp_value_options)) !== false)
		update_option("rpp_$option",$value.' ');
	else
		update_option("rpp_$option",$value);
	// new in 3.1: clear cache when updating certain settings.
	if (array_search($option,$rpp_clear_cache_options) !== false)
		$rpp_cache->flush();
}

function rpp_get_option($option,$escapehtml = false) {
	global $rpp_value_options;
	if (array_search($option,array_keys($rpp_value_options)) !== false)
		$return = chop(get_option("rpp_$option"));
	else
		$return = get_option("rpp_$option");
	if ($escapehtml)
		$return = htmlspecialchars(stripslashes($return));
	return $return;
}

function rpp_microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// new in 3.3: use PHP serialized format instead of JSON
function rpp_version_info($enforce_cache = false) {
	if (false === ($result = get_transient('rpp_version_info')) || $enforce_cache) {
		$version = RPP_VERSION;
		$remote = wp_remote_post("http://www.escalateseo.com/code/rpp/checkversion.php?format=php&version={$version}");
		
		if (is_wp_error($remote))
			return false;
		
		$result = unserialize($remote['body']);
		set_transient('rpp_version_info', $result, 60*60*12);
	}
	return $result;
}

function rpp_add_metabox() {
	if (function_exists('add_meta_box')) {
    add_meta_box( 'rpp_relatedposts', __( 'Related Posts' , 'rpp'), 'rpp_metabox', 'post', 'normal' );
	}
}
function rpp_metabox() {
	global $post;
	echo '<div id="rpp-related-posts">';
	if ($post->ID)
		rpp_related(array('post'),array('limit'=>1000),true,false,'metabox');
	else
		echo "<p>".__("Related entries may be displayed once you save your entry",'rpp').".</p>";
	echo '</div>';
}

// since 3.3: default metaboxes to show:
function rpp_default_hidden_meta_boxes($hidden, $screen) {
	if ( 'settings_page_rpp' == $screen->id )
		$hidden = array( 'rpp_pool', 'rpp_relatedness' );
	return $hidden;
}

// since 3.3.2: fix for WP 3.0.x
if ( !function_exists( 'self_admin_url' ) ) {
	function self_admin_url($path = '', $scheme = 'admin') {
		if ( defined( 'WP_NETWORK_ADMIN' ) && WP_NETWORK_ADMIN )
			return network_admin_url($path, $scheme);
		elseif ( defined( 'WP_USER_ADMIN' ) && WP_USER_ADMIN )
			return user_admin_url($path, $scheme);
		else
			return admin_url($path, $scheme);
	}
}
