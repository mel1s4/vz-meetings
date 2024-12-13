<?php
/*
Plugin Name: Viroz Appointment Manager
Plugin URI: https://viroz.studio/vz-appointment-manager
Description: This plugin will help you to sell and manage your appointments for services and products.
Version: 0.0.1
Author: Melisa Viroz
Author URI: https://melisaviroz.com
License: GPL2
*/

if (!defined('ABSPATH')) {
  die;
}

if (!function_exists('print_x')) {
  function print_x($x) {
    echo '<pre>';
    print_r($x);
    echo '</pre>';
  }
}

function vz_html($tag, $text) {
  $txt = __($text, 'vz-am');
  echo "<$tag>$txt</$tag>";
}

function e_vz($text) {
  echo __vz($text);
}

function __vz($text) {
  return __($text, 'vz-am');
}

add_action('admin_enqueue_scripts', 'vz_am_enqueue_styles');
function vz_am_enqueue_styles() {
  wp_enqueue_style('vz-am-styles', plugin_dir_url(__FILE__) . 'style.css');  
  
  // if the current page is edit post vz-calendar 
  // editing post tyle = vz-calendar
  if (get_current_screen()->post_type === 'vz-calendar') {
    wp_enqueue_style('vz-availability-rules-styles', plugin_dir_url(__FILE__) . 'availability-rules/build/static/css/main.css', array(), '1.0.0', 'all');
    wp_enqueue_script('vz-availability-rules', plugin_dir_url(__FILE__) . 'availability-rules/build/static/js/main.js' , array('wp-element'), '0.0.1', true);
    $params = [
      'availability_rules' => JSON_decode(get_post_meta(get_the_ID(), 'vz_availability_rules', true)),
      'time_zone' => get_option('timezone_string'),
    ];
    wp_localize_script('vz-availability-rules', 'vz_availability_rules_params', $params);
  }
}


// create settings page with a single text input field
add_action('admin_menu', 'vz_am_settings_page');
function vz_am_settings_page() {
  add_menu_page(
    __vz("Appointments"),
    __vz("Appointments"),
    'manage_options', 
    'vz_am_settings', 
    'vz_am_settings_page_content',
    'dashicons-calendar-alt',
  );
  add_submenu_page(
    'vz_am_settings',
    'Calendar View',
    'Calendar View', 
    'manage_options', 
    'vz_am_calendar', 
    'vz_am_calendar_page_content'
  );
}

function vz_am_settings_page_content() {
  echo '<h1>' . __vz("Appointments") . '</h1>';
  echo '<form method="post" action="options.php">';
  settings_fields('vz_am_settings');
  do_settings_sections('vz_am_settings');
  submit_button();
  echo '</form>';
}

function vz_am_calendar_page_content() {
  echo '<h1>' . __vz("Calendar") . '</h1>';
}

// create a post type called "calendar"
add_action('init', 'vz_am_calendar_post_type');

function vz_am_calendar_post_type() {
  register_post_type('vz-calendar', array(
    'labels' => array(
      'name' => __vz('My Calendars'),
      'singular_name' => __vz('Calendar'),
    ),
    'public' => true,
    'has_archive' => false,
    'show_ui' => true,	
    'show_in_menu' => 'vz_am_settings',
    'supports' => array('title'),
  ));
}

// create a shotcode to display the calendar send a prop to select the calendar by id
add_shortcode('vz_calendar', 'vz_am_calendar_shortcode');
function vz_am_calendar_shortcode($atts) {
  wp_enqueue_style('vz-calendar-view-styles', plugin_dir_url(__FILE__) . 'calendar-view/build/static/css/main.css', array(), '1.0.0', 'all');
  wp_enqueue_script('vz-calendar-view', plugin_dir_url(__FILE__) . 'calendar-view/build/static/js/main.js' , array('wp-element'), '0.0.1', true);
  $params = [
    'availability_rules' => JSON_decode(get_post_meta(get_the_ID(), 'vz_availability_rules', true)),
    'time_zone' => get_option('timezone_string'),
  ];
  wp_localize_script('vz-calendar-view', 'vz_availability_rules_params', $params);

  $atts = shortcode_atts(array(
    'id' => null,
  ), $atts);
  ob_start();
  $calendar = get_post($atts['id']);
  include 'vz-calendar.php';
  return ob_get_clean();
}


// add options to calendar post type where the user can select thew minimup appointment size in minutes
add_action('add_meta_boxes', 'vz_am_calendar_options');
function vz_am_calendar_options() {
  add_meta_box(
    'vz_am_calendar_options',
    __vz('Calendar Options'),
    'vz_am_calendar_options_content',
    'vz-calendar',
    'normal',
    'default'
  );

  // add an option called "Rules of Availability" where the user can set the days and hours of availability
  add_meta_box(
    'vz_am_availability_options',
    __vz('Availability Rules'),
    'vz_am_availability_options_content',
    'vz-calendar',
    'normal',
    'default'
  );
}

function vz_am_availability_options_content($post) {
  ?>
    <div id="vz-availability-rules"></div>
  <?php 
}

function vz_am_calendar_options_content($post) {
  $duration = get_post_meta($post->ID, 'vz_am_duration', true);
  echo '<label for="vz_am_duration">' . __vz('Minimum appointment size in minutes') . '</label>';
  echo '<input type="number" id="vz_am_duration" name="vz_am_duration" value="' . $duration . '">';
  $rest = get_post_meta($post->ID, 'vz_am_rest', true);
  echo '<label for="vz_am_rest">' . __vz('Rest between appointments') . '</label>';
  echo '<input type="number" id="vz_am_rest" name="vz_am_rest" value="' . $rest . '">';
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
  if (array_key_exists('vz-appointments-availability-rules', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_availability_rules',
      $_POST['vz-appointments-availability-rules']
    );
  }
}


// add options to product post type if woocommerce is active
add_action('init', 'vz_am_add_product_options');
function vz_am_add_product_options() {
  if (class_exists('WooCommerce')) {
    add_action('woocommerce_product_options_general_product_data', 'vz_am_product_options');
    add_action('woocommerce_process_product_meta', 'vz_am_save_product_options');
  } else {
    add_action('admin_notices', 'vz_am_woocommerce_not_active');
  }
}

function vz_am_woocommerce_not_active() {
  echo '<div class="notice notice-error"><p>' . __vz('WooCommerce is not active. Please activate WooCommerce to use the Appointment Manager plugin.') . '</p></div>';
}

function vz_am_product_options() {
  woocommerce_wp_text_input(array(
    'id' => 'vz_am_duration',
    'label' => __vz('Duration'),
    'description' => __vz('Enter the duration of the appointment in minutes.'),
  ));
  // una opcion para permitirle al administrador decidir si los minutos se pueden agendar de manera separa o unida
  woocommerce_wp_checkbox(array(
    'id' => 'vz_am_allow_multiple_appointments',
    'label' => __vz('Allow multiple appointments'),
    'description' => __vz('Check this box to allow customers to create more than one appointment with their available minutes.'),
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

<p class="form-field vz_am_allow_multiple_appointments_field ">

  <label for="vz_am_allow_multiple_appointments">
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
  $allow_multiple_appointments = isset($_POST['vz_am_allow_multiple_appointments']) ? 'yes' : 'no';
  update_post_meta($post_id, 'vz_am_allow_multiple_appointments', $allow_multiple_appointments);
  $calendar = $_POST['vz_am_calendar'];
  update_post_meta($post_id, 'vz_am_calendar', $calendar);
}

// create an endpoint to check the availability days of a month
add_action('rest_api_init', 'vz_am_register_rest_routes');
function vz_am_register_rest_routes() { 
  register_rest_route('vz-am/v1', '/availability', array(
    'methods' => 'GET',
    'callback' => 'vz_am_get_availability',
  ));
}

function vz_am_get_days_of_month($month, $year) {
  $days = [];
  $first_day = new DateTime("$year-$month-01");
  $last_day = new DateTime("$year-$month-" . $first_day->format('t'));
  $interval = new DateInterval('P1D');
  $period = new DatePeriod($first_day, $interval, $last_day);
  foreach ($period as $day) {
    $days[] = $day;
  }
  return $days;
}

function vz_am_get_availability($request) {
  $month = $request->get_param('month');
  $year = $request->get_param('year');
  $calendar_id = $request->get_param('calendar_id');
  $calendar = get_post($calendar_id);
  $availability_rules = JSON_decode(get_post_meta($calendar_id, 'vz_availability_rules', true));
  // sort the rules by id
  usort($availability_rules, function($a, $b) {
    return $a->id - $b->id;
  });
  $days = vz_am_get_days_of_month($month, $year);
  $available_days = [];

  /* 
  Rule structure: 
    "id": 1, // position in array
    "name": "New Rule", 
    "type": "between-dates",
    "action": "unavailable", unavailable or available
    "includeTime": false,
    "startTime": "00:00",
    "endTime": "23:59",
    "weekdays": [],
    "specificDate": "",
    "startDate": "",
    "endDate": "",
    "showWeekdays": false
  */

  foreach ($availability_rules as $rule) {
    $available = $rule->action === 'available';
    if ($rule->type === 'specific-date') {
      $rule_date = new DateTime($rule->specificDate);
      if ($rule_date->format('m') == $month) {
        $available_days[$rule_date->format('d')] = $available;
      }
    }
    if ($rule->type === 'between-dates') {
      $rule_start_date = new DateTime($rule->startDate);
      $rule_end_date = new DateTime($rule->endDate);
      $month_start_date = new DateTime("$year-$month-01");
      $month_end_date = new DateTime("$year-$month-" . $month_start_date->format('t'));
      $start_date = $rule_start_date > $month_start_date ? $rule_start_date : $month_start_date;
      $end_date = $rule_end_date < $month_end_date ? $rule_end_date : $month_end_date;
      $interval = new DateInterval('P1D');
      $period = new DatePeriod($start_date, $interval, $end_date);
      foreach ($period as $day) {
        if ($rule->showWeekdays) {
          $week_day = $day->format('N');
          if (in_array($week_day, $rule->weekdays)) {
            $available_days[$day->format('d')] = $available;
          }
        } else {
          $available_days[$day->format('d')] = $available;
        }
      }
    }
    if ($rule->type === 'every-week') {
      $start_date = new DateTime("$year-$month-01");
      $end_date = new DateTime("$year-$month-" . $start_date->format('t'));
      $interval = new DateInterval('P1W');
      $period = new DatePeriod($start_date, $interval, $end_date);
      foreach ($period as $day) {
        $week_day = $day->format('N');
        if (in_array($week_day, $rule->weekdays)) {
          $available_days[$day->format('d')] = $available;
        }
      }
    }
  }
  // sort the days by key
  ksort($available_days);

  return rest_ensure_response( [
    $available_days,
    $availability_rules,
    $calendar_id,
    ] );
  // the endpoint would be called like this: /wp-json/vz-am/v1/availability?month=1&year=2021&calendar_id=1
}