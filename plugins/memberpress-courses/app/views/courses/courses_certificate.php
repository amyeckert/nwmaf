<?php
use memberpress\courses as base;

switch($course->certificates_style) {
  case 'style_a':
    $bg_image = 'MemberPress_Certificate_1-pdf-background.jpg';
    break;
  case 'style_b':
    $bg_image = 'MemberPress_Certificate_2-pdf-background.jpg';
    break;
  case 'style_c':
    $bg_image = 'MemberPress_Certificate_3-pdf-background.jpg';
    break;
  default:
    $bg_image = 'MemberPress_Certificate_1-pdf-background.jpg';
}

/** @var base\models\Course $course */
$base_path          = ABSPATH;
$no_bottom_logo     = empty($course->certificates_bottom_logo);
$no_top_logo        = empty($course->certificates_logo);
$no_instructor_sign = empty($course->certificates_instructor_signature);
$title              = $course->certificates_title;
$paper_size         = $course->certificates_paper_size;
$footer_message     = wp_trim_words($course->certificates_footer_message, 55, '...');
$instructor_title   = $course->certificates_instructor_title;
$instructor_name    = $course->certificates_instructor_name;
$bg_image_path      = base\IMAGES_URL . '/' . $bg_image;
$top_logo_path      = $course->certificates_logo;
$signature_path     = $course->certificates_instructor_signature;
$bottom_logo_path   = $course->certificates_bottom_logo;
$student_name       = esc_textarea(ucwords(strtolower($user->first_name . ' ' . $user->last_name)));
$course_title       = apply_filters('mpcs_certificate_pdf_course_title', esc_textarea(ucwords(strtolower($course->post_title))), $course->post_title);

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href='https://fonts.googleapis.com/css2?family=Great+Vibes' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css2?family=Crimson+Text' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css2?family=Poppins' rel='stylesheet' type='text/css'>
    <style>
      @page {
        margin: 0;
        padding: 0;
      }

      body {
        margin: 0;
        padding: 0;
        font-family: Poppins, sans-serif;
        background: url('<?php echo $bg_image_path; ?>') no-repeat center center;
        background-size: 100% 100%;
      }

      .container {
        height: <?php if ($paper_size == 'A4') echo '793px'; elseif ($paper_size == 'letter') echo '815px'; else echo apply_filters('mpcs_certificate_pdf_file_height', '800px'); ?>;
        position: relative;
        border: 0;
        margin: 0;
        padding: 0;
      }

      .vertical-center {
        margin: 0;
        padding: 0;
        position: absolute;
        top: 50%;
        -ms-transform: translateY(-50%);
        transform: translateY(-50%);
        color: <?php echo esc_attr($course->certificates_text_color); ?>;
        width: 100%;
        text-align: center;
      }

      p, h1, h2, h3, h4 {
        text-align: center;
      }

      img.top-logo {
        max-height: 100px;
      }

      h1.top-title {
        font-size: 3em;
        font-family: 'Crimson Text', serif;
      }

      div.footer-message {
        text-align: center;
        width: 80%;
        margin: auto;
      }

      .course-name {
          max-width: 80%;
          margin: auto;
      }

      p.student-name {
        margin: 0;
        padding: 0;
        padding-bottom: 27px;
        font-family: 'Great Vibes', sans-serif;
        font-size: 4em;
        line-height: 1em;
      }

      div.footer-wrap {
        margin: 0 auto;
        margin-top: 42px;
        padding: 0;
        line-height: 1em;
        width: 68%;
        font-weight: bold;
        font-family: 'Poppins';
        text-align: center;
      }

      div.signature-wrap {
        margin:0;
        padding:0;
        width:39%;
        text-align: center;
        display: inline-block;
        border:0;
        /*margin-right: <?php echo ($no_bottom_logo) ? '50px' : '0'; ?>;*/
      }

      div.signature {
        width:100%;
        text-align: center;
      }

      div.signature img {
        margin: 0;
        padding: 0;
        margin-bottom:3px;
        max-height: 60px;
        max-width: 250px;
      }

      b.signature-denom {
        height: 40px;
        padding-top: 5px;
        border-top: 2px solid;
        display: inline-block;
        text-align: center;
        width: 100%;
      }

      div.bottom-logo {
        margin: 0 auto;
        padding: 0;
        width: 20%;
        text-align: center;
        display: inline-block;
        border:0;
      }

      div.bottom-logo img {
        max-height: 100px;
        max-width: 100px;
        margin: 0;
        padding: 0;
      }

      div.instructor-wrap {
        margin: 0;
        padding: 0;
        width: 39%;
        text-align: center;
        display: inline-block;
        border:0;
        /*margin-left: <?php echo ($no_bottom_logo) ? '50px' : '0'; ?>;*/
      }

      div.instructor-name {
        width: 100%;
        margin: 0 auto;
        margin-bottom:3px;
        font-family: 'Poppins';
        font-size: 1.3em;
      }

      div.instructor-title {
        height:40px;
        padding-top: 5px;
        border-top: 2px solid;
        margin: 0 auto;
      }
      .completion-date > span {
        padding: auto 1em;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="vertical-center">
        <?php if(!$no_top_logo): ?>
          <p>
            <img class="top-logo" src="<?php echo $top_logo_path; ?>" />
          </p>
        <?php endif; ?>
        <h1 class="top-title">
          <?php esc_html_e('Certificate of Completion', 'memberpress-courses'); ?>
        </h1>
        <h2>
          <?php esc_html_e(strtoupper($title), 'memberpress-courses'); ?>
        </h2>
        <p class="student-name">
          <?php echo $student_name; ?>
        </p>
        <div class="footer-message">
          <?php esc_html_e($footer_message, 'memberpress-courses'); ?>
        </div>
        <h4 class="course-name">
          <?php echo $course_title; ?>
        </h4>
        <?php if ($course->certificates_completion_date == 'enabled' || $course->certificates_expiration_date == 'enabled'){ ?>
        <p class="completion-date">
          <?php if ($course->certificates_completion_date == 'enabled') { ?><span><b><?php esc_html_e('COMPLETED', 'memberpress-courses'); ?>:</b> <?php echo esc_html(wp_date(apply_filters('mpcs_certificate_pdf_completion_date', 'F jS Y'), strtotime($last_completion_date))); ?></span><?php } ?>
          <?php if ($course->certificates_expiration_date == 'enabled') { ?><span><b><?php esc_html_e('EXPIRES', 'memberpress-courses'); ?>:</b> <?php echo esc_html(wp_date(apply_filters('mpcs_certificate_pdf_expiration_date', 'F jS Y'), $last_completion_datetime->getTimestamp())); ?></span><?php } ?>
        </p>
        <?php } ?>
        <div class="footer-wrap">
          <?php if(!$no_instructor_sign): ?>
          <div class="signature-wrap">
              <div class="signature">
                <img src="<?php echo $signature_path; ?>" />
              </div>
              <b class="signature-denom"><?php esc_html_e('Signature', 'memberpress-courses'); ?></b>
          </div>
          <?php endif; ?>
          <?php if(!$no_bottom_logo): ?>
          <div class="bottom-logo">
              <img src="<?php echo $bottom_logo_path; ?>" />
          </div>
          <?php endif; ?>
          <?php if(!(empty($instructor_name) || empty($instructor_title))): ?>
            <div class="instructor-wrap">
              <div class="instructor-name">
                <?php echo esc_textarea($instructor_name); ?>
              </div>
              <div class="instructor-title">
                <?php echo esc_textarea($instructor_title); ?>
              </div>
            </div>
          <?php endif ?>
        </div>
      </div>
    </div>
  </body>
</html>
