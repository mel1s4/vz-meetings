<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");

$calendar_slug = get_option('vz_am_calendar_slug', 'calendar');

register_post_type('vz-calendar', array(
  'labels' => array(
    'name' => __vz('My Calendars'),
    'singular_name' => __vz('Calendar'),
  ),
  'public' => true,
  
  
  
  // has single
  'has_archive' => true,
  'rewrite' => array('slug' => $calendar_slug),
  'show_ui' => true,	
  'show_in_menu' => 'vz_am_settings',
  'supports' => array('title', 'editor'),
  // uses gutenburg editor
  'show_in_rest' => true,
  'rest_base' => 'vz-calendars',
  'rest_controller_class' => 'WP_REST_Posts_Controller',
  'capability_type' => 'post',
  'map_meta_cap' => true,
  
));

$appointment_slug = get_option('vz_am_appointment_slug', 'appointment');
register_post_type('vz-appointment', array(
  'labels' => array(
    'name' => __vz('My Appointments'),
    'singular_name' => __vz('Appointment'),
  ),
  'public' => false,
  // has single
  'has_archive' => false,
  'rewrite' => array('slug' => $appointment_slug),
  'show_ui' => true,	
  'show_in_menu' => 'vz_am_settings',
  'supports' => array('title'),
));

if (class_exists('WooCommerce')) {
  add_action('woocommerce_product_options_general_product_data', 'vz_am_product_options');
  add_action('woocommerce_process_product_meta', 'vz_am_save_product_options');
} else {
  add_action('admin_notices', 'vz_am_woocommerce_not_active');
}