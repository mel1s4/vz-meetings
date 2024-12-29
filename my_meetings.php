<?php
  $user_id = get_current_user_id();
  $args = [
    'author' => $user_id,
    'post_type' => 'vz-invite',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => [
      [
        'key' => 'vz_am_number_of_uses',
        'value' => 0,
        'compare' => '>',
        'type' => 'NUMERIC'
      ]
    ]
  ];
?>

<section class="vzm__my-invites">

  <?php
    $query = new WP_Query($args);
    if($query):
  ?>
    <h1 class="vzm__my-invites__title"> 
      <?php e_vzm('My Invites') ?>
    </h1>
    <ul class="vzm__my-invites__list">
    <?php
      while($query->have_posts()):
        $query->the_post();
        $id = get_the_ID();
        $calendar = get_post_meta($id, 'calendar_id', true);
        $code = get_post_meta($id, 'random_code', true);
    ?>
      <li class="vzm__my-invites__list__item">
        <article>
          <p class="calendar">
            <a href="<?php echo get_permalink($calendar); ?>?invite=<?php echo $code; ?>">
              <?php echo get_the_title($calendar); ?>
            </a>
          </p>
          <p class="code">
            <?php echo $code; ?>
          </p>
          <p class="uses">
            <?php echo get_post_meta($id, 'vz_am_number_of_uses', true); ?>
            <?php e_vzm('uses') ?>
          </p>
        </article>
      </li>
    <?php
        endwhile;
      echo "</ul>";
    endif;
    ?>
</section>

<section class="vzm__my-meetings">
  <h1 class="vzm__my-meetings__title">
    <?php e_vzm('My Meetings') ?>
  </h1>
  <ul class="vzm__my-meetings__list">
    <?php
      $args = [
        'post_type' => 'vz-meeting',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'user_id',
        'meta_value' => $user_id,
        'orderby' => 'meta_value',
      ];
      $query = new WP_Query($args);
      if($query):
        while($query->have_posts()):
          $query->the_post();
          $calendar_id = get_post_meta(get_the_ID(), 'calendar_id', true);
          $id = get_the_ID();
          $code = get_post_meta($id, 'random_code', true);
          $meeting_start = new DateTime(get_post_meta($id, 'date_time', true));
          $visitor_timezone = get_post_meta($id, 'visitor_timezone', true);
          if (empty($visitor_timezone)) {
            $visitor_timezone = get_option('timezone_string');
          }
          $timezone = new DateTimeZone($visitor_timezone);
          $meeting_start->setTimezone($timezone);
          $date = date_i18n('d M, Y', $meeting_start->getTimestamp());
          $time = date_i18n('H:i', $meeting_start->getTimestamp());
          $weekday = date_i18n('l', $meeting_start->getTimestamp());
    ?>
      <li class="vzm__my-meetings__list__item">
        <article>
          <p class="calendar">
            <?php echo get_the_title($calendar_id); ?>
          </p>
          <p class="date">
            <span class="weekday">
              <?php echo $weekday ?>
            </span>
            <?php echo $date ?>
            <span class="time">
              @<?php echo $time ?>
            </span>
          </p>
        </article>
      </li>
    <?php
        endwhile;
      echo "</ul>";
    endif;
    ?>
</section>
