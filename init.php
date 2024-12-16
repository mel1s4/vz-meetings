<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
  header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
}
if (class_exists('WooCommerce')) {
  add_action('woocommerce_product_options_general_product_data', 'vz_am_product_options');
  add_action('woocommerce_process_product_meta', 'vz_am_save_product_options');
} else {
  add_action('admin_notices', 'vz_am_woocommerce_not_active');
}

$calendar_slug = get_option('vz_am_calendar_slug', 'calendar');
register_post_type('vz-calendar', array(
  'labels' => array(
    'name' => __vz('Calendars'),
    'singular_name' => __vz('Calendar'),
  ),
  'public' => true,
  'has_archive' => true,
  'rewrite' => array('slug' => $calendar_slug),
  'show_ui' => true,	
  'show_in_menu' => 'vz_am_settings',
  'supports' => array('title', 'editor'),
  'show_in_rest' => true,
  'rest_base' => 'vz-calendars',
  'rest_controller_class' => 'WP_REST_Posts_Controller',
  'capability_type' => 'post',
  'map_meta_cap' => true,
));

$invitation_slug = get_option('vz_am_invitation_slug', 'appointment');
register_post_type('vz-am-invitation', array(
  'labels' => array(
    'name' => __vz('Invitations'),
    'singular_name' => __vz('Invitation'),
  ),
  'public' => false,
  // has single
  'has_archive' => false,
  'rewrite' => array('slug' => $invitation_slug),
  'show_ui' => true,	
  'show_in_menu' => 'vz_am_settings',
  'supports' => array('title'),
));

$appointment_slug = get_option('vz_am_appointment_slug', 'appointment');
register_post_type('vz-appointment', array(
  'labels' => array(
    'name' => __vz('Appointments'),
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



