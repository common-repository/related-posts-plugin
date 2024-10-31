<?php

global $wpdb, $rpp_value_options, $rpp_binary_options, $wp_version, $rpp_cache, $rpp_templateable, $rpp_myisam;

// Reenforce RPP setup:
if (!get_option('rpp_version'))
  rpp_activate();
else
  rpp_upgrade_check();

// if action=flush, reset the cache
if (isset($_GET['action']) && $_GET['action'] == 'flush') {
  $rpp_cache->flush();
}

// check to see that templates are in the right place
$rpp_templateable = (count(glob(STYLESHEETPATH . '/rpp-template-*.php')) > 0);
if (!$rpp_templateable) {
  rpp_set_option('use_template',false);
  rpp_set_option('rss_use_template',false);
}

// 3.3: move version checking here, in PHP:
if ( current_user_can('update_plugins' ) ) {
	$rpp_version_info = rpp_version_info();
	
	// these strings are not localizable, as long as the plugin data on wordpress.org
	// cannot be.
	$slug = 'related-posts-plugin';
	$plugin_name = 'Related Posts Plugin';
	$file = basename(RPP_DIR) . '/rpp.php';
	if ( $rpp_version_info['result'] == 'new' ) {
		// make sure the update system is aware of this version
		$current = get_site_transient( 'update_plugins' );
		if ( !isset( $current->response[ $file ] ) ) {
			delete_site_transient( 'update_plugins' );
			wp_update_plugins();
		}
	
		echo '<div class="updated"><p>';
		$details_url = self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $slug . '&TB_iframe=true&width=600&height=800');
		printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">update automatically</a>.'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $rpp_version_info['current']['version'], wp_nonce_url( self_admin_url('update.php?action=upgrade-plugin&plugin=') . $file, 'upgrade-plugin_' . $file) );
		echo '</p></div>';
	} else if ( $rpp_version_info['result'] == 'newbeta' ) {
		echo '<div class="updated"><p>';
		printf(__("There is a new beta (%s) of Related Posts Plugin. You can <a href=\"%s\">download it here</a> at your own risk.","rpp"), $rpp_version_info['beta']['version'], $rpp_version_info['beta']['url']);
		echo '</p></div>';
	}
}

if (isset($_POST['myisam_override'])) {
	rpp_set_option('myisam_override',1);
	echo "<div class='updated'>"
	.__("The MyISAM check has been overridden. You may now use the \"consider titles\" and \"consider bodies\" relatedness criteria.",'rpp')
	."</div>";
}

$rpp_myisam = true;
if (!rpp_get_option('myisam_override')) {
	$rpp_check_return = rpp_myisam_check();
	if ($rpp_check_return !== true) { // if it's not *exactly* true
		echo "<div class='updated'>"
		.sprintf(__("RPP's \"consider titles\" and \"consider bodies\" relatedness criteria require your <code>%s</code> table to use the <a href='http://dev.mysql.com/doc/refman/5.0/en/storage-engines.html'>MyISAM storage engine</a>, but the table seems to be using the <code>%s</code> engine. These two options have been disabled.",'rpp'),$wpdb->posts,$rpp_check_return)
		."<br />"
		.sprintf(__("To restore these features, please update your <code>%s</code> table by executing the following SQL directive: <code>ALTER TABLE `%s` ENGINE = MyISAM;</code> . No data will be erased by altering the table's engine, although there are performance implications.",'rpp'),$wpdb->posts,$wpdb->posts)
		."<br />"
		.sprintf(__("If, despite this check, you are sure that <code>%s</code> is using the MyISAM engine, press this magic button:",'rpp'),$wpdb->posts)
		."<br />"
		."<form method='post'><input type='submit' class='button' name='myisam_override' value='"
		.__("Trust me. Let me use MyISAM features.",'rpp')
		."'></input></form>"
		."</div>";

		rpp_set_option('title',1);
		rpp_set_option('body',1);
		$rpp_myisam = false;
	}
}

if ($rpp_myisam && !rpp_enabled()) {
  echo '<div class="updated"><p>';
  if (rpp_activate()) {
    _e('The RPP database had an error but has been fixed.','rpp');
  } else {
    _e('The RPP database has an error which could not be fixed.','rpp');
    printf(__('Please try <a href="%s" target="_blank">manual SQL setup</a>.','rpp'), 'http://www.escalateseo.com/code/rpp/sql.php?prefix='.urlencode($wpdb->prefix));
  }
  echo '</div></p>';
}

if (isset($_POST['update_rpp'])) {
	foreach (array_keys($rpp_value_options) as $option) {
    if (isset($_POST[$option]) && is_string($_POST[$option]))
      rpp_set_option($option,addslashes($_POST[$option]));
	}
	foreach (array('title','body','tags','categories') as $key) {
		if (!isset($_POST[$key])) rpp_set_option($key,1);
	}
	if (isset($_POST['discats'])) {
		rpp_set_option('discats',implode(',',array_keys($_POST['discats']))); // discats is different
	} else {
		rpp_set_option('discats','');
	}

	if (isset($_POST['distags'])) {
		rpp_set_option('distags',implode(',',array_keys($_POST['distags']))); // distags is also different
	} else {
		rpp_set_option('distags','');
	}
	
	foreach (array_keys($rpp_binary_options) as $option) {
		(isset($_POST[$option])) ? rpp_set_option($option,1) : rpp_set_option($option,0);
	}
	echo '<div class="updated fade"><p>'.__('Options saved!','rpp').'</p></div>';
}

?>
<script type="text/javascript">
//<!--

// since 3.3: add screen option toggles
jQuery(function() {
	postboxes.add_postbox_toggles(pagenow);
});

var spinner = '<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>';

function load_display_demo_web() {
	jQuery.ajax({type:'POST',
	  url: ajaxurl,
	  data:'action=rpp_display_demo_web',
	  beforeSend:function(){jQuery('#display_demo_web').eq(0).html('<img src="' + spinner + '" alt="loading..."/>')},
	  success:function(html){jQuery('#display_demo_web').eq(0).html('<pre>'+html+'</pre>')},
	  dataType:'html'}
	)
}

function load_display_demo_rss() {
	jQuery.ajax({type:'POST',
	  url: ajaxurl,
	  data:'action=rpp_display_demo_rss',
	  beforeSend:function(){jQuery('#display_demo_rss').eq(0).html('<img src="'+spinner+'" alt="loading..."/>')},
	  success:function(html){jQuery('#display_demo_rss').eq(0).html('<pre>'+html+'</pre>')},
	  dataType:'html'}
	)
}

function load_display_distags() {
	jQuery.ajax({type:'POST',
	  url: ajaxurl,
	  data:'action=rpp_display_distags',
	  beforeSend:function(){jQuery('#display_distags').eq(0).html('<img src="'+spinner+'" alt="loading..."/>')},
	  success:function(html){jQuery('#display_distags').eq(0).html(html)},
	  dataType:'html'}
	)
}

function load_display_discats() {
	jQuery.ajax({type:'POST',
	  url: ajaxurl,
	  data:'action=rpp_display_discats',
	  beforeSend:function(){jQuery('#display_discats').eq(0).html('<img src="'+spinner+'" alt="loading..."/>')},
	  success:function(html){jQuery('#display_discats').eq(0).html(html)},
	  dataType:'html'}
	)
}
//-->
</script>

<div class="wrap">
		<h2>
			<?php _e('Related Posts Plugin Options','rpp');?> <small><?php
      echo rpp_get_option('version');
			?></small>
		</h2>

	<?php echo "<div id='rpp-version' style='display:none;'>".rpp_get_option('version')."</div>"; ?>

	<form method="post">

<!--	<div style='border:1px solid #ddd;padding:8px;'>-->

<?php
wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>
<div id="poststuff" class="metabox-holder has-right-sidebar">

<div class="inner-sidebar" id="side-info-column">
<?php
do_meta_boxes('settings_page_rpp', 'side', array());
?>
</div>

<div id="post-body-content">
<?php
do_meta_boxes('settings_page_rpp', 'normal', array());
?>
</div>

<script language="javascript">
//<!--
	function template() {
		if (jQuery('.template').eq(0).attr('checked')) {
			jQuery('.templated').show();
			jQuery('.not_templated').hide();
		} else {
			jQuery('.templated').hide();
			jQuery('.not_templated').show();
		}
		excerpt();
	}
	jQuery('.template').click(template);
	
	function excerpt() {
		if (!jQuery('.template').eq(0).attr('checked') && jQuery('.show_excerpt').eq(0).attr('checked'))
			jQuery('.excerpted').show();
		else
			jQuery('.excerpted').hide();
	}
	jQuery('.show_excerpt,.template').click(excerpt);

	function rss_display() {
		if (jQuery('.rss_display').eq(0).attr('checked'))
			jQuery('.rss_displayed').show();
		else
			jQuery('.rss_displayed').hide();
		rss_excerpt();
	}
	jQuery('.rss_display').click(rss_display);
	
	function rss_template() {
		if (jQuery('.rss_template').eq(0).attr('checked')) {
			jQuery('.rss_templated').show();
			jQuery('.rss_not_templated').hide();
		} else {
			jQuery('.rss_templated').hide();
			jQuery('.rss_not_templated').show();
		}
		rss_excerpt();
	}
	jQuery('.rss_template').click(rss_template);
	
	function rss_excerpt() {
		if (jQuery('.rss_display').eq(0).attr('checked') && jQuery('.rss_show_excerpt').eq(0).attr('checked'))
			jQuery('.rss_excerpted').show();
		else
			jQuery('.rss_excerpted').hide();
	}
	jQuery('.rss_display,.rss_show_excerpt').click(rss_excerpt);

	function rpp_js_init() {
		template();
		rss_template();
		load_display_discats();
		load_display_distags();
		load_display_demo_web();
		load_display_demo_rss();

		var version = jQuery('#rpp-version').html();
	}

	jQuery(rpp_js_init);
	//-->
	</script>

	<div>
		<p class="submit">
			<input type="submit" class='button-primary' name="update_rpp" value="<?php _e("Update options",'rpp')?>" />
			<!--<input type="submit" onclick='return confirm("<?php _e("Do you really want to reset your configuration?",'rpp');?>");' class="rpp_warning" name="reset_rpp" value="<?php _e('Reset options','rpp')?>" />-->
		</p>
	</div>
<!--cache engine: <?php echo $rpp_cache->name;?>; cache status: <?php echo $rpp_cache->cache_status();?>-->

</form>
