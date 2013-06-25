<?php
/**
 * The template for displaying the Events archive
 */

/* get events to display */
$events = EventPostType::query_events();
//print('<pre>');print_r($events);print('</pre>');exit;


get_header();

global $post;
if ($events->options['ept_archive_options']["archive_search"]) {
	echo EventPostType::get_search_bar();
}
if ($events->options['ept_archive_options']["archive_calendar"]) {
	echo EventPostType::get_events_calendar();
}
if ($events->query_type == "search" && $events->query_meta["no_results"] > 0) {
	printf('<p class="results_title">%s %s %s <strong>%s</strong></p>', __('Showing', 'event-post-type'), $events->query_meta["no_results"], __('results for your search', 'event-post-type'), $events->query_meta["query"]);
}
if (count($events->posts) || count($events->stickies)):
	if (count($events->stickies)) :
	    foreach ($events->stickies as $post):
	    	$opts = array("class" => "sticky");
	    	print(EventPostType::get_formatted_event($post, $opts));
		endforeach;
	endif;
	if (count($events->posts)) :
	    foreach ($events->posts as $post):
	    	print(EventPostType::get_formatted_event($post));
		endforeach;
	endif;
	echo $events->paging["html"];

else : ?>

    <h2 class="center"><?php _e('Events', 'event-post-type'); ?></h2>
    <?php if ($events->query_type == "search") { ?>
	<p class="results_title"><?php _e('Sorry, nothing matched your search for', 'event-post-type'); ?> <strong><?php echo $events->query_meta["query"]; ?></strong></p>
	<?php } else  { ?>
    <p><?php _e('Sorry, but there are no events to show here.', 'event-post-type'); ?></p>
    <?php } ?>

<?php 
endif; 

get_footer(); ?>