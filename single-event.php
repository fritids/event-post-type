<?php
/**
 * Single event template
 * Requires the event post type plugin
 * 
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.3
 * @package Wordpress
 * @subpackage UoL_theme
 */

get_header(); 

if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h2 class="entry-title"><?php the_title(); ?></h2>

					<div class="entry-meta">
						<?php EventPostType::the_date(); ?>
						<?php EventPostType::posted_in(); ?>
					</div><!-- .entry-meta -->

					<div class="entry-content">
						<?php the_content(); ?>
					</div><!-- .entry-content -->

					<div class="entry-utility">
						<?php edit_post_link( __( 'Edit', 'event-post-type' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-utility -->
				</div><!-- #post-## -->

				<div id="nav-below" class="navigation">
					<div class="nav-previous"><?php EventPostType::previous_event_link( '%link', '<span class="meta-nav prev">' . _x( '&larr;', 'Previous event', 'event-post-type' ) . '</span> %title' ); ?></div>
					<div class="nav-next"><?php EventPostType::next_event_link( '%link', '%title <span class="meta-nav next">' . _x( '&rarr;', 'Next event', 'event-post-type' ) . '</span>' ); ?></div>
				</div><!-- #nav-below -->

		<?php comments_template( '', true ); ?>

<?php endwhile; // end of the loop. ?>
<?php get_footer(); ?>