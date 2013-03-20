<?php
/**
 * The template for displaying the Events archive
 */

/* first deal with the events json/ical feeds */
EventPostType::do_feed();

/* now get events to display */
$events = EventPostType::query_events();
//print('<pre>');print_r($events);print('</pre>');exit;


get_header();
?>
		<div id="primary">
			<div id="content" role="main">
<?php
global $post;
if ($events->options['ept_archive_options']["archive_search"]) {
	echo EventPostType::get_search_bar();
}
if ($events->options['ept_archive_options']["archive_calendar"]) {
	echo EventPostType::get_events_calendar();
}
if (count($events->posts) || count($events->stickies)):
	if (count($events->stickies)) :
	    foreach ($events->stickies as $post):
    ?>

					<article class="event sticky" id="event-<?php $post->ID; ?>">
						<h2><a href="<?php echo EventPostType::get_url($post->ID) ?>" rel="bookmark" title="Permanent Link to <?php echo esc_attr($post->post_title); ?>">
						<?php echo apply_filters("the_title", $post->post_title); ?></a></h2>
						<small><?php echo EventPostType::get_date($post->ID); ?></small>
						<div class="entry">
						<?php 
							if ($events->options["archive_format"] == "full") {
								the_content();
							} elseif (the_excerpt('Read the rest of this entry »'); ?>
						</div>
						<?php //comments_popup_link('No Comments »', '1 Comment »', '% Comments »'); ?></p>
					</article>

	<?php 
		endforeach;
	endif;
	if (count($events->posts)) :
	    foreach ($events->posts as $post):
	?>

					<article class="event" id="event-<?php $post->ID; ?>">
						<h3><a href="<?php echo EventPostType::get_url($post->ID) ?>" rel="bookmark" title="Permanent Link to <?php echo esc_attr($post->post_title); ?>">
						<?php echo apply_filters("the_title", $post->post_title); ?></a></h3>
						<small><?php echo EventPostType::get_date($post->ID); ?></small>
						<div class="entry">
						<?php the_excerpt('Read the rest of this entry »'); ?>
						</div>
						<?php //comments_popup_link('No Comments »', '1 Comment »', '% Comments »'); ?></p>
					</article>

	<?php 
		endforeach;
	endif;

else : ?>

    <h2 class="center"><?php _e('Events', 'event-post-type'); ?></h2>
    <p class="center"><?php _e('Sorry, but there are no events to show here.', 'event-post-type'); ?></p>

<?php endif; 
?>
			</div>
		</div>
<?php get_footer(); ?>