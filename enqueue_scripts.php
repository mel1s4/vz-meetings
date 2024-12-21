<?php

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
      'meeting_rest' => get_post_meta($id, 'vz_am_rest', true),
      'availability' => [],
      'language' => get_locale(),
      'meetings' => $meetings,
      'invite' => isset($_GET['invite']) ? $_GET['invite'] : '',
      'requires_invite' => get_post_meta($id, 'vz_am_requires_invite', true),
    ];
    
    if (vz_check_invite($id, $invite)) {
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