<?php
  $user_id = get_current_user_id();
  $args = [
    'author' => $user_id,
    'post_type' => 'vz-invite',
    'post_status' => 'publish',
    'posts_per_page' => -1,
  ];
?>

<h1> 
  My Invites
</h1>

<p>
  Here are the invites you have received.
</p>
<?php
  $query = new WP_Query($args);
  if($query):
?>
  <ul>
  <?php
    while($query->have_posts()):
      $query->the_post();
      $id = get_the_ID();
      $calendar = get_post_meta($id, 'calendar_id', true);
      $code = get_post_meta($id, 'random_code', true);
      $date_created = get_the_date('D d M, Y. h:i A');
      // $calendar = get_post_meta(get_the_ID(), 'calendar_id', true);
  ?>
    <li>
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
        </p>
        <p class="date">
          <?php echo $date_created ?>
        </p>
      </article>
    </li>
  <?php
    endwhile;
  ?>
  </ul>
  <?php
  endif;
