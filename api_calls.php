<?php 

// create an endpoint to check the availability days of a month
add_action('rest_api_init', 'vz_am_register_rest_routes');
function vz_am_register_rest_routes() { 
  register_rest_route('vz-am/v1', '/availability', array(
    'methods' => 'POST',
    'callback' => 'vz_am_month_availability',
  ));
  register_rest_route('vz-am/v1', '/timeslots', array(
    'methods' => 'POST',
    'callback' => 'vz_am_timeslots',
  ));
  register_rest_route('vz-am/v1', '/confirm', array(
    'methods' => 'POST',
    'callback' => 'vz_am_confirm_meeting',
  ));
  register_rest_route('vz-am/v1', '/invite_link', array(
    'methods' => 'POST',
    'callback' => 'vz_am_create_calendar_invite',
  ));
}

function vz_create_meeting_title($meeting) {
  $calendar = get_post($meeting['calendar_id']);
  $calendar_title = $calendar->post_title;
  $date_time = new DateTime($meeting['date_time']);
  $date_time_str = $date_time->format('Y-m-d H:i');
  // user name
  $user = get_user_by('id', $meeting['user_id']);
  $user_name = $user->display_name;
  return "$user_name | $date_time_str";
}

function vz_get_email_template($template_name) {
  $template = file_get_contents(__DIR__ . "/mail-templates/$template_name.html");
  return $template;
}

function vz_send_password_reset_email($user_id) {
  $user = get_user_by('id', $user_id);
  $user_login = $user->user_login;
  $user_email = $user->user_email;
  $key = get_password_reset_key($user);
  $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
  $template = vz_get_email_template('password_reset');
  $template = str_replace('{{reset_link}}', $reset_link, $template);
  $template = str_replace('{{site_url}}', get_site_url(), $template);
  $template = str_replace('{{site_name}}', get_bloginfo('name'), $template);
  $template = str_replace('{{user_displayname}}', $user->display_name, $template);
  wp_mail($user_email, 'Password Reset', $message);
}

function vz_send_meeting_confirmation_email($meeting_id, $visitor_timezone) {
  $meeting = get_post($meeting_id);
  $calendar_id = get_post_meta($meeting_id, 'calendar_id', true);
  $calendar = get_post($calendar_id);
  $calendar_title = $calendar->post_title;
  $date_time = new DateTime(get_post_meta($meeting_id, 'date_time', true));
  $date_time->setTimezone(new DateTimeZone($visitor_timezone));
  $date_time_str = $date_time->format('Y-m-d H:i');
  $duration = get_post_meta($meeting_id, 'duration', true);
  $user_id = get_post_meta($meeting_id, 'user_id', true);
  $user = get_user_by('id', $user_id);
  $user_name = $user->display_name;
  $user_email = $user->user_email;
  $template = vz_get_email_template('meeting_confirmation');
  $template = str_replace('{{site_url}}', get_site_url(), $template);
  $template = str_replace('{{site_name}}', get_bloginfo('name'), $template);
  $template = str_replace('{{calendar_title}}', $calendar_title, $template);
  $template = str_replace('{{date_time}}', $date_time_str, $template);
  $template = str_replace('{{duration}}', $duration, $template);
  $template = str_replace('{{user_name}}', $user_name, $template);
  $template = str_replace('{{user_email}}', $user_email, $template);
  wp_mail($user_email, 'Meeting Confirmation', $template);
}

function vz_am_confirm_meeting($request) {
  $params = $request->get_params();
  $nonce = $request->get_header('X-WP-Nonce'); // Get the nonce from the request header
  if (!wp_verify_nonce($nonce, 'wp_rest')) {
    return new WP_Error('invalid_nonce', 'Invalid nonce', ['status' => 403]);
  }
  $calendar_id = $request->get_param('calendar_id');
  $visitor_timezone = $request->get_param('visitor_timezone');
  $selected_time_slot = $request->get_param('date_time');
  $duration = get_post_meta($calendar_id, 'vz_am_duration', true);
  $invite = $request->get_param('invite');

  if (!vz_check_invite($calendar_id, $invite)) {
    return new WP_Error('invalid_invite', 'Invalid invite', ['status' => 403]);
  }
  
  $user_id = get_current_user_id();
  if (!is_user_logged_in()) {
    $user_email = $request->get_param('user_email');
    $user_name = $request->get_param('user_name');

    if (!is_email($user_email)) {
      return new WP_Error('invalid_email', 'Invalid email', ['status' => 400]);
    }

    $user_id = email_exists($user_email);
    if ($user_id) {
      return new WP_Error('email_exists', 'Login to use this email for schedule.', ['status' => 400]);
    } else {
      $user_id = wp_create_user($user_email, wp_generate_password(), $user_email);
      wp_update_user([
        'ID' => $user_id,
        'display_name' => $user_name,
      ]);
      vz_send_password_reset_email($user_id);
    }
  }

  $new_meeting = [
    'date_time' => $selected_time_slot,
    'duration' => $duration,
    'user_id' => $user_id,
    'calendar_id' => $calendar_id, 
  ];
  $new_meeting_id = wp_insert_post([
    'post_type' => 'vz-meeting',
    'post_title' => vz_create_meeting_title($new_meeting),
    'post_status' => 'publish',
  ]);
  if (is_wp_error($new_meeting_id)) {
    return new WP_Error('error', 'Error creating meeting', ['status' => 500]);
  }
  foreach ($new_meeting as $key => $value) {
    update_post_meta($new_meeting_id, $key, $value);
  }

  vz_send_meeting_confirmation_email($new_meeting_id, $visitor_timezone);
  // destroy invitation
  $found = get_page_by_path($invite, OBJECT, 'vz-am-invite');
  $invitation_used = json_encode($found);
  if ($found) {
    wp_delete_post($found->ID, true);
  }
  update_post_meta($new_meeting_id, 'invitation_used', $invitation_used);
  return rest_ensure_response( [
    'meeting' => $new_meeting_id,
  ]);
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

function vz_check_invite($calendar_id, $invite) {
  if (!get_post_meta($calendar_id, 'vz_am_requires_invite', true)) return true;
  $found = get_page_by_path($invite, OBJECT, 'vz-am-invite');
  if (!$found) return false;
  if (is_wp_error($found)) return false;
  $invite_calendar = get_post_meta($found->ID, 'calendar_id', true);
  if (!$invite_calendar) return false;
  if ($invite_calendar != $calendar_id) return false;
  return true;
}

function vz_am_month_availability($request) {
  $calendar_id = $request->get_param('calendar_id');
  $invite = $request->get_param('invite');
    
  if (!vz_check_invite($calendar_id, $invite)) {
    // return rest_ensure_response( [
    //   'error' => 'Invalid invite',
    // ]);
    return new WP_Error('invalid_invite', 'Invalid invite', ['status' => 403]);
  }

  $month = $request->get_param('month');
  $year = $request->get_param('year');
  // return vz_am_get_month_availability($calendar_id, $year, $month);
  return rest_ensure_response( [
    'available_days' => vz_am_get_month_availability($calendar_id, $year, $month),
    'availability_rules' => JSON_decode(get_post_meta($calendar_id, 'vz_availability_rules', true)),
  ]);
}

function vz_am_get_month_availability($calendar_id, $year, $month) { 
  $calendar = get_post($calendar_id);
  $availability_rules = JSON_decode(get_post_meta($calendar_id, 'vz_availability_rules', true));
  $max_days_in_advance = get_post_meta($calendar_id, 'vz_am_maximum_days_in_advance', true);
  $max_days_in_advance = $max_days_in_advance ? $max_days_in_advance : 60;
  $today = new DateTime();


  // obey rule hierachy
  usort($availability_rules, function($a, $b) {
    return $a->id - $b->id;
  });



  $days = vz_am_get_days_of_month($month, $year);
  $available_days = [];
  $limit_date = $today->setTime(0, 0);
  $limit_date->add(new DateInterval('P' . $max_days_in_advance . 'D'));

  $limit_date_next_month = new DateTime($limit_date->format('Y-m-d'));
  $limit_date_next_month->add(new DateInterval('P1M'));
  $limit_date_next_month->setTime(0, 0);
  
  $request_first_day_of_month = new DateTime("$year-$month-01");
  if ($request_first_day_of_month >= $limit_date_next_month) {
    return []; // helps me sleep at night
  }

  /* 
  Rule structure: 
    "id": 1, // position in array
    "name": 
    "New Rule", 
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
      $start_date = $rule_start_date > clone $month_start_date ? $rule_start_date : clone $month_start_date;
      $end_date = $rule_end_date < $month_end_date ? clone $rule_end_date : clone $month_end_date;
      $interval = new DateInterval('P1D');
      $end_date = $end_date->add($interval);
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
    if ($rule->type === 'weekday') {
      $weekdays = $rule->weekdays;
      $start_date = new DateTime("$year-$month-01");
      $end_date = new DateTime("$year-$month-" . $start_date->format('t'));
      $interval = new DateInterval('P1D');
      $end_date = $end_date->add($interval);
      $period = new DatePeriod($start_date, $interval, $end_date);
      foreach ($period as $day) {
        $week_day = $day->format('N');
        if (in_array($week_day, $weekdays)) {
          $available_days[$day->format('d')] = $available;
        }
      }
    }
  }

  foreach ($available_days as $day => $available) {
    $date = new DateTime("$year-$month-$day");
    if ($date > $limit_date || !$available) {
      unset($available_days[$day]);
    }
  }

  ksort($available_days);
  return $available_days;
}

function vzFormatFrame($available, $start = false, $end = false) {
  // formatted to the website timezone, as the rules where made there
  $website_timezone = new DateTimeZone(get_option('timezone_string'));
  if (!$start) {
    return;
  }
  if (!$end) {
    return;
  }
  return [
    'available' => $available,
    'start' => $start,
    'end' => $end,
    'timezone' => $website_timezone->getName(),
  ];
}

function vz_am_timeslots($request) {
  $calendar_id = $request->get_param('calendar_id');
  $invite = $request->get_param('invite');
  if (!vz_check_invite($calendar_id, $invite)) {
    return rest_ensure_response( [
      'error' => 'Invalid invite',
    ]);
  }
  $month = $request->get_param('month');
  $year = $request->get_param('year');
  $day = $request->get_param('day');
  $visitor_time_zone = $request->get_param('timezone');

  return rest_ensure_response( [
    'timeslots' => vz_am_get_timeslots($calendar_id, $year, $month, $day, $visitor_time_zone),
  ]);
}


# The heart of the availability system and the whole damn plugin
function vz_am_get_timeslots($calendar_id, $year, $month, $day, $timezone) {
  $duration = get_post_meta($calendar_id, 'vz_am_duration', true);
  $rest = get_post_meta($calendar_id, 'vz_am_rest', true);
  $availability_rules = JSON_decode(get_post_meta($calendar_id, 'vz_availability_rules', true));
  // timezone example = "America/New_York"
  $visitor_timezone = new DateTimeZone($timezone);
  $website_timezone = new DateTimeZone(get_option('timezone_string'));
  
  // Request the day scope of the visitor
  $day_date = new DateTime("$year-$month-$day 00:00", $visitor_timezone);
  $day_date_end = new DateTime("$year-$month-$day 23:59", $visitor_timezone);
  $day_date->setTimezone($website_timezone);
  $day_date_end->setTimezone($website_timezone);

  $day_of_week = $day_date->format('N');
  $day_of_month = $day_date->format('d');
  $availability_frames = [];

  // this will create "availability frames" that will be used to determine the available frames, used to create the timeslots
  foreach ($availability_rules as $index => $rule) {
    $available = $rule->action === 'available';
    if ($rule->type == 'weekday') {
      $weekdays = $rule->weekdays;
      $day_date_weekday = $day_date->format('N');
      if (in_array($day_date_weekday, $weekdays)) {
        // start of request is in a week day
        // availability by default would be the start of request until the end of that day
        // but if the rule has start time and end time
        // then the frame start will be the bigger number between the rule start time and the request start time
        // and the frame end will be the smaller number between the rule end time and the request end time
        
        $rst = $rule->startTime;
        $rst = explode(':', $rst);
        $rule_start_time = clone $day_date;
        $rule_start_time = $rule_start_time->setTime($rst[0], $rst[1]);
        $ret = $rule->endTime;
        $ret = explode(':', $ret);
        $rule_end_time = clone $day_date;
        $rule_end_time = $rule_end_time->setTime($ret[0], $ret[1]);
        $frame_start = $rule_start_time > $day_date ? $rule_start_time : $day_date;
        $frame_end = $rule_end_time < $day_date_end ? $rule_end_time : $day_date_end;
        
        $availability_frames[] = vzFormatFrame($available, $frame_start, $frame_end);
      } 
      if (in_array($day_date_end->format('N'), $weekdays)) {
        // end of request is in a week day
        // availability by default would be the start of day until the end of request
        // but if the rule has start time and end time
        // then the frame end will be the smaller number between the rule end time and the request end time
        // and the frame start will be the bigger number between 00:00 and the rule start time

        $rst = $rule->startTime;
        $rst = explode(':', $rst);
        $rule_start_time = clone $day_date;
        $rule_start_time = $rule_start_time->setTime($rst[0], $rst[1]);
        $ret = $rule->endTime;
        $ret = explode(':', $ret);
        $rule_end_time = clone $day_date;
        $rule_end_time = $rule_end_time->setTime($ret[0], $ret[1]);
        
        $frame_start = $rule_start_time > $day_date ? $rule_start_time : $day_date;
        $frame_end = $rule_end_time < $day_date_end ? $rule_end_time : $day_date_end;

        $availability_frames[] = vzFormatFrame($available, $rule_end_datetim, $rule_end_datetim);
      }
      continue; // enough login for the timeslot, do no more
    } else if ($rule->type == 'between-dates') {
      if ($rule->includeTime) {
        $rule_start_datetime = new DateTime("$rule->startDate $rule->startTime", $website_timezone);
        $rule_end_datetime = new DateTime("$rule->endDate $rule->endTime", $website_timezone);
      } else {
        $rule_start_datetime = new DateTime("$rule->startDate 00:00", $website_timezone);
        $rule_end_datetime = new DateTime("$rule->endDate 23:59", $website_timezone);
      }
    } else if ($rule->type == 'specific-date') {
      $rule_start_datetime = new DateTime("$rule->specificDate 00:00", $website_timezone);
      $rule_end_datetime = new DateTime("$rule->specificDate 23:59", $website_timezone);
    }

    if ($day_date_end < $rule_start_datetime || $day_date > $rule_end_datetime) {
      continue; // out of range
    }
    $availability_frames[] = vzFormatFrame($available, $rule_start_datetime, $rule_end_datetime);
  }
  
  $slot_lowest_start_time = clone $day_date_end;
  $slot_highest_end_time = clone $day_date;
  foreach ($availability_frames as $frame) {
    if (!$frame['available']) continue; // ignore substractions
    
    if ($frame['start'] < $slot_lowest_start_time) {
      $slot_lowest_start_time = clone $frame['start'];
    }

    if ($frame['end'] > $slot_highest_end_time) {
      $slot_highest_end_time = clone $frame['end'];
    }
  }

  if (!is_numeric($duration)) {
    $duration = 30;
  }
  if (!is_numeric($rest)) {
    $rest = 30;
  }
  $slot_total_duration = $duration + $rest;
  $interval = new DateInterval('PT' . $slot_total_duration . 'M');

  $timeslots = [];
  $slot = clone $slot_lowest_start_time;
  $slot_end_time = clone $slot;
  $slot_duration_interval = new DateInterval('PT' . $duration . 'M');
  $slot_end_time = $slot_end_time->add($slot_duration_interval);

  $availability_frames = array_reverse($availability_frames);

  // query the database for meetings in the same day and remove the timeslots that are already taken
  $args = [
    'post_type' => 'vz-meeting',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      [
        'key' => 'calendar_id',
        'value' => $calendar_id,
      ],
      [
        'key' => 'date_time',
        'compare' => '>=',
        'value' => $day_date->format('Y-m-d H:i'),
        'type' => 'DATETIME',
      ],
      [
        'key' => 'date_time',
        'compare' => '<=',
        'value' => $day_date_end->format('Y-m-d H:i'),
        'type' => 'DATETIME',
      ],
    ],
  ];

  $meetings_ids = get_posts($args);
  $meetings = array_map(function($id) {
    return [
      'id' => $id,
      'date_time' => get_post_meta($id, 'date_time', true),
      'duration' => get_post_meta($id, 'duration', true),
    ];
  }, $meetings_ids);

  while ($slot < $slot_highest_end_time) {
    $is_available = true;
    foreach ($availability_frames as $frame) {
      if ($frame['available']) continue; // ignore additions
      // if the slot is inside the frame, dont add it
      if ($slot >= $frame['start'] && $slot < $frame['end']) {
        $is_availane = false;
      }
      if ($slot_end_time > $frame['start'] && $slot_end_time <= $frame['end']) {
        $is_available = false;
      }
    }

    foreach ($meetings as $meeting) {
      $meeting_timezone = new DateTimeZone('UTC');
      $meeting_start = new DateTime($meeting['date_time'], $meeting_timezone);
      $meeting_start->setTimezone($website_timezone);
      $meeting_end = clone $meeting_start;
      $meeting_end->add(new DateInterval('PT' . $meeting['duration'] . 'M'));
      
      if ($slot >= $meeting_start && $slot < $meeting_end) {
        $mets[] = [
          'start' => $meeting_start->format('Y-m-d H:i'),
          'end' => $meeting_end->format('Y-m-d H:i'),
          'slot' => $slot,
          'slot_end' => $slot_end_time,
          'problem' => 'inside',
        ];
        $is_available = false;
      }
    }

    if ($is_available) {
      $timeslots[] = clone $slot;
    }
    $slot = $slot->add($interval);
    // slot end time is updated automatically
  }


  return [
    'timeslots' => $timeslots,
    'meeting' => $mets,
    'interval' => $slot_total_duration,
    'availability_frames' => $availability_frames,
    'lowest_start_time' => $slot_lowest_start_time,
  ];
}


function vz_am_make_invite_link($calendar_id, $random_id) {
  $calendar_slug = get_post_field('post_name', $calendar_id);
  return home_url("/calendar/$calendar_slug?invite=$random_id");
}

function vz_am_create_calendar_invite($request) {
  $params = $request->get_params();
  $calendar_id = $request->get_param('calendar_id');
  $random_id = strtoupper(wp_generate_password(6, false));
  $invite_details = [
    'calendar_id' => $calendar_id,
    'random_id' => $random_id,
    'invite_link' => vz_am_make_invite_link($calendar_id, $random_id),
  ];  
  
  $invite_id = wp_insert_post([
    'post_type' => 'vz-am-invite',
    'post_title' => 'Invite | ' . $invite_details['random_id'],
    'post_name' => $invite_details['random_id'],
  ]);

  if (is_wp_error($invite_id)) {
    return new WP_Error('error', 'Error creating invite', ['status' => 500]);
  }
  update_post_meta($invite_id, 'calendar_id', $calendar_id);

  return rest_ensure_response( [
    'invite' => $invite_details,
  ]);
}