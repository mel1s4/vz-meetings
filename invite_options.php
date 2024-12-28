<?php
  global $post; 
  $calendar_ids = get_posts([
    'post_type' => 'vz-calendar',
    'numberposts' => -1,
    'fields' => 'ids',
  ]);
  $uses = get_post_meta($post->ID, 'vz_am_number_of_uses', true);
  $expiration_date = get_post_meta($post->ID, 'vz_am_expiration_date', true);
  $one_meeting_at_a_time = get_post_meta($post->ID, 'vz_am_one_meeting_at_a_time', true);
  $calendar = get_post_meta($post->ID, 'calendar_id', true);  
  $random_id = get_post_meta($post->ID, 'random_code', true);
  $invitation_url = vz_am_make_invite_link($calendar, $random_id);

  print_x($invitation_url);
?>

<article class="vzm__invitation">
  <div class="vzm__invitation__input number-of-uses">
    <label>
      # of uses
    </label>
    <input type="number" 
            name="vz_am_number_of_uses"
            value="<?php echo $uses ?>">
  </div>
  <div class="vzm__invitation__input">
    <label>
      Expiration Date
    </label>
    <input type="date"
            name="vz_am_expiration_date" 
            value="<?php echo $expiration_date ?>">
  </div>
  <div class="vzm__invitation__input">
  <label>
    <input type="checkbox"
            name="vz_am_one_meeting_at_a_time" 
            checked="<?php echo $one_meeting_at_a_time ?>">
    One meeting at a time
  </label>
</div>
<section class="vzm__invitation__input">
  <label>
    Calendar
  </label>
  <div class="vzm__invitation-calendar">
    <select name="calendar_id">
      <option value="" disabled>Select a calendar</option>
      <?php foreach ($calendar_ids as $calendar_id) : ?>
        <option value="<?php echo $calendar_id; ?>"
          <?php echo selected($calendar_id, $calendar, false); ?>>
          <?php echo get_the_title($calendar_id); ?>
        </option>
      <?php endforeach; ?>
      </select>
    </div>
</section>
<?php
  if ($invitation_url) :
?>
<div class="vzm__invitation__input">
  <label>
    Invitation Url
  </label>
  <div class="invitation-url">
    <input type="text"
            id="invitation-url" 
            value="<?php echo $invitation_url ?>" readonly>
    <button data-vzclipboard="invitation-url" type="button">
      Copy Link</button>
  </div>
</div>
<?php
  endif;
?>
</article>