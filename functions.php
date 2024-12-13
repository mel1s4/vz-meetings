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
    $params = [
      'availability_rules' => JSON_decode(get_post_meta(get_the_ID(), 'vz_availability_rules', true)),
      'time_zone' => get_option('timezone_string'),
      'endpoint_domain' => get_rest_url(),
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

add_action('wp_enqueue_scripts', 'vz_am_enqueue_calendar_scripts');
function vz_am_enqueue_calendar_scripts() {
  global $post;
  // if is single vz-calendar post
  if (is_single() && $post->post_type === 'vz-calendar') {
    wp_enqueue_style('vz-calendar-view-styles', plugin_dir_url(__FILE__) . 'calendar-view/build/static/css/main.css', array(), '1.0.0', 'all');
    wp_enqueue_script('vz-calendar-view', plugin_dir_url(__FILE__) . 'calendar-view/build/static/js/main.js' , array('wp-element'), '0.0.1', true);
    $params = [
      'availability_rules' => JSON_decode(get_post_meta(get_the_ID(), 'vz_availability_rules', true)),
      'time_zone' => get_option('timezone_string'),
      'calendar_id' => get_the_ID(),
      'rest_nonce' => wp_create_nonce('wp_rest'),
      'rest_url' => get_rest_url(),
      'slot_size' => get_post_meta(get_the_ID(), 'vz_am_duration', true),
      'availability' => vzGetAvailability(get_the_ID()),
    ];
    wp_localize_script('vz-calendar-view', 'vz_calendar_view_params', $params);
  }
}

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
add_action(
  'add_meta_boxes', 
  'vz_am_calendar_options'
);

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
