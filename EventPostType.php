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
		/* run the upgrade routine */
		add_action('init', array('EventPostType', 'upgrade'), 182 );

		/* i18n */
		add_action('plugins_loaded', array('EventPostType', 'load_text_domain'));

		/************************************************
		 * Custom post type and taxonomy registration   *
		 ************************************************/

		/* initialise custom taxonomies */
		add_action( 'init', array('EventPostType', 'register_event_taxonomies'), 180 );

		/* initialise custom post type */
		add_action( 'init', array('EventPostType', 'register_event_post_type' ), 181 );

		/* add filter to update messages */
		add_filter( 'post_updated_messages', array('EventPostType', 'updated_messages') );

		/* Use the admin_menu action to define custom editing boxes */
		add_action( 'admin_menu', array('EventPostType', 'add_custom_boxes') );

		/* Use the quick_edit_custom_box action to add the sticky checkbox to the quick edit form */
		add_action('quick_edit_custom_box', array('EventPostType', 'add_sticky_to_quickedit'), 10, 2);

		/* Use the save_post action to do something with the data entered */
		add_action( 'save_post', array('EventPostType', 'save_postdata') );

		/* initialise custom rewrites for events */
		add_action( 'init', array('EventPostType', 'add_rewrite_rules') );

		/* adds eventsJSON javascript variable to  head */		
		add_action('wp_head', array('EventPostType','add_json_feed_url'));



		/************************************************
		 * Admin and Dashboard related methods		  *
		 ************************************************/

		/* put columns on events list table and make sortable by date and filterable by category */
		add_action( 'manage_edit-event_columns', array('EventPostType', 'add_event_columns') );
		add_action( 'manage_event_posts_custom_column', array('EventPostType', 'show_event_columns'), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', array('EventPostType', 'date_column_register_sortable') );
		add_filter( 'request', array('EventPostType', 'date_column_orderby') );
		add_filter( 'parse_query', array('EventPostType', 'sort_events_by_event_date')) ;


		/* Use the admin_print_scripts action to add scripts for theme options */
		add_action( 'admin_print_scripts', array('EventPostType', 'plugin_admin_scripts') );
		/* Use the admin_print_styles action to add CSS for theme options */
 		add_action( 'admin_print_styles', array('EventPostType', 'plugin_admin_styles') );

		/* add counts to the Right Now widget on the dashboard */
		add_action( 'right_now_content_table_end', array('EventPostType', 'add_event_counts') );
		//add_action( 'right_now_discussion_table_end', array('EventPostType', 'add_pending_event_counts') );



		/************************************************
		 * Templates, Shortcode and widgets             *
		 ************************************************/

		/* add filters for templates */
		add_filter('single_template', array('EventPostType', 'single_template'));
		add_filter('archive_template', array('EventPostType', 'archive_template'));

		/* add classes */
		add_filter( 'body_class', array('EventPostType', 'add_body_class') );
		add_filter( 'post_class', array('EventPostType', 'add_post_class') );

		/* add scripts and styles for front-end */
		add_action( 'wp_enqueue_scripts', array('EventPostType', 'plugin_scripts') );
 		add_action( 'wp_enqueue_scripts', array('EventPostType', 'plugin_styles') );

 		/* handle paging */
		add_filter( 'pre_get_posts', array('EventPostType', 'override_wp_paging') );

		/* add shortcode */
		add_shortcode( 'events', array('EventPostType', 'events_shortcode') );
		
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
		$options = EventPostTypeOptions::get_plugin_options();

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
		$options = EventPostTypeOptions::get_plugin_options();
	
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
		add_meta_box( 'event_settings', 'Event Settings', array('EventPostType', 'event_settings_custom_box'), 'event', 'side', 'high' );
		add_meta_box( 'event_url', 'Event URL', array('EventPostType', 'event_url_custom_box'), 'event', 'advanced', 'high' );
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
		printf('<p id="event_dates_start" class="event_datep"><label for="event_dates_start_date"></label><br />', __( 'Event start', 'event-post-type' ));
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
		if (!preg_match('!^https?://(.*)$', $url)) {
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
		$options = EventPostTypeOptions::get_plugin_options();

		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/([0-9]{4})/?$', 'index.php?post_type=event&event_year=$matches[1]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/([0-9]{4})/([0-9]{2})/?$', 'index.php?post_type=event&event_year=$matches[1]&event_month=$matches[2]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$', 'index.php?post_type=event&event_year=$matches[1]&event_month=$matches[2]&event_day=$matches[3]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/(json|ical|rss|atom)/?$', 'index.php?post_type=event&event_feed=$matches[1]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/(json|ical|rss|atom)/([0-9]{4})/([0-9]{2})/?$', 'index.php?post_type=event&event_feed=$matches[1]&event_year=$matches[2]&event_month=$matches[3]', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/' . $options['ept_plugin_options']['post_type_future_slug'] . '/?$', 'index.php?post_type=event&event_future=1&future_page=1', 'top');
		add_rewrite_rule('^' . $options['ept_plugin_options']['post_type_slug'] . '/' . $options['ept_plugin_options']['post_type_future_slug'] . '/([0-9]+)/?$', 'index.php?post_type=event&event_future=1&future_page=$matches[1]', 'top');
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
		$options = EventPostTypeOptions::get_plugin_options();
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
		$posts_columns['event_is_sticky'] = __( 'Sticky?', 'event-post-type' );
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
			$classes[] = 'event';
			$eventmeta = self::get_event_meta($post->ID);
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
		return $classes;
	}

	/**
	 * add scripts to front-end
	 */
	public static function plugin_scripts()
	{
		$options = EventPostTypeOptions::get_plugin_options();
		if ($options['ept_plugin_options']['enqueue_js']) {
			wp_enqueue_script('EventPostTypeScript', plugins_url('/js/EventPostType.js', __FILE__), array('jquery'));
		}
	}

	/**
	 * add styles to front-end
	 */
	public static function plugin_styles()
	{
		$options = EventPostTypeOptions::get_plugin_options();
		if ($options['ept_plugin_options']['enqueue_css']) {
			wp_enqueue_style('EventPostTypeStyle', plugins_url('/css/EventPostType.css', __FILE__));
		}
	}

	/**
	 * overrides posts_per_page (paging is handled by the plugin)
	 * sets this to 1 to prevent wordpress 404 pages
	 * @param object wp_query object
	 */
	function override_wp_paging($query)
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
			"options" => EventPostTypeOptions::get_plugin_options(),
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
		usort($events->current, array('EventPostType', 'sort_events_by_start_date_asc'));
		usort($events->past, array('EventPostType', 'sort_events_by_start_date_desc'));

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
				$start = ($events->options['ept_archive_options']['archive_perpage'] * (intVal($wp_query->query_vars["paged"]) - 2)) - $past_on_frontpage;
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
				if ($sticky_on_frontpage > 0) {
					$toremove = array();
					foreach ($events->current as $e) {
						if ($stickycount < $sticky_on_frontpage) {
							$events->stickies[] = $e;
							$toremove = $e->ID;
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
				$out .= sprintf('<div class="newer-events"><a href="%s/%s/%s/%s">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->options['ept_plugin_options']['post_type_future_slug'], $events->paging["newer"], __('Future Events', 'event-post-type'));
			}
			if ($events->paging["older"] === false || $events->paging["older"] == 1) {
				/* older events are on the main events archive page */
				$out .= sprintf('<div class="older-events"><a href="%s/%s/">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], __('Current Events', 'event-post-type'));
			} else {
				/* future events are being paged */
				$out .= sprintf('<div class="older-events"><a href="%s/%s/%s/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->options['ept_plugin_options']['post_type_future_slug'], $events->paging["older"], __('Older Events', 'event-post-type'));
			}
		} else {
			if ($events->paging["newer"] !== false) {
				switch ($events->paging["newer"]) {
					case 0:
						/* link to future events page from main events archive */
						$out .= sprintf('<div class="newer-events"><a href="%s/%s/%s/">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->options['ept_plugin_options']['post_type_future_slug'], __('Future Events', 'event-post-type'));
						break;
					case 1:
						/* link to main events archive from first page of past events */
						$out .= sprintf('<div class="newer-events"><a href="%s/%s/">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], __('Current Events', 'event-post-type'));
						break;
					default:
						/* link to more recent past events */
						$out .= sprintf('<div class="newer-events"><a href="%s/%s/page/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->paging["newer"], __('More Recent Events', 'event-post-type'));
						break;
				}
			}
			if ($events->paging["older"] !== false) {
				switch ($events->paging["older"]) {
					case 1:
						$out .= sprintf('<div class="older-events"><a href="%s/%s/page/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->paging["older"], __('Current Events', 'event-post-type'));
						break;
					default:
						$out .= sprintf('<div class="older-events"><a href="%s/%s/page/%d">%s</a></div>', get_bloginfo("url"), $events->options['ept_plugin_options']['post_type_slug'], $events->paging["older"], __('Older Events', 'event-post-type'));
						break;
				}
			}
		}
		if ($out) {
			$out = '<div class="events-navigation">' . $out . '</div>';
		}
		return $out;
	}

	/**
	 * checks to see if an event is current, i.e. if the event starts 
	 * in the future or has started but not finished yet
	 * @param object event
	 */
	public static function is_current($event)
	{
		if ($event->meta["event_start"] > time()) {
			/* event starts in the future */
			return true;
		} elseif (isset($event->meta["event_end"]) && $event->meta["event_start"] <= time() && $event->meta["event_end"] > time()) {
			/* event has started but not finished */
			return true;
		} elseif ($event->meta["event_allday"]) {
			$end = mktime(0, 0, 0, date("n", $event->meta["event_start"]), (date("j", $event->meta["event_start"]) + 1), date("Y", $event->meta["event_start"]));
			if ($event->meta["event_start"] > time() || ($event->meta["event_start"] <= time() && $end > time())) {
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
		for ($i = 0; $i < count($allEvents); $i++) {
	 		$allTaxEvents[$i]->meta = self::get_event_meta($allEvents[$i]->ID);
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
		$options = EventPostTypeOptions::get_plugin_options();
		$event_categories = get_terms('event_category');
		$out = "";
		if (count($event_categories)) {
			$out .= '<div class="events-search-bar">';
			if (is_tax('event_category')) {
				/* already filtering */
				$term = get_term_by( 'slug', $wp_query->query_vars["term"], "event_category");
				$out .= sprintf('<div class="event-category-filter"><p>%s <strong class="current-term filter-trigger">%s</strong> <a class="remove-filter" href="%s/%s">remove filter</a></p>', __('Filtering events by category', 'event-post-type'), $term->name, get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"]);
			} else {
				$out .= sprintf('<div class="event-category-filter"><p class="filter-trigger">Filter events by category</p>', __('Filter events by category', 'event-post-type'));
			}
			$out .= '<div class="event-category-list"><ul>';
			foreach($event_categories as $term) {
				$out .= sprintf('<li><a href="%s/%s/%s/%s">%s</a></li>', get_bloginfo('url'), $options['ept_plugin_options']["post_type_slug"], $options['ept_plugin_options']['event_category_slug'], $term->slug, $term->name);
			}
			$out .= '</div>';
		}
		
		return $out;
	}
	
	/**
	 * gets HTML and script to include FullCalendar
	 */
	public static function get_full_events_calendar($opts)
	{
		/* get plugin options */
		$options = EventPostTypeOptions::get_plugin_options();
		
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
		$options = EventPostTypeOptions::get_plugin_options();

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


	/**
	 * shortcode for events
	 */
	public static function events_shortcode($atts)
	{
		return self::format_events($atts);	
	}

	/**
	 * function to retrieve a formatted list of events
	 * @param array $options
	 * @return array
	 */
	public static function format_events($opts = array())
	{
		/* get plugin options and function options */
		$options = EventPostTypeOptions::get_plugin_options();
		$formats = array_keys(EventPostTypeOptions::get_formats());
		$opts = shortcode_atts(array(
			'category' => '',
			'tag' => '',
			'start_date' => '',
			'end_date' => '',
			'current' => 1,
			'sticky' => 0,
			'class' => '',
			'heading' => 'h3',
			'format' => $formats[0],
			'max' => $options['ept_widget_options']["max"],
			'min' => 2,
			'size' => 'thumbnail',
			'include' => '',
			'exclude' => ''
		), $opts);
		/* clear up booleans */
		$opts["current"] = (bool) $opts["current"];
		$opts["sticky"] = (bool) $opts["sticky"];

		/* get events */
		$allEvents = self::get_events();
		if (!count($allEvents)) {
			return "";
		}

		/* see if a date range has been requested */
		$start = strtotime($opts["start_date"]);
		$end = strtotime($opts["end_date"]);

		/* if a calendar has been requested, return it */
		if ($opts["format"] == "calendar") {
			if ($start) {
				return EventPostType::get_events_calendar($start);
			} else {
				return EventPostType::get_events_calendar();
			}
		}

		/* store returned events in here */
		$events = array();

		/* see if a category has been requested */
		if (!empty($opts["category"])) {
			/* split multiple categories at the comma */
			if (strpos($opts["category"], ",") !== false) {
				$cat = array_map("trim", explode(",", $opts["category"]));
			} else {
				$cat = trim($opts["category"]);
			}
			foreach ($allEvents as $evt) {
				if (has_term($cat, "event_category", $evt)) {
					$events[$evt->ID] = $evt;
				}
			}
		/* see if a tag has been requested */
		} elseif (!empty($opts["tag"])) {
			/* split multiple categories at the comma */
			if (strpos($opts["tag"], ",") !== false) {
				$tag = array_map("trim", explode(",", $opts["tag"]));
			} else {
				$tag = trim($opts["tag"]);
			}
			foreach ($allEvents as $evt) {
				if (has_term($tag, "event_tag", $evt)) {
					$events[$evt->ID] = $evt;
				}
			}
		/* see if a time frame has been requested */
		} elseif ($start !== false && $end !== false) {
			foreach ($allEvents as $event) {
				if (($event->meta["event_start"] > $start && $event->meta["event_start"] < $end) || ($event->meta["event_start"] < $start && $event->meta["event_end"] > $start)) {
					/* event starts or ends in the target timeframe */
					$events[$event->ID] = $event;
				}
			}
		/* see if past events have been requested */
		} elseif ($opts["current"] === false) {
			foreach ($allEvents as $event) {
				if ($event->meta["event_end"] < time()) {
					/* event starts or ends in the target timeframe */
					$events[$event->ID] = $event;
				}
			}
		} else {
			/* current events have been requested (default) */
			foreach ($allEvents as $event) {
				if (($event->meta["event_start"] > time()) || ($event->meta["event_start"] < time() && $event->meta["event_end"] > time())) {
					$events[$event->ID] = $event;
				}
			}
		}

		/* include/exclude */
		$include = (!empty($opts["include"]))? explode(",", $opts["include"]): array();
		$exclude = (!empty($opts["exclude"]))? explode(",", $opts["exclude"]): array();
		if (count($include)) {
			foreach ($allEvents as $e) {
				if (in_array($e->ID, $include) && !in_array($e->ID, $exclude)) {
					if (!isset($events[$e->ID])) {
						$events[$e->ID] = $e;
					}
				}
			}
		}
		if (count($exclude)) {
			$newevents = array();
			foreach ($events as $id => $obj) {
				if (!in_array($id, $exclude)) {
					$newevents[$id] = $obj;
				}
			}
			$events = $newevents;
		}

		/* show only sticky events */
		if ($sticky === true) {
			$newevents = array();
			foreach ($events as $id => $obj) {
				if (self::is_sticky($obj)) {
					$newevents[$id] = $obj;
				}
			}
			$events = $newevents;
		}

		/* see if we still have some to play with */
		$out = "";
		if (count($events)) {
			/* sort events */
			if ($opts["current"] === false) {
				usort($events, array('EventPostType', 'sort_events_by_start_date_desc'));
			} else {
				usort($events, array('EventPostType', 'sort_events_by_start_date_asc'));
			}
			if (isset($opts['max']) && intval($opts['max']) > 0) {
				$events = array_slice($events, 0, intval($opts['max']));
			}
			$cls = ($opts["current"] === false)? "past": "current";
			if (!empty($opts["class"])) {
				$cls .= " " . trim($opts["class"]);
			}
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
	public static function get_formatted_event($evt, $opts)
	{
		$options = EventPostTypeOptions::get_plugin_options();
		if (has_filter("event-format")) {
			return apply_filters("event-format", $evt, $opts, $options);
		}
		switch ($format) {
			case "full":
			case "featured":
				/* get the thumbnail for the event */
				$thumb = "";
				if (has_post_thumbnail($evt->ID)) {
   					if (!isset($options["thumbnail_size"])) {
   						$size = 'thumbnail';
   					} else {
   						if (preg_match("/([0-9]+),([0-9]+)/", $options["thumbnail_size"], $matches)) {
   							$size = array($matches[1], $matches[2]);
   						} else {
   							$size = $opts["thumbnail_size"];
   						}
   					}
   					$thumbnail = get_the_post_thumbnail($evt->ID, $size);
					if ($thumbnail != "") {
   						$thumb = sprintf('<a href="%s" title="%s">%s</a>', self::get_url($evt->ID), esc_attr($evt->post_title), $thumbnail);
	   				}
				}
				if ($format == "featured") {
					return sprintf('%s<h3><a href="%s" title="%s">%s</a></h3><p class="eventdate">%s</p>%s', $thumb, self::get_url($evt->ID), esc_attr($evt->post_title), esc_attr($evt->post_title), self::get_date($evt->ID, $opts), apply_filters("the_excerpt", $evt->post_excerpt));
				} elseif ($format == "full") {
					return sprintf('%s<h3><a href="%s" title="%s">%s</a></h3><p class="eventdate">%s</p>%s', $thumb, self::get_url($evt->ID), esc_attr($evt->post_title), esc_attr($evt->post_title), self::get_date($evt->ID, $opts), apply_filters("the_content", $evt->post_content));
				}
				break;
			case "short":
				return sprintf('<h3><a href="%s" title="%s">%s</a></h3><p class="eventdate">%s</p>%s', self::get_url($evt->ID), esc_attr($evt->post_title), esc_attr($evt->post_title), self::get_date($evt->ID, $opts), apply_filters("the_excerpt", $evt->post_excerpt));
				break;
			default:
				return sprintf('<p><a href="%s" title="%s">%s</a><br />%s</p>', self::get_url($evt->ID), esc_attr($evt->post_title), esc_attr($evt->post_title), self::get_date($evt->ID, $opts));
				break;
		}
	}

	public static function widget_form($instance, $widget)
	{
		$fields = array(
			'category' => '',
			'tag' => '',
			'start_date' => '',
			'end_date' => '',
			'current' => 1,
			'sticky' => 0,
			'class' => '',
			'format' => 'list',
			'limit' => $options["max"],
			'min' => 2,
			'size' => 'thumbnail',
			'include' => '',
			'exclude' => ''
		);
		$presets = array(
			"next_latest_with_calendar" => "Next/latest event with calendar (4 columns)",
			"next_latest_2col" => "Next/latest 2 events in 2 columns",
			"next_latest_3col" => "Next/latest 3 events in 3 columns",
			"next_latest_4col" => "Next/latest 4 events in 4 columns"
		);
		print('<h3>Events Widget settings</h3>');
		printf('<p>Select preset: <select name="%s" id="%s">', $widget->get_field_name('events_preset'), $widget->get_field_id('events_preset'));
		foreach ($preset as $key => $label) {
			$sel = ($instance['events_preset'] == $key)? ' selected="selected"': '';
			printf('<option value="%s"%s>%s</option>', $key, $sel, $label);
		}
		print('</select></p>');
		printf('<p><label for="%s">Display as:</label><select id="%s" name="%s">', $widget->get_field_id('events_format'), $widget->get_field_id('events_format'), $widget->get_field_name('events_format'));
        foreach (array('Title & Time'=>'title','Title & Time + Excerpt'=>'title_excerpt','Title & Time + Excerpt + Thumbnail'=>'title_excerpt_thumbnail') as $name => $value) {
        	$sel = ($value == $instance['events_format'])? ' selected="selected"': '';
        	printf('<option value="%s"%s>%s</option>', $value, $sel, $name);
        }
		print('</select></p>');
		printf('<p><label for="%s">Thumbnail size:</label>%s</p>', $widget->get_field_id('events_thumbnial_size'), self::get_image_sizes_select($widget->get_field_name('events_thumbnial_size'), $widget->get_field_id('events_thumbnial_size'), $instance['events_thumbnial_size']));


	}

	public static function process_widget_form($new_values, &$instance)
	{

	}

	/**
     * gets all configured image sizes in the theme and return them as 
     * an unordered list with dimensions and cropping details
     */
    public static function get_image_sizes_select($select_name = '', $select_id = '', $selected = '')
    {
		global $_wp_additional_image_sizes;
        $sizes = array();
        $out = "";
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
			$out .= '</select>';			
		}
		return $out;
	}


	/**
	 * get_date
	 * returns a text representation of a date for an event
	 */
	public static function get_date($event_id = false, $display_options = array())
	{
		if ($event_id === false) {
			global $post;
			$event_id = $post->ID;
		}
		$options = wp_parse_args($display_options, EventPostTypeOptions::get_plugin_options('ept_date_options'));
		$event_start = get_post_meta($event_id, 'event_start', true);
		$event_end = get_post_meta($event_id, 'event_end', true);
		$event_allday = (bool) get_post_meta($event_id, 'event_allday', true);
		$date_html = "";
		if ($event_start !== "") {
			if ($event_allday) {
				/* all day event - only need start date */
				$start_date = @date($options["date_fmt"], $event_start);
				$date_html = '<span class="event-date-label">' . $options["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span><span class="event-allday">' . $options["allday"] . '</span>';
			} else {
				/*
				 * either:
				 * - an event spanning multiple days
				 * - an event on a single day with time bracket specified
				 * - an event with only the start date set
				 */
				$start_date = @date($options["date_fmt"], $event_start);
				$start_time = @date($options["time_fmt"], $event_start);
				$end_date = @date($options["date_fmt"], $event_end);
				$end_time = @date($options["time_fmt"], $event_end);
				if (!$start_date) {
					/* no start date set (nothing to display) */
					$date_html = "";
				} elseif (!$end_date) {
					/* no end date set - output start date and time */
					$date_html = '<span class="event-date-label">' . $options["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span>' . $options["date_time_separator"];
					$date_html .= '<span class="event-time-label">' . $options["time_label"] . '</span><span class="event-start-time">' . $start_time . '</span>';
				} elseif ($start_date == $end_date) {
					/* start and end dates are on the same day */
					$date_html = '<span class="event-date-label">' . $options["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span>' . $options["date_time_separator"];
					$date_html .= '<span class="event-time-label">' . $options["time_label"] . '</span><span class="event-start-time">' . $start_time . '</span>' . $options["time_separator"] . '<span class="event-end-time">' . $end_time . '</span>';
				} else {
					/* start and end dates on different days */
					$date_html = '<span class="event-date-label">' . $options["date_label"] . '</span><span class="event-start-date">' . $start_date . '</span>' . $options["date_separator"] . '<span class="event-end-date">' . $end_date . '</span>';
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
		echo self::get_date($post->ID);
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

if (0 && !class_exists('Widget_EventsPostTypeList') ) :
/**
 * widget for events lists
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package WordPress
 * @subpackage EventPostType_Plugin
 */
class Widget_EventsPostTypeList extends WP_Widget {

	function Widget_EventsPostTypeList() {
		$widget_ops = array('classname' => 'widget_ept_list', 'description' => 'A list of upcoming and recent Events' );
		$this->WP_Widget('ept_list', 'Events List', $widget_ops);
	}

	function widget( $args, $instance )
	{
		global $post;
		if (EventPostType::is_event()) {
			return;
		}
		extract($args);
		echo $before_widget;
		echo '<div id="events_widget_wrap">';
		echo EventPostType::format_events($instance);
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$options = EventPostTypeOptions::get_plugin_options();
		$instance = $old_instance;
		$instance['category'] = trim($new_instance['category']);
		$instance['tag'] = trim($new_instance['tag']);
		$instance['start_date'] = (strtotime($new_instance['start_date']) === false)? '': $new_instance['start_date'];
		$instance['end_date'] = (strtotime($new_instance['end_date']) === false)? '': $new_instance['end_date'];
		$instance['current'] = isset($new_instance["current"])? 1: 0;
		$instance['sticky'] = isset($new_instance["sticky"])? 1: 0;
		$instance['class'] = trim(esc_attr($new_instance['class']));
		$instance['format'] = in_array($new_instance['format'], array_keys(EventPostTypeOptions::$formats))? $new_instance['format']: '';
		$possible_sizes = get_intermediate_image_sizes();
		if (in_array($new_instance['thumbnail_size_select'], $possible_sizes)) {
			$instance['size'] = $new_instance['thumbnail_size_select'];
		} elseif ($new_instance['thumbnail_size_select'] == "custom") {
			if (preg_match('/^([0-9]+),([0-9]+)$', $new_instance['thumbnail_size_input'], $matches)) {
				$instance['size'] = $new_instance['thumbnail_size_input'];
			} else {
				$instance['size'] = 'thumbnail';
			}
		} else {
			$instance['size'] = 'thumbnail';
		}
		$instance['include'] = trim($new_instance['include']);
		$instance['exclude'] = trim($new_instance['exclude']);
		$instance['max'] = intVal($new_instance['max']) > 0? intVal($new_instance['max']): '';
		return $instance;
	}

	function form( $instance )
	{
		global $ept;
		$instance = extract(wp_parse_args( (array) $instance, EventPostTypeOptions::get_plugin_options('ept_widget_options') ));
?>
		<fieldset><legend>Widget output</legend>

		<p><label for="<?php echo $this->get_field_id('max'); ?>">Maximum number events to show:</label>
		<?php echo $this->get_number_select($this->get_field_id('max'), $this->get_field_name('max'), intVal($max)); ?></p>

		<p><label for="<?php echo $this->get_field_id('current'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('current'); ?>" name="<?php echo $this->get_field_name('current'); ?>"<?php if ($current) { echo ' checked="checked"'; } ?> /> Check this box to show only future events</label></p>

		<p><label for="<?php echo $this->get_field_id('sticky'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('sticky'); ?>" name="<?php echo $this->get_field_name('sticky'); ?>"<?php if ($sticky) { echo ' checked="checked"'; } ?> /> Check this box to show only sticky events</label></p>

		<p><label for="<?php echo $this->get_field_id('class'); ?>">Class for events list:</label>
		<input maxlength="255" size="20" id="<?php echo $this->get_field_id('class'); ?>" name="<?php echo $this->get_field_name('class'); ?>" type="text" value="<?php echo esc_attr($class); ?>" /></p>

		<?php if (!has_filter("event-format")) : ?>
		<p><label for="<?php echo $this->get_field_id('format'); ?>">Format for listed events:</label>
		<?php echo EventPostTypeOptions::get_format_select($this->get_field_id('format'), $this->get_field_name('format'), $format); ?></p>
		<?php else: ?>
		<input type="hidden" name="<?php echo $this->get_field_name('format'); ?>" id="<?php echo $this->get_field_id('format'); ?>" value="user" />
		<?php endif; ?>

		</fieldset>

		<fieldset><legend>Include/Exclude specific events</legend>
		<p><label for="<?php echo $this->get_field_id('include'); ?>">Include specific events in the list:</label><input maxlength="255" size="20" id="<?php echo $this->get_field_id('include'); ?>" name="<?php echo $this->get_field_name('include'); ?>" type="text" value="<?php echo esc_attr($include); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('exclude'); ?>">Include specific events in the list:</label><input maxlength="255" size="20" id="<?php echo $this->get_field_id('exclude'); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" type="text" value="<?php echo esc_attr($exclude); ?>" /></p>
		</fieldset>

		<fieldset><legend>Filter Events by taxonomy</legend>
		<p><label for="<?php echo $this->get_field_id('category'); ?>">Filter by Category:</label>
		<input maxlength="255" size="20" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>" type="text" value="<?php echo esc_attr($category); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('tag'); ?>">Filter by Tags:</label>
		<?php echo $this->get_number_select($this->get_field_id('tag'), $this->get_field_name('tag'), $tag); ?></p>
		</fieldset>
		
		<fieldset><legend>Filter Events by date</legend>
		<p><label for="<?php echo $this->get_field_id('start_date'); ?>">Start date:</label>
		<input maxlength="255" size="20" id="<?php echo $this->get_field_id('start_date'); ?>" name="<?php echo $this->get_field_name('start_date'); ?>" type="text" value="<?php echo esc_attr($start_date); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('end_date'); ?>">End date:</label>
		<input maxlength="255" size="20" id="<?php echo $this->get_field_id('end_date'); ?>" name="<?php echo $this->get_field_name('end_date'); ?>" type="text" value="<?php echo esc_attr($end_date); ?>" /></p>
		</fieldset>

<?php
	}
	
	function get_number_select($id, $name, $selected, $max = 10)
	{
		$out = sprintf('<select name="%s" id="%s"><option value="0"></option>', $name, $id);
		for ($i = 1; $i <= $max; $i++) {
			$sel = $i == $selected? ' selected="selected"': '';
			$out .= sprintf('<option value="%s"%s>%s</option>', $i, $sel, $i);
		}
		$out .= '</select>';
		return $out;
	}

	/**
	 * set size of thumbnail to use in longer listing formats
	 * @uses get_intermediate_image_sizes()
	 */
	public static function get_thumbnail_size_select()
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$option_value = (isset($options['thumbnail_size']) && $options['thumbnail_size'] != "")? $options['thumbnail_size']: "";
		if (!has_filter("event-format")) {
			$sizes = get_intermediate_image_sizes();
			print('<select id="thumbnail_size_select" name="thumbnail_size_select">');
			foreach ($sizes as $size) {
				$sel = $option_value == $size? ' selected="selected"': '';
				printf('<option value="%s"%s>%s</option>', $size, $sel, $size);
			}
			$sel = !in_array($option_value, $sizes)? ' selected="selected"': '';
			$custom_value = !in_array($option_value, $sizes)? $option_value: '';
			printf('<option value="custom"%s>Custom&hellip;</option></select><br /><input type="text" name="thumbnail_size_input" id="thumbnail_size_input" size="7" value="%s" />', $sel, $custom_value);
		} else {
			$option_value = "user";
			print('(User defined)');
		}
		printf('<input type="hidden" id="ept_plugin_options_thumbnail_size" name="%s[thumbnail_size]" value="%s" />', $group, $option_value);
		printf('<p id="custom_thumbnail_desc"><em>%s</em></p>', __('Custom settings consist of two numbers separated by a comma. These represent the width and height of the cropped image.', 'event-post-type'));
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

if (!class_exists('EventPosttypeHelp' )) :
/**
 * Class to add help to the Admin screen for the Event post type
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package WordPress
 * @subpackage EventPostType_Plugin
 */
class EventPostTypeHelp
{
	/**
	 * adds an action to register the help tabs with the Wordpress API
	 */
	public static function register()
	{
		/* adds the help to the Wordpress help system */
		add_action( 'admin_head', array('EventPostTypeHelp', 'add_help') );
        /* adds a link to the help page from the plugins page */
        add_filter( 'plugin_action_links', array('EventPostTypeHelp', 'add_help_page_link'), 10, 2 );
	}

	/**
	 * adds a link to the help page from the plugins listing page
	 * called using the plugin_action_links filter
	 */
	public static function add_help_page_link($links, $file)
	{
		if ($file == plugin_basename(__FILE__)) {
			$help_page_link = '<a href="edit.php?post_type=event&amp;page=event_options&amp;tab=ept_help">Help</a>';
			array_unshift($links, $help_page_link);
		}
		return $links;
	}

	/**
	 * adds help to the Wordpress help system
	 * this will include HTML from the /doc directory and place it in 
	 * help tabs in the Wordpress help system
	 */
	public static function add_help()
	{
    	$index_tab = array(
            "id" => "event-post-type-help",
            "title" => "Events",
            "callback" => array( 'EventPostTypeHelp', 'help_index' )
    	);
    	$options_tab = array(
            "id" => "event-post-type-options",
            "title" => "Events options",
            "callback" => array( 'EventPostTypeHelp', 'help_options' )
    	);
    	$shortcode_tab = array(
            "id" => "event-post-type-shortcode",
            "title" => "Events shortcode",
            "callback" => array( 'EventPostTypeHelp', 'help_shortcode' )
    	);
    	$widget_tab = array(
            "id" => "event-post-type-widget",
            "title" => "Events widget",
            "callback" => array( 'EventPostTypeHelp', 'help_widgets' )
    	);
    	$screen_tab = array(
    		"id" => "debug-screen-object-tab",
    		"title" => "screen",
    		"callback" => array( 'EventPostTypeHelp', 'help_screen' )
    	);
    	$screen = get_current_screen();
    	if ($screen->post_type == "event") {
    		$screen->set_help_sidebar(self::help_sidebar());
    		$screen->add_help_tab($index_tab);
    		$screen->add_help_tab($screen_tab);
	    	switch ($screen->id) {
	    		case "post":
	    		case "edit-post":
	    			$screen->add_help_tab($options_tab);
	    			$screen->add_help_tab($shortcode_tab);
	    			$screen->add_help_tab($widget_tab);
	    			break;
	    	}
		}  	
    }

    /**
     * Adds help to the plugin options page (in a tab)
     */
    public static function getAdminHelpPage()
    {
    	$sections = array(
    		"index"     => __('EventPostType plugin help', 'event-post-type'),  
    		"options"   => __('Plugin options', 'event-post-type'), 
    		"shortcode" => __('Shortcode', 'event-post-type'), 
    		"widgets"   => __('Widgets', 'event-post-type')
    	);
    	$out = '<div id="ept-help-tabs"><ul>';
    	$content = "";
    	foreach ($sections as $section => $name) {
    		$out .= sprintf('<li><a href="#%s-content">%s</a></li>', $section, $name);
	    	$content .= sprintf('<div id="%s-content">%s</div>', $section, self::get_contents($section . ".html"));
	   	}
	   	$out .= '</ul>';
	   	$out .= $content;
	   	$out .= '</div>';
	   	echo $out;
    }

    /**
     * gets the content for the help sidebar
     */
    public static function help_sidebar()
    {
    	return self::get_contents("sidebar.html");
    }

    /**
     * gets the content for the help index
     */
    public static function help_index()
    {
    	echo self::get_contents("index.html");
    }

    /**
     * gets the content for the event options help
     */
    public static function help_options()
    {
    	echo self::get_contents("options.html");
    }

    /**
     * gets the content for the shortcode help
     */
    public static function help_shortcode()
    {
    	echo self::get_contents("shortcode.html");
    }

    /**
     * gets the content for the widget help
     */
    public static function help_widgets()
    {
    	echo self::get_contents("widgets.html");
    }

    public static function help_screen()
    {
    	print_r(get_current_screen());
    }

    /**
     * returns the contents of a file in the doc/[locale]/ directory
     */
    private static function get_contents($filename = "")
    {
    	if (trim($filename) !== "") {
    		$path = plugin_dir_path(__FILE__) . 'doc/' . get_locale() . '/' . $filename;
    		if (file_exists($path)) {
    			return file_get_contents($path);
    		}
    	}
    	return "";
    }
}
EventPostTypeHelp::register();
endif;

/************************************************************
 * PLUGIN OPTIONS ADMINISTRATION							*
 ************************************************************/
if ( ! class_exists('EventPostTypeOptions')) :
/**
 * Class to add options for the Event post type
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package WordPress
 * @subpackage EventPostType_Plugin
 */
class EventPostTypeOptions
{

	/**
	 * register with the Wordpress API
	 */
	public static function register()
	{
		/* add a menu item to the Events Post type menu */
		add_action( 'admin_menu', array('EventPostTypeOptions', 'add_plugin_admin_menu') );
		/* register plugin admin options */
		add_action( 'admin_init', array('EventPostTypeOptions', 'register_plugin_options') );
	}

	/**
	 * add a submenu to the theme admin menu to access the theme settings page
	 */
	public static function add_plugin_admin_menu()
	{
		/* Plugin Options page */
		$options_page = add_submenu_page("edit.php?post_type=event", "Events Options", "Events Options", "manage_options", "event_options", array('EventPostTypeOptions', "plugin_options_page") );
	}

	/**
	 * registers settings and sections
	 */
	function register_plugin_options()
	{
		register_setting('ept_plugin_options', 'ept_plugin_options', array('EventPostTypeOptions', 'validate_ept_plugin_options'));
		register_setting('ept_archive_options', 'ept_archive_options', array('EventPostTypeOptions', 'validate_ept_archive_options'));
		register_setting('ept_widget_options', 'ept_widget_options', array('EventPostTypeOptions', 'validate_ept_widget_options'));
		register_setting('ept_date_options', 'ept_date_options', array('EventPostTypeOptions', 'validate_ept_date_options'));
				
		/* main plugin options */
		add_settings_section(
			'main-options',
			__('Main Plugin Options', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_section_text'),
			'ept_plugin_options_section'
		);
		add_settings_field(
			'post_type_slug',
			__('Post type slug', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_text'),
			'ept_plugin_options_section',
			'main-options',
			array(
				"settings-group" => 'ept_plugin_options', 
				"fieldname" => "post_type_slug", 
				"description" => __('This will form the basis of all URLs for the events plugin', 'event-post-type')
			)
		);
		add_settings_field(
			'post_type_future_slug',
			__('Future events slug', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_text'),
			'ept_plugin_options_section',
			'main-options',
			array(
				"settings-group" => 'ept_plugin_options', 
				"fieldname" => "post_type_future_slug", 
				"description" => __('This is used in the URL for events taking place in the future (beyond those shown on the archive page)', 'event-post-type')
			)
		);
		add_settings_field(
			'event_category_slug',
			__('Event Category slug', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_text'),
			'ept_plugin_options_section',
			'main-options',
			array(
				"settings-group" => 'ept_plugin_options', 
				"fieldname" => "event_category_slug", 
				"description" => __('This will form the basis of all URLs for event categories', 'event-post-type')
			)
		);
		add_settings_field(
			'event_tag_slug',
			__('Event Tag slug', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_text'),
			'ept_plugin_options_section',
			'main-options',
			array(
				"settings-group" => 'ept_plugin_options', 
				"fieldname" => "event_tag_slug", 
				"description" => __('This will form the basis of all URLs for event tags', 'event-post-type')
			)
		);
		add_settings_field(
			'enqueue_js',
			_x('Enqueue Javascript', 'Whether to enqueue script from the plugin or from the theme', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_checkbox'),
			'ept_plugin_options_section', 
			'main-options', 
			array(
				"settings-group" => 'ept_plugin_options', 
				"fieldname" => "enqueue_js",
				"description" => sprintf( __('Check this box if you would like the script for the plugin to be loaded in the front end.<br />If this box is not checked, <a href="%s">download the script here and include it in your theme</a>.', 'event-post-type'), plugins_url('/js/EventPostType.min.js', __FILE__))
			)
		);
        add_settings_field(
        	'enqueue_css', 
        	_x('Enqueue CSS', 'Whether to enqueue CSS from the plugin or from the theme', 'event-post-type'),
        	array('EventPostTypeOptions', 'ept_setting_checkbox'),
			'ept_plugin_options_section', 
			'main-options',
			array(
				"settings-group" => 'ept_plugin_options', 
				"fieldname" => "enqueue_css",
				"description" => sprintf( __('Check this box if you would like the CSS for the plugin to be loaded in the front end.<br />If this box is not checked, <a href="%s">download the CSS here and include it in your theme</a>.', 'event-post-type'), plugins_url('/css/EventPostType.min.css', __FILE__))
			)
		);

		/* archive page options */
		add_settings_section(
			'archive-options',
			__('Events Archive Options', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_section_text'),
			'ept_archive_options_section'
		);
		add_settings_field(
			'archive_title',
			__('Archive page title', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_text'),
			'ept_archive_options_section',
			'archive-options',
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_title", 
				"description" => __('This title will be displayed at the top of all archive pages.', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_frontpage_content',
			__('Archive page content', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_richtext'),
			'ept_archive_options_section',
			'archive-options',
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_content", 
				"description" => __('Text to put at the top of the main archive page', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_search',
			__('Display Search bar?', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_checkbox'),
			'ept_archive_options_section',
			'archive-options',
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_search", 
				"description" => __('The search bar includes Event specific search box, and a dropdown to filter by event category. Checking this box will display this bar at the top of all archive pages.', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_calendar',
			__('Display calendar?', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_checkbox'),
			'ept_archive_options_section',
			'archive-options',
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_calendar", 
				"description" => __('This will place an events calendar on the archive pages.', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_frontpage_sticky',
			__('Number of sticky events to display on the main events archive page', 'event-post-type'),
			array('EventPostTypeOptions', 'ept_setting_number'),
			'ept_archive_options_section',
			'archive-options',
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_frontpage_sticky", 
				"description" => __('This will limit the number of upcoming &ldquo;sticky&rdquo; events displayed on the main archive and taxonomy archive pages', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_frontpage_events', 
			__('Number of regular events to display on the main events archive page (excluding sticky events)', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_number'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_frontpage_events", 
				"description" => __('The number of non-sticky events to display on the main archive page. This will be made up from upcoming events (in chronological order with the nearest first).', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_perpage', 
			__('Number of events to display per page', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_number'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_perpage", 
				"description" => __('Number of events displayed per page in the archive', 'event-post-type')
			)
		);
		add_settings_field(
			'archive_format', 
			__('Format of events on archive pages', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_format'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_format", 
				"description" => ""
			)
		);
		add_settings_field(
			'archive_thumbnail_size', 
			__('Thumbnail size to use on archive pages', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_thumbnail'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_thumbnail_size"
			)
		);

		/* widget/shortcode options */
		add_settings_section(
			'widget-options',
			__('Shortcode/Widget default options', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_section_text'), 
			'ept_widget_options_section'
		);
		add_settings_field(
			'current', 
			__('Show current events?', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_checkbox'), 
			'ept_widget_options_section', 
			'widget-options', 
			array(
				"settings-group" => 'ept_widget_options', 
				"fieldname" => "current", 
				"description" => __('Check this box if the default is to show events which are in the future', 'event-post-type')
			)
		);
		add_settings_field(
			'sticky', 
			__('Show only sticky events?', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_checkbox'), 
			'ept_widget_options_section', 
			'widget-options', 
			array(
				"settings-group" => 'ept_widget_options', 
				"fieldname" => "sticky", 
				"description" => __('Check this box if the default is to show only sticky events', 'event-post-type')
			)
		);
		add_settings_field(
			'max', 
			__('Maximum number of events to display', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_number'), 
			'ept_widget_options_section', 
			'widget-options', 
			array(
				"settings-group" => 'ept_widget_options', 
				"fieldname" => "max", 
				"description" => ""
			)
		);
		add_settings_field(
			'format', 
			__('Format of events list', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_format'), 
			'ept_widget_options_section', 
			'widget-options', 
			array(
				"settings-group" => 'ept_widget_options', 
				"fieldname" => "format", 
				"description" => ""
			)
		);
		add_settings_field(
			'thumbnail_size', 
			__('Thumbnail size to use in longer listing formats', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_thumbnail'), 
			'ept_widget_options_section', 
			'widget-options', 
			array(
				"settings-group" => 'ept_widget_options', 
				"fieldname" => "thumbnail_size"
			)
		);
		
		/* date format options */
		add_settings_section(
			'date-options', 
			__('Date Display Options', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_section_date'), 
			'ept_date_options_section'
		);
		add_settings_field(
			'date_fmt', 
			__('Date format', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "date_fmt", 
				"description" => __('Use format strings for the <a href="http://www.php.net/manual/en/function.date.php">PHP <code>date()</code> function</a> here', 'event-post-type')
			)
		);
		add_settings_field(
			'time_fmt', 
			__('Time format', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "time_fmt", 
				"description" => __('Use format strings for the <a href="http://www.php.net/manual/en/function.date.php">PHP <code>date()</code> function</a> here', 'event-post-type')
			)
		);
		add_settings_field(
			'date_label', 
			__('Date label', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "date_label", 
				"description" => __("Text used as a label preceding a date", 'event-post-type')
			)
		);
		add_settings_field(
			'time_label', 
			__('Time label', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "time_label", 
				"description" => __("Text used as a label preceding a time", 'event-post-type')
			)
		);
		add_settings_field(
			'date_time_separator', 
			__('Date/Time separator', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "date_time_separator", 
				"description" => __("Text used to separate dates and times", 'event-post-type')
			)
		);
		add_settings_field(
			'date_separator', 
			__('Date separator', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "date_separator", 
				"description" => __("Text used to separate two dates when an event spans multiple days", 'event-post-type')
			)
		);
		add_settings_field(
			'time_separator',
			__('Time separator', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "time_separator", 
				"description" => __("Text used to separate two times when an event takes place between two times on the same day", 'event-post-type')
			)
		);
		add_settings_field(
			'allday', 
			__('All day event indicator', 'event-post-type'), 
			array('EventPostTypeOptions', 'ept_setting_dateformat'), 
			'ept_date_options_section', 
			'date-options', 
			array(
				"settings-group" => 'ept_date_options', 
				"fieldname" => "allday", 
				"description" => __("Text placed after a date for an all day event", 'event-post-type')
			)
		);
	}

	/**
	 * creates the options page
	 */
	public static function plugin_options_page()
	{
		printf('<div class="wrap"><div class="icon32" id="icon-options-general"><br /></div><h2>%s</h2>', __('Events Options', 'event-post-type'));
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'ept_plugin_options';
		if ($active_tab != "ept_help") {
			settings_errors($active_tab);
		}
		if (isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == "true")
		{
			printf('<div id="message" class="updated fadeout"><p><strong>%s.</strong></p></div>', __('Settings saved', 'event-post-type'));
		}
		$settings_sections = array(
			"ept_plugin_options" => __("Plugin Options", 'event-post-type'),
			"ept_archive_options" => __("Archive page Options", 'event-post-type'),
			"ept_widget_options" => __("Widget/Shortcode Settings", 'event-post-type'),
			"ept_date_options"   => __("Date Settings", 'event-post-type'),
			"ept_help"           => __("Help", 'event-post-type')
		);
		print('<h2 class="nav-tab-wrapper">');
		foreach ($settings_sections as $setting => $section)
		{
			$activeclass = ($active_tab == $setting)? ' nav-tab-active': '';
			printf('<a href="%s%s" class="nav-tab%s">%s</a>', admin_url('admin.php?page=event_options&tab='), $setting, $activeclass, $section);
		}
		print('</h2>');
		if ($active_tab == "ept_help") {
			EventPostTypeHelp::getAdminHelpPage();
		} else {
			print('<form method="post" action="options.php">');
			settings_fields($active_tab);
			do_settings_sections($active_tab . '_section');
			printf('<p class="submit"><input type="submit" class="button-primary" name="Submit" value="%s" /></p>', __('Save Changes', 'event-post-type'));
			print('</form>');
		}
		print('</div>');
	}

	/**
	 * settings section text
	 */
	public static function ept_section_text()
		{ echo ""; }

	/**
	 * settings section text
	 */
	public static function ept_section_date()
		{ echo "<div id=\"date_preview\"></div>"; }

	/**
	 * input for text
	 */
	public static function ept_setting_text($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$option_value = (isset($options[$field ]) && trim($options[$field ]) != "")? trim($options[$field ]): "";
		printf('<input id="%s" name="%s[%s]" type="text" value="%s" size="20" />', $field, $group, $field, $option_value);
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * returns the HTML for a rich text field
	 */
	public static function ept_setting_richtext($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$option_value = (isset($options[$field ]) && trim($options[$field ]) != "")? trim($options[$field ]): "";
        /* wordpress richtext editor ID can only contain lowercase letters! */
        $editor_id = preg_replace( "/[^a-z]*/", "", strtolower($field) );
        /* options for editor */
        $options = array(
            //"wpautop" => true,
            "media_buttons" => false,
            "textarea_name" => $field,
            "textarea_rows" => 3,
            "teeny" => true //use minimal editor configuration
        );
        /* echo the editor */
        wp_editor($option_value, $editor_id, $options );
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	
	/**
	 * input for date format text
	 */
	public static function ept_setting_dateformat($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$option_value = (isset($options[$field]))? htmlentities($options[$field]): "";
		printf('<input class="dateformat" id="%s" name="%s[%s]" type="text" value="%s" size="10" />', $field, $group, $field, $option_value);
		if ($field == 'date_fmt' || $field == 'time_fmt') {
			/* example date/time */
			$ts = mktime(13,45,0,date("n"),date("j"),date("Y"));
			/* date format examples */
			$examples = array(
				"date_fmt" => array("d/m/Y", "j/n/Y", "d.m.Y", "j.n.Y", "M j, Y", "M jS, Y", "F j, Y", "F jS, Y", "jS M, Y", "jS F, Y", "l jS F, Y", "D jS F, Y"),
				"time_fmt" => array("g:ia", "g.ia", "g:iA", "g.iA", "h:ia", "h.ia", "h:iA", "h.iA", "G:i", "G.i")
			);
			print('<select id="ex_' . $field . '" class="format-examples"><option value="" selected="selected">Examples...</option>');
			foreach ($examples[$field] as $ex) {
				printf('<option value="%s">%s</option>', $ex, date($ex, $ts));
			}
			print('</select>');
		}
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * input for number
	 */
	public static function ept_setting_number($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$option_value = (isset($options[$field]) && $options[$field] != "")? intval($options[$field]): "";
		printf('<input id="%s" name="%s[%s]" type="text" value="%s" size="2" />', $field, $group, $field, $option_value);
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * input field for format
	 */
	public static function ept_setting_checkbox($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$chckd = ($options[$field])? ' checked="checked"': '';
		printf('<input id="%s" name="%s[%s]" type="checkbox"%s />', $field, $group, $field, $chckd);
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * input field for format
	 */
	public static function ept_setting_format($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$formats = array_keys(self::get_formats());
		$option_value = (isset($options[$field]) && $options[$field] != "" && in_array($options[$field], $formats))? $options[$field]: $formats[0];
		print self::get_format_select($field, $group . "[" . $field . "]", $option_value);
	}

	/**
	 * formats allowed for output of events
	 */
	public static function get_formats()
	{
		return array(
			"list" => __('Title and date', 'event-post-type'),
			"short" => __('Title, date and excerpt', 'event-post-type'),
			"featured" => __('Post thumbnail, title, date and excerpt', 'event-post-type'),
			"full" => __('Title, date and full content', 'event-post-type')
		);
	}

	/**
	 * gets a select list of output formats
	 * checks to see if a filter has been registered on "event-format"
	 */
	public static function get_format_select($id, $name, $selected = "list")
	{
		$out = "";
		if (!has_filter("event-format")) {
			$out .= '<ul>';
			$suffix = "1";
			$formats = self::get_formats();
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
	 * set size of thumbnail to use in longer listing formats
	 * @uses get_intermediate_image_sizes()
	 */
	public static function ept_setting_thumbnail($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options($group);
		$option_value = (isset($options['thumbnail_size']) && $options['thumbnail_size'] != "")? $options['thumbnail_size']: "";
		if (!has_filter("event-format")) {
			$sizes = get_intermediate_image_sizes();
			print('<select id="thumbnail_size_select" name="thumbnail_size_select">');
			foreach ($sizes as $size) {
				$sel = $option_value == $size? ' selected="selected"': '';
				printf('<option value="%s"%s>%s</option>', $size, $sel, $size);
			}
			$sel = !in_array($option_value, $sizes)? ' selected="selected"': '';
			$custom_value = !in_array($option_value, $sizes)? $option_value: '';
			printf('<option value="custom"%s>Custom&hellip;</option></select><br /><input type="text" name="thumbnail_size_input" id="thumbnail_size_input" size="7" value="%s" />', $sel, $custom_value);
		} else {
			$option_value = "user";
			print('(User defined)');
		}
		printf('<input type="hidden" id="ept_plugin_options_thumbnail_size" name="%s[thumbnail_size]" value="%s" />', $group, $option_value);
		printf('<p id="custom_thumbnail_desc"><em>%s</em></p>', __('Custom settings consist of two numbers separated by a comma. These represent the width and height of the cropped image.', 'event-post-type'));
	}

	/**
	 * gets all default plugin options
	 */
	public static function get_default_options()
	{
		return array(
			'ept_plugin_options' => array(
				'post_type_slug' => 'events',
				'post_type_future_slug' => 'future',
				'event_category_slug' => 'category',
				'event_tag_slug' => 'tag',
				'enqueue_js' => true,
				'enqueue_css' => true
			),
			'ept_archive_options' => array(
				'archive_title' => __('Events', 'event-post-type'),
				'archive_frontpage_content' => '',
				'archive_search' => false,
				'archive_calendar' => false,
				'archive_frontpage_sticky' => 1,
				'archive_frontpage_events' => 8, 
				'archive_perpage' => 10
			),
			'ept_widget_options' => array(
				"current" => true,
				"sticky" => true,
				"max" => 4,
				"format" => "list",
				"thumbnail_size" => "thumbnail",
		        "category" => "",
		        "tag" => "",
		        "start_date" => "",
		        "end_date" => "",
		        "class" => "",
		        "include" => "",
		        "exclude" => ""
			),
			'ept_date_options' => array(
				"date_fmt" => "j/n/Y",
				"time_fmt" => "g.ia",
				"date_time_separator" => " | ",
				"time_separator" => " &ndash; ",
				"date_separator" => " &ndash; ",
				"allday" => __(" (all day)", 'event-post-type'),
				"date_label" => __("Date: ", 'event-post-type'),
				"time_label" => __("Time: ", 'event-post-type')
			)
		);
	}

	/**
	 * gets plugin options - merges saved options with defaults
	 * @return array
	 */
	public static function get_plugin_options()
	{

		$defaults = self::get_default_options();
		$all_options = array();
		foreach ($defaults as $option => $default_settings) {
			$saved = get_option($option);
			$all_options[$option]  = wp_parse_args($saved, $default_settings);
		} 
		return $all_options;
	}

	/**
	 * input validation callbacks
	 */
	public static function validate_ept_plugin_options($ept_plugin_options)
	{
		$defaults = self::get_default_options();
		$ept_plugin_options['enqueue_js'] = isset($ept_plugin_options['enqueue_js']);
		$ept_plugin_options['enqueue_css'] = isset($ept_plugin_options['enqueue_css']);
		foreach(array('post_type_slug', 'post_type_future_slug', 'event_category_slug', 'event_tag_slug') as $opt) {
			$ept_plugin_options[$opt] = (trim(preg_replace('/[^a-z]/', '', strtolower($ept_plugin_options[$opt]))) == '')? $defaults['ept_plugin_options'][$opt]: trim(preg_replace('/[^a-z]/', '', strtolower($ept_plugin_options[$opt])));
		}
		return $ept_plugin_options;
	}
	public static function validate_ept_archive_options($ept_archive_options)
	{
		$defaults = self::get_default_options();
		$ept_archive_options['archive_search'] = isset($ept_archive_options['archive_search']);
		$ept_archive_options['archive_calendar'] = isset($ept_archive_options['archive_calendar']);
		foreach(array('archive_frontpage_sticky', 'archive_frontpage_events', 'archive_perpage') as $o) {
			$ept_archive_options[$o] = (intval($ept_archive_options[$o]) < 0)? $defaults['ept_archive_options'][$o]: intval($ept_archive_options[$o]);
		}
		return $ept_archive_options;
	}
	public static function validate_ept_widget_options($ept_widget_options)
	{
		//print_r($ept_widget_options);exit();
		return $ept_widget_options;
	}
	public static function validate_ept_date_options($ept_date_options)
	{
		//print_r($ept_widget_options);exit();
		return $ept_date_options;
	}

}/* end of class definition EventPostTypeOptions */
EventPostTypeOptions::register();
endif;