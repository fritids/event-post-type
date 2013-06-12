<?php
/**
 * EventPostType Admin and help classes
 * http://essl-pvac.github.com
 * @version: 1.2
 * @author: Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @license: GPL2
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


/************************************************************
 * PLUGIN OPTIONS ADMINISTRATION							*
 ************************************************************/
if ( ! class_exists('EventPostTypeAdmin')) :
/**
 * Class to add options for the Event post type
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package WordPress
 * @subpackage EventPostType_Plugin
 */
class EventPostTypeAdmin
{

	/**
	 * register with the Wordpress API
	 */
	public static function register()
	{
		/* add a menu item to the Events Post type menu */
		add_action( 'admin_menu', array(__CLASS__, 'add_plugin_admin_menu') );
		/* register plugin admin options */
		add_action( 'admin_init', array(__CLASS__, 'register_plugin_options') );
	}

	/**
	 * add a submenu to the theme admin menu to access the theme settings page
	 */
	public static function add_plugin_admin_menu()
	{
		/* Plugin Options page */
		$options_page = add_submenu_page("edit.php?post_type=event", "Events Options", "Events Options", "manage_options", "event_options", array(__CLASS__, "plugin_options_page") );
	}

	/**
	 * registers settings and sections
	 */
	public static function register_plugin_options()
	{
		register_setting('ept_plugin_options', 'ept_plugin_options', array(__CLASS__, 'validate_ept_plugin_options'));
		register_setting('ept_archive_options', 'ept_archive_options', array(__CLASS__, 'validate_ept_archive_options'));
		register_setting('ept_date_options', 'ept_date_options', array(__CLASS__, 'validate_ept_date_options'));
				
		/* main plugin options */
		add_settings_section(
			'main-options',
			__('Main Plugin Options', 'event-post-type'),
			array(__CLASS__, 'ept_section_text'),
			'ept_plugin_options_section'
		);
		add_settings_field(
			'post_type_slug',
			__('Post type slug', 'event-post-type'),
			array(__CLASS__, 'ept_setting_text'),
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
			array(__CLASS__, 'ept_setting_text'),
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
			array(__CLASS__, 'ept_setting_text'),
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
			array(__CLASS__, 'ept_setting_text'),
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
			array(__CLASS__, 'ept_setting_checkbox'),
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
			array(__CLASS__, 'ept_setting_checkbox'),
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
			array(__CLASS__, 'ept_section_text'),
			'ept_archive_options_section'
		);
		add_settings_field(
			'archive_title',
			__('Archive page title', 'event-post-type'),
			array(__CLASS__, 'ept_setting_text'),
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
			array(__CLASS__, 'ept_setting_richtext'),
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
			array(__CLASS__, 'ept_setting_checkbox'),
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
			array(__CLASS__, 'ept_setting_checkbox'),
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
			__('The number of &ldquo;sticky&rdquo; events displayed on the main events archive page', 'event-post-type'),
			array(__CLASS__, 'ept_setting_number'),
			'ept_archive_options_section',
			'archive-options',
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_frontpage_sticky", 
				"description" => ''
			)
		);
		add_settings_field(
			'archive_frontpage_events', 
			__('The number of events to display on the main events archive page (excluding sticky events)', 'event-post-type'), 
			array(__CLASS__, 'ept_setting_number'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_frontpage_events", 
				"description" => ''
			)
		);
		add_settings_field(
			'archive_perpage', 
			__('Number of events to display per page', 'event-post-type'), 
			array(__CLASS__, 'ept_setting_number'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_perpage", 
				"description" => ''
			)
		);
		add_settings_field(
			'archive_format', 
			__('Format of individual events on the archive pages', 'event-post-type'), 
			array(__CLASS__, 'ept_setting_format'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_format", 
				"description" => ''
			)
		);
		add_settings_field(
			'archive_thumbnail_size', 
			__('Format of individual events on the archive pages', 'event-post-type'), 
			array(__CLASS__, 'ept_setting_thumbnail_size'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_thumbnail_size", 
				"description" => ''
			)
		);
		add_settings_field(
			'archive_title_tag', 
			__('HTML tag used for titles of individual events on the archive pages', 'event-post-type'), 
			array(__CLASS__, 'ept_setting_title_tag'), 
			'ept_archive_options_section', 
			'archive-options', 
			array(
				"settings-group" => 'ept_archive_options', 
				"fieldname" => "archive_title_tag", 
				"description" => ''
			)
		);


		/* date format options */
		add_settings_section(
			'date-options', 
			__('Date Display Options', 'event-post-type'), 
			array(__CLASS__, 'ept_section_date'), 
			'ept_date_options_section'
		);
		add_settings_field(
			'date_fmt', 
			__('Date format', 'event-post-type'), 
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			array(__CLASS__, 'ept_setting_dateformat'), 
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
			"ept_date_options"   => __("Date Settings", 'event-post-type'),
			"ept_help"		   => __("Help", 'event-post-type')
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
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field]) && trim($options[$group][$field]) != "")? trim($options[$group][$field]): "";
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
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field ]) && trim($options[$group][$field ]) != "")? trim($options[$field ]): "";
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
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field]))? htmlentities($options[$group][$field]): "";
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
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field]) && $options[$group][$field] != "")? intval($options[$group][$field]): "";
		printf('<input id="%s" name="%s[%s]" type="text" value="%s" size="2" />', $field, $group, $field, $option_value);
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * input for number
	 */
	public static function ept_setting_format($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field]) && $options[$group][$field] != "")? $options[$group][$field]: "";
		$fieldname = $group . '[' . $field . ']';
		$fieldid = $group . '_' . $field;
		print(EventPostType::get_format_select($fieldid, $fieldname, $option_value));
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
		$options = self::get_plugin_options();
		$chckd = ($options[$group][$field])? ' checked="checked"': '';
		printf('<input id="%s" name="%s[%s]" type="checkbox"%s />', $field, $group, $field, $chckd);
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * input field for thumbnail size
	 */
	public static function ept_setting_thumbnail_size($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field]) && $options[$group][$field] != "")? $options[$group][$field]: "";
		$fieldname = $group . '[' . $field . ']';
		$fieldid = $group . '_' . $field;
		print(EventPostType::get_image_sizes_select($fieldname, $fieldid, $option_value));
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * input field for title tag
	 */
	public static function ept_setting_title_tag($args)
	{
		$field = $args["fieldname"];
		$group = $args["settings-group"];
		$options = self::get_plugin_options();
		$option_value = (isset($options[$group][$field]) && $options[$group][$field] != "")? $options[$group][$field]: "";
		$fieldname = $group . '[' . $field . ']';
		$fieldid = $group . '_' . $field;
		print(EventPostType::get_title_tag_select($fieldname, $fieldid, $option_value));
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
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
				'archive_perpage' => 10,
				'archive_format' => 'title_excerpt_thumbnail',
				'archive_thumbnail_size' => 'thumbnail',
				'archive_title_tag' => 'h3'
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
	public static function validate_ept_date_options($ept_date_options)
	{
		//print_r($ept_widget_options);exit();
		return $ept_date_options;
	}

	

}/* end of class definition EventPostTypeOptions */
EventPostTypeAdmin::register();
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
			"title" => __("Events", 'event-post-type'),
			"callback" => array( 'EventPostTypeHelp', 'help_index' )
		);
		$options_tab = array(
			"id" => "event-post-type-options",
			"title" => __("Events options", 'event-post-type'),
			"callback" => array( 'EventPostTypeHelp', 'help_options' )
		);
		$shortcode_tab = array(
			"id" => "event-post-type-shortcode",
			"title" => __("Events shortcode", 'event-post-type'),
			"callback" => array( 'EventPostTypeHelp', 'help_shortcode' )
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
			//$screen->add_help_tab($screen_tab);
			switch ($screen->id) {
				case "post":
				case "edit-post":
					$screen->add_help_tab($options_tab);
					$screen->add_help_tab($shortcode_tab);
					break;
			}
		}  	
	}

	/**
	 * Adds help to the plugin options page (in a tab)
	 */
	public static function getAdminHelpPage()
	{
		$sections = array("index", "options", "shortcode");
		$content = "";
		foreach ($sections as $section) {
			$content .= sprintf('<div id="%s-content">%s</div>', $section, self::get_contents($section . ".html"));
	   	}
	   	echo $content;
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
			} else {
				$path = plugin_dir_path(__FILE__) . 'doc/en_US/' . $filename;
				if (file_exists($path)) {
					return file_get_contents($path);
				}
			}
		}
		return "";
	}
}
EventPostTypeHelp::register();
endif;
