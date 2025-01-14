<?php

// setup the ajax action hooks
if (function_exists('add_action')) {
	add_action('wp_ajax_rpp_display_discats', 'rpp_ajax_display_discats');
	add_action('wp_ajax_rpp_display_distags', 'rpp_ajax_display_distags');
	add_action('wp_ajax_rpp_display_demo_web', 'rpp_ajax_display_demo_web');
	add_action('wp_ajax_rpp_display_demo_rss', 'rpp_ajax_display_demo_rss');
}

function rpp_ajax_display_discats() {
	global $wpdb;
	
	header("HTTP/1.1 200");
	header("Content-Type: text/html; charset=UTF-8");
	
	$discats = explode(',',rpp_get_option('discats'));
	array_unshift($discats,' ');
	foreach ($wpdb->get_results("select $wpdb->terms.term_id, name from $wpdb->terms natural join $wpdb->term_taxonomy where $wpdb->term_taxonomy.taxonomy = 'category' order by name") as $cat) {
		echo "<input type='checkbox' name='discats[$cat->term_id]' value='true'". (array_search($cat->term_id,$discats) ? ' checked="checked"': '' )."  /> <label>$cat->name</label> ";//for='discats[$cat->term_id]' it's not HTML. :(
	}
	exit;
}

function rpp_ajax_display_distags() {
	global $wpdb;
	
	header("HTTP/1.1 200");
	header("Content-Type: text/html; charset=UTF-8");
	
	$distags = explode(',',rpp_get_option('distags'));
	array_unshift($distags,' ');
	foreach ($wpdb->get_results("select $wpdb->terms.term_id, name from $wpdb->terms natural join $wpdb->term_taxonomy where $wpdb->term_taxonomy.taxonomy = 'post_tag' order by name") as $tag) {
		echo "<input type='checkbox' name='distags[$tag->term_id]' value='true'". (array_search($tag->term_id,$distags) ? ' checked="checked"': '' )."  /> <label>$tag->name</label> ";// for='distags[$tag->term_id]'
	}
	exit;
}

function rpp_ajax_display_demo_web() {
	header("HTTP/1.1 200");
	header("Content-Type: text/html; charset=UTF-8");

	$return = rpp_related(array('post'),array(),false,false,'demo_web');
	echo ereg_replace("[\n\r]",'',nl2br(htmlspecialchars($return)));
	exit;
}

function rpp_ajax_display_demo_rss() {
	header("HTTP/1.1 200");
	header("Content-Type: text/html; charset=UTF-8");

	$return = rpp_related(array('post'),array(),false,false,'demo_rss');
	echo ereg_replace("[\n\r]",'',nl2br(htmlspecialchars($return)));
	exit;
}
