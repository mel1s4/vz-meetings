<?php

if (get_post_type($post_id) == 'vz-calendar') {
  vzm_update_fields($post_id, [
    'vz_am_rest',
    'vz_am_duration',
    'vz_availability_rules',
    'vz_am_maximum_days_in_advance',
    'vz_am_enabled',
    'vz_am_requires_invite',
  ]);
}

if (get_post_type($post_id) == 'vz-invite') {

  vzm_update_fields($post_id, [
    'vz_am_number_of_uses',
    'vz_am_expiration_date',
    'vz_am_one_meeting_at_a_time',
    'calendar_id',
  ]);

  // if it does not have a random code
  if (!get_post_meta($post_id, 'random_code', true)) {
    // generate invite code
    $random_id = strtoupper(wp_generate_password(9, false));
    update_post_meta($post_id, 'random_code', $random_id);
  }
} 

