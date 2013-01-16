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
?>

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

		<div id="primary">
			<div id="content" role="main">

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h1 class="entry-title"><?php the_title(); ?></h1>

					<div class="entry-meta">
						<?php EventPostType::get_date(); ?>
					</div><!-- .entry-meta -->

					<div class="entry-content">
						<?php the_content(); ?>
					</div><!-- .entry-content -->

					<div class="entry-utility">
						<?php edit_post_link( __( 'Edit', 'event-post-type' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-utility -->
				</div><!-- #post-## -->

				<div id="nav-below" class="navigation">
					<div class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link', 'twentyten' ) . '</span> %title' ); ?></div>
					<div class="nav-next"><?php next_post_link( '%link', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link', 'twentyten' ) . '</span>' ); ?></div>
				</div><!-- #nav-below -->
			</div>
		</div>

				<?php comments_template( '', true ); ?>

<?php endwhile; // end of the loop. ?>
<?php get_footer(); ?>