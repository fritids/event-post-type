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
	echo $events->paging;

else : ?>

    <h2 class="center"><?php _e('Events', 'event-post-type'); ?></h2>
    <p class="center"><?php _e('Sorry, but there are no events to show here.', 'event-post-type'); ?></p>

<?php 
endif; 

get_footer(); ?>