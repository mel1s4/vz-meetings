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

function vz_am_confirm_meeting($request) {
  $params = $request->get_params();
  $nonce = $request->get_header('X-WP-Nonce'); // Get the nonce from the request header
  if (!wp_verify_nonce($nonce, 'wp_rest')) {
    return new WP_Error('invalid_nonce', 'Invalid nonce', ['status' => 403]);
  }
  $calendar_id = $request->get_param('calendar_id');
  $selected_time_slot = $request->get_param('date_time');
  $duration = get_post_meta($calendar_id, 'vz_am_duration', true);
  $invite = $request->get_param('invite');

  if (!vz_check_invite($calendar_id, $invite)) {
    return rest_ensure_response( [
      'error' => 'Invalid invite',
    ]);
  }

  $new_meeting = [
    'date_time' => $selected_time_slot,
    'duration' => $duration,
    'user_id' => get_current_user_id(),
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

function vzGetAvailability($calendar_id, $year = false, $month = false, $day = false) {
  if (!$year) $year = date('Y');
  if (!$month) $month = date('m');
  if (!$day) $day = date('d');
  
  $timeslots = vz_am_get_timeslots($calendar_id, $year, $month, $day);
  $month_availability = vz_am_get_month_availability($calendar_id, $year, $month);
  return [
    'timeslots' => $timeslots,
    'available_days' => $month_availability,
  ];
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
    return rest_ensure_response( [
      'error' => 'Invalid invite',
    ]);
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

  if ($today->format('Y-m') > "$year-$month" || "$year-$month" > $today->add(new DateInterval('P' . $max_days_in_advance . 'D'))->format('Y-m')) {
    return;
  }

  // // if month is more than 60 days in the future, return empty array
  // if ($today->add(new DateInterval('P' . $max_days_in_advance . 'D'))->format('Y-m') < "$year-$month") {
  //   return [];
  // }

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
      $period = new DatePeriod($start_date, $interval, $end_date, DatePeriod::INCLUDE_END_DATE);
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
      // $end_date->add(new DateInterval('P1D'));
      $interval = new DateInterval('P1D');
      $period = new DatePeriod($start_date, $interval, $end_date, DatePeriod::INCLUDE_END_DATE);
      foreach ($period as $day) { // this is not iterating to the last day of the month
        $week_day = $day->format('N');
        if (in_array($week_day, $weekdays)) {
          $available_days[$day->format('d')] = $available;
        }
      }
    }
  }

  // remove days that are beyond the 60 days mark
  $limit_date = $today->setTime(0, 0);
  $limit_date->add(new DateInterval('P' . $max_days_in_advance . 'D'));
  foreach ($available_days as $day => $available) {
    $date = new DateTime("$year-$month-$day");
    if ($date > $limit_date || !$available) {
      unset($available_days[$day]);
    }
  }
  $available_days['today'] = $today->format('Y-m-d');
  $available_days['limit_date'] = $limit_date->format('Y-m-d');
  $available_days['max_days_in_advance'] = $max_days_in_advance;

  ksort($available_days);
  return $available_days;
}

function vzFormatFrame($available, $start = false, $end = false) {
  $website_timezone = new DateTimeZone(get_option('timezone_string'));
  if (!$start) {
    $start = '00:00';
  }
  if (!$end) {
    $end = '23:59';
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
  // return vz_am_get_timeslots($calendar_id, $year, $month, $day);
  return rest_ensure_response( [
    'timeslots' => vz_am_get_timeslots($calendar_id, $year, $month, $day),
  ]);
}

function vz_am_get_timeslots($calendar_id, $year, $month, $day) {
  $calendar = get_post($calendar_id);
  $duration = get_post_meta($calendar_id, 'vz_am_duration', true);
  $rest = get_post_meta($calendar_id, 'vz_am_rest', true);
  $availability_rules = JSON_decode(get_post_meta($calendar_id, 'vz_availability_rules', true));
  $day_date = new DateTime("$year-$month-$day");
  $day_of_week = $day_date->format('N');
  $day_of_month = $day_date->format('d');
  $availability_frames = [];

  foreach ($availability_rules as $index => $rule) {
    $available = $rule->action === 'available';
    // if specific date and is not equal to the current date, skip
    if ($rule->type === 'specific-date' && $rule->specificDate !== "$year-$month-$day") {
      continue;
    }
    if (!$rule->includeTime) {
      // specific-date
      if ($rule->type === 'specific-date' && $rule->specificDate === "$year-$month-$day") {
        $availability_frames[] = vzFormatFrame($available);
        continue; // kinda redundant maybe?
      }
      // between-dates
      if ($rule->type === 'between-dates') {
        $rule_start_date = new DateTime($rule->startDate);
        $rule_end_date = new DateTime($rule->endDate);
        if ($day_date >= $rule_start_date && $day_date <= $rule_end_date) {
          if (!$rule->showWeekdays || in_array($day_of_week, $rule->weekdays)) 
            $availability_frames[$index] = vzFormatFrame($available);
        }
      }

      // weekdays
      if ($rule->type === 'weekday' && in_array($day_of_week, $rule->weekdays)) {
        $availability_frames[] = vzFormatFrame($available);
      }
    } else {
      $start_time = $rule->startTime;
      $end_time = $rule->endTime;
      if ($rule->type === 'weekday' && in_array($day_of_week, $rule->weekdays)) {
        $availability_frames[] = vzFormatFrame($available, $start_time, $end_time);
      }
  
      if ($rule->type === 'specific-date' && $rule->specificDate == "$year-$month-$day") {
        $availability_frames[] = vzFormatFrame($available, $start_time, $end_time);
      }

      if ($rule->type === 'between-dates') {
        $start_date = new DateTime($rule->startDate);
        $end_date = new DateTime($rule->endDate);
        if ($day_date < $start_date || $day_date > $end_date){
          // if its out of range
           continue;
        } else if ($rule->showWeekdays && in_array($day_of_week, $rule->weekdays)) {
          // in range - selected weekdays
          $availability_frames[] = vzFormatFrame($available, $start_time, $end_time);
        } else if ($day_date >= $start_date && $day_date <= $end_date) {
          // in range - all days
          $availability_frames[] = vzFormatFrame($available, $start_time, $end_time);
        }
      }
    }
  }

  $timeslots = [];
  // start time is the lowest start time of all the available frames
  $start_time = new DateTime('23:59');
  // end time is the highest end time of all the available frames
  $end_time = new DateTime('00:00');
  foreach ($availability_frames as $frame) {
    $start = new DateTime($frame['start']);
    $end = new DateTime($frame['end']);
    if ($start < $start_time) {
      $start_time = $start;
    }
    if ($end > $end_time) {
      $end_time = $end;
    }
  }
  $slot_total_duration = $duration + $rest;
  $interval = new DateInterval('PT' . $slot_total_duration . 'M');
  $period = new DatePeriod($start_time, $interval, $end_time);
  
  // inverse the availability frames
  $availability_frames = array_reverse($availability_frames);
  foreach ($availability_frames as $frame) {
    $start = new DateTime($frame['start']);
    $end = new DateTime($frame['end']);
    $available = $frame['available'];
    $slot = clone $start;
    while ($slot <= $end) {
      $timeslots[$slot->format('H:i')] = $available;
      $slot->add($interval);
    }
  }


  // query the database for meetings 
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
        'value' => "$year-$month-$day 00:00",
      ],
      [
        'key' => 'date_time',
        'compare' => '<=',
        'value' => "$year-$month-$day 23:59",
      ],
    ],
  ];

  // remove meetings from timeslots
  $meetings_ids = get_posts($args);
  if (!empty($meetings_ids)) {
    foreach ($meetings as $meeting) {
      $date_time = new DateTime(get_post_meta($meeting->ID, 'date_time', true));
      $start = $date_time->format('H:i');
      $end = $date_time->add(new DateInterval('PT' . $slot_total_duration . 'M'))->format('H:i');
      foreach ($timeslots as $time => $available) {
        if ($time >= $start && $time < $end) {
          $timeslots[$time] = false;
        }
      }
    }
  }
  
  $cTimeslots = [];
  // remove unavailable timeslots
  foreach ($timeslots as $time => $available) {
    if ($available) {
      $cTimeslots[] = $time;
    }
  }

  return $timeslots;
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