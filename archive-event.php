<?php
/**
 * The template for displaying the Events archive
 */

/* first deal with the events json/ical feeds */
EventPostType::do_feed();

/* now get events to display */
$events = EventPostType::query_events();

get_header();
?>
		<div id="primary">
			<div id="content" role="main">
<?php
global $post;
if ($events->options["archive_search"]) {
	echo EventPostType::get_search_bar();
}
if ($events->options["archive_calendar"]) {
	echo EventPostType::get_events_calendar();
}
if (count($events->posts) || count($events->stickies)):
	if (count($events->stickies)) :
	    foreach ($events->stickies as $post):
    ?>

					<article class="event sticky" id="event-<?php $post->ID; ?>">
						<h2><a href="<?php echo get_permalink($post->ID) ?>" rel="bookmark" title="Permanent Link to <?php echo esc_attr($post->post_title); ?>">
						<?php echo apply_filters("the_title", $post->post_title); ?></a></h2>
						<small><?php echo EventPostType::get_date($post->ID); ?></small>
						<div class="entry">
						<?php the_excerpt('Read the rest of this entry »'); ?>
						</div>
						<?php //comments_popup_link('No Comments »', '1 Comment »', '% Comments »'); ?></p>
					</article>

	<?php 
		endforeach;
	endif;
	if (count($events->posts)) :
	    foreach ($events->posts as $post):
	?>

					<article class="event sticky" id="event-<?php $post->ID; ?>">
						<h3><a href="<?php echo get_permalink($post->ID) ?>" rel="bookmark" title="Permanent Link to <?php echo esc_attr($post->post_title); ?>">
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

    <h2 class="center">Not Found</h2>
    <p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; 
?>
			</div>
		</div>
<?php get_footer(); ?>