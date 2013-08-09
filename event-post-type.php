<?php
/**
Plugin Name: EventPostType
Plugin URI: https://github.com/essl-pvac/event-post-type
Description: A Plugin for Wordpress which creates a new post type for Events
Version: 1.2
Author: Peter Edwards <p.l.edwards@leeds.ac.uk>
Author URI: http://essl-pvac.github.com
Text Domain: event-post-type
License: GPL2
*/

/*  Copyright 2011  Peter Edwards  (email : p.l.edwards@leeds.ac.uk)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * check to see if an earlier version of the plugin 
 * is activated (or another one with the same name)
 * and deactivate it.
 */
if (class_exists('EventPostType' )) {
	/* find the path to the plugin */
	$reflector = new ReflectionClass('EventPostType');
	if ($reflector !== false) {
		$plugin_path = $reflector->getFileName();
		/* deactivate the plugin */
		deactivate_plugins($plugin_path, true);
	}
} else {
/**
 * Class to create a custom post type for events
 * Adds the custom post type and additional editing fields for
 * the post editor to handle custom event properties. Provides
 * methods to use in templates and feeds. Registers custom
 * taxonomies for the new post type.
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.2
 * @package EventPostType_Plugin
 */
class EventPostType
{

	/**
	 * registers all actions in Wordpress API
	 */
	public static function register()
	{
		/* load the plugin options and help */
		require_once(dirname(__FILE__) . '/event-post-type-admin.php');

		/* run the upgrade routine */
		add_action('init', array(__CLASS__, 'upgrade'), 182 );

		/* i18n */
		add_action('plugins_loaded', array(__CLASS__, 'load_text_domain'));

		/************************************************
		 * Custom post type and taxonomy registration   *
		 ************************************************/

		/* initialise custom taxonomies */
		add_action( 'init', array(__CLASS__, 'register_event_taxonomies'), 180 );

		/* initialise custom post type */
		add_action( 'init', array(__CLASS__, 'register_event_post_type' ), 181 );

		/* add filter to update messages */
		add_filter( 'post_updated_messages', array(__CLASS__, 'updated_messages') );

		/* Use the admin_menu action to define custom editing boxes */
		add_action( 'admin_menu', array(__CLASS__, 'add_custom_boxes') );

		/* Use the quick_edit_custom_box action to add the sticky checkbox to the quick edit form */
		add_action('quick_edit_custom_box', array(__CLASS__, 'add_sticky_to_quickedit'), 10, 2);

		/* Use the save_post action to do something with the data entered */
		add_action( 'save_post', array(__CLASS__, 'save_postdata') );

		/* initialise custom rewrites for events */
		add_action( 'init', array(__CLASS__, 'add_rewrite_rules') );

		/* adds eventsJSON javascript variable to  head */		
		add_action('wp_head', array(__CLASS__,'add_json_feed_url'));



		/************************************************
		 * Admin and Dashboard related methods		  *
		 ************************************************/

		/* put columns on events list table and make sortable by date and filterable by category */
		add_action( 'manage_edit-event_columns', array(__CLASS__, 'add_event_columns') );
		add_action( 'manage_event_posts_custom_column', array(__CLASS__, 'show_event_columns'), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', array(__CLASS__, 'date_column_register_sortable') );
		add_filter( 'request', array(__CLASS__, 'date_column_orderby') );
		add_filter( 'parse_query', array(__CLASS__, 'sort_events_by_event_date')) ;


		/* Use the admin_print_scripts action to add scripts for theme options */
		add_action( 'admin_print_scripts', array(__CLASS__, 'plugin_admin_scripts') );
		/* Use the admin_print_styles action to add CSS for theme options */
 		add_action( 'admin_print_styles', array(__CLASS__, 'plugin_admin_styles') );

		/* add counts to the Right Now widget on the dashboard */
		add_action( 'right_now_content_table_end', array(__CLASS__, 'add_event_counts') );
		//add_action( 'right_now_discussion_table_end', array(__CLASS__, 'add_pending_event_counts') );



		/************************************************
		 * Templates, Shortcode and widgets             *
		 ************************************************/

		/* add filters for templates */
		add_filter('single_template', array(__CLASS__, 'single_template'));
		add_filter('archive_template', array(__CLASS__, 'archive_template'));
		add_filter('taxonomy_template', array(__CLASS__, 'taxonomy_template'));

		/* add classes */
		add_filter( 'body_class', array(__CLASS__, 'add_body_class') );
		add_filter( 'post_class', array(__CLASS__, 'add_post_class') );

		/* add scripts and styles for front-end */
		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'plugin_scripts') );
 		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'plugin_styles') );

 		/* handle paging */
		add_filter( 'pre_get_posts', array(__CLASS__, 'override_wp_paging') );

		/* add shortcode */
		add_shortcode( 'events', array(__CLASS__, 'events_shortcode') );
		
	}

	/************************************************
	 * Internationalization						 *
	 ************************************************/

	public static function load_text_domain()
	{
		load_plugin_textdomain( 'events-post-type', false, dirname(plugin_basename(__FILE__)) . '/lang/');
	}

	/************************************************
	 * Custom post type and taxonomy registration   *
	 ************************************************/

	/**
	 * initialise Events post type
	 */
	public static function register_event_post_type()
	{
		/* get plugin options */
		$options = EventPostTypeAdmin::get_plugin_options();

		if ( function_exists("register_post_type")) :
			$labels = array(
				'name' => _x('Events', 'Post type general name', 'event-post-type' ),
				'singular_name' => _x('Event', 'Post type singular name', 'event-post-type' ),
				'add_new' => _x('Add New', 'event', 'event-post-type' ),
				'add_new_item' => __( 'Add New Event', 'event-post-type' ),
				'edit_item' => __( 'Edit Event', 'event-post-type' ),
				'new_item' => __( 'New Event', 'event-post-type' ),
				'view_item' => __( 'View Event', 'event-post-type' ),
				'search_items' => __( 'Search Events', 'event-post-type' ),
				'not_found' =>  __( 'No Events found', 'event-post-type' ),
				'not_found_in_trash' => __( 'No Events found in Trash', 'event-post-type' ),
				'parent_item_colon' => '',
				'menu_name' => __( 'Events', 'event-post-type' )
			);
			$args = array(
				'labels' => $labels,
				'public' => true,
				'has_archive' => true,
				'menu_position' => 20,
				'menu_icon' => plugins_url('/img/EventPostType.png', __FILE__),
				'rewrite' => array('slug' => $options['ept_plugin_options']['post_type_slug'], 'with_front' => false),
				'supports' => array('title','editor','excerpt','thumbnail'),
				'taxonomies' => array( 'event_category', 'event_tag' )
			);
			register_post_type('event',$args);
		endif;
	}
	
	/**
	 * update messages so the word Event appears in them
	 */
	public static function updated_messages( $messages )
	{
		global $post, $post_ID;
		$messages['event'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Event updated. <a href="%s">View event</a>', 'event-post-type' ), esc_url( get_permalink($post_ID) ) ),
			2 => __( 'Custom field updated.', 'event-post-type' ),
			3 => __( 'Custom field deleted.', 'event-post-type' ),
			4 => __( 'Event updated.', 'event-post-type' ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __( 'Event restored to revision from %s', 'event-post-type' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Event published. <a href="%s">View event</a>', 'event-post-type' ), esc_url( get_permalink($post_ID) ) ),
			7 => __( 'Event saved.', 'event-post-type' ),
			8 => sprintf( __( 'Event submitted. <a target="_blank" href="%s">Preview event</a>', 'event-post-type' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( 'Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>', 'event-post-type' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i', 'event-post-type' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __( 'Event draft updated. <a target="_blank" href="%s">Preview event</a>', 'event-post-type' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}

	/**
	 * create taxonomies for events
	 */
	public static function register_event_taxonomies() 
	{
		/* get plugin options */
		$options = EventPostTypeAdmin::get_plugin_options();
	
		/* Add new hierarchical taxonomy (like categories) */
		$category_labels = array(
			'name' => _x('Event Categories', 'Event Category general name', 'event-post-type' ),
			'singular_name' => _x('Event Category', 'Event Category singular name', 'event-post-type' ),
			'search_items' => __( 'Search Event Categories', 'event-post-type' ),
			'all_items' => __( 'All Event Categories', 'event-post-type' ),
			'parent_item' => __( 'Parent Event Category', 'event-post-type' ),
			'parent_item_colon' => __( 'Parent Event Category:', 'event-post-type' ),
			'edit_item' => __( 'Edit Event Category', 'event-post-type' ), 
			'update_item' => __( 'Update Event Category', 'event-post-type' ),
			'add_new_item' => __( 'Add New Event Category', 'event-post-type' ),
			'new_item_name' => __( 'New Event Category', 'event-post-type' ),
			'menu_name' => __( 'Event Categories', 'event-post-type' )
		);  

		register_taxonomy('event_category', array('event'), array(
			'hierarchical' => true,
			'labels' => $category_labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $options['ept_plugin_options']['post_type_slug'] . '/' . $options['ept_plugin_options']['event_category_slug'], 'with_front' => false)
		));

		/* Add new non-hierarchical taxonomy (like post-tags) */
		$tag_labels = array(
			'name' => _x('Event Tags', 'Event Tag general name', 'event-post-type' ),
			'singular_name' => _x('Event Tag', 'Event Tag singular name', 'event-post-type' ),
			'search_items' => __( 'Search Event Tags', 'event-post-type' ),
			'all_items' => __( 'All Event Tags', 'event-post-type' ),
			'parent_item' => __( 'Parent Event Tag', 'event-post-type' ),
			'parent_item_colon' => __( 'Parent Event Tag:', 'event-post-type' ),
			'edit_item' => __( 'Edit Event Tag', 'event-post-type' ), 
			'update_item' => __( 'Update Event Tag', 'event-post-type' ),
			'add_new_item' => __( 'Add New Event Tag', 'event-post-type' ),
			'new_item_name' => __( 'New Event Tag', 'event-post-type' ),
			'separate_items_with_commas' => __( 'Separate event tags with commas', 'event-post-type' ),
			'add_or_remove_items' => __( 'Add or remove event tags', 'event-post-type' ),
			'choose_from_most_used' => __( 'Choose from the most used event tags', 'event-post-type' ),
			'menu_name' => __( 'Event Tags', 'event-post-type' )
		);  

		register_taxonomy('event_tag', array('event'), array(
			'hierarchical' => false,
			'labels' => $tag_labels,
			'show_ui' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array( 'slug' => $options['ept_plugin_options']['post_type_slug'] . '/' . $options['ept_plugin_options']['event_tag_slug'], 'with_front' => false )
		));
	}
	
	/*
	 * Adds custom sections to the event edit screens
	 */
	public static function add_custom_boxes()
	{
		add_meta_box( 'event_settings', 'Event Settings', array(__CLASS__, 'event_settings_custom_box'), 'event', 'side', 'high' );
		//add_meta_box( 'event_url', 'Event URL', array(__CLASS__, 'event_url_custom_box'), 'event', 'advanced', 'high' );
	}

	/**
	 * adds the sticky checkbox to the quick edit form
	 */
	public static function add_sticky_to_quickedit($col, $type)
	{
		if ( $col != 'event_is_sticky' || $type != 'event' ) {
        	return;
    	}
    	$out = '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><div class="inline-edit-group">';
    	$out .= '<label class="alignleft"><input type="checkbox" name="event_is_sticky" class="event_is_sticky_cb"><span class="checkbox-title"> ';
    	$out .= __( 'Stick this event to the top of the archive page', 'event-post-type' );
    	$out .= '</span></label></div></fieldset>';
    	echo $out;
	}

	/**
	 * extracts post_meta for a given post
	 * @param int post ID
	 * @return array
	 */
	public static function get_event_meta($postID)
	{
		$meta["event_start"] = get_post_meta($postID, 'event_start', true);
		$meta["event_end"] = get_post_meta($postID, 'event_end', true);
		$meta["event_url"] = get_post_meta($postID, 'event_url', true);
		$meta["event_allday"] = (bool) get_post_meta($postID, 'event_allday', true);
		$meta["event_is_sticky"] = (bool) get_post_meta($postID, 'event_is_sticky', true);
		return $meta;
	}
	/**
	 * Prints the fields for the custom date input sections of event pages
	 */
	public static function event_settings_custom_box()
	{
		global $post;
		$event_data = self::get_event_meta($post->ID);
		$start_date = $event_data["event_start"]? date("d/m/Y", $event_data["event_start"]): date("d/m/Y");
		$start_time = $event_data["event_start"]? date("h:iA", $event_data["event_start"]): "12:00PM";
		$end_date = $event_data["event_end"]? date("d/m/Y", $event_data["event_end"]): "";
		$end_time = $event_data["event_end"]? date("h:iA", $event_data["event_end"]): "";
		
		/* Use nonce for verification */
		printf('<input type="hidden" name="event_dates" id="event_dates" value="%s" />', wp_create_nonce('events_custom_dates'));
		/* fields for data entry */
		printf('<p id="event_dates_start" class="event_datep"><label for="event_dates_start_date">%s</label><br />', __( 'Event start', 'event-post-type' ));
		printf('<input type="text" id="event_dates_start_date" name="event_dates_start_date" value="%s" size="25" class="datepicker" />', $start_date);
		printf('<input type="text" id="event_dates_start_time" name="event_dates_start_time" value="%s" size="25" class="timepicker" /></p>', $start_time);
		$checked = $event_data["event_allday"]? ' checked="checked"': '';
		printf('<p id="event_dates_duration" class="event_datep"><label for="event_dates_allday">%s</label><input type="checkbox" id="event_dates_allday" name="event_dates_allday"%s>', __( 'All Day?', 'event-post-type' ), $checked);
		printf('<select name="event_dates_duration_minutes" id="event_dates_duration_minutes"><option value="null" selected="selected">%s&hellip;</option>', __( 'duration', 'event-post-type' ));
		for ($i = 1; $i < 20; $i++) {
			$hoursTotal = floor(($i * 30) / 60);
			$hoursStr = _n( 'hour ', 'hours ', $hoursTotal, 'event-post-type' );
			$optionValue = $hoursTotal > 0? $hoursTotal . " " . $hoursStr: "";
			$optionValue .= ($i % 2 != 0)? '30 ' . __( 'minutes', 'event-post-type'): '';
			echo '<option value="' . ($i * 30) . '">' . $optionValue . '</option>';
		}
		echo '</select></p>';
		printf('<p id="event_dates_end" class="event_datep"><label for="event_dates_end_date">%s</label><br />', __( 'Event end', 'event-post-type' ));
		printf('<input type="text" id="event_dates_end_date" name="event_dates_end_date" value="%s" size="25" class="datepicker" />', $end_date);
		printf('<input type="text" id="event_dates_end_time" name="event_dates_end_time" value="%s" size="25" class="timepicker" /></p>', $end_time);
		$checked = $event_data["event_is_sticky"]? ' checked="checked"': '';
		printf('<p class="event_datep"><input type="checkbox" id="event_is_sticky" name="event_is_sticky"%s><label for="event_is_sticky"> %s</label>', $checked, __( 'Stick this event to the top of the archive page', 'event-post-type' ));
		echo '<p class="event_datep"></p>';
		$url = (isset($event_data['event_url']) && trim($event_data['event_url']) != "" && self::check_url($event_data['event_url']))? trim($event_data['event_url']): "";
		printf('<p class="event_datep"><input id="event_url" name="event_url" type="text" value="%s" size="20" /><br /><em>%s</em></p>', esc_attr($url), __('Input a URL here if details for the event are held on another website.', 'event-post-type'));
		echo '<script type="text/javascript"><!--' . "\n";
		echo "var anytime_settings = {\n  'dateFormat':'%d/%m/%Y',\n  'timeFormat':'%h:%i%p'\n};";

/*
			this.dAbbr = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
	this.dNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
	this.mAbbr = [ 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec' ];
	this.mNames = [ 'January','February','March','April','May','June','July','August','September','October','November','December' ];
	*/
		echo "\n//--></script>";
	}

	/**
	 * Prints the field for the custom event URL
	 */
	public static function event_url_custom_box()
	{
		global $post;
		$event_data = self::get_event_meta($post->ID);
		$url = (isset($event_data['event_url']) && trim($event_data['event_url']) != "" && self::check_url($event_data['event_url']))? trim($event_data['event_url']): "";
		printf('<input id="event_url" name="event_url" type="text" value="%s" size="20" />', esc_attr($url));
		printf('<p><em>%s</em></p>', __('Input a URL here if details for the event are held on another website.', 'event-post-type'));
	}

	/**
	 * checks a URL for validity
	 * simple syntax check - just make sure we have http:// or https://
	 */
	public static function check_url($url = "")
	{
		$url = strtolower(trim(esc_url($url)));
		if (!preg_match('!^https?://(.*)$!', $url)) {
			return false;
		}
		return true;
	}

	
	/**
	 * Saves data from all custom sections
	 */
	public static function save_postdata( $post_id )
	{
		if ( isset($_POST['post_type']) && 'event' == $_POST['post_type'] ) {

			/* this will save the event_is_sticky checkbox state from quickedit */
			add_post_meta($post_id, 'event_is_sticky', isset($_POST["event_is_sticky"]), true) or update_post_meta($post_id, 'event_is_sticky', isset($_POST["event_is_sticky"]));

			/* verify this came from the main editing page and with proper authorization */
			if ( !isset($_POST['event_dates']) || !wp_verify_nonce( $_POST['event_dates'], 'events_custom_dates' )) {
				return $post_id;
			}
			/**
			 * verify if this is an autosave routine.
			 * If it is our form has not been submitted, so we dont want to do anything
			 */
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
				return $post_id;
			}
			/* Check permissions */
			if ( !current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
			/* save event dates in post_meta */
			$start_date = isset($_POST['event_dates_start_date'])? $_POST['event_dates_start_date']: "";
			$start_time = isset($_POST['event_dates_start_time'])? $_POST['event_dates_start_time']: "";
			$end_date = isset($_POST['event_dates_end_date'])? $_POST['event_dates_end_date']: "";
			$end_time = isset($_POST['event_dates_end_time'])? $_POST['event_dates_end_time']: "";
			$event_start = self::parse_date($start_date, $start_time);
			$event_end = self::parse_date($end_date, $end_time);
			$event_allday = isset($_POST["event_dates_allday"]);
			$event_url = (self::check_url($_POST["event_url"]))? trim($_POST["event_url"]): "";
			add_post_meta($post_id, 'event_start', $event_start, true) or update_post_meta($post_id, 'event_start', $event_start);
			add_post_meta($post_id, 'event_end', $event_end, true) or update_post_meta($post_id, 'event_end', $event_end);
			add_post_meta($post_id, 'event_allday', $event_allday, true) or update_post_meta($post_id, 'event_allday', $event_allday);
			add_post_meta($post_id, 'event_url', $event_url, true) or update_post_meta($post_id, 'event_url', $event_url);
		}
	}

	/**
	 * adds rewrite rules to enable:
	 * - calendar json feeds
	 * - custom taxonomy requests
	 * - date-based archives
	 */
	public static function add_rewrite_rules()
	{
		/* get plugin options */
		$options = EventPostTypeAdmin::get_plugin_options();

		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/([0-9]{4})/?$', 'index.php?post_type=event&event_year=$matches[1]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/([0-9]{4})/([0-9]{2})/?$', 'index.php?post_type=event&event_year=$matches[1]&event_month=$matches[2]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$', 'index.php?post_type=event&event_year=$matches[1]&event_month=$matches[2]&event_day=$matches[3]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/(json|ical|rss|atom)/?$', 'index.php?post_type=event&event_feed=$matches[1]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/(json|ical|rss|atom)/([0-9]{4})/([0-9]{2})/?$', 'index.php?post_type=event&event_feed=$matches[1]&event_year=$matches[2]&event_month=$matches[3]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/' . $options['ept_plugin_options']['post_type_future_slug'] . '/?$', 'index.php?post_type=event&event_future=1&future_page=1', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/' . $options['ept_plugin_options']['post_type_future_slug'] . '/([0-9]+)/?$', 'index.php?post_type=event&event_future=1&future_page=$matches[1]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/search/?(.*)$', 'index.php?post_type=event&event_query=$matches[1]', 'top');
		add_rewrite_tag('%event_query%', '(0|1)');
		add_rewrite_tag('%event_future%', '(0|1)');
		add_rewrite_tag('%future_page%', '[0-9]+');
		add_rewrite_tag('%event_feed%', '(json|ical|rss|atom)');
		add_rewrite_tag('%event_year%', '[0-9]{4}');
		add_rewrite_tag('%event_month%', '[0-9]{2}');
		add_rewrite_tag('%event_day%', '[0-9]{2}');
	}

	/**
	 * adds the JSON feed URL to the <head>
	 */
	public static function add_json_feed_url()
	{
		/* get plugin options */
		$options = EventPostTypeAdmin::get_plugin_options();
		/* only do this for the front-end */
		if (!is_admin()) {
			printf('<script type="text/javascript">var eventsJSON="%s/%s/json";</script>', get_bloginfo("url"), $options['ept_plugin_options']['post_type_slug']);
		}
	}

	/************************************************
	 * Admin and Dashboard related methods		  *
	 ************************************************/

	/**
	 * adds columns to the events listing table
	 * hooks into 'manage_edit-event_columns'
	 * @param array $posts_columns
	 * @return array $new_posts_columns
	 */
	public static function add_event_columns( $posts_columns )
	{
		$posts_columns['title'] = __( 'Event Title', 'event-post-type' );
		$posts_columns['event_is_sticky'] = __( 'Sticky', 'event-post-type' );
		$posts_columns['author'] = __( 'Author', 'event-post-type' );
		$posts_columns['event_category'] = __( 'Categories', 'event-post-type' );
		$posts_columns['event_tag'] = __( 'Tags', 'event-post-type' );
		$posts_columns['date'] = __( 'Date', 'event-post-type' );
		return $posts_columns;
	}

   	/**
	 * shows the event date column of the manage events table
	 * hooks into 'manage_event_posts_custom_column'
	 * @param $column_id
	 * @param $post_id
	 */
	public static function show_event_columns( $column_id, $post_id )
	{
		global $post;
		switch ($column_id) {
			case "date":
				echo self::get_date($post->ID);
				break;
			case "event_category":
			case "event_tag":
				$et = get_the_terms($post_id, $column_id);
				$url = "edit.php?post_status=all&post_type=event&$column_id=";				
				if (is_array($et)) {
					$term_links = array();
					foreach($et as $key => $term) {
						$term_links[] = '<a href="' . $url . $term->slug . '">' . $term->name . '</a>';
					}
					echo implode(' | ', $term_links);
				}
				break;
			case "event_is_sticky":
				$meta = self::get_event_meta($post->ID);
				if (isset($meta["event_is_sticky"]) && $meta["event_is_sticky"]) {
					$text = "Yes";
					$val = 1;
				} else {
					$text = "No";
					$val = 0;
				}
				printf('<span id="event_is_sticky_text_%d" data-event_is_sticky="%d">%s</span>', $post->ID, $val, $text);
				break;
		}
	}
	
	/**
	 * registers the date column as sortable
	 * @param $columns array of sortable columns
	 * @return new array of sortabkle columns with the event_date column added
	 */
	public static function date_column_register_sortable( $columns )
	{
		$columns["date"] = "date";
		return $columns;
	}
	
	/**
	 * enables Wordpress to order the event listing table
	 * by the event_date column
	 */
	public static function date_column_orderby( $vars )
	{
		if (isset($vars["orderby"]) && $vars["orderby"] == "date") {
			$vars = array_merge ($vars, array(
				"meta_key" => "event_start",
				"orderby" => "meta_value_num"
			));
		}
		return $vars;
   	}

	/**
	 * this is used to sort events by event date on the manage events
	 * page in admin. It hooks into the filter "request" and adds extra
	 * parameters to $query_vars when necessary 
	 * @param $query
	 */
	public static function sort_events_by_event_date($query)
	{
		global $pagenow;
		if (is_admin() && $pagenow=='edit.php' && $query->query_vars['post_type'] == 'event' && !isset($query->query_vars['orderby']))  {
			$query->query_vars['orderby'] = 'meta_value_num';
			$query->query_vars['meta_key'] = 'event_start';
			$query->query_vars['order'] = 'DESC';
		}
		return $query;
	}
	
	/**
	 * add scripts to admin
	 */
	public static function plugin_admin_scripts()
	{
		wp_enqueue_script('EventPostTypeAdminScript', plugins_url('/js/EventPostTypeAdmin.min.js', __FILE__), array('jquery', 'jquery-ui-tabs'));
		wp_enqueue_script('anytime', plugins_url('/js/anytimec.js', __FILE__), array('jquery'));
	}
	
	/**
	 * add styles to admin
	 */
	public static function plugin_admin_styles()
	{
		wp_enqueue_style('jquery-ui-smoothness', "https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/smoothness/jquery.ui.all.css", false, '1.8.21');
		wp_enqueue_style('eventsStyle', plugins_url('/css/EventPostTypeAdmin.min.css', __FILE__));
		wp_enqueue_style('anytime', plugins_url('/css/anytimec.css', __FILE__));
   	}

	/*
	 * adds a bit at the end of the content part of the Right Now widget
	 * on the dashboard to show event counts
	 * triggered by the right_now_content_table_end hook
	 */
	public static function add_event_counts()
	{
		$num_events = 0;
		$allEvents = self::get_events();
		foreach ($allEvents as $event) {
			if ($event->post_parent == 0 && $event->post_status == "publish") {
				$num_events++;
			}
		}
		$num = $num_events;
		$text = __( 'Events', 'event-post-type' );
		if ( current_user_can( 'edit_posts' ) ) {
			$num = '<a href="edit.php?post_type=event">' . $num . '</a>';
			$text = '<a href="edit.php?post_type=event">' . $text . '</a>';
		}
		echo '<tr>';
		echo '<td class="first b b-cats">' . $num . '</td>';
		echo '<td class="t cats">' . $text . '</td>';
		echo '</tr>';
	}
	
	/*
	 * adds a bit at the end of the discussion part of the Right Now widget
	 * on the dashboard to show pending event counts
	 * triggered by the right_now_discussion_table_end hook
	 */
	public static function add_pending_event_counts()
	{
		$num_events = 0;
		$allEvents = self::get_events();
		foreach ($allEvents as $event) {
			if ($event->post_parent == 0 && $event->post_status == "pending") {
				$num_events++;
			}
		}
		$num = $num_events;
		$text = __( 'Pending', 'event-post-type');
		if ( current_user_can( 'edit_posts' ) ) {
			$num = '<a href="edit.php?post_status=pending&post_type=event">' . $num . '</a>';
			$text = '<a class="waiting" href="edit.php?post_status=pending&post_type=event">' . $text . '</a>';
		}
		echo '<tr>';
		echo '<td class="first b b-cats">' . $num . '</td>';
		echo '<td class="t cats">' . $text . '</td>';
		echo '</tr>';
	}

	/**
	 * parses dates/times
	 * @param string date/time formatted as dd/mm/yyyy hh:mm
	 * @return integer UNIX timestamp
	 */
	public static function parse_date($datestr = "", $timestr = "")
	{
		if (trim($datestr) !== "") {
	  		if (preg_match("|([0-9]+)/([0-9]+)/([0-9]+)|", trim($datestr), $matches)) {
	  			$year = intval($matches[3]);
	  			$month = intval($matches[2]);
	  			$day = intval($matches[1]);
	  			$hour = 0;
	  			$minute = 0;
	  			$second = 0;
	  			if (trim($timestr) !== "") {
	  				if (preg_match("/([0-9]+):([0-9]+)(AM|PM)/", trim($timestr), $tmatches)) {
	  					$hour = intval($tmatches[1]);
	  					$minute = intval($tmatches[2]);
	  					if ($tmatches[3] == "PM" && $hour < 12) {
	  						$hour += 12;
	  					}
	  				}
	  			}
				$datetime = @mktime($hour, $minute, $second, $month, $day, $year);
				if ($datetime !== false) {
					return $datetime;
				}
			}
		}
		return "";
	}
	
	/**
	 * @var version
	 */
	public static $version = '1.2';
	/**
	 * upgrade function (called in the registration method)
	 */
	public static function upgrade()
	{
		$current_version = get_option("eventposttype_version");
		if ($current_version != self::$version) {
			switch ($current_version) {
				case false:
					/**
					 * before versioning was introduced, all event data was stored in an array
					 * in postmeta. Bad idea! Changed to make start, end and all_day separate
					 * postmeta entries so they could be used in queries
					 */
					$allEvents = self::get_events();
					if (count($allEvents)) {
						foreach ($allEvents as $event) {
							/* get the old metadata and add it to each event in the new format */
							$old_meta = get_post_meta($event->ID, 'eventmeta', true);
							if ($old_meta != "") {
								add_post_meta($event->ID, 'event_start', $old_meta["start_datetime"], true) or update_post_meta($event->ID, 'event_start', $old_meta["start_datetime"]);
								add_post_meta($event->ID, 'event_end', $old_meta["end_datetime"], true) or update_post_meta($event->ID, 'event_end', $old_meta["end_datetime"]);
								add_post_meta($event->ID, 'event_allday', $old_meta["all_day"], true) or update_post_meta($event->ID, 'event_allday', $old_meta["all_day"]);
							}
							delete_post_meta($event->ID, 'eventmeta');
							delete_post_meta($event->ID, 'event_date');
						}
					}
					/* go on to upgrade to 1.2 */
				case '1.1':
					/**
					 * changed events to use their own custom taxonomy (previously used Wordpress
					 * categories) - to help in generating pages of events in different categories
					 */
					$allEvents = self::get_events();
					register_taxonomy_for_object_type( 'post_tag', 'event' );
        			register_taxonomy_for_object_type( 'category', 'event' );
					if (count($allEvents)) {
						$newterms = array('event_category' => array(), 'event_tag' => array());
						$term_map = array('event_category' => array(), 'event_tag' => array());
						foreach ($allEvents as $event) {
							/* get existing categories/tags from core taxonomies and map to new taxonomies */
							$map = array(
								'category' => 'event_category',
								'post_tag'  => 'event_tag'
							);
							foreach ($map as $old_tax => $new_tax) {
								$old_terms = get_the_terms($event->ID, $old_tax);
								/* add the terms to the new taxonomies */
								if ($old_terms !== false && ! is_wp_error( $old_terms )) {
									foreach ($old_terms as $t) {
										if ($t->name == "Featured" || $t->name == "Home Page") {
											add_post_meta($event->ID, 'event_is_sticky', true, true) or update_post_meta($event->ID, 'event_is_sticky', true);
										} else {
											if (!isset($newterms[$new_tax][$t->slug])) {
												$newterms[$new_tax][$t->slug] = $t;
											}
											if (!isset($term_map[$new_tax][$event->ID])) {
												$term_map[$new_tax][$event->ID] = array();
											}
											$term_map[$new_tax][$event->ID][] = $t->term_id;
										}
									}
								}
								/* remove association with old taxonomy */
								wp_set_object_terms($event->ID, NULL, $old_tax );
							}
						}
						foreach ($newterms as $tax => $terms) {
							if (count($terms)) {
								foreach ($terms as $slug => $term) {
									$newterm = wp_insert_term(
										$term->name,
										$tax,
											array(
												"description" => $term->description,
												"slug" => $term->slug,
											)
										);
								}
							}
						}
						foreach ($term_map as $tax => $map) {
							foreach ($map as $eventID => $terms) {
								wp_set_post_terms($eventID, $terms, $tax, true);
							}
						}
					}
					break;
			}
			update_option("eventposttype_version", self::$version);
		}
	}


	/************************************************
	 * Template related methods                     *
	 ************************************************/
	
    /**
     * Determines whether the current view is an event archive.
     * @return boolean
     */
    public static function is_event() 
    {
	    global $wp_query;
	    /* check for events post type */
	    if ( isset($wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == "event") {
	    	return true;
	    }
	    return false;
	}

	/**
	 * gets the path for a template to be used for single events
	 * first looks for the corresponding templates in the theme/parent theme
	 * used by the single_template filter
	 * @param string single template path passed by Wordpress
	 * @retur string single template path (possibly altered)
	 */
	public static function single_template($single)
	{
		if (self::is_event()) {
			$theme_path = get_stylesheet_directory() . '/single-event.php';
			$template_path = get_template_directory() . '/single-event.php';
			$plugin_path = dirname(__FILE__) . '/single-event.php';
			if (file_exists($theme_path)) {
				return $theme_path;
			} elseif (file_exists($template_path)) {
				return $template_path;
			} elseif (file_exists($plugin_path)) {
				return $plugin_path;
			}
		}
		return $single;
	}
	
	/**
	 * gets the path for a template to be used for the events archive
	 * first looks for the corresponding templates in the theme/parent theme
	 * used by the archive_template filter
	 * @param string archive template path passed by Wordpress
	 * @return string archive template path (possibly altered)
	 */
	public static function archive_template($archive)
	{
		if (self::is_event()) {
			$theme_path = get_stylesheet_directory() . '/archive-event.php';
			$template_path = get_template_directory() . '/archive-event.php';
			$plugin_path = dirname(__FILE__) . '/archive-event.php';
			if (file_exists($theme_path)) {
				return $theme_path;
			} elseif (file_exists($template_path)) {
				return $template_path;
			} elseif (file_exists($plugin_path)) {
				return $plugin_path;
			}
		}
		return $archive;
	}

	/**
	 * gets the path for a template to be used for the events taxonomy archive
	 * first looks for the corresponding templates in the theme/parent theme
	 * used by the archive_template filter
	 * @param string archive template path passed by Wordpress
	 * @return string archive template path (possibly altered)
	 */
	public static function taxonomy_template($archive)
	{
		if (is_tax('event_category')) {
			$theme_path = get_stylesheet_directory() . '/category-event.php';
			$template_path = get_template_directory() . '/category-event.php';
			$plugin_path = dirname(__FILE__) . '/category-event.php';
			if (file_exists($theme_path)) {
				return $theme_path;
			} elseif (file_exists($template_path)) {
				return $template_path;
			} elseif (file_exists($plugin_path)) {
				return $plugin_path;
			}
		}
		if (is_tax('event_tag')) {
			$theme_path = get_stylesheet_directory() . '/tag-event.php';
			$template_path = get_template_directory() . '/tag-event.php';
			$plugin_path = dirname(__FILE__) . '/tag-event.php';
			if (file_exists($theme_path)) {
				return $theme_path;
			} elseif (file_exists($template_path)) {
				return $template_path;
			} elseif (file_exists($plugin_path)) {
				return $plugin_path;
			}
		}
		return $archive;
	}

   	/**
	 * adds a class to the body of the page - used in the body_class filter
	 * @param array of classes passed by Wordpress
	 * @return array of classes (possibly altered)
	 */
	public static function add_body_class($classes)
	{
		global $wp_query;
		if (self::is_event()) {
			$classes[] = 'event';
			if (is_single()) {
				$classes[] = 'single-event';
				$eventmeta = self::get_event_meta($wp_query->post->ID);
				if ($eventmeta) {
					if ($eventmeta["event_start"] > time()) {
						$classes[] = 'future-event';
					} elseif ($eventmeta["event_end"] < time()) {
						$classes[] = 'past-event';
					} else {
						$classes[] = 'current-event';
					}
				}
			}
		}
		return $classes;
	}

   	/**
	 * adds a class to individual posts - used in the post_class filter
	 * @param array of classes passed by Wordpress
	 * @return array of classes (possibly altered)
	 */
	public static function add_post_class($classes)
	{
		global $post;
		if (self::is_event()) {
			$classes = array_unique(array_merge($classes, self::get_classes($post->ID)));
		}
		return $classes;
	}

	/**
	 * returns classes based on a event date
	 */
	public static function get_classes($event_id)
	{
		$eventmeta = self::get_event_meta($event_id);
		$classes = array("event");
		if ($eventmeta["event_start"] > time()) {
			$classes[] = 'future-event';
		} elseif ($eventmeta["event_end"] < time()) {
			$classes[] = 'past-event';
		} else {
			$classes[] = 'current-event';
		}
		if ($eventmeta["event_is_sticky"]) {
			$classes[] .= 'sticky-event';
		}
	}

	/**
	 * add scripts to front-end
	 */
	public static function plugin_scripts()
	{
		$options = EventPostTypeAdmin::get_plugin_options();
		if ($options['ept_plugin_options']['enqueue_js']) {
			wp_enqueue_script('EventPostTypeScript', plugins_url('/js/EventPostType.js', __FILE__), array('jquery'));
		}
	}

	/**
	 * add styles to front-end
	 */
	public static function plugin_styles()
	{
		$options = EventPostTypeAdmin::get_plugin_options();
		if ($options['ept_plugin_options']['enqueue_css']) {
			wp_enqueue_style('EventPostTypeStyle', plugins_url('/css/EventPostType.css', __FILE__));
		}
	}

	/**
	 * overrides posts_per_page (paging is handled by the plugin)
	 * sets this to 1 to prevent wordpress 404 pages
	 * @param object wp_query object
	 */
	public static function override_wp_paging($query)
	{
    	if (!is_admin() && self::is_event()) {
       		$query->query_vars['posts_per_page'] = 1;
       	}
   		return $query;
	}

	/**
	 * main query handler for events
	 */
	public static function query_events()
	{
		/* if this is a feed request, do the feed */
		self::do_feed();
		/* otherwise, examine $wp_query to see what needs to be displayed */
		global $wp_query;
		/* return an object with the following properties */
		$events = (object) array(
			/* array of posts (events) */
			"posts" => array(),
			/* type of query this is */
			"query_type" => false,
			/* meta information for the type of query */
			"query_meta" => array(),
			/* total number of posts found */
			"total_posts" => 0,
			/* paging links for the query */
			"paging" => array("current" => "", "newer" => "", "older" => "", "future" => false, "html" => ""),
			/* plugin options */
			"options" => EventPostTypeAdmin::get_plugin_options(),
			/* store current events */
			"current" => array(),
			/* store past events */
			"past" => array(),
			/* store IDs of sticky events */
			"stickies" => array()
		);
		if (isset($wp_query->query_vars["event_year"])) {

			/* date based events archive page */

			$events->query_type = "date";

			$year = intVal($wp_query->query_vars["event_year"]);
			$events->query_meta["dateStr"] = $year;

			if (isset($wp_query->query_vars["event_month"])) {
				/* monthly archive */
				$month = intVal($wp_query->query_vars["event_month"]);
				$events->query_meta["dateStr"] = date_i18n("F, Y", mktime(1,1,1,$month,1,$year));
			} else {
				$month = false;
			}

			if (isset($wp_query->query_vars["event_day"])) {
				/* daily archive */
				$day = intVal($wp_query->query_vars["event_day"]);
				$events->query_meta["dateStr"] = date_i18n("jS F, Y", mktime(1,1,1,$month,$day,$year));
			} else {
				$day = false;
			}
			$events->posts = self::get_events_for($year, $month, $day);

			/* if there is only one event for this day, redirect to the single event page */
			if (count($events->posts) == 1) {
				wp_redirect(self::get_url($events->posts[0]->ID));
			} else {
				$events->total_posts = count($events->posts);
			}

			/* this type of query isn't paged, so we can bail here */
			return $events;

		} elseif (isset($wp_query->query_vars["event_query"])) {

			/* event search */
			$events->query_type = "search";

			$query = self::get_query_term();
			if (!empty($query)) {
				$allEvents = self::get_events();
				$query_terms = preg_split("/\s+/", strtolower($query));
				foreach ($allEvents as $e) {
					$match = false;
					foreach ($query_terms as $qt) {
						if ((strpos(strtolower($e->post_content), $qt) !== false) || strpos(strtolower($e->post_title), $qt) !== false) {
							$match = true;
						}
					}
					if ($match) {
						$events->posts[] = $e;
					}
				}
			}
			/* if there is only one event for this query, redirect to the single event page */
			if (count($events->posts) == 1) {
				wp_redirect(self::get_url($events->posts[0]->ID));
			}

			$events->query_meta = array(
				"no_results" => count($events->posts),
				"query" => $query
			);
			usort($events->posts, array(__CLASS__, 'sort_events_by_start_date_desc'));
			return $events;

		} elseif (is_tax()) {

			/* taxonomy based archives */
			$events->query_type = "taxonomy";
			/* get the ready-made taxonomy query from $wp_query (pity you can't just use it out of the box) */
			$tax_query = $wp_query->tax_query->queries;
			if ($wp_query->tax_query->relation) {
				$tax_query["relation"] = $wp_query->tax_query->relation;
			}
			$events->posts = self::get_events_by_term($tax_query);

			if (is_tax('event_tag')) {

				/* taxonomy archive for event tags */
				$events->query_meta["taxonomy"] = "event_tag";
				$events->query_meta["term"] = get_term_by( 'slug', $wp_query->query_vars["term"], "event_tag");

			} elseif (is_tax('event_category')) {

				/* taxonomy archive for event categories */
				$events->query_meta["taxonomy"] = "event_category";
				$events->query_meta["term"] = get_term_by( 'slug', $wp_query->query_vars["term"], "event_category");
			}
		} else {

			/* normal archives */
			$events->posts = self::get_events();
		}

		/* store total number of posts */
		$events->total_posts = count($events->posts);

		/* sort events into current (future), sticky and past containers */
		$stickycount = 0;
		foreach ($events->posts as $event) {
			if (self::is_current($event)) {
				$events->current[] = $event;
				if (self::is_sticky($event)) {
					$stickycount++;
				}
			} else {
				$events->past[] = $event;
			}
		}
		/* store the number of stickies on the front page to help paging calculations */
		$sticky_on_frontpage = min($stickycount, $events->options['ept_archive_options']['archive_frontpage_sticky']);


		/* sort events within each container */
		usort($events->current, array(__CLASS__, 'sort_events_by_start_date_asc'));
		usort($events->past, array(__CLASS__, 'sort_events_by_start_date_desc'));

		/**
		 * alter posts member variable to contain the events requested
		 * and set paging parameters
		 */
		if (isset($wp_query->query_vars["paged"]) && intVal($wp_query->query_vars["paged"]) > 0) {

			/* set posts variable to relevant events */
			if (count($events->past)) {
				/* first see if we need to offset when past events are placed on the first page */
				$past_on_frontpage = 0;
				if (count($events->current) < ($events->options['ept_archive_options']['archive_frontpage_events'] + $sticky_on_frontpage)) {
					/* find out how many past events were displayed on the front page */
					$past_on_frontpage = ($events->options['ept_archive_options']['archive_frontpage_events'] + $sticky_on_frontpage) - count($events->current);
				}
				$start = ($events->options['ept_archive_options']['archive_perpage'] * (intVal($wp_query->query_vars["paged"]) - 2)) + $past_on_frontpage;
				$events->posts = array_slice($events->past, $start, $events->options['ept_archive_options']["archive_perpage"]);
			}

			/* set paging variables */
			$events->paging["current"] = intVal($wp_query->query_vars["paged"]);
			$events->paging["newer"] = $events->paging["current"] - 1;
			$events->paging["older"] = (count($events->past) > ($past_on_frontpage + ($events->options['ept_archive_options']["archive_perpage"] * (intVal($wp_query->query_vars["paged"]) - 1))))? (intVal($wp_query->query_vars["paged"]) + 1): false;

		} elseif (isset($wp_query->query_vars["event_future"]) && ($wp_query->query_vars["event_future"] == 1)) {

			/* future events pages */
			$events->paging["future"] = true;
			if (count($events->current)) {
				/* remove sticky events shown on front page */
				if ($sticky_on_frontpage > 0) {
					$toremove = array_slice($events->stickies, 0, $sticky_on_frontpage);
					foreach ($toremove as $id) {
						for ($i = 0; $i < count($events->current); $i++) {
							if ($id == $events->current[$i]->ID) {
								array_splice($events->current, $i, 1);
							}
						}
					}
				}
				if (isset($wp_query->query_vars["future_page"]) && $wp_query->query_vars["future_page"] > 1 && (count($events->current) > ($events->options['ept_archive_options']["archive_frontpage_events"] + $events->options['ept_archive_options']["archive_perpage"]))) {
					/* future events (paged) */
					$events->paging["current"] = $wp_query->query_vars["future_page"];
					$events->paging["newer"] = (count($events->current) > ($events->options['ept_archive_options']["archive_frontpage_events"] + $sticky_on_frontpage + ($wp_query->query_vars["future_page"] * $events->options['ept_archive_options']["archive_perpage"])))? ($wp_query->query_vars["future_page"] + 1): false;
					$events->paging["older"] = $wp_query->query_vars["future_page"] - 1;
					$events->posts = array_slice($events->current, ($events->options['ept_archive_options']["archive_frontpage_events"] + $sticky_on_frontpage + ($events->options['ept_archive_options']["archive_perpage"] * ($events->paging["current"] - 1))), $events->options['ept_archive_options']["archive_perpage"]);
				} else {
					$events->paging["current"] = 1;
					$events->paging["newer"] = (count($events->current) > ($events->options['ept_archive_options']["archive_frontpage_events"] + $sticky_on_frontpage + $events->options['ept_archive_options']["archive_perpage"]))? 2: false;
					$events->paging["older"] = false;
					$events->posts = array_slice($events->current, $events->options['ept_archive_options']["archive_frontpage_events"]  + $sticky_on_frontpage, $events->options['ept_archive_options']["archive_perpage"]);
				}
			}
		} else {

			/**
			 * first page of events
			 * this contains a set number of stickies (in options), set number of posts (which
			 * may be different to the number per page), and all posts are taken from forthcoming events.
			 */
			$events->paging["current"] = 1;
			$events->paging["newer"] = (count($events->current) > ($events->options['ept_archive_options']["archive_frontpage_events"] + $sticky_on_frontpage))? 0: false;
			$events->paging["older"] = (count($events->past) > 0)? 2: false;

			/* sort out the events to display */
			if (count($events->current)) {

				/**
				 * identify sticky events in current container and
				 * remove them and place them in the stickies container
				 * up to the maximum of the archive_frontpage_sticky option
				 */
				$stickycount = 0;
				$toremove = array();
				if ($sticky_on_frontpage > 0) {
					foreach ($events->current as $e) {
						if ($stickycount < $sticky_on_frontpage) {
							$events->stickies[] = $e;
							$toremove[] = $e->ID;
							$stickycount++;
						}
					}
				}
				/* remove sticky events from current container */
				foreach ($toremove as $id) {
					for ($i = 0; $i < count($events->current); $i++) {
						if ($id == $events->current[$i]->ID) {
							array_splice($events->current, $i, 1);
						}
					}
				}

				/* fill up the posts */
				$events->posts = array_slice($events->current, 0, $events->options['ept_archive_options']["archive_frontpage_events"]);

				/* not enough posts for the front page? get some past ones to display */
				if (count($events->posts) < $events->options['ept_archive_options']["archive_frontpage_events"]) {
					$tofill = $events->options['ept_archive_options']["archive_frontpage_events"] - count($events->posts);
					for ($i = 0; $i < $tofill; $i++) {
						if (isset($events->past[$i])) {
							$events->posts[] = $events->past[$i];
						}
					}
				}
			} else {
				/* no current events to display - show past events */
				$events->posts = array_slice($events->past, 0, ($events->options['ept_archive_options']["archive_frontpage_events"]));
			}
		}
		$events->paging["html"] = self::get_paging_links($events);

		return $events;
	}

	/**
	 * sorting functions for events
	 * TODO: refactor as a single method which returns an anonymous function
	 * and takes the sort direction (and meta key?) as an argument
	 */
	public static function sort_events_by_start_date_asc($a, $b)
	{
		if ($a->meta["event_start"] == $b->meta["event_start"]) { 
			return 0;
		}
		return ($a->meta["event_start"] < $b->meta["event_start"])? -1: 1;
	}
	public static function sort_events_by_start_date_desc($a, $b)
	{
		if ($a->meta["event_start"] == $b->meta["event_start"]) { 
			return 0;
		}
		return ($a->meta["event_start"] > $b->meta["event_start"])? -1: 1;
	}

	/**
	 * gets the query term for the events search
	 */
	public static function get_query_term()
	{
		global $wp_query;
		$terms = "";
		if (isset($_POST["eventsearch"])) {
			/* search not using query term in URL */
			$terms = trim($_POST["eventsearch"]);
		} elseif (isset($wp_query->query_vars["event_query"]) && trim($wp_query->query_vars["event_query"]) != "") {
			$terms = trim($wp_query->query_vars["event_query"]);
		}
		return $terms;
	}

	/**
	 * returns links to different events pages
	 * @param object events object
	 * @return string HTML - links enclosed in <div> tags with classes "older-events"
	 * and "newer-events", themselves enclosed in a div with class "events-navigation"
	 */
	public static function get_paging_links($events)
	{
		$out = '';
		if ($events->paging["future"]) {
			/* links between pages in future events */
			if ($events->paging["newer"] !== false) {
				/* events to display in the more distant future */
				$out .= sprintf('<div class="nav-previous"><a href="%s/%s/%s/%s">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->options['ept_plugin_options']['post_type_future_slug'], $events->paging["newer"], __('&larr; Future Events', 'event-post-type'));
			}
			if ($events->paging["older"] === false || $events->paging["older"] == 1) {
				/* older events are on the main events archive page */
				$out .= sprintf('<div class="nav-next"><a href="%s/%s/">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], __('&larr; Current Events &rarr;', 'event-post-type'));
			} else {
				/* future events are being paged */
				$out .= sprintf('<div class="nav-next"><a href="%s/%s/%s/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->options['ept_plugin_options']['post_type_future_slug'], $events->paging["older"], __('Older Events &rarr;', 'event-post-type'));
			}
		} else {
			if ($events->paging["newer"] !== false) {
				switch ($events->paging["newer"]) {
					case 0:
						/* link to future events page from main events archive */
						$out .= sprintf('<div class="nav-previous"><a href="%s/%s/%s/">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->options['ept_plugin_options']['post_type_future_slug'], __('&larr; Future Events', 'event-post-type'));
						break;
					case 1:
						/* link to main events archive from first page of past events */
						$out .= sprintf('<div class="nav-previous"><a href="%s/%s/">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], __('&larr; Current Events', 'event-post-type'));
						break;
					default:
						/* link to more recent past events */
						$out .= sprintf('<div class="nav-previous"><a href="%s/%s/page/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->paging["newer"], __('&larr; More Recent Events', 'event-post-type'));
						break;
				}
			}
			if ($events->paging["older"] !== false) {
				switch ($events->paging["older"]) {
					case 1:
						$out .= sprintf('<div class="nav-previous"><a href="%s/%s/page/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->paging["older"], __('&larr; Current Events', 'event-post-type'));
						break;
					default:
						$out .= sprintf('<div class="nav-next"><a href="%s/%s/page/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->paging["older"], __('Older Events &rarr;', 'event-post-type'));
						break;
				}
			}
		}
		if ($out) {
			$out = '<div class="nav-below">' . $out . '</div>';
		}
		return $out;
	}

	/**
	 * checks to see if an event is current, i.e. if the event starts 
	 * in the future or has started but not finished yet
	 * @param object event
	 */
	public static function is_current($event, $now = false)
	{
		if ($now === false) {
			$now = time();
		}
		if ($event->meta["event_start"] > $now) {
			/* event starts in the future */
			return true;
		} elseif (isset($event->meta["event_end"]) && $event->meta["event_start"] <= $now && $event->meta["event_end"] > $now) {
			/* event has started but not finished */
			return true;
		} elseif ($event->meta["event_allday"]) {
			$end = mktime(0, 0, 0, date("n", $event->meta["event_start"]), (date("j", $event->meta["event_start"]) + 1), date("Y", $event->meta["event_start"]));
			if ($event->meta["event_start"] > $now || ($event->meta["event_start"] <= $now && $end > $now)) {
				return true;
			}
		}
	}
	/* checks to see if an event is sticky */
	public static function is_sticky($event)
	{
		return (bool) $event->meta["event_is_sticky"];
	}	

	/**
	 * gets events by year, month or day
	 */
	public static function get_events_for($year = false, $month = false, $day = false)
	{		
		/* get events */
		$allEvents = self::get_events();
		/* if called with no arguments, return all events */
		if ($year === false) {
			return $allEvents;
		}
		/* set start and end timestamps according to request */
		if ($day !== false && $month !== false) {
			/* a specific day has been requested */
			$start = mktime(0, 0, 0, $month, $day, $year);
			$end = mktime(0, 0, 0, $month, ($day + 1), $year);
		} elseif ($day === false && $month !== false) {
			/* a month has been requested */
			$start = mktime(0, 0, 0, $month, 1, $year);
			$end = mktime(0, 0, 0, ($month + 1), 1, $year);
		}
		/* store events for the given period in here */
		$events = array();
		/* loop through events and add today's events to the $events array */
		if (count($allEvents)) {
			foreach ($allEvents as $evt) {
				/* events which start between the start and end times */
				if ($evt->meta["event_start"] && $evt->meta["event_start"] >= $start && $evt->meta["event_start"] < $end) {
					$events[] = $evt;
				/* catch events which start before the start time, but end afterwards */
				} elseif ($evt->meta["event_start"] && $evt->meta["event_start"] < $start && $evt->meta["event_end"] && $evt->meta["event_end"] >= $start) {
					$events[] = $evt;
				}
			}
		}
		return $events;
	}

	/**
	 * gets events by taxonomy
	 */
	public static function get_events_by_term($tax_query)
	{
		/* set arguments for query */
		$args = array(
			"numberposts" => -1,
			"post_type" => "event",
			"post_status" => "publish",
			"meta_key" => "event_start",
			"orderby" => "meta_value_num",
        	"order" => "ASC",
        	"tax_query" => $tax_query,
           	"nopaging" => true
		);
		$allTaxEvents = get_posts($args);
		/* add custom meta */
		for ($i = 0; $i < count($allTaxEvents); $i++) {
	 		$allTaxEvents[$i]->meta = self::get_event_meta($allTaxEvents[$i]->ID);
		}
		return $allTaxEvents;
	}
	
	/**
	 * gets all events and adds event metadata
	 * @return array
	 */
	public static function get_events()
	{
		$allEvents = wp_cache_get( 'allEvents' );
		if ($allEvents == false) {
			$args = array(
				"numberposts" => -1,
				"post_type" => "event",
				"post_status" => "publish",
				"meta_key" => "event_start",
				"orderby" => "meta_value_num",
            	"order" => "ASC",
            	"nopaging" => true
			);
			/* get all events */
			$allEvents = get_posts($args);
			/* add custom meta */
			for ($i = 0; $i < count($allEvents); $i++) {
	 			$allEvents[$i]->meta = self::get_event_meta($allEvents[$i]->ID);
			}
			wp_cache_set( 'allEvents', $allEvents);
		}
		return $allEvents;
	}

	/**
	 * handles feeds for JSON and iCAL output
	 */
	public static function do_feed()
	{
		global $wp_query;
		if (isset($wp_query->query_vars["event_feed"]) && in_array(strtolower($wp_query->query_vars["event_feed"]), array("ical", "json"))) {
			$format = strtolower($wp_query->query_vars["event_feed"]);
			/* feeds can accept additional parameters for month and year */
			$month = isset($wp_query->query_vars["event_month"])? $wp_query->query_vars["event_month"]: false;
			$year = isset($wp_query->query_vars["event_year"])? $wp_query->query_vars["event_year"]: false;
			echo self::get_events_feed($format, $month, $year);
			exit();
		}
	}

	
	/**
	 * gets a feed of events
	 */
	public static function get_events_feed($format = "json", $month = false, $year = false)
	{
		/* get all events and filter on month and year if necessary */
		$feedEvents = self::get_events_for($year, $month);
		$host = @parse_url(home_url());
		$host = $host['host'];
		$self = esc_url('http' . ( (isset($_SERVER['https']) && $_SERVER['https'] == 'on') ? 's' : '' ) . '://'	. $host	. stripslashes($_SERVER['REQUEST_URI']));
		$events = array();
		if (count($feedEvents)) {
			foreach ($feedEvents as $event) {
				$eventObj = new stdClass();
				$eventObj->id = $event->ID;
				$eventObj->title = $event->post_title;
				$eventObj->allDay = ($event->meta["event_allday"])? true: false;
				$eventObj->start_unixtimestamp = intVal($event->meta["event_start"]);
				$eventObj->end_unixtimestamp = intVal($event->meta["event_end"]);
				$eventObj->start_jstimestamp = ($eventObj->start_unixtimestamp * 1000);
				$eventObj->end_jstimestamp = ($eventObj->end_unixtimestamp * 1000);
				$eventObj->start = date('c', $eventObj->start_unixtimestamp);
				$eventObj->end = date('c', $eventObj->end_unixtimestamp);
				$eventObj->dateStr = esc_js(strip_tags(self::get_date($event->ID)));
				$eventObj->content = $eventObj->dateStr . "\n" . esc_js(apply_filters('the_excerpt_rss', $event->post_content));
				$eventObj->url = get_permalink($event->ID);
				$eventObj->publish_date = $event->post_date;
				$event_categories = wp_get_post_categories($event->ID);
				$classes = array();
				foreach($event_categories as $c){
					$cat = get_category( $c );
					$classes[] = $cat->slug;
				}
				if (count($classes)) {
					if (count($classes) == 1){
						$eventObj->className = $classes[0];
					} else {
						$eventObj->className = $classes;
					}
				}
				$events[] = $eventObj;
			}
		}
		switch(strtolower($format))
		{
			case "json":
				return json_encode($events);
				break;
			case "ical":
				$out = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//EventPostType-Wordpress-Plugin//NONSGML v1.2//EN\n";
				foreach ($events as $event) {
					$out .= "BEGIN:VEVENT\n";
					$out .= sprintf("UID:%s\n", $event->id);
					$out .= sprintf("DTSTAMP:%sZ\n", str_replace(array(" ","-",":"), array("T", "", ""), $event->publish_date ));
					$out .= sprintf("DTSTART:%s\n", date("Ymd\THis\Z", $event->start_ts));
					if ($event->allDay) {
						$out .= "DURATION:P1D\n";
					} else {
						$out .= sprintf("DTEND:%s\n", date("Ymd\THis\Z", $event->end_ts));
					}
					$out .= sprintf("SUMMARY:%s\n", $event->title);
					$out .= "END:VEVENT\n";
				}
				$out .= "END:VCALENDAR\n";
				return $out;
			case "rss":
				header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
				echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
				echo '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/"';
				do_action('rss2_ns');
				echo '><channel>';
				printf('<title>%s - %s</title>', get_bloginfo_rss('name'), _('Events', 'event-post-type'));
				printf('<atom:link href="%s" rel="self" type="application/rss+xml" />', $self_link);
				printf('<link>%s</link>', get_bloginfo_rss('url'));
				printf('<lastBuildDate>%s</lastBuildDate>', mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false));
				printf('<language>%s</language>', get_bloginfo_rss( 'language' ));
				printf('<sy:updatePeriod>%s</sy:updatePeriod>', apply_filters( 'rss_update_period', 'hourly' ));
				printf('<sy:updateFrequency>%s</sy:updateFrequency>', apply_filters( 'rss_update_frequency', '1' ));
				foreach ($events as $event) {
					print('<item>');
					printf('<title>%s</title>', apply_filters( 'the_title_rss', $event->title ));
					printf('<link>%s</link>', apply_filters('the_permalink_rss', EventPostType::get_url($event->id)));
					printf('<pubDate>%s</pubDate>', mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), $event->id));
					printf('<dc:creator>%s</dc:creator>', get_the_author_meta('display_name', $event->id));
					printf('<description><![CDATA[%s]]></description>', $event->content);
					print('</item>');
				}
				print('</channel></rss>');
				break;
			default:
				return "";
				break;
		}
	}

	/**
	 * gets HTML for the search bar
	 */
	public static function get_search_bar()
	{
		global $wp_query;
		$options = EventPostTypeAdmin::get_plugin_options();
		$event_categories = get_terms('event_category');
		$event_tags = get_terms('event_tag');
		$out = '<div class="events-search-bar">';
		if (count($event_categories)) {
			if (is_tax('event_category')) {
				/* already filtering */
				$term = get_term_by( 'slug', $wp_query->query_vars["term"], "event_category");
				$out .= sprintf('<div class="event-filtered"><p>%s <span class="current-term">%s</span> <a class="remove-filter" href="%s/%s">%s</a></p></div>', __('Filtering events by category', 'event-post-type'), $term->name, get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"], __('remove filter', 'event-post-type'));
			} else {
				if (!is_tax('event_tag')) {
					$out .= sprintf('<div class="event-filter"><p>%s <a class="add-filter" href="#">%s</a></p>', __('Filter events by category', 'event-post-type'), __('add filter', 'event-post-type'));
					$out .= '<ul class="event-taxonomy-list">';
					foreach($event_categories as $term) {
						$sel = is_tax('event_category', $term->slug)? ' class="active"': '';
						$out .= sprintf('<li><a href="%s/%s/%s/%s"%s>%s</a></li>', get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"], $options['ept_plugin_options']['event_category_slug'], $term->slug, $sel, $term->name);
					}
					$out .= '</ul>';
					$out .= '</div>';
				}
			}
		}
		if (count($event_tags)) {
			if (is_tax('event_tag')) {
				/* already filtering */
				$term = get_term_by( 'slug', $wp_query->query_vars["term"], "event_tag");
				$out .= sprintf('<div class="event-filtered"><p>%s <span class="current-term">%s</span> <a class="remove-filter" href="%s/%s">%s</a></p></div>', __('Filtering events by tag', 'event-post-type'), $term->name, get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"], __('remove filter', 'event-post-type'));
			} else {
				if (!is_tax('event_category')) {
					$out .= sprintf('<div class="event-filter"><p>%s <a class="add-filter" href="#">%s</a></p>', __('Filter events by tag', 'event-post-type'), __('add filter', 'event-post-type'));
					$out .= '<ul class="event-taxonomy-list">';
					foreach($event_tags as $term) {
						$sel = is_tax('event_tag', $term->slug)? ' class="active"': '';
						$out .= sprintf('<li><a href="%s/%s/%s/%s"%s>%s</a></li>', get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"], $options['ept_plugin_options']['event_tag_slug'], $term->slug, $sel, $term->name);
					}
					$out .= '</ul>';
					$out .= '</div>';
				}
			}

		}
		$query = self::get_query_term();
		$out .= sprintf('<div class="event-search"><form action="%s/%s/search" method="post"><input type="text" name="eventsearch" placeholder="%s" class="searchinput" value="%s" /><input type="submit" value="Go" class="searchsubmit" /></form></div>', get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"], __("Search events", "event-post-type"), $query);
		$out .= '<br style="clear:both" />';
		$out .= '</div>';
		if (has_filter('event-search-bar')) {
			return apply_filters('event-search-bar', $out);
		}
		return $out;
	}
	
	/**
	 * gets HTML and script to include FullCalendar
	 */
	public static function get_full_events_calendar($opts)
	{
		/* get plugin options */
		$options = EventPostTypeAdmin::get_plugin_options();
		
		$out = "<div id=\"events-calendar\"></div>\n";
		$out .= "<script type=\"text/javascript\">\n";
		$out .= "jQuery(function($){\n";
		$out .= "  $('head').append('<link/>');\n";
		$out .= "  var css = $('head').children(':last');\n";
		$out .= "  css.attr({rel:'stylesheet',type:'text/css',href:'" . plugins_url("css/fullcalendar.css", __FILE__) . "'});\n";
		$out .= "  $.getScript('" . plugins_url("js/fullcalendar.min.js", __FILE__) . "', function(){\n";
		$out .= "	setTimeout('init_fullcalendar()', 250);\n";
		$out .= "  });\n";
		$out .= "});\n";
		$out .= "function init_fullcalendar(){\n";
		$out .= "  jQuery('#events-calendar').fullCalendar({events:{'url':'" . get_bloginfo("url") . "/" . $options['ept_plugin_options']["post_type_slug"] . "/json',startParam:'events_start',endParam:'events_end',type:'POST'}});\n";
		$out .= "}\n";
		$out .= "</script>\n";
		return $out;
	}
	
	/**
	 * gets HTML and script to include events calendar
	 */
	public static function get_events_calendar($start = false)
	{
		/* get plugin options */
		$options = EventPostTypeAdmin::get_plugin_options();

		$days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
		$allEvents = self::get_events();
		if ($start) {
			$this_month = mktime(0, 0, 0, date('n', $start), 1, date('Y', $start));
			$prev_month = mktime(0, 0, 0, (date('n', $start) - 1), 1, date('Y', $start));
			$next_month = mktime(0, 0, 0, (date('n', $start) + 1), 1, date('Y', $start));
		} else {
			$this_month = mktime(0, 0, 0, date("n"), 1, date("Y"));
			$prev_month = mktime(0, 0, 0, (date("n") - 1), 1, date("Y"));
			$next_month = mktime(0, 0, 0, (date("n") + 1), 1, date("Y"));
		}
		$days_this_month = date("t", $this_month);
		$days_prev_month = date("t", $prev_month);
		$weekday = date('w', $this_month);
		$dow = 1;
		/* generate a HTML based calendar for the current date */
		$out = "<div id=\"eventsCalendar\">\n  <div class=\"ec-container\">\n";
		$out .= "	<h4>" . date("F Y", $this_month) . "</h4>\n";
		$out .= "	<div id=\"ec-calendar\">\n	  <table cellspacing=\"2\" class=\"eventsCalendar\">\n		<thead>\n		  <tr>";
		foreach ($days as $day) {
			$class = (strpos($day, "S") === 0)? "weekend": "weekday";
			$out .= sprintf('<th scope="col" abbr="%s" title="%s" class="%s">%s</th>', $day, $day, $class, substr($day, 0, 1));
		}
		$out .= "</tr>\n		</thead>\n		<tbody>\n";
		$out .= "		  <tr>";
		if ($weekday != 1) {
			/* month doesn't start on Monday - print dates from previous month */
			$offset = ($weekday == 0)? 5: -((1 - $weekday) + 1);
			for ($day = ($days_prev_month - $offset); $day <= $days_prev_month; $day++) {
				$today = mktime(0, 0, 0, date("n", $prev_month), $day, date("Y", $prev_month));
				$class = "other-month ";
				$class .= ($dow < 6)? "weekday": "weekend";
				$events = self::get_events_for(date("Y", $prev_month), date("n", $prev_month), $day);
				if (count($events)) {
					$titles = array();
					foreach ($events as $evt) {
						$titles[] = esc_attr($evt->post_title);
					}
					$title = implode(", ", $titles);
					$out .= sprintf('<td class="%s eventday" title="%s"><a title="%s" href="%s/%s/%s/%s/%s">%s</a></td>', $class, esc_attr($title), esc_attr($title), get_bloginfo("url"), $options['ept_plugin_options']["post_type_slug"], date("Y", $today), date("m", $today), date("d", $today), $day);
				} else {
					$out .= '<td class="' . $class . '">' . $day . '</td>';
				}
				$dow++;
			}
		}
		/* print dayes for current month */
		for ($day = 1; $day <= $days_this_month; $day++) {
			if($dow > 7) {
				$dow = 1;
				$out .= "</tr>\n		  <tr>";
			}
			$today = mktime(0, 0, 0, date("n", $this_month), $day, date("Y", $this_month));
			$class = "current-month ";
			$class .= ($dow < 6)? "weekday": "weekend";
			$events = self::get_events_for(date("Y", $this_month), date("n", $this_month), $day);
			if (count($events)) {
				$titles = array();
				foreach ($events as $evt) {
					$titles[] = esc_attr($evt->post_title);
				}
				$title = implode(", ", $titles);
					$out .= sprintf('<td class="%s event" title="%s"><a title="%s" href="%s/%s/%s/%s/%s">%s</a></td>', $class, esc_attr($title), esc_attr($title), get_bloginfo("url"), $options['ept_plugin_options']["post_type_slug"], date("Y", $today), date("m", $today), date("d", $today), $day);
			} else {
				$out .= '<td class="' . $class . '">' . $day . '</td>';
			}
			$dow++;
		}
		if ($dow < 8) {
			$days_to_go = 8 - $dow;
			/* fill in remaining days from next month */
			for ($day = 1; $day <= $days_to_go; $day++) {
				$today = mktime(0, 0, 0, date("n", $next_month), $day, date("Y", $next_month));
				$class = "other-month ";
				$class .= ($dow < 6)? "weekday": "weekend";
				$events = self::get_events_for(date("Y", $next_month), date("n", $next_month), $day);
				if (count($events)) {
					foreach ($events as $evt) {
						$titles[] = esc_attr($evt->post_title);
					}
					$title = implode(", ", $titles);
					$out .= sprintf('<td class="%s event" title="%s"><a title="%s" href="%s/%s/%s/%s/%s">%s</a></td>', $class, esc_attr($title), esc_attr($title), get_bloginfo("url"), $options['ept_plugin_options']["post_type_slug"], date("Y", $today), date("m", $today), date("d", $today), $day);
				} else {
					$out .= '<td class="' . $class . '">' . $day . '</td>';
				}
				$dow++;
			}
		}
		$out .= "</tr>\n		</tbody>\n	  </table>\n	</div>\n  </div>\n</div>";
		return $out;
	}

	/********************************************
	 * WIDGETS AND SHORTCODES                   *
	 ********************************************/

	/**
	 * function to retrieve a formatted list of events
	 * @param array $options
	 * @return array
	 */
	public static function events_output($opts = array())
	{
		/* get default options and merge with passed options */
		$default_options = self::get_default_display_options();
		$opts = shortcode_atts($default_options, $opts);

		/* if a calendar has been requested, return it */
		if ($opts["events_format"] == "calendar") {
			if ($opts["events_start"]) {
				return self::get_events_calendar($opts["events_start"]);
			} else {
				return self::get_events_calendar();
			}
		}

		/* get events */
		$allEvents = self::get_events();

		/* bail if no events found */
		if (!count($allEvents)) {
			return "";
		}

		/* filter by date */
		if ($opts["events_start"]) {
			$tmp_events = array();
			foreach ($allEvents as $e) {
				if (self::is_current($e, $opts["events_start"])) {
					$tmp_events[] = $e;
				}
			}
			$allEvents = $tmp_events;
		}
		if ($opts["events_end"]) {
			$tmp_events = array();
			foreach ($allEvents as $e) {
				if (($e->meta["event_start"] < $opts["events_end"]) || ($e->meta["event_end"] < $opts["events_end"])) {
					$tmp_events[] = $e;
				}
			}
			$allEvents = $tmp_events;
		}

		/* filter by category / tag */
		$tmp_events = array();
		foreach ($allEvents as $evt) {
			foreach(array("category", "tag") as $tax) {
				if (isset($opts["events_" . $tax . "_filter"]) && is_array($opts["events_" . $tax . "_filter"]) && count($opts["events_" . $tax . "_filter"])) {
					foreach ($opts["events_" . $tax . "_filter"] as $term) {
						if (has_term($term, "event_" . $tax, $evt) && !isset($tmp_events[$evt->ID])) {
							$tmp_events[$evt->ID] = $evt;
						}
					}
				}
			}
		}
		if (!count($tmp_events)) {
			return "";
		} else {
			$allEvents = array_values($tmp_events);
		}

		/* exclude */
		$exclude = (!empty($opts["exclude"]))? explode(",", $opts["exclude"]): array();
		if (count($exclude)) {
			$tmp_events = array();
			foreach ($allEvents as $evt) {
				if (!in_array($evt->ID, $exclude)) {
					$tmp_events[] = $evt;
				}
			}
			$allEvents = $tmp_events;
		}

		/* bail if no events left */
		if (!count($allEvents)) {
			return "";
		}

		/* store returned events in here */
		$current_events = array();
		$sticky_events = array();
		$past_events = array();

		/* sort events into sets */
		foreach ($allEvents as $evt) {
			if (self::is_current($evt)) {
				if ($opts["events_prioritise_sticky"] && self::is_sticky($evt)) {
					$sticky_events[] = $evt;
				} else {
					$current_events[] = $evt;
				}
			} else {
				$past_events[] = $evt;
			}
		}

		/* sort the sets of events */
		usort($past_events, array(__CLASS__, 'sort_events_by_start_date_desc'));
		usort($current_events, array(__CLASS__, 'sort_events_by_start_date_asc'));
		usort($sticky_events, array(__CLASS__, 'sort_events_by_start_date_asc'));

		/* now populate the resulting set */
		$events = array();
		while (count($events) < $opts["events_num"]) {
			if (count($sticky_events)) {
				$events[] = array_pop($sticky_events);
			} else {
				if (count($current_events)) {
					$events[] = array_pop($current_events);
				} else { 
					if (count($past_events)) {
						$events[] = array_pop($past_events);
					} else {
						break;
					}
				}
			}
		}

		/* now get the output */
		$out = "";
		if (count($events)) {
			$out .= sprintf('<ul class="events-list %s">', $cls);
			foreach ($events as $evt) {
				$out .= '<li>' . self::get_formatted_event($evt, $opts) . '</li>';
			}
			$out .= '</ul>';
		}
		return $out;
	}

	/**
	 * returns a single formatted event
	 */
	public static function get_formatted_event($evt, $opts = array())
	{
		$options = EventPostTypeAdmin::get_plugin_options();
		$map = array(
			'events_title_tag' => 'archive_title_tag',
			'events_format' => 'archive_format',
			'events_thumbnail_size' => 'archive_thumbnail_size'
		);
		foreach ($map as $opts_key => $default_key) {
			if (isset($opts[$opts_key])) {
				$options['ept_archive_options'][$default_key] = $opts[$opts_key];
			}
		}
		$classes = self::get_classes($evt->ID);
		if (isset($opts["class"])) {
			$classes[] = $opts["class"];
		}
		$class = ' class="' . implode(" ", $classes);
		if (has_filter("event-format")) {
			return apply_filters("event-format", $evt, $opts, $options);
		} else {
			$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
			if (trim($evt->post_excerpt) != "") {
				$excerpt = trim($evt->post_excerpt) . $excerpt_more;
			} else {
				$text = strip_shortcodes( $evt->post_content );
				$text = apply_filters('the_content', $text);
				$text = str_replace(']]>', ']]&gt;', $text);
				$excerpt_length = apply_filters('excerpt_length', 55);
				$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
				$excerpt = wp_trim_words( $text, $excerpt_length, $excerpt_more );
			}
			switch ($options['ept_archive_options']['archive_format']) {
				case 'calendar':
					return self::get_events_calendar($opts);
					break;
				case 'title':
					return sprintf('<div%s><%s><a href="%s" title="%s">%s</a></%s><p class="eventdate">%s</p>', $class, $options['ept_archive_options']['archive_title_tag'], self::get_url($evt->ID), esc_attr($evt->post_title), $evt->post_title, $options['ept_archive_options']['archive_title_tag'], self::get_date($evt->ID));
					break;
				case 'title_excerpt':
				case 'title_content':
					$content = ($format == 'title_content')? apply_filters("the_content", $evt->post_content): apply_filters("get_the_excerpt", $excerpt);
					return sprintf('<div%s><%s><a href="%s" title="%s">%s</a></%s><p class="eventdate">%s</p>%s</div>', $class, $opts["events_title_tag"], self::get_url($evt->ID), esc_attr($evt->post_title), $evt->post_title, $opts["events_title_tag"], self::get_date($evt->ID), $content);
					break;
				case 'title_excerpt_thumbnail':
				case 'title_content_thumbnail':
					/* get the thumbnail for the event */
					$thumb = "";
					if (has_post_thumbnail($evt->ID)) {
						if (!isset($options['ept_archive_options']['archive_thumbnail_size'])) {
							$size = 'thumbnail';
						} else {
							if (preg_match("/([0-9]+),([0-9]+)/", $options['ept_archive_options']['archive_thumbnail_size'], $matches)) {
								$size = array($matches[1], $matches[2]);
							} else {
								$size = $options['ept_archive_options']['archive_thumbnail_size'];
							}
						}
						$thumbnail = get_the_post_thumbnail($evt->ID, $size);
						if ($thumbnail != "") {
							$thumb = sprintf('<a href="%s" title="%s">%s</a>', self::get_url($evt->ID), esc_attr($evt->post_title), $thumbnail);
						}
					}
					$content = ($options['ept_archive_options']['archive_format'] == 'title_content_thumbnail')? apply_filters("the_content", $evt->post_content): $excerpt;
					return sprintf('<div%s>%s<%s><a href="%s" title="%s">%s</a></%s><p class="eventdate">%s</p>%s</div>', $class, $thumb, $options['ept_archive_options']['archive_title_tag'], self::get_url($evt->ID), esc_attr($evt->post_title), $evt->post_title, $options['ept_archive_options']['archive_title_tag'], self::get_date($evt->ID), $content);
					break;
			}
		}
	}

	/**
	 * shortcode for events
	 */
	public static function events_shortcode($atts)
	{
		$defaults = self::get_default_display_options();
		$options = $defaults;

		/* validate format preset */
		$presets = self::get_events_format_presets();
		if (isset($atts['preset']) && in_array($atts['preset'], array_keys($presets))) {
			$options['events_preset'] = $atts['preset'];
		}

		/* validate content format */
		$content_formats = self::get_formats();
		if (!has_filter("event-format")) {
			if (isset($atts['format']) && in_array($atts['format'], array_keys($content_formats))) {
				$options['events_format'] = $atts['format'];
			}
		} else {
			$options['events_format'] = 'user';
		}

		/* validate category and tag filters */
		if (isset($atts["category"])) {
			$cats = array_map('trim', explode(",", $atts["category"]));
			foreach ($cats as $cat) {
				if ($cat != '') {
					$options["events_category_filter"][] = $cat;
				}
			}
		}
		if (isset($atts["tag"])) {
			$tags = array_map('trim', explode(",", $atts["tag"]));
			foreach ($tags as $tag) {
				if ($tag != '') {
					$options["events_tag_filter"][] = $tag;
				}
			}
		}

		/* validate start and end time filters */
		if (isset($atts["start"])) {
			$options['events_start'] = strtotime($atts["start"]);
		}
		if (isset($atts["end"])) {
			$options['events_end'] = strtotime($atts["end"]);
		}

		/* copy remaining values */
		if (isset($atts['thumbnail_size'])) {
			$options['events_thumbnail_size'] = $atts['events_thumbnail_size'];
		}
		if (isset($atts['prioritise_sticky'])) {
			$options['events_prioritise_sticky'] = (bool) $atts['prioritise_sticky'];
		}
		if (isset($atts["num"])) {
			$options['events_num'] = intval($atts['num']);
		}
		return self::events_output($options);	
	}

	/**
	 * displays the settings form for the events widget
	 */
	public static function widget_form($instance, $widget)
	{
		printf('<h3>%s</h3>', __('Events Widget settings', 'event-post-type'));
		$defaults = self::get_default_display_options();
		/* event format presets */
		$presets = self::get_events_format_presets();
		$sel = (!isset($instance['events_preset']) || !in_array($instance['events_preset'], array_keys($presets)) || $instance['events_preset'] == "none")? ' selected="selected"': '';
		printf('<p>%s <select name="%s" id="%s" class="events-preset-select"><option value="none"%s>%s</option>', __('Format:', 'event-post-type'), $widget->get_field_name('events_preset'), $widget->get_field_id('events_preset'), $sel, __('Select preset...', 'event-post-type'));
		foreach ($presets as $key => $label) {
			$sel = (isset($instance['events_preset']) && $instance['events_preset'] == $key)? ' selected="selected"': '';
			printf('<option value="%s"%s>%s</option>', $key, $sel, $label);
		}
		print('</select></p>');
		/* number of events to show */
		$no = intval($instance['events_num']);
		printf('<p class="input-events-num events-list"><label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" size="3" /></p>', $widget->get_field_id('events_num'), __('Number of events to display:', 'event-post-type'), $widget->get_field_id('events_num'), $widget->get_field_name('events_num'), $no);

		/* format of event content */
		if (!isset($instance['events_format'])) {
			$instance['events_format'] = $defaults['events_format'];
		}
		printf('<div class="events-format events-list">$s</div>', self::get_format_select($widget->get_field_id('events_format'), $widget->get_field_name('events_format'), $instance['events_format'])); 
		/* thumnbnail size */
		if (!isset($instance['events_thumbnail_size'])) {
			$instance['events_thumbnail_size'] = $defaults['events_thumbnail_size'];
		}
		printf('<p class="events-list"><label for="%s">%s</label>%s</p>', $widget->get_field_id('events_thumbnail_size'), __('Thumbnail size:', 'event-post-type'), self::get_image_sizes_select($widget->get_field_name('events_thumbnail_size'), $widget->get_field_id('events_thumbnail_size'), $instance['events_thumbnail_size']));
		/* tag to use for titles */
		if (!isset($instance['events_title_tag'])) {
			$instance['events_title_tag'] = $defaults['events_title_tag'];
		}
		printf('<p class="events-list"><label for="%s">%s</label>%s</p>', $widget->get_field_id('events_title_tag'), __('Tag to use for titles:', 'event-post-type'), self::get_title_tags_select($widget->get_field_name('events_title_tag'), $widget->get_field_id('events_title_tag'), $instance['events_title_tag']));


		/* whether to prioritise sticky events */
		$chckd = (isset($instance['events_prioritise_sticky']) && ((bool) $instance['events_prioritise_sticky'] === true))? ' checked="checked"': '';
		printf('<p class="events-list"><label for="%s"><input type="checkbox" id="%s" name="%s" value="1"%s /> %s</label></p>', $widget->get_field_id('events_prioritise_sticky'), $widget->get_field_id('events_prioritise_sticky'), $widget->get_field_name('events_prioritise_sticky'), $chckd, __('Check this box to prioritise sticky events.', 'event-post-type'));

		/* filter for events category */
		if (!isset($instance["events_category_filter"]) || !is_array($instance["events_category_filter"])) {
			$instance["events_category_filter"] = $defaults["events_category_filter"];
		}
		$category_filter = self::get_event_terms_select('event_category', $widget->get_field_id('events_category_filter'), $widget->get_field_name('events_category_filter'), $instance["events_category_filter"]);
		if ($category_filter != '') {
			printf('<h4>%s</h4>%s', __('Filter events by category', 'event-p[ost-type'), $category_filter);
		}

		/* filter for events tags */
		if (!isset($instance["events_tag_filter"]) || !is_array($instance["events_tag_filter"])) {
			$instance["events_tag_filter"] = $defaults["events_tag_filter"];
		}
		$tag_filter = self::get_event_terms_select('event_tag', $widget->get_field_id('events_tag_filter'), $widget->get_field_name('events_tag_filter'), $instance["events_tag_filter"]);
		if ($tag_filter != '') {
			printf('<h4>%s</h4>%s', __('Filter events by tag', 'event-post-type'), $tag_filter);
		}

		/* filter by date */
		$start_date = (isset($instance['events_start']) && $instance['events_start'] != '')? date("d/m/Y", $instance["events_start"]): '';
		$start_time = (isset($instance['events_start']) && $instance['events_start'] != '')? date("h:iA", $instance['events_start']): '';
		$end_date = (isset($instance['events_end']) && $instance['events_end'] != '')? date("d/m/Y", $instance['events_end']): "";
		$end_time = (isset($instance['events_end']) && $instance['events_end'] != '')? date("h:iA", $instance['events_end']): "";

		printf('<h4>%s</h4>', __('Filter Events by date', 'event-post-type'));
		printf('<p class="event_datep"><label for="%s">%s</label><br />', $widget->get_field_id('events_start_date'), __( 'Event start', 'event-post-type' ));
		printf('<input type="text" id="" name="" value="%s" size="25" class="datepicker" />', $widget->get_field_id('events_start_date'), $widget->get_field_name('events_start_date'), $start_date);
		printf('<input type="text" id="%s" name="%s" value="%s" size="25" class="timepicker" /></p>', $widget->get_field_id('events_start_time'), $widget->get_field_name('events_start_time'), $start_time);
		printf('<p class="event_datep"><label for="%s">%s</label><br />', $widget->get_field_id('events_end_date'), __( 'Event end', 'event-post-type' ));
		printf('<input type="text" id="" name="" value="%s" size="25" class="datepicker" />', $widget->get_field_id('events_end_date'), $widget->get_field_name('events_end_date'), $end_date);
		printf('<input type="text" id="%s" name="%s" value="%s" size="25" class="timepicker" /></p>', $widget->get_field_id('events_end_time'), $widget->get_field_name('events_end_time'), $end_time);

	}

	/**
	 * processes values returned by the widget form
	 */
	public static function process_widget_form($new_values, $instance)
	{
		return self::validate_display_options($new_values, $instance);
	}

	/**
	 * valuidates the options passed to the widget or shortcode
	 */
	private static function validate_display_options($new_values, $old_values = false)
	{
		$defaults = self::get_default_display_options();
		if (!$old_values) {
			$values = $defaults;
		} else {
			$values = wp_parse_args($old_values, $defaults);
		}

		/* validate format preset */
		$presets = self::get_events_format_presets();
		if (in_array($new_values['events_preset'], array_keys($presets))) {
			$values['events_preset'] = $new_values['events_preset'];
		}

		/* validate content format */
		$content_formats = self::get_formats();
		if (!has_filter("event-format")) {
			if (in_array($new_values['events_format'], array_keys($content_formats))) {
				$values['events_format'] = $new_values['events_format'];
			}
		} else {
			$values['events_format'] = 'user';
		}

		/* validate title tag */
		$title_tags = self::get_title_tags();
		if (!has_filter("event-format")) {
			if (in_array($new_values['events_title_tag'], $title_tags)) {
				$values['events_title_tag'] = $new_values['events_title_tag'];
			}
		} else {
			$values['events_format'] = 'user';
		}

		/* validate category and tag filters */
		if (isset($new_values["events_category_filter"]) && is_array($new_values["events_category_filter"])) {
			$values["events_category_filter"] = $new_values["events_category_filter"];
		}
		if (isset($new_values["events_tag_filter"]) && is_array($new_values["events_tag_filter"])) {
			$values["events_tag_filter"] = $new_values["events_tag_filter"];
		}

		/* validate start and end time filters */
		$start_date = isset($new_values['events_start_date'])? $new_values['events_start_date']: "";
		$start_time = isset($new_values['events_start_time'])? $new_values['events_start_time']: "";
		$end_date = isset($new_values['events_end_date'])? $new_values['events_end_date']: "";
		$end_time = isset($new_values['events_end_time'])? $new_values['events_end_time']: "";
		$values['events_start'] = self::parse_date($start_date, $start_time);
		$values['events_end'] = self::parse_date($end_date, $end_time);

		/* copy remaining values */
		$values['events_thumbnail_size'] = $new_values['events_thumbnail_size'];
		$values['events_prioritise_sticky'] = (isset($new_values['events_prioritise_sticky']) && intval($new_values['events_prioritise_sticky']) > 0);
		$values['events_num'] = intval($new_values['events_num']);

		return $values;
	}

	/**
	 * prints output for the events widget
	 */
	public static function widget_output($instance, $post)
	{
		print(self::events_output($instance));
	}

	/**
	 * gets a set of presets for the format of events in the events widget
	 */
	private static function get_events_format_presets()
	{
		return array(
			"calendar" => __("Calendar only", 'event-post-type'),
			"list_with_calendar" => __("Next/latest events with calendar", 'event-post-type'),
			"list" => __("Next/latest events as a list", 'event-post-type')
		);
	}

	/**
	 * gets a set of presets for event content formatting
	 */
	private static function get_formats()
	{
		return array(
			'calendar' => __('Calendar', 'event-post-type'),
			'title' => __('Title & date', 'event-post-type'),
			'title_excerpt' => __('Title, date & excerpt', 'event-post-type'),
			'title_excerpt_thumbnail' => __('Title, date, excerpt & thumbnail', 'event-post-type'),
			'title_content' => __('Title, date & content', 'event-post-type'),
			'title_content_thumbnail' => __('Title, date, content & thumbnail', 'event-post-type'),
		);
	}

	/**
	 * gets a set of tags to use for titles of events
	 */
	private static function get_title_tags()
	{
		return array(
			'span',
			'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6'
		);
	}

	/**
	 * gets default display options for widget and shortcodes
	 */
	private static function get_default_display_options()
	{
		$content_formats = self::get_formats();
		return array(
			'events_preset' => 'none',
			'events_format' => array_pop(array_keys($content_formats)),
			'events_category_filter' => array(),
			'events_tag_filter' => array(),
			'events_start' => '',
			'events_end' => '',
			'events_class' => '',
			'events_exclude' => '',
			'events_thumbnail_size' => '',
			'events_title_tag' => 'h3',
			'events_prioritise_sticky' => true,
			'events_num' => ''
		);
	}

	/**
	 * gets a select list of output formats
	 * checks to see if a filter has been registered on "event-format"
	 */
	public static function get_format_select($id, $name, $selected = false)
	{
		$out = "";
		if (!has_filter("event-format")) {
			$out .= '<ul>';
			$suffix = 1;
			$formats = self::get_formats();
			if (!$selected || !in_array($selected, array_keys($formats))) {
				$selected = array_shift(array_keys($formats));
			}
			foreach ($formats as $fmt => $desc) {
				$id .= "_" . $suffix;
				$chckd = ($fmt === $selected)? ' checked="checked"': '';
				$out .= sprintf('<li><input type="radio" name="%s" id="%s" value="%s"%s /> %s</li>', $name, $id, $fmt, $chckd, $desc);
				$suffix++;
			}
			$out .= '</ul>';
			return $out;
		} else {
			$out .= sprintf('(%s) <input type="hidden" name="%s" id="%s" value="user" />', __('User defined', 'event-post-type'), $name, $id);
		}
		return $out;
	}

	/**
	 * gets a select list of tags to use for titles of events
	 */
	public static function get_title_tag_select($id, $name, $selected = false)
	{
		$out = "";
		if (!has_filter("event-format")) {
			$tags = self::get_title_tags();
			if (!$selected || !in_array($selected, $tags)) {
				$defaults = self::get_default_display_options();
				$selected = $defaults["events_title_tag"];
			} 
			$out .= sprintf('<select id="%s" name="%s">', $id, $name);
			foreach ($tags as $tag) {
				$sel = ($tag == $selected)? ' selected="selected"': '';
				$out .= sprintf('<option value="%s"%s>%s</option>', $tag, $sel, $tag);
			}
			$out .= '</select>';
			return $out;
		} else {
			$out .= sprintf('(%s) <input type="hidden" name="%s" id="%s" value="user" />', __('User defined', 'event-post-type'), $name, $id);
		}
		return $out;
	}

	/**
     * gets all configured image sizes in the theme and return them as 
     * an unordered list with dimensions and cropping details
     */
    public static function get_image_sizes_select($select_name = '', $select_id = '', $selected = '')
    {
		$out = "";
		if (!has_filter("event-format")) {
			global $_wp_additional_image_sizes;
	        $sizes = array();
			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[$s] = array( 'name' => '', 'width' => '', 'height' => '', 'crop' => FALSE );
				/* Read theme added sizes or fall back to default sizes set in options... */
				$sizes[$s]['name'] = $s;
				if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) {
					$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); 
				} else {
					$sizes[$s]['width'] = get_option( "{$s}_size_w" );
				}
				if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) {
					$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] );
				} else {
					$sizes[$s]['height'] = get_option( "{$s}_size_h" );
				}
				if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) {
					$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] );
				} else {
					$sizes[$s]['crop'] = get_option( "{$s}_crop" );
				}
			}
			if (count($sizes)) {
				$out .= sprintf('<select id="%s" name="%s">', $select_id, $select_name);
				foreach ($sizes as $s ) {
					$cropped = $s['crop']? " - cropped": "";
					$sel = ($s['name'] == $selected)? ' selected="selected"': '';
					$out .= sprintf('<option value="%s"%s>%s (%s x %s%s)</option>', $s['name'], $sel, $s['name'], $s['width'], $s['height'], $cropped);
				}
				$sel = (!in_array($selected, array_keys($sizes)))? ' selected="selected"': '';
				$custom_value = (!in_array($selected, array_keys($sizes)))? $selected: '';
				printf('<option value="custom"%s>%s</option></select><br /><input type="text" name="thumbnail_size_input" id="thumbnail_size_input" size="7" value="%s" />', __('Custom size', 'event-post-type'), $sel, $custom_value);
				printf('<p id="custom_thumbnail_desc"><em>%s</em></p>', __('Custom settings consist of two numbers separated by a comma. These represent the width and height of the cropped image.', 'event-post-type'));
			}
		} else {
			$out .= sprintf('(%s) <input type="hidden" name="%s" id="%s" value="user" />', __('User defined', 'event-post-type'), $select_name, $select_id);
		}
		return $out;
	}

	/**
	 * get event terms select
	 * returns a multiple select list for event categories or terms
	 */
	public static function get_event_terms_select($tax = false, $field_id = '', $field_name = '', $selected = array())
	{
		$out = '';
		$taxonomies = array("event_category", "event_tag");
		if (!$tax || !in_array($tax, $taxonomies) || empty($field_name) || empty($field_id)) {
			return $out;
		}
		$args = array(
			'hide_empty' => false
		);
		$terms = get_terms($tax, $args);
		if (is_array($terms) && count($terms)) {
			$out .= sprintf('<p class="select-%s">', $tax);
			$count = 1;
			$inputs = array();
			$fname = $field_name . '[]';
			foreach ($terms as $term) {
				$sel = in_array($term->slug, $selected)? ' checked="checked"': '';
				$fid = $field_id . '-' . $count;
				$inputs[] = sprintf('<label for="%s"><input type="checkbox" id="%s" name="%s"%s />%s</label>', $fid, $fid, $fname, $sel, $term->name);
				$count++;
			}
			$out .= implode('<br />', $inputs) . '</p>';
		}
		return $out;
	}

	/**
	 * get_date
	 * returns a text representation of a date for an event
	 */
	public static function get_date($event_id = false)
	{
		if ($event_id === false) {
			global $post;
			$event_id = $post->ID;
		}
		$options = EventPostTypeAdmin::get_plugin_options();
		$event_start = get_post_meta($event_id, 'event_start', true);
		$event_end = get_post_meta($event_id, 'event_end', true);
		$event_allday = (bool) get_post_meta($event_id, 'event_allday', true);
		$date_html = "";
		if (has_filter("event-date-format")) {
			return apply_filters("event-date-format", array('start' => $event_start, "end" => $event_end, "allday" => $event_allday));
		}
		if ($event_start !== "") {
			if ($event_allday) {
				/* all day event - only need start date */
				$start_date = @date($options['ept_date_options']["date_fmt"], $event_start);
				$date_html = '<span class="event-date-label">' . $options['ept_date_options']["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span><span class="event-allday">' . $options['ept_date_options']["allday"] . '</span>';
			} else {
				/*
				 * either:
				 * - an event spanning multiple days
				 * - an event on a single day with time bracket specified
				 * - an event with only the start date set
				 */
				$start_date = @date($options['ept_date_options']["date_fmt"], $event_start);
				$start_time = @date($options['ept_date_options']["time_fmt"], $event_start);
				$end_date = @date($options['ept_date_options']["date_fmt"], $event_end);
				$end_time = @date($options['ept_date_options']["time_fmt"], $event_end);
				if (!$start_date) {
					/* no start date set (nothing to display) */
					$date_html = "";
				} elseif (!$end_date) {
					/* no end date set - output start date and time */
					$date_html = '<span class="event-date-label">' . $options['ept_date_options']["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span>' . $options['ept_date_options']["date_time_separator"];
					$date_html .= '<span class="event-time-label">' . $options['ept_date_options']["time_label"] . '</span><span class="event-start-time">' . $start_time . '</span>';
				} elseif ($start_date == $end_date) {
					/* start and end dates are on the same day */
					$date_html = '<span class="event-date-label">' . $options['ept_date_options']["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span>' . $options['ept_date_options']["date_time_separator"];
					$date_html .= '<span class="event-time-label">' . $options['ept_date_options']["time_label"] . '</span><span class="event-start-time">' . $start_time . '</span>' . $options['ept_date_options']["time_separator"] . '<span class="event-end-time">' . $end_time . '</span>';
				} else {
					/* start and end dates on different days */
					$date_html = '<span class="event-date-label">' . $options['ept_date_options']["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span>' . $options['ept_date_options']["date_separator"] . '<span class="event-end-date">' . $end_date . '</span>';
				}
			}
		}
		return $date_html;
	}

	/**
	 * gets the URL for the event
	 */
	public static function get_url($event_id = false)
	{
		if ($event_id === false) {
			return "#";
		} else {
			$event_url = get_post_meta($event_id, 'event_url', true);
			if ($event_url && self::check_url($event_url)) {
				return $event_url;
			} else {
				return get_permalink($event_id);
			}
		}
	}

	/**
	 * wraps get_date for use in the loop
	 */
	public static function the_date()
	{
		global $post;
		echo '<p class="date">' . self::get_date($post->ID) . '</p>';
	}

	/**
	 * adds a list of categories/tags for an event
	 */
	public static function posted_in($args = array())
	{
        $post = get_post();
        $defaults = array(
        	"show" => "category,tag",
        	"before" => '<p class="terms">',
        	"after" => '</p>',
        	"category_before" => __("Posted in", 'event-post-type'),
        	"sep" => " ",
        	"tag_before" => __("Tagged with", 'event-post-type')
        );
        $opts = wp_parse_args($args, $defaults);
        $output = '';
        $taxonomies = array_map("trim", array_map("strtolower", explode(',', $opts["show"]) ) );
        foreach ( $taxonomies as $taxonomy ) {
        	if ( !in_array($taxonomy, array("category", "tag"))) {
        		continue;
        	}
			$terms = get_object_term_cache($post->ID, 'event_' . $taxonomy);
			if ( false === $terms ) {
				$terms = wp_get_object_terms($post->ID, 'event_' . $taxonomy);
			}
			$links = array();
			foreach ( $terms as $term ) {
				$links[] = "<a href='" . esc_attr( get_term_link($term) ) . "'>$term->name</a>";
			}
			if ( !empty($links) ) {
				$output .= sprintf('%s: %s%s', $opts[$taxonomy . '_before'], implode(', ', $links), $opts["sep"]);
			}
		}
		if (!empty($output)) {
			echo $opts["before"] . $output . $opts["after"];
		}
	}

	/**
	 * wraps logic to link between previous and next events
	 */
	private static function adjacent_event_link( $format = '%link', $link = '%title', $which = "previous" ) 
	{
		global $post;
		$allEvents = self::get_events();
		$adjacent = array("next" => false, "previous" => false);
		$out = '';
		for ($i = 0; $i < count($allEvents); $i++) {
			if ($allEvents[$i]->ID == $post->ID) {
				if ($i > 0) {
					$adjacent["next"] = $allEvents[($i - 1)];
				}
				if ($i < (count($allEvents) - 1)) {
					$adjacent["previous"] = $allEvents[($i + 1)];
				}
			}
		}
		if ( $adjacent[$which] ) {
			$title = apply_filters( 'the_title', $adjacent[$which]->post_title, $post->ID );
			$string = '<a href="' . get_permalink( $adjacent[$which] ) . '" rel="' . $which . '">';
			$inlink = str_replace( '%title', $title, $link );
			$inlink = $string . $inlink . '</a>';
			$out = str_replace( '%link', $inlink, $format );
		}
		return $out;
	}
	
	/**
	 * echos link to next event
	 */
	public static function next_event_link( $format = '%link', $link = '%title')
	{
		echo self::adjacent_event_link($format, $link, 'next');
	}

	/**
	 * echos link to previous event
	 */
	public static function previous_event_link( $format = '%link', $link = '%title')
	{
		echo self::adjacent_event_link($format, $link, 'previous');
	}


} /* end class definition */
/* initialise */
EventPostType::register();
}

if (0 && !class_exists('Widget_EventsPostType') ) :
/**
 * widget for events
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package WordPress
 * @subpackage EventPostType_Plugin
 */
class Widget_EventsPostType extends WP_Widget {

	function Widget_EventsPostType() {
		$widget_ops = array('classname' => 'widget_ept_list', 'description' => 'Widget to display events as a list, calendar or a combination of the two' );
		$this->WP_Widget('ept_widget', 'Events Widget', $widget_ops);
	}

	function widget( $args, $instance )
	{
		global $post;
		extract($args);
		echo $before_widget;
		echo '<div id="events_widget_wrap">';
		echo EventPostType::widget_output($instance, $post);
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		return EventPostType::process_widget_form($new_instance, $old_instance);
	}

	function form( $instance )
	{
		EventPostType::widget_form($instance, $this);
	}
	
}/* end of widget class definition */

/* initialise widget */
add_action( 'widgets_init', create_function('', 'return register_widget("Widget_EventsPostTypeList");') );

endif;

if ( !class_exists('Widget_EventsPostTypeCalendar') ):
/**
 * widget for events calendar
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package WordPress
 * @subpackage EventPostType_Plugin
 */
class Widget_EventsPostTypeCalendar extends WP_Widget {

	function Widget_EventsPostTypeCalendar() {
		$widget_ops = array('classname' => 'ept_calendar', 'description' => 'A compact calendar for Events' );
		$this->WP_Widget('ept_calendar', 'Events Calendar', $widget_ops);
	}

	function widget( $args, $instance )
	{
		global $post;
		extract($args);
		echo $before_widget;
		echo '<div id="events_widget_wrap">';
		echo EventPostType::get_events_calendar($instance);
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		return $instance;
	}

	function form( $instance )
	{
		echo "<p>There are no options for this widget</p>";
	}
} /* end calendar widget class definition */

/* initialise widget */
add_action( 'widgets_init', create_function('', 'return register_widget("Widget_EventsPostTypeCalendar");') );

endif;

