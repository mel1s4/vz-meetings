<?php
/*
Plugin Name: Viroz Meeting Manager
Plugin URI: https://viroz.studio/vz-meeting-manager
Description: This plugin will help you to sell and manage your meetings for services and products.
Version: 0.0.1
Author: Melisa Viroz
Author URI: https://melisaviroz.com
License: GPL2
*/

if (!defined('ABSPATH')) {
  die;
}

add_action('init', 'vz_am_load_init_file');
function vz_am_load_init_file() {
  include 'init.php';
}

include 'viroz_helpers.php';
include 'api_calls.php';
include 'calendar-block/calendar-block.php';


add_action('admin_enqueue_scripts', 'vz_am_enqueue_styles');
function vz_am_enqueue_styles() {
  wp_enqueue_style('vz-am-styles', plugin_dir_url(__FILE__) . 'style.css');  
  if (get_current_screen()->post_type === 'vz-calendar') {
    wp_enqueue_style('vz-availability-rules-styles', plugin_dir_url(__FILE__) . 'availability-rules/build/static/css/main.css', array(), '1.0.0', 'all');
    wp_enqueue_script('vz-availability-rules', plugin_dir_url(__FILE__) . 'availability-rules/build/static/js/main.js' , array('wp-element'), '0.0.1', true);
    $availability_rules = get_post_meta(get_the_ID(), 'vz_availability_rules', true);
    if (!$availability_rules) {
      $availability_rules = '[]';
    }
    $params = [
      'availability_rules' => JSON_decode($availability_rules),
      'time_zone' => get_option('timezone_string'),
      'rest_url' => get_rest_url(),
      'meeting_duration' => get_post_meta(get_the_ID(), 'vz_am_duration', true),
      'meeting_rest' => get_post_meta(get_the_ID(), 'vz_am_rest', true),
      'maximum_days_in_advance' => get_post_meta(get_the_ID(), 'vz_am_maximum_days_in_advance', true),
      'enabled' => get_post_meta(get_the_ID(), 'vz_am_enabled', true),
      'requires_invite' => get_post_meta(get_the_ID(), 'vz_am_requires_invite', true),
      'calendar_id' => get_the_ID(),
      'rest_nonce' => wp_create_nonce('wp_rest'),
    ];
    wp_localize_script('vz-availability-rules', 'vz_availability_rules_params', $params);
  }
}

// create settings page with a single text input field
add_action('admin_menu', 'vz_am_settings_page');
function vz_am_settings_page() {
  add_menu_page(
    __vz("Meetings"),
    __vz("Meetings"),
    'manage_options', 
    'vz_am_settings', 
    'vz_am_settings_page_content',
    'dashicons-calendar-alt',
  );
  add_submenu_page(
    'vz_am_settings',
    __vz('Settings'),
    __vz('Settings'), 
    'manage_options', 
    'vz_am_my_schedule', 
    'vz_am_settings_page_content'
  );
}

function vz_am_settings_page_content() {
  echo '<h1>' . __vz("Meetings") . '</h1>';
}

function vz_am_my_schedule_page_content() {
  echo '<h1>' . __vz("Calendar") . '</h1>';
  echo "<div id='vz-am-schedule'></div>";
}

add_action('wp_enqueue_scripts', 'vz_am_enqueue_calendar_scripts');
function vz_am_enqueue_calendar_scripts() {
  global $post;
  $id = $post->ID;
  // if is single vz-calendar post
  if (is_single() && $post->post_type === 'vz-calendar') {
    wp_enqueue_style('vz-calendar-view-styles', plugin_dir_url(__FILE__) . 'calendar-view/build/static/css/main.css', array(), '1.0.0', 'all');
    wp_enqueue_script('vz-calendar-view', plugin_dir_url(__FILE__) . 'calendar-view/build/static/js/main.js' , array('wp-element'), '0.0.1', true);
    // get meetings from the same calendar_id and user_id, and that their date_time is greater than the current date
    $meetings = get_posts(array(
      'post_type' => 'vz-meeting',
      'numberposts' => -1,	
      'post_status' => 'publish',
      'fields' => 'ids',
      'meta_query' => array(
        array(
          'key' => 'calendar_id',
          'value' => $id,
        ),
        array(
          'key' => 'user_id',
          'value' => get_current_user_id(),
        ),
        array(
          'key' => 'date_time',
          'value' => date('Y-m-d H:i:s'),
          'compare' => '>=',
        ),
      ),
    ));
    $meetings = array_map(function($id) {
      return [
        'id' => $id,
        'date_time' => get_post_meta($id, 'date_time', true),
        'duration' => get_post_meta($id, 'duration', true),
      ];
    }, $meetings);
    usort($meetings, function($a, $b) {
      return strtotime($a['date_time']) - strtotime($b['date_time']);
    });
    $invite = $_GET['invite'] ?? '';

    $tdate = explode('-', date('Y-m-d'));
    $params = [
      'availability_rules' => JSON_decode(get_post_meta($id, 'vz_availability_rules', true)),
      'time_zone' => get_option('timezone_string'),
      'calendar_id' => $id,
      'rest_nonce' => wp_create_nonce('wp_rest'),
      'rest_url' => get_rest_url(),
      'slot_size' => get_post_meta($id, 'vz_am_duration', true),
      'availability' => [],
      'invite' => false,
      'language' => get_locale(),
      'meetings' => $meetings,
      'invitation_code' => $_GET['invite'],
      'requires_invite' => get_post_meta($id, 'vz_am_requires_invite', true),
    ];

    $checked = vz_check_invite($id, $invite);
    
    if ($checked) {
      $params['invite'] = $invite;
      $year = $tdate[0];
      $month = $tdate[1];
      $params['availability'] = [
        'available_days' => vz_am_get_month_availability($id, $year, $month),
        'availability_rules' => JSON_decode(get_post_meta($id, 'vz_availability_rules', true)),
      ];
    }
    

    wp_localize_script('vz-calendar-view', 'vz_calendar_view_params', $params);
  }
}

function vz_am_calendar_options() {
  add_meta_box(
    'vz_am_availability_options',
    __vz('Availability Rules'),
    'vz_am_availability_options_content',
    'vz-calendar',
    'normal',
    'default'
  );

  add_meta_box(
    'vz_am_invite_options',
    __vz('Invite Details'),
    'vz_am_invite_details_content',
    'vz-am-invite',
    'normal',
    'default'
  );
}


function vz_am_invite_details_content($post) {
  $calendar_ids = get_posts([
    'post_type' => 'vz-calendar',
    'numberposts' => -1,
    'fields' => 'ids',
  ]);
  print_x($calendar_ids);
  ?>
  <article class="vz-am__invitation">
    <div class="vz-am__invitation__input status">
      <label>
        <input type="checkbox">
        <span>Active</span>
      </label>
    </div>
    <div class="vz-am__invitation__input number-of-uses">
      <label>
        # of uses
      </label>
      <input type="number" value="1">
    </div>
    <div class="vz-am__invitation__input">
      <label>
        Expiration Date
      </label>
      <input type="date" value="2024-12-17">
    </div>
    <div class="vz-am__invitation__input">
      <label>
        <input type="checkbox">
        User may reuse
      </label>
    <label>
      <input type="checkbox" checked="">
      One meeting at a time
    </label>
  </div>
  <div class="vz-am__invitation__input">
    <label>
      Invitation Url'
    </label>
    <div class="invitation-url">
      <input type="text" value="">
      <button>Copy</button>
    </div>
  </div>
  </article>
  <?php
}

add_action(
  'add_meta_boxes', 
  'vz_am_calendar_options'
);

function vz_am_availability_options_content($post) {
  ?>
    <div id="vz-availability-rules"></div>
  <?php 
}

add_action('save_post', 'vz_am_save_calendar_options');
function vz_am_save_calendar_options($post_id) {
  if (get_post_type($post_id) !== 'vz-calendar') {
    return;
  }
  if (array_key_exists('vz_am_rest', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_rest',
      $_POST['vz_am_rest']
    );
  }
  if (array_key_exists('vz_am_duration', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_duration',
      $_POST['vz_am_duration']
    );
  }
  if (array_key_exists('vz-meetings-availability-rules', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_availability_rules',
      $_POST['vz-meetings-availability-rules']
    );
  }
  if (array_key_exists('vz_am_maximum_days_in_advance', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_maximum_days_in_advance',
      $_POST['vz_am_maximum_days_in_advance']
    );
  }
  if (array_key_exists('vz_am_enabled', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_enabled',
      $_POST['vz_am_enabled']
    );
  }
  if (array_key_exists('vz_am_requires_invite', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_requires_invite',
      $_POST['vz_am_requires_invite']
    );
  }
}

function vz_am_woocommerce_not_active() {
  echo '<div class="notice notice-error"><p>' . __vz('WooCommerce is not active. Please activate WooCommerce to use the Meeting Manager plugin.') . '</p></div>';
}

function vz_am_product_options() {
  woocommerce_wp_text_input(array(
    'id' => 'vz_am_duration',
    'label' => __vz('Duration'),
    'description' => __vz('Enter the duration of the meeting in minutes.'),
  ));
  // una opcion para permitirle al administrador decidir si los minutos se pueden agendar de manera separa o unida
  woocommerce_wp_checkbox(array(
    'id' => 'vz_am_allow_multiple_meetings',
    'label' => __vz('Allow multiple meetings'),
    'description' => __vz('Check this box to allow customers to create more than one meeting with their available minutes.'),
  ));
  // a drop down menu to select the calendar from the calendar post type
  $selected_calendar = get_post_meta(get_the_ID(), 'vz_am_calendar', true);
  vz_product_select_calendar_option($selected_calendar);
}

function vz_product_select_calendar_option($selected_calendar) {
  $calendars = get_posts(array(
    'post_type' => 'vz-calendar',
    'numberposts' => -1,
  ));
?>

<p class="form-field vz_am_allow_multiple_meetings_field ">

  <label for="vz_am_allow_multiple_meetings">
    <?php e_vz('Calendar')  ?>
  </label>
  <select name="vz_am_calendar">
    <?php
      vz_html('option', 'Select a calendar');
      foreach ($calendars as $calendar) {
        echo '<option value="' . $calendar->ID . '" ' . selected($selected_calendar, $calendar->ID, false) . '>' . $calendar->post_title . '</option>';
      }
    ?>
  </select>
  </p>
  <?php
}

function vz_am_save_product_options($post_id) {
  $duration = $_POST['vz_am_duration'];
  update_post_meta($post_id, 'vz_am_duration', $duration);
  $allow_multiple_meetings = isset($_POST['vz_am_allow_multiple_meetings']) ? 'yes' : 'no';
  update_post_meta($post_id, 'vz_am_allow_multiple_meetings', $allow_multiple_meetings);
  $calendar = $_POST['vz_am_calendar'];
  update_post_meta($post_id, 'vz_am_calendar', $calendar);
}


// add sortable column to meetings archive page for the calendar column
add_filter('manage_vz-meeting_posts_columns', 'vz_am_meeting_columns');
function vz_am_meeting_columns($columns) {
  $columns['vz_am_calendar'] = __vz('Calendar');

  // scheduled hour
  $columns['vz_am_date_time'] = __vz('Date and Time');
  return $columns;
}

add_action('manage_vz-meeting_posts_custom_column', 'vz_am_meeting_column_content', 10, 2);
function vz_am_meeting_column_content($column, $post_id) {
  if ($column === 'vz_am_calendar') {
    $calendar_id = get_post_meta($post_id, 'calendar_id', true);
    echo get_the_title($calendar_id);
  }
  if ($column === 'vz_am_date_time') {
    $date_time = get_post_meta($post_id, 'date_time', true); // this is a date object
    // echo date_format( date($date_time), 'Y-m-d H:i:s');
    echo date('D, M d, Y @H:i:s', strtotime($date_time));
    // echo duration
    $duration = get_post_meta($post_id, 'duration', true);
    echo ' (' . $duration . ' ' . __vz('minutes') . ')';
  }
}

// add calendar filter
add_action('restrict_manage_posts', 'vz_am_meeting_filter');
function vz_am_meeting_filter() {
  global $typenow;
  if ( $typenow === 'vz-meeting') {
    $calendars = vz_get_calendars();
    echo '<select name="calendar_id">';
    echo '<option value="">'.__vz('All Calendars').'</option>';
    foreach ($calendars as $calendar) {
      $selected_calendar =  '';
      if (isset($_GET['calendar_id']) && $_GET['calendar_id'] == $calendar['ID']) {
        $selected_calendar = 'selected';
      }
      echo '<option value="' . $calendar['ID']  .'" '.$selected_calendar.'>' . $calendar['post_title'] . '</option>';
    }
    echo '</select>';

    $status = [
      ['name' => 'past', 'title' => __vz('Past')],
      ['name' => 'upcoming', 'title' => __vz('Upcoming')],
    ];
    echo '<select name="vz-ap-status">';
    echo '<option value="">'.__vz('All Status').'</option>';
    foreach ($status as $calendar) {
      $selected_status =  '';
      if (isset($_GET['vz-ap-status']) && $_GET['vz-ap-status'] == $calendar['ID']) {
        $selected_status = 'selected';
      }
      echo '<option value="' . $calendar['name']  .'" '.$selected_status.'>' . $calendar['title'] . '</option>';
    }
    echo '</select>';
  }
}

function vz_get_calendars() {
  // make a custom sql query to the database to get all the calendars
  global $wpdb;
  $query = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'vz-calendar'";
  $calendars = $wpdb->get_results($query, ARRAY_A);
  return $calendars;
}

add_filter('parse_query', 'vz_am_meeting_filter_query');
function vz_am_meeting_filter_query($query) {
  global $typenow;
  if ($typenow === 'vz-meeting' && is_admin()) {
    $calendar_id = $_GET['calendar_id'] ?? '';
    if ($calendar_id) {
      $query->query_vars['meta_key'] = 'calendar_id';
      $query->query_vars['meta_value'] = $calendar_id;
    }

    $status = $_GET['vz-ap-status'] ?? '';
    if ($status) {
      switch ($status) {
        case 'past':
          $query->query_vars['meta_key'] = 'date_time';
          $query->query_vars['meta_value'] = date('Y-m-d H:i:s');
          $query->query_vars['meta_compare'] = '<';
          break;
        case 'upcoming':
          $query->query_vars['meta_key'] = 'date_time';
          $query->query_vars['meta_value'] = date('Y-m-d H:i:s');
          $query->query_vars['meta_compare'] = '>';
          break;
      }
    }
  }
}



// make the column sortable
add_filter('manage_edit-vz-meeting_sortable_columns', 'vz_am_meeting_sortable_columns');
function vz_am_meeting_sortable_columns($columns) {
  $columns['vz_am_calendar'] = 'vz_am_calendar';
  $columns['vz_am_date_time'] = 'vz_am_date_time';
  return $columns;
}

add_action('pre_get_posts', 'vz_am_meeting_sortable_columns_orderby');
function vz_am_meeting_sortable_columns_orderby($query) {
  $post_type = $query->get('post_type');
  if (!is_admin() || $post_type !== 'vz-meeting') {
    return;
  }
  $orderby = $query->get('orderby');
  if ($orderby === 'vz_am_calendar') {
    $query->set('meta_key', 'calendar_id');
    $query->set('orderby', 'meta_value_num');
  }
  if ($orderby === 'vz_am_date_time') {
    $query->set('meta_key', 'date_time');
    $query->set('orderby', 'meta_value');
    $query->set('meta_type', 'DATE'); 
  }
}


// add a cron job that removes invite links older than 48 hours
add_action('vz_am_remove_old_invites', 'vz_am_remove_old_invites');
function vz_am_remove_old_invites() {
  $invites = get_posts(array(
    'post_type' => 'vz-am-invite',
    'numberposts' => -1,
    'fields' => 'ids',
    'date_query' => array(
      'before' => '48 hours ago',
    ),
  ));
  foreach ($invites as $invite) {
    wp_delete_post($invite, true);
  }
}
