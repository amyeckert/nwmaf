<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<div class="mp_wrapper mpcs-course-list">
  <div class="mpcs-cards">
    <?php foreach($my_courses as $course): ?>
      <div class="mpcs-card-wrapper<?php echo isset($attributes['show_3_col']) ? ' col-3' : ''; ?>">
        <div class="mpcs-card">
          <?php if (!isset($attributes['hide_image']) && has_post_thumbnail($course->ID)) { ?>
            <div class="mpcs-card-thumb">
              <a href="<?php echo get_permalink($course->ID); ?>">
                <?php echo get_the_post_thumbnail($course->ID, apply_filters('mpcs_course_image_size', 'mpcs-course-thumbnail'), ['class' => 'img-responsive']); ?>
              </a>
            </div>
          <?php } ?>
          <div class="mpcs-card-title">
            <a class="mpcs-card-link" href="<?php echo get_permalink($course->ID); ?>">
              <?php if (!isset($attributes['hide_lock_icon']) && \MeprRule::is_locked(get_post($course->ID))) { ?>
                <svg viewBox="0 0 30 30" width="16px" height="16px">    <path d="M 15 2 C 11.145666 2 8 5.1456661 8 9 L 8 11 L 6 11 C 4.895 11 4 11.895 4 13 L 4 25 C 4 26.105 4.895 27 6 27 L 24 27 C 25.105 27 26 26.105 26 25 L 26 13 C 26 11.895 25.105 11 24 11 L 22 11 L 22 9 C 22 5.2715823 19.036581 2.2685653 15.355469 2.0722656 A 1.0001 1.0001 0 0 0 15 2 z M 15 4 C 17.773666 4 20 6.2263339 20 9 L 20 11 L 10 11 L 10 9 C 10 6.2263339 12.226334 4 15 4 z"/></svg>
              <?php } ?>
              <?php echo $course->post_title; ?>
            </a>
          </div>
          <div class="mpcs-card-content">
            <?php if (!isset($attributes['hide_excerpt'])) {
              $excerpt = get_the_excerpt($course->ID);
              $excerpt = substr($excerpt, 0, 140);
              $result = substr($excerpt, 0, strrpos($excerpt, ' '));
              echo wpautop($result . ' [...]');
            } ?>
          </div>
          <?php if (!isset($attributes['hide_author'])) { ?>
            <div class="mpcs-card-footer">
                <span class="mpcs-card-author">
              <?php $user_id = (int) $course->post_author; ?>
              <?php echo memberpress\courses\lib\Utils::get_avatar( $user_id, '30' ) . memberpress\courses\lib\Utils::get_full_name( $user_id ); ?>
            </span>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!isset($attributes['hide_navigation'])) { ?>
    <div class="mpcs-card-nav">
      <div class="alignleft">
        <?php previous_posts_link( __('&laquo; Previous', 'memberpress-courses') ); ?>
      </div>
      <div class="alignright">
        <?php next_posts_link( __('Next &raquo;', 'memberpress-courses'), $course_query->max_num_pages); ?>
      </div>
    </div>
  <?php } ?>
</div>

<?php if (!isset($attributes['hide_cards'])) { ?>
  <style>
      .mpcs-cards * {
        box-sizing: border-box;
      }
      .mpcs-cards {
        display: flex;
        flex-wrap: wrap;
      }
      .mpcs-card-wrapper {
        margin-bottom: 1em;
        width: 100%;
        max-width: 100%;
        padding-left: 0.4rem;
        padding-right: 0.4rem;
      }
      .mpcs-card-wrapper.col-3 {
        width: 33.3333%;
      }
      @media screen and (min-width: 768px) {
        .mpcs-card-wrapper {
          width: 50%;
        }
      }
      .mpcs-card {
        box-shadow: 0 0.25rem 1rem rgba(48,55,66,.15);
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 0.05rem solid #dadee4;
      }
      .mpcs-card-thumb img {
        display: block;
        max-width: 100%;
        height: auto;
      }
      .mpcs-card-link {
        display: flex;
        align-items: center;
        padding: 0.8rem;
        padding-bottom: 0;
        color: #007cba !important;
        text-decoration: none;
        font-size: 19px;
        font-weight: 500;
      }
      .mpcs-card-link svg {
        fill: #007cba;
        margin-right: 5px;
      }
      .mpcs-card-content {
        padding: 0.8rem;
        padding-bottom: 0;
        font-size: 16px;
      }
      .mpcs-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem;
        font-size: 16px;
      }
      .mpcs-card-author {
        display: inline-flex;
      }
      .mpcs-card-author img {
        border-radius: 50px !important;
        margin-right: 5px;
        float: left;
      }
      .mpcs-card-nav {
        display: flex;
        justify-content: space-between;
        padding-left: 0.4rem;
        padding-right: 0.4rem;
      }
  </style>
<?php } else { ?>
    <style>
      .mpcs-card-wrapper {margin-bottom: 2em;}
      .mpcs-card-thumb {margin-bottom: 10px;}
      .mpcs-card-content p {margin-bottom: 10px;}
      .mpcs-card-author img {margin-right: 5px;}
      .mpcs-card-title {font-size: 21px;}
    </style>
<?php } ?>
