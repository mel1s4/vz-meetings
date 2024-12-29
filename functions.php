<?php
/*
* Plugin Name: Viroz Meeting Manager
* Plugin URI: https://viroz.studio/vz-meeting-manager/
* Description: This plugin will help you to sell and manage your meetings for services and products.
* Version: 0.1
* Requires at least: 6.0
* Requires PHP: 7.2
* Author: Melisa Viroz
* Author URI: https://melisaviroz.com/
* License: GPLv2
* text-domain: vz-meeting-manager
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
include 'enqueue_scripts.php';

add_action('admin_menu', 'vz_am_settings_page');
function vz_am_settings_page() {
  add_menu_page(
    __vzm("VZ Meetings"),
    __vzm("VZ Meetings"),
    'manage_options', 
    'vz_am_settings', 
    'vz_am_settings_page_content',
    'dashicons-calendar-alt',
    40
  );
  add_submenu_page(
    'vz_am_settings',
    __vzm('Settings'),
    __vzm('Settings'), 
    'manage_options', 
    'vz_am_my_schedule', 
    'vz_am_settings_page_content'
  );
}

function vz_am_settings_page_content() {
  echo '<h1>' . __vzm("Meetings") . '</h1>';
}

function vz_am_my_schedule_page_content() {
  echo '<h1>' . __vzm("Calendar") . '</h1>';
  echo "<div id='vz-am-schedule'></div>";
}

function vz_am_calendar_options() {
  add_meta_box(
    'vz_am_availability_options',
    __vzm('Availability Rules'),
    'vz_am_availability_options_content',
    'vz-calendar',
    'normal',
    'default'
  );

  add_meta_box(
    'vz_am_invite_options',
    __vzm('Invite Details'),
    'vz_am_invite_details_content',
    'vz-invite',
    'normal',
    'default'
  );
}


function vz_am_invite_details_content($post) {
  include 'invite_options.php';
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

add_action('save_post', 'vz_am_save_calendar_options', 10, 2);
function vz_am_save_calendar_options($post_id) {
  include 'save_post.php';
}

function vz_product_select_calendar_option($selected_calendar) {
  $calendars = get_posts(array(
    'post_type' => 'vz-calendar',
    'numberposts' => -1,
  ));
?>
<p class="form-field vz_am_allow_multiple_meetings_field ">
  <label for="vz_am_allow_multiple_meetings">
    <?php e_vzm('Calendar')  ?>
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


add_action('woocommerce_product_options_general_product_data', 'vz_am_product_options');
function vz_am_product_options() {
  global $post;
  $selected_calendar = get_post_meta($post->ID, 'vz_am_calendar', true);
  vz_product_select_calendar_option($selected_calendar);

  woocommerce_wp_text_input(array(
    'id' => 'vz_am_number_of_uses',
    'label' => __vzm('Number of uses'),
    'type' => 'number',
  ));

  woocommerce_wp_checkbox(array(
    'id' => 'vz_am_create_invite_one_at_a_time',
    'label' => __vzm('One use at a time'),
  ));
  
}
add_action('woocommerce_process_product_meta', 'vz_am_save_product_options');

function vz_am_save_product_options($post_id) {
  if (array_key_exists('vz_am_calendar', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_calendar',
      $_POST['vz_am_calendar']
    );
  }
  if (array_key_exists('vz_am_number_of_uses', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_number_of_uses',
      $_POST['vz_am_number_of_uses']
    );
  }
  if (array_key_exists('vz_am_create_invite_one_at_a_time', $_POST)) {
    update_post_meta(
      $post_id,
      'vz_am_create_invite_one_at_a_time',
      $_POST['vz_am_create_invite_one_at_a_time']
    );
  }
}

add_filter('manage_vz-meeting_posts_columns', 'vz_am_meeting_columns');
function vz_am_meeting_columns($columns) {
  $columns['vz_am_calendar'] = __vzm('Calendar');
  $columns['vz_am_date_time'] = __vzm('Date and Time');
  return $columns;
}

add_action('manage_vz-meeting_posts_custom_column', 'vz_am_meeting_column_content', 10, 2);
function vz_am_meeting_column_content($column, $post_id) {
  if ($column === 'vz_am_calendar') {
    $calendar_id = get_post_meta($post_id, 'calendar_id', true);
    echo get_the_title($calendar_id);
  }
  if ($column === 'vz_am_date_time') {
    $date_time = get_post_meta($post_id, 'date_time', true);
    echo date('D, M d, Y @H:i:s', strtotime($date_time));
    $duration = get_post_meta($post_id, 'duration', true);
    echo ' (' . $duration . ' ' . __vzm('minutes') . ')';
  }
}

add_action('restrict_manage_posts', 'vz_am_meeting_filter');
function vz_am_meeting_filter() {
  global $typenow;
  if ( $typenow === 'vz-meeting') {
    $calendars = vz_get_calendars();
    echo '<select name="calendar_id">';
    echo '<option value="">'.__vzm('All Calendars').'</option>';
    foreach ($calendars as $calendar) {
      $selected_calendar =  '';
      if (isset($_GET['calendar_id']) && $_GET['calendar_id'] == $calendar['ID']) {
        $selected_calendar = 'selected';
      }
      echo '<option value="' . $calendar['ID']  .'" '.$selected_calendar.'>' . $calendar['post_title'] . '</option>';
    }
    echo '</select>';

    $status = [
      ['name' => 'past', 'title' => __vzm('Past')],
      ['name' => 'upcoming', 'title' => __vzm('Upcoming')],
    ];
    echo '<select name="vz-ap-status">';
    echo '<option value="">'.__vzm('All Status').'</option>';
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
    'post_type' => 'vz-invite',
    'numberposts' => -1,
    'fields' => 'ids',
    'meta_query' => [
      'key' => 'vz_am_expiration_date',
      'compare' => '<',
      'value' => date('Y-m-d'),
      'type' => 'DATE',
    ],
  ));
  foreach ($invites as $invite) {
    wp_trash_post($invite);
  }
}

function vzm_update_fields($post_id, $fields) {
  foreach ($fields as $field) :
    if (array_key_exists($field, $_POST)) :
      update_post_meta(
        $post_id,
        $field,
        $_POST[$field]
      );
    endif;
  endforeach;
}


function vz_use_invitation($invite_code, $meeting_id) {
  $args = [
    'post_type' => 'vz-invite',
    'meta_key' => 'random_code',
    'meta_value' => $invite_code,
    'fields' => 'ids',
  ];
  $invite = get_posts($args);
  $invite_id = $invite[0];
  $uses = get_post_meta($invite_id, 'vz_am_number_of_uses', true);
  $uses--;
  update_post_meta($invite_id, 'vz_am_number_of_uses', $uses);
  $meetings = get_post_meta($invite_id, 'vz_am_meetings', true);
  if (!$meetings) {
    $meetings = [];
  }
  $meetings[] = $meeting_id;
  update_post_meta($invite_id, 'meetings', $meetings);
}

function vz_am_make_invite_link($calendar_id, $random_id) {
  $calendar_link = get_the_permalink($calendar_id);
  return $calendar_link . '?invite=' . $random_id;
}

// check if woocommerce is active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
  add_action('woocommerce_order_status_completed', 'vz_am_order_completed', 20, 2);
  add_filter('woocommerce_account_menu_items', 'vz_am_my_account_menu_items');
  add_action('init', 'vz_am_add_my_account_endpoints');
  add_action('woocommerce_account_vz-meetings_endpoint', 'vz_am_my_meetings_endpoint');

}

function vz_am_my_account_menu_items($items) {
  $items['vz-meetings'] = __vzm('My Meetings');
  return $items;
}

function vz_am_add_my_account_endpoints() {
  add_rewrite_endpoint('vz-invites', EP_PAGES);
  add_rewrite_endpoint('vz-meetings', EP_PAGES);
}

function vz_am_my_meetings_endpoint() {
  include 'my_meetings.php';
}

function vz_am_order_completed($order_id, $order) {
  $items = $order->get_items();
  foreach ($items as $item) {
    $product_id = $item->get_product_id();
    $product_quantity = $item->get_quantity();
    $product = wc_get_product($product_id);
    $calendar_id = get_post_meta($product_id, 'vz_am_calendar', true);
    $number_of_uses = get_post_meta($product_id, 'vz_am_number_of_uses', true);
    $create_invite_one_at_a_time = get_post_meta($product_id, 'vz_am_create_invite_one_at_a_time', true);
    $order_user = $order->get_user();
    
    $number_of_uses = $number_of_uses * $product_quantity;
    $random_id = strtoupper(wp_generate_password(9, false));
    $invite_id = wp_insert_post([
      'post_type' => 'vz-invite',
      'post_title' => 'Invite for ' . $product->get_title(),
      'post_status' => 'publish',
    ]);
    update_post_meta($invite_id, 'calendar_id', $calendar_id);
    update_post_meta($invite_id, 'vz_am_number_of_uses', $number_of_uses);
    update_post_meta($invite_id, 'vz_am_create_invite_one_at_a_time', $create_invite_one_at_a_time);
    update_post_meta($invite_id, 'random_code', $random_id);
    update_post_meta($invite_id, 'vz_am_expiration_date', date('Y-m-d', strtotime('+30 days')));

    wp_update_post([
      'ID' => $invite_id,
      'post_author' => $order_user->ID,
    ]);
    
  }
}