<?php

class RPP_Meta_Box {
	function checkbox($option,$desc,$tr="<tr valign='top'>
				<th class='th-full' colspan='2' scope='row'>",$inputplus = '',$thplus='') {
		echo "			$tr<input $inputplus type='checkbox' name='$option' value='true'". ((rpp_get_option($option) == 1) ? ' checked="checked"': '' )."  /> $desc</th>$thplus
			</tr>";
	}
	function textbox($option,$desc,$size=2,$tr="<tr valign='top'>
				<th scope='row'>", $note = '') {
		$value = stripslashes(rpp_get_option($option,true));
		echo "			$tr$desc</th>
				<td><input name='$option' type='text' id='$option' value='$value' size='$size' />";
		if ( !empty($note) )
			echo " <em><small>{$note}</small></em>";
		echo "</td>
			</tr>";
	}
	function beforeafter($options,$desc,$size=10,$tr="<tr valign='top'>
				<th scope='row'>", $note = '') {
		echo "			$tr$desc</th>
				<td>";
		$value = stripslashes(rpp_get_option($options[0],true));
		echo "<input name='{$options[0]}' type='text' id='{$options[0]}' value='$value' size='$size' /> / ";
		$value = stripslashes(rpp_get_option($options[1],true));
		echo "<input name='{$options[1]}' type='text' id='{$options[1]}' value='$value' size='$size' />";
		if ( !empty($note) )
			echo " <em><small>{$note}</small></em>";
		echo "</td>
			</tr>";
	}

	function importance($option,$desc,$type='word',$tr="<tr valign='top'>
				<th scope='row'>",$inputplus = '') {
		$value = rpp_get_option($option);
	
		// $type could be...
		__('word','rpp');
		__('tag','rpp');
		__('category','rpp');
	
		echo "		$tr$desc</th>
				<td>
													<select name='$option'>
				<option $inputplus value='1'". (($value == 1) ? ' selected="selected"': '' )."  > ".__("do not consider",'rpp')."</option>
				<option $inputplus value='2'". (($value == 2) ? ' selected="selected"': '' )."  > ".__("consider",'rpp')."</option>
				<option $inputplus value='3'". (($value == 3) ? ' selected="selected"': '' )."  >
				".sprintf(__("require at least one %s in common",'rpp'),__($type,'rpp'))."</option>
				<option $inputplus value='4'". (($value == 4) ? ' selected="selected"': '' )."  >
				".sprintf(__("require more than one %s in common",'rpp'),__($type,'rpp'))."</option>
													</select>
				</td>
			</tr>";
	}
	
	function importance2($option,$desc,$type='word',$tr="<tr valign='top'>
				<th scope='row'>",$inputplus = '') {
		$value = rpp_get_option($option);
	
		echo "		$tr$desc</th>
				<td>
													<select name='$option'>
				<option $inputplus value='1'". (($value == 1) ? ' selected="selected"': '' )."  >".__("do not consider",'rpp')."</option>
				<option $inputplus value='2'". (($value == 2) ? ' selected="selected"': '' )."  > ".__("consider",'rpp')."</option>
				<option $inputplus value='3'". (($value == 3) ? ' selected="selected"': '' )."  > ".__("consider with extra weight",'rpp')."</option>
													</select>
				</td>
			</tr>";
	}
	
	function select($option,$desc,$type='word',$tr="<tr valign='top'>
				<th scope='row'>",$inputplus = '') {
		echo "		$tr$desc</th>
				<td>
				<input $inputplus type='radio' name='$option' value='1'". ((rpp_get_option($option) == 1) ? ' checked="checked"': '' )."  />
				".__("do not consider",'rpp')."
				<input $inputplus type='radio' name='$option' value='2'". ((rpp_get_option($option) == 2) ? ' checked="checked"': '' )."  />
				".__("consider",'rpp')."
				<input $inputplus type='radio' name='$option' value='3'". ((rpp_get_option($option) == 3) ? ' checked="checked"': '' )."  />
				".sprintf(__("require at least one %s in common",'rpp'),__($type,'rpp'))."
				<input $inputplus type='radio' name='$option' value='4'". ((rpp_get_option($option) == 4) ? ' checked="checked"': '' )."  />
				".sprintf(__("require more than one %s in common",'rpp'),__($type,'rpp'))."
				</td>
			</tr>";
	}
}

class RPP_Meta_Box_Pool extends RPP_Meta_Box {
	function display() {
?>
	<p><?php _e('"The Pool" refers to the pool of posts and pages that are candidates for display as related to the current entry.','rpp');?></p>

	<table class="form-table" style="margin-top: 0; clear:none;">
		<tbody>
			<tr valign='top'>
				<th scope='row'><?php _e('Disallow by category:','rpp');?></th><td><div id='display_discats' style="overflow:auto;max-height:100px;"></div></td></tr>
			<tr valign='top'>
				<th scope='row'><?php _e('Disallow by tag:','rpp');?></th>
				<td><div id='display_distags' style="overflow:auto;max-height:100px;"></div></td></tr>
<?php
	$this->checkbox('show_pass_post',__("Show password protected posts?",'rpp'));

	$recent_number = "<input name=\"recent_number\" type=\"text\" id=\"recent_number\" value=\"".stripslashes(rpp_get_option('recent_number',true))."\" size=\"2\" />";
	$recent_units = "<select name=\"recent_units\" id=\"recent_units\">
		<option value='day'". (('day'==rpp_get_option('recent_units'))?" selected='selected'":'').">".__('day(s)','rpp')."</option>
		<option value='week'". (('week'==rpp_get_option('recent_units'))?" selected='selected'":'').">".__('week(s)','rpp')."</option>
		<option value='month'". (('month'==rpp_get_option('recent_units'))?" selected='selected'":'').">".__('month(s)','rpp')."</option>
	</select>";
	$this->checkbox('recent_only',str_replace('NUMBER',$recent_number,str_replace('UNITS',$recent_units,__("Show only posts from the past NUMBER UNITS",'rpp'))));
?>

		</tbody>
	</table>
<?php
	}
}

add_meta_box('rpp_pool', __('"The Pool"','rpp'), array(new RPP_Meta_Box_Pool, 'display'), 'settings_page_rpp', 'normal', 'core');

class RPP_Meta_Box_Relatedness extends RPP_Meta_Box {
	function display() {
		global $rpp_myisam;
?>
	<p><?php _e('RPP limits the related posts list by (1) a maximum number and (2) a <em>match threshold</em>.','rpp');?> <a href="#" class='info'><?php _e('more&gt;','rpp');?><span><?php _e('The higher the match threshold, the more restrictive, and you get less related posts overall. The default match threshold is 5. If you want to find an appropriate match threshhold, take a look at some post\'s related posts display and their scores. You can see what kinds of related posts are being picked up and with what kind of match scores, and determine an appropriate threshold for your site.','rpp');?></span></a></p>

	<table class="form-table" style="margin-top: 0; clear:none;">
		<tbody>

<?php
	$this->textbox('threshold',__('Match threshold:','rpp'));
	$this->importance2('title',__("Titles: ",'rpp'),'word',"<tr valign='top'>
			<th scope='row'>",(!$rpp_myisam?' readonly="readonly" disabled="disabled"':''));
	$this->importance2('body',__("Bodies: ",'rpp'),'word',"<tr valign='top'>
			<th scope='row'>",(!$rpp_myisam?' readonly="readonly" disabled="disabled"':''));
	$this->importance('tags',__("Tags: ",'rpp'),'tag',"<tr valign='top'>
			<th scope='row'>",'');
	$this->importance('categories',__("Categories: ",'rpp'),'category',"<tr valign='top'>
			<th scope='row'>",'');
	$this->checkbox('cross_relate',__("Cross-relate posts and pages?",'rpp')." <a href='#' class='info'>".__('more&gt;','rpp')."<span>".__("When the \"Cross-relate posts and pages\" option is selected, the <code>related_posts()</code>, <code>related_pages()</code>, and <code>related_entries()</code> all will give the same output, returning both related pages and posts.",'rpp')."</span></a>");
	$this->checkbox('past_only',__("Show only previous posts?",'rpp'));
?>
			</tbody>
		</table>
<?php
	}
}

add_meta_box('rpp_relatedness', __('"Relatedness" options','rpp'), array(new RPP_Meta_Box_Relatedness, 'display'), 'settings_page_rpp', 'normal', 'core');

class RPP_Meta_Box_Display_Web extends RPP_Meta_Box {
	function display() {
		global $rpp_templateable;
	?>
		<table class="form-table" style="margin-top: 0; clear:none;">
		<tbody>
<?php
		$this->checkbox('auto_display',__("Automatically display related posts?",'rpp')." <a href='#' class='info'>".__('more&gt;','rpp')."<span>".__("This option automatically displays related posts right after the content on single entry pages. If this option is off, you will need to manually insert <code>related_posts()</code> or variants (<code>related_pages()</code> and <code>related_entries()</code>) into your theme files.",'rpp')."</span></a>","<tr valign='top'>
			<th class='th-full' colspan='2' scope='row' style='width:100%;'>",'','<td rowspan="3" style="border-left:8px transparent solid;"><b>'.__("Website display code example",'rpp').'</b><br /><small>'.__("(Update options to reload.)",'rpp').'</small><br/>'
."<div id='display_demo_web' style='overflow:auto;width:350px;max-height:500px;'></div></td>");
		$this->textbox('limit',__('Maximum number of related posts:','rpp'));
		$this->checkbox('use_template',__("Display using a custom template file",'rpp')." <a href='#' class='info'>".__('more&gt;','rpp')."<span>".__("This advanced option gives you full power to customize how your related posts are displayed. Templates (stored in your theme folder) are written in PHP.",'rpp')."</span></a>","<tr valign='top'><th colspan='2'>",' class="template"'.(!$rpp_templateable?' disabled="disabled"':'')); ?>
		</tbody></table>
		<table class="form-table" style="clear:none;"><tbody>
			<tr valign='top' class='templated'>
				<th><?php _e("Template file:",'rpp');?></th>
				<td>
					<select name="template_file" id="template_file">
						<?php foreach (glob(STYLESHEETPATH . '/rpp-template-*.php') as $template): ?>
						<option value='<?php echo htmlspecialchars(basename($template))?>'<?php echo (basename($template)==rpp_get_option('template_file'))?" selected='selected'":'';?>><?php echo htmlspecialchars(basename($template))?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
	<?php
	$this->beforeafter(array('before_related', 'after_related'),__("Before / after related entries:",'rpp'),15,"<tr class='not_templated' valign='top'>\r\t\t\t\t<th>", __("For example:",'rpp') . ' &lt;ol&gt;&lt;/ol&gt;' . __(' or ','rpp') . '&lt;div&gt;&lt;/div&gt;');
	$this->beforeafter(array('before_title', 'after_title'),__("Before / after each related entry:",'rpp'),15,"<tr class='not_templated' valign='top'>\r\t\t\t\t<th>", __("For example:",'rpp') . ' &lt;li&gt;&lt;/li&gt;' . __(' or ','rpp') . '&lt;dl&gt;&lt;/dl&gt;');
	
	$this->checkbox('show_excerpt',__("Show excerpt?",'rpp'),"<tr class='not_templated' valign='top'><th colspan='2'>",' class="show_excerpt"');
	$this->textbox('excerpt_length',__('Excerpt length (No. of words):','rpp'),10,"<tr class='excerpted' valign='top'>
				<th>")?>

			<tr class="excerpted" valign='top'>
				<th><?php _e("Before / after (Excerpt):",'rpp');?></th>
				<td><input name="before_post" type="text" id="before_post" value="<?php echo stripslashes(rpp_get_option('before_post',true)); ?>" size="10" /> / <input name="after_post" type="text" id="after_post" value="<?php echo stripslashes(rpp_get_option('after_post')); ?>" size="10" /><em><small> <?php _e("For example:",'rpp');?> &lt;li&gt;&lt;/li&gt;<?php _e(' or ','rpp');?>&lt;dl&gt;&lt;/dl&gt;</small></em>
				</td>
			</tr>

			<tr valign='top'>
				<th><?php _e("Order results:",'rpp');?></th>
				<td><select name="order" id="order">
					<option value="score DESC" <?php echo (rpp_get_option('order')=='score DESC'?' selected="selected"':'')?>><?php _e("score (high relevance to low)",'rpp');?></option>
					<option value="score ASC" <?php echo (rpp_get_option('order')=='score ASC'?' selected="selected"':'')?>><?php _e("score (low relevance to high)",'rpp');?></option>
					<option value="post_date DESC" <?php echo (rpp_get_option('order')=='post_date DESC'?' selected="selected"':'')?>><?php _e("date (new to old)",'rpp');?></option>
					<option value="post_date ASC" <?php echo (rpp_get_option('order')=='post_date ASC'?' selected="selected"':'')?>><?php _e("date (old to new)",'rpp');?></option>
					<option value="post_title ASC" <?php echo (rpp_get_option('order')=='post_title ASC'?' selected="selected"':'')?>><?php _e("title (alphabetical)",'rpp');?></option>
					<option value="post_title DESC" <?php echo (rpp_get_option('order')=='post_title DESC'?' selected="selected"':'')?>><?php _e("title (reverse alphabetical)",'rpp');?></option>
				</select>
				</td>
			</tr>

	<?php $this->textbox('no_results',__('Default display if no results:','rpp'),'40',"<tr class='not_templated' valign='top'>
				<th>")?>
	<?php $this->checkbox('promote_rpp',__("Help promote Related Posts Plugin?",'rpp')
	." <a href='#' class='info'>".__('more&gt;','rpp')."<span>"
	.sprintf(__("This option will add the code %s. Try turning it on, updating your options, and see the code in the code example to the right. These links and donations are greatly appreciated.", 'rpp'),"<code>".htmlspecialchars(sprintf(__("Related posts brought to you by <a href='%s'>Related Posts Plugin</a>.",'rpp'), 'http://www.escalateseo.com'))."</code>")	."</span></a>"); ?>
		</tbody>
		</table>
<?php
	}
}

add_meta_box('rpp_display_web', __('Display options <small>for your website</small>','rpp'), array(new RPP_Meta_Box_Display_Web, 'display'), 'settings_page_rpp', 'normal', 'core');

class RPP_Meta_Box_Display_Feed extends RPP_Meta_Box {
	function display() {
		global $rpp_templateable;
?>
		<table class="form-table" style="margin-top: 0; clear:none;"><tbody>
<?php

$this->checkbox('rss_display',__("Display related posts in feeds?",'rpp')." <a href='#' class='info'>".__('more&gt;','rpp')."<span>".__("This option displays related posts at the end of each item in your RSS and Atom feeds. No template changes are needed.",'rpp')."</span></a>","<tr valign='top'><th colspan='2' style='width:100%'>",' class="rss_display"','<td class="rss_displayed" rowspan="4" style="border-left:8px transparent solid;"><b>'.__("RSS display code example",'rpp').'</b><br /><small>'.__("(Update options to reload.)",'rpp').'</small><br/>'
."<div id='display_demo_rss' style='overflow:auto;width:350px;max-height:500px;'></div></td>");
$this->checkbox('rss_excerpt_display',__("Display related posts in the descriptions?",'rpp')." <a href='#' class='info'>".__('more&gt;','rpp')."<span>".__("This option displays the related posts in the RSS description fields, not just the content. If your feeds are set up to only display excerpts, however, only the description field is used, so this option is required for any display at all.",'rpp')."</span></a>","<tr class='rss_displayed' valign='top'>
			<th class='th-full' colspan='2' scope='row'>");

	$this->textbox('rss_limit',__('Maximum number of related posts:','rpp'),2, "<tr valign='top' class='rss_displayed'>
				<th scope='row'>");
	$this->checkbox('rss_use_template',__("Display using a custom template file",'rpp')." <!--<span style='color:red;'>".__('NEW!','rpp')."</span>--> <a href='#' class='info'>".__('more&gt;','rpp')."<span>".__("This advanced option gives you full power to customize how your related posts are displayed. Templates (stored in your theme folder) are written in PHP.",'rpp')."</span></a>","<tr valign='top' class='rss_displayed'><th colspan='2'>",' class="rss_template"'.(!$rpp_templateable?' disabled="disabled"':'')); ?>
	</tbody></table>
	<table class="form-table rss_displayed" style="clear:none;">
		<tbody>
			<tr valign='top' class='rss_templated'>
				<th><?php _e("Template file:",'rpp');?></th>
				<td>
					<select name="rss_template_file" id="rss_template_file">
						<?php foreach (glob(STYLESHEETPATH . '/rpp-template-*.php') as $template): ?>
						<option value='<?php echo htmlspecialchars(basename($template))?>'<?php echo (basename($template)==rpp_get_option('rss_template_file'))?" selected='selected'":'';?>><?php echo htmlspecialchars(basename($template))?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

	<?php 
	$this->beforeafter(array('rss_before_related', 'rss_after_related'),__("Before / after related entries:",'rpp'),15,"<tr class='rss_not_templated' valign='top'>\r\t\t\t\t<th>", __("For example:",'rpp') . ' &lt;ol&gt;&lt;/ol&gt;' . __(' or ','rpp') . '&lt;div&gt;&lt;/div&gt;');
	$this->beforeafter(array('rss_before_title', 'rss_after_title'),__("Before / after each related entry:",'rpp'),15,"<tr class='rss_not_templated' valign='top'>\r\t\t\t\t<th>", __("For example:",'rpp') . ' &lt;li&gt;&lt;/li&gt;' . __(' or ','rpp') . '&lt;dl&gt;&lt;/dl&gt;');
	
	$this->checkbox('rss_show_excerpt',__("Show excerpt?",'rpp'),"<tr class='rss_not_templated' valign='top'><th colspan='2'>",' class="rss_show_excerpt"');
	$this->textbox('rss_excerpt_length',__('Excerpt length (No. of words):','rpp'),10,"<tr class='rss_excerpted' valign='top'>\r\t\t\t\t<th>");

	$this->beforeafter(array('rss_before_post', 'rss_after_post'),__("Before / after (excerpt):",'rpp'),10,"<tr class='rss_excerpted' valign='top'>\r\t\t\t\t<th>", __("For example:",'rpp') . ' &lt;li&gt;&lt;/li&gt;' . __(' or ','rpp') . '&lt;dl&gt;&lt;/dl&gt;');

	?>
			<tr class='rss_displayed' valign='top'>
				<th><?php _e("Order results:",'rpp');?></th>
				<td><select name="rss_order" id="rss_order">
					<option value="score DESC" <?php echo (rpp_get_option('rss_order')=='score DESC'?' selected="selected"':'')?>><?php _e("score (high relevance to low)",'rpp');?></option>
					<option value="score ASC" <?php echo (rpp_get_option('rss_order')=='score ASC'?' selected="selected"':'')?>><?php _e("score (low relevance to high)",'rpp');?></option>
					<option value="post_date DESC" <?php echo (rpp_get_option('rss_order')=='post_date DESC'?' selected="selected"':'')?>><?php _e("date (new to old)",'rpp');?></option>
					<option value="post_date ASC" <?php echo (rpp_get_option('rss_order')=='post_date ASC'?' selected="selected"':'')?>><?php _e("date (old to new)",'rpp');?></option>
					<option value="post_title ASC" <?php echo (rpp_get_option('rss_order')=='post_title ASC'?' selected="selected"':'')?>><?php _e("title (alphabetical)",'rpp');?></option>
					<option value="post_title DESC" <?php echo (rpp_get_option('rss_order')=='post_title DESC'?' selected="selected"':'')?>><?php _e("title (reverse alphabetical)",'rpp');?></option>
				</select>
				</td>
			</tr>

	<?php $this->textbox('rss_no_results',__('Default display if no results:','rpp'),'40',"<tr valign='top' class='rss_not_templated'>
			<th scope='row'>")?>
	<?php $this->checkbox('rss_promote_rpp',__("Help promote Related Posts Plugin?",'rpp')." <a href='#' class='info'>".__('more&gt;','rpp')."<span>"
	.sprintf(__("This option will add the code %s. Try turning it on, updating your options, and see the code in the code example to the right. These links and donations are greatly appreciated.", 'rpp'),"<code>".htmlspecialchars(sprintf(__("Related posts brought to you by <a href='%s'>Related Posts Plugin</a>.",'rpp'), 'http://www.escalateseo.com'))."</code>")	."</span></a>","<tr valign='top' class='rss_displayed'>
			<th class='th-full' colspan='2' scope='row'>"); ?>
		</tbody></table>
<?php
	}
}

add_meta_box('rpp_display_rss', __('Display options <small>for RSS</small>','rpp'), array(new RPP_Meta_Box_Display_Feed, 'display'), 'settings_page_rpp', 'normal', 'core');

class RPP_Meta_Box_Contact extends RPP_Meta_Box {
	function display() {
		$pluginurl = plugin_dir_url(__FILE__);
		?>
<?php
	}
}

// since 3.3: hook for registering new RPP meta boxes
do_action( 'add_meta_boxes_settings_page_rpp' );

