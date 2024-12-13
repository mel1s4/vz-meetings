<?php 

// create an endpoint to check the availability days of a month
add_action('rest_api_init', 'vz_am_register_rest_routes');
function vz_am_register_rest_routes() { 
  register_rest_route('vz-am/v1', '/availability', array(
    'methods' => 'GET',
    'callback' => 'vz_am_month_availability',
  ));
  register_rest_route('vz-am/v1', '/timeslots', array(
    'methods' => 'GET',
    'callback' => 'vz_am_timeslots',
  ));
}

function vzGetAvailability($calendar_id, $year = false, $month = false, $day = false) {
  if (!$year) {
    $year = date('Y');
  }

  if (!$month) {
    $month = date('m');
  }

  if (!$day) {
    $day = date('d');
  }
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

function vz_am_month_availability($request) {
  $month = $request->get_param('month');
  $year = $request->get_param('year');
  $calendar_id = $request->get_param('calendar_id');
  // return vz_am_get_month_availability($calendar_id, $year, $month);
  return rest_ensure_response( [
    'available_days' => vz_am_get_month_availability($calendar_id, $year, $month),
  ]);
}

function vz_am_get_month_availability($calendar_id, $year, $month) { 
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
    if ($rule->type === 'weekdays') {
      $weekdays = $rule->weekdays;
      $start_date = new DateTime("$year-$month-01");
      $end_date = new DateTime("$year-$month-" . $start_date->format('t'));
      $interval = new DateInterval('P1D');
      $period = new DatePeriod($start_date, $interval, $end_date);
      foreach ($period as $day) {
        $week_day = $day->format('N');
        if (in_array($week_day, $weekdays)) {
          $available_days[$day->format('d')] = $available;
        }
      }
    }
  }
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
  $month = $request->get_param('month');
  $year = $request->get_param('year');
  $day = $request->get_param('day');
  $calendar_id = $request->get_param('calendar_id');
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
    } else {
      $start_time = $rule->startTime;
      $end_time = $rule->endTime;
      if ($rule->type === 'weekdays' && in_array($day_of_week, $rule->weekdays)) {
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

  // remove unavailable timeslots
  foreach ($timeslots as $time => $available) {
    if ($available) {
      $cTimeslots[] = $time;
    }
  }

  return $timeslots;
}