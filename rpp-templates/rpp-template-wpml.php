<?php /*
Example template for use with WPML (WP Multilingual, http://wpml.org)
Author: EscalateSEO
*/

if (function_exists("icl_register_string")) {
  icl_register_string("Related Posts Plugin","related posts header","Related Posts");
  icl_register_string("Related Posts Plugin","no related posts message","No related posts.");
}

?><h3><?php echo (function_exists("icl_t") ? icl_t("Related Posts Plugin","related posts header","Related Posts") : "Related Posts B") ?></h3>
<?php if ($related_query->have_posts()):?>
<ol>
	<?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
	<li><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></li>
	<?php endwhile; ?>
</ol>
<?php else: ?>
<p><?php echo (function_exists("icl_t") ? icl_t("Related Posts Plugin","no related posts message","No related posts.") : "No related posts. B") ?></p>
<?php endif; ?>
