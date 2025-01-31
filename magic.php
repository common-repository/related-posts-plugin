<?php

//=TEMPLATING/DISPLAY===========

function rpp_set_score_override_flag($q) {
	global $rpp_cache;
	if ($rpp_cache->rpp_time) {
		$rpp_cache->score_override = ($q->query_vars['orderby'] == 'score');

		if (!empty($q->query_vars['showposts'])) {
			$rpp_cache->online_limit = $q->query_vars['showposts'];
		} else {
			$rpp_cache->online_limit = false;
    }
	} else {
    $rpp_cache->score_override = false;
    $rpp_cache->online_limit = false;
	}
}

//=CACHING===========

function rpp_sql($args,$giveresults = true,$reference_ID=false,$domain='website') {
	global $wpdb, $post, $rpp_debug, $rpp_cache;

	if (is_object($post) and !$reference_ID) {
		$reference_ID = $post->ID;
	}

	// set the "domain prefix", used for all the preferences.
	if ($domain == 'rss')
		$domainprefix = 'rss_';
	else
		$domainprefix = '';

	$options = array('limit'=>"${domainprefix}limit",
		'threshold'=>'threshold',
		'show_pass_post'=>'show_pass_post',
		'past_only'=>'past_only',
		'body'=>'body',
		'title'=>'title',
		'tags'=>'tags',
		'categories'=>'categories',
		'distags'=>'distags',
		'discats'=>'discats',
		'recent_only'=>'recent_only',
		'recent_number'=>'recent_number',
		'recent_units'=>'recent_units');
	$optvals = array();
	foreach (array_keys($options) as $option) {
		if (isset($args[$option])) {
			$optvals[$option] = stripslashes($args[$option]);
		} else {
			$optvals[$option] = stripslashes(stripslashes(rpp_get_option($options[$option])));
		}
	}

	extract($optvals);

	// Fetch keywords
  $body_terms = $rpp_cache->get_keywords($reference_ID,'body');
  $title_terms = $rpp_cache->get_keywords($reference_ID,'title');

  if ($rpp_debug) echo "<!--TITLE TERMS: $title_terms-->"; // debug
  if ($rpp_debug) echo "<!--BODY TERMS: $body_terms-->"; // debug

	// get weights

	$bodyweight = (($body == 3)?3:(($body == 2)?1:0));
	$titleweight = (($title == 3)?3:(($title == 2)?1:0));
	$tagweight = (($tags != 1)?1:0);
	$catweight = (($categories != 1)?1:0);
	$weights = array();
	$weights['body'] = $bodyweight;
	$weights['title'] = $titleweight;
	$weights['cat'] = $catweight;
	$weights['tag'] = $tagweight;

	$totalweight = $bodyweight + $titleweight + $tagweight + $catweight;

	// get disallowed categories and tags

	$disterms = implode(',', array_filter(array_merge(explode(',',$discats),explode(',',$distags)),'is_numeric'));

	$usedisterms = count(array_filter(array_merge(explode(',',$discats),explode(',',$distags)),'is_numeric'));

	$criteria = array();
	if ($bodyweight)
		$criteria['body'] = "(MATCH (post_content) AGAINST ('".$wpdb->escape($body_terms)."'))";
	if ($titleweight)
		$criteria['title'] = "(MATCH (post_title) AGAINST ('".$wpdb->escape($title_terms)."'))";
	if ($tagweight)
		$criteria['tag'] = "COUNT( DISTINCT tagtax.term_taxonomy_id )";
	if ($catweight)
		$criteria['cat'] = "COUNT( DISTINCT cattax.term_taxonomy_id )";

	$newsql = "SELECT $reference_ID as reference_ID, ID, "; //post_title, post_date, post_content, post_excerpt,

	$newsql .= 'ROUND(0';
	foreach ($criteria as $key => $value) {
		$newsql .= "+ $value * ".$weights[$key];
	}
	$newsql .= ',1) as score';

	$newsql .= "\n from $wpdb->posts \n";

	if ($usedisterms)
		$newsql .= " left join $wpdb->term_relationships as blockrel on ($wpdb->posts.ID = blockrel.object_id)
		left join $wpdb->term_taxonomy as blocktax using (`term_taxonomy_id`)
		left join $wpdb->terms as blockterm on (blocktax.term_id = blockterm.term_id and blockterm.term_id in ($disterms))\n";

	if ($tagweight)
		$newsql .= " left JOIN $wpdb->term_relationships AS thistag ON (thistag.object_id = $reference_ID )
		left JOIN $wpdb->term_relationships AS tagrel on (tagrel.term_taxonomy_id = thistag.term_taxonomy_id
		AND tagrel.object_id = $wpdb->posts.ID)
		left JOIN $wpdb->term_taxonomy AS tagtax ON ( tagrel.term_taxonomy_id = tagtax.term_taxonomy_id
		AND tagtax.taxonomy = 'post_tag')\n";

	if ($catweight)
		$newsql .= " left JOIN $wpdb->term_relationships AS thiscat ON (thiscat.object_id = $reference_ID )
		left JOIN $wpdb->term_relationships AS catrel on (catrel.term_taxonomy_id = thiscat.term_taxonomy_id
		AND catrel.object_id = $wpdb->posts.ID)
		left JOIN $wpdb->term_taxonomy AS cattax ON ( catrel.term_taxonomy_id = cattax.term_taxonomy_id
		AND cattax.taxonomy = 'category')\n";

	// WHERE

	$newsql .= " where (post_status IN ( 'publish',  'static' ) and ID != '$reference_ID')";

	if ($past_only) { // 3.1.8: revised $past_only option
    if ( is_object($post) && $reference_ID == $post->ID )
	    $reference_post_date = $post->post_date;
	  else
	    $reference_post_date = $wpdb->get_var("select post_date from $wpdb->posts where ID = $reference_ID");
		$newsql .= " and post_date <= '$reference_post_date' ";
	}
	if (!$show_pass_post)
		$newsql .= " and post_password ='' ";
	if ($recent_only)
		$newsql .= " and post_date > date_sub(now(), interval $recent_number $recent_units) ";

  $newsql .= " and post_type = 'post'";

	// GROUP BY
	$newsql .= "\n group by ID \n";
	// HAVING
	// safethreshold is so the new calibration system works.
	// number_format fix suggested by vkovalcik! :)
	$safethreshold = number_format(max($threshold,0.1), 2, '.', '');
	$newsql .= " having score >= $safethreshold";
	if ($usedisterms)
		$newsql .= " and count(blockterm.term_id) = 0";

	$newsql .= (($categories == 3)?' and '.$criteria['cat'].' >= 1':'');
	$newsql .= (($categories == 4)?' and '.$criteria['cat'].' >= 2':'');
	$newsql .= (($tags == 3)?' and '.$criteria['tag'].' >= 1':'');
	$newsql .= (($tags == 4)?' and '.$criteria['tag'].' >= 2':'');
	$newsql .= " order by score desc limit ".$limit;

	if (!$giveresults) {
		$newsql = "select count(t.ID) from ($newsql) as t";
	}

  // in caching, we cross-relate regardless of whether we're going to actually
  // use it or not.
  $newsql = "($newsql) union (".str_replace("post_type = 'post'","post_type = 'page'",$newsql).")";

	if ($rpp_debug) echo "<!--$newsql-->";
	return $newsql;
}

/* new in 2.1! the domain argument refers to {website,widget,rss}, though widget is not used yet. */

/* new in 3.0! new query-based approach: EXTREMELY HACKY! */

function rpp_related($type,$args,$echo = true,$reference_ID=false,$domain = 'website') {
	global $post, $wp_query, $id, $page, $pages, $authordata, $day, $currentmonth, $multipage, $more, $pagenow, $numpages, $rpp_cache;

  rpp_upgrade_check();

	if ($domain != 'demo_web' and $domain != 'demo_rss') {
		if ($rpp_cache->rpp_time) // if we're already in a RPP loop, stop now.
			return false;

		if (is_object($post) and !$reference_ID)
			$reference_ID = $post->ID;
	} else {
		if ($rpp_cache->demo_time) // if we're already in a RPP loop, stop now.
			return false;
	}

	get_currentuserinfo();

	// set the "domain prefix", used for all the preferences.
	if ($domain == 'rss' or $domain == 'demo_rss')
		$domainprefix = 'rss_';
	else
		$domainprefix = '';

	// get options
	// note the 2.1 change... the options array changed from what you might call a "list" to a "hash"... this changes the structure of the $args to something which is, in the long term, much more useful
	$options = array(
	  'cross_relate'=>"cross_relate",
    'limit'=>"${domainprefix}limit",
		'use_template'=>"${domainprefix}use_template",
		'order'=>"${domainprefix}order",
		'template_file'=>"${domainprefix}template_file",
		'promote_rpp'=>"${domainprefix}promote_rpp");
	$optvals = array();
	foreach (array_keys($options) as $option) {
		if (isset($args[$option])) {
			$optvals[$option] = stripslashes($args[$option]);
		} else {
			$optvals[$option] = stripslashes(stripslashes(rpp_get_option($options[$option])));
		}
	}
	extract($optvals);

	if ($cross_relate)
		$type = array('post','page');

  rpp_cache_enforce($reference_ID);

  $output = '';

	if ($domain != 'demo_web' and $domain != 'demo_rss')
		$rpp_cache->begin_rpp_time($reference_ID); // get ready for RPP TIME!
	else {
		$rpp_cache->demo_time = true;
    if ($domain == 'demo_web')
      $rpp_cache->demo_limit = rpp_get_option('limit');
    else
      $rpp_cache->demo_limit = rpp_get_option('rss_limit');
	}
	// just so we can return to normal later
	$current_query = $wp_query;
	$current_post = $post;
	$current_id = $id;
	$current_page = $page;
	$current_pages = $pages;
	$current_authordata = $authordata;
	$current_numpages = $numpages;
	$current_multipage = $multipage;
	$current_more = $more;
	$current_pagenow = $pagenow;
	$current_day = $day;
	$current_currentmonth = $currentmonth;

	$related_query = new WP_Query();
	$orders = explode(' ',$order);
	if ($domain != 'demo_web' and $domain != 'demo_rss')
		$related_query->query(array('p'=>$reference_ID,'orderby'=>$orders[0],'order'=>$orders[1],'showposts'=>$limit,'post_type'=>$type));
	else
		$related_query->query('');

	$wp_query = $related_query;
	$wp_query->in_the_loop = true;
  $wp_query->is_feed = $current_query->is_feed;
  // make sure we get the right is_single value
  // (see http://wordpress.org/support/topic/288230)
	$wp_query->is_single = false;

	if ($domain == 'metabox') {
		include(RPP_DIR.'/template-metabox.php');
	} elseif ($use_template and file_exists(STYLESHEETPATH . '/' . $template_file) and $template_file != '') {
		ob_start();
		include(STYLESHEETPATH . '/' . $template_file);
		$output = ob_get_contents();
		ob_end_clean();
	} elseif ($domain == 'widget') {
		include(RPP_DIR.'/template-widget.php');
	} else {
		include(RPP_DIR.'/template-builtin.php');
	}

	unset($related_query);
	if ($domain != 'demo_web' and $domain != 'demo_rss')
		$rpp_cache->end_rpp_time(); // RPP time is over... :(
	else
		$rpp_cache->demo_time = false;

	// restore the older wp_query.
	$wp_query = null; $wp_query = $current_query; unset($current_query);
	$post = null; $post = $current_post; unset($current_post);
  $authordata = null; $authordata = $current_authordata; unset($current_authordata);
	$pages = null; $pages = $current_pages; unset($current_pages);
	$id = $current_id; unset($current_id);
	$page = $current_page; unset($current_page);
	$numpages = null; $numpages = $current_numpages; unset($current_numpages);
	$multipage = null; $multipage = $current_multipage; unset($current_multipage);
	$more = null; $more = $current_more; unset($current_more);
	$pagenow = null; $pagenow = $current_pagenow; unset($current_pagenow);
  $day = null; $day = $current_day; unset($current_day);
  $currentmonth = null; $currentmonth = $current_currentmonth; unset($current_currentmonth);

	if ($promote_rpp and $domain != 'metabox')
		$output .= "\n<p>".sprintf(__("Related posts brought to you by <a href='%s'>Related Posts Plugin</a>.",'rpp'), 'http://www.escalateseo.com')."</p>";

	if ($echo) echo $output; else return ((!empty($output))?"\n\n":'').$output;
}

function rpp_related_exist($type,$args,$reference_ID=false) {
	global $post, $rpp_cache;

  rpp_upgrade_check();

	if (is_object($post) and !$reference_ID)
		$reference_ID = $post->ID;

	if ($rpp_cache->rpp_time) // if we're already in a RPP loop, stop now.
		return false;

	if (rpp_get_option('cross_relate'))
		$type = array('post','page');

  rpp_cache_enforce($reference_ID);

	$rpp_cache->begin_rpp_time($reference_ID); // get ready for RPP TIME!
	$related_query = new WP_Query();
	// Note: why is this 10000? Should we just make it 1?
  $related_query->query(array('p'=>$reference_ID,'showposts'=>10000,'post_type'=>$type));
  $return = $related_query->have_posts();
  unset($related_query);
  $rpp_cache->end_rpp_time(); // RPP time is over. :(

	return $return;
}

function rpp_save_cache($post_ID,$force=true) {
	global $wpdb;

  // new in 3.2: don't compute cache during import
  if ( defined( 'WP_IMPORTING' ) )
    return;

  $sql = "select post_parent from $wpdb->posts where ID='$post_ID'";
	$parent_ID = $wpdb->get_var($sql);

	if ($parent_ID != $post_ID and $parent_ID)
		$post_ID = $parent_ID;

  rpp_cache_enforce($post_ID,$force);
}

// Clear the cache for this entry and for all posts which are "related" to it.
// New in 3.2: This is called when a post is deleted.
function rpp_delete_cache($post_ID) {
  global $rpp_cache;

  // Clear the cache for this post.
	$rpp_cache->clear($post_ID);

	// Find all "peers" which list this post as a related post.
	$peers = $rpp_cache->related(null, $post_ID);
	// Clear the peers' caches.
  $rpp_cache->clear($peers);
}

// New in 3.2.1: handle various post_status transitions
function rpp_status_transition($new_status, $old_status, $post) {
  global $rpp_cache;
  switch ($new_status) {
    case "draft":
      rpp_delete_cache($post->ID);
      break;
    case "publish":
      // find everything which is related to this post, and clear them, so that this
      // post might show up as related to them.
      $related = $rpp_cache->related($post->ID, null);
      $rpp_cache->clear($related);
  }
}

function rpp_cache_enforce($reference_ID,$force=false) {
	global $rpp_debug, $rpp_cache;

	if ($reference_ID === '' || $reference_ID === false)
	  return false;

	if (!$force && $rpp_cache->is_cached($reference_ID)) {
		if ($rpp_debug) echo "<!--RPP is using the cache right now.-->";
		return false;
	}

	$rpp_cache->cache_keywords($reference_ID);

	// let's update the related post
	$rpp_cache->update($reference_ID);
	return true;

}

