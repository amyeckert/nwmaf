<?php if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); } ?>
<?php
use memberpress\courses\helpers as helpers;
?>

<div><?php printf(__('You can also change your settings in the <a href="%s">WordPress Customizer</a>', 'memberpress-courses'), admin_url( '/customize.php?autofocus[section]=mpcs_classroom&url=' ) . home_url( 'courses' ) ) ?></div>

<table class="form-table">
  <tbody>

    <tr valign="top">
      <th scope="row">
        <label for="mpcs-options[show-protected-courses]"><?php _e('Show Protected Courses in Listing', 'memberpress-courses'); ?></label>
        <?php helpers\App::info_tooltip('mpcs-show-protected-courses',
                _x('Show Protected Courses in Listing', 'ui', 'memberpress-courses'),
                _x('By default, protected courses are displayed in Course Listing page with a padlock icon appearing before the title. Use this field to show/hide protected courses in Course Listing', 'ui', 'memberpress-courses'));
        ?>
      </th>
      <td>
        <label class="switch">
          <input id="mpcs_options_show_protected_courses" name="mpcs-options[show-protected-courses]" class="" type="checkbox" value="1" <?php checked( 1, helpers\Options::val($options,'show-protected-courses', 1) ); ?> />
          <span class="slider round"></span>
        </label>

      </td>
    </tr>

    <tr valign="top">
      <th scope="row">
        <label for="mpcs-options[remove-instructor-link]"><?php _e('Remove your instructor link', 'memberpress-courses'); ?></label>
        <?php helpers\App::info_tooltip('mpcs-show-protected-courses',
                _x('Remove instructor link in classroom mode', 'ui', 'memberpress-courses'),
                _x('By default, a link to instructor of the course will be displayed in Classroom page. Use this field to show/hide the link', 'ui', 'memberpress-courses'));
        ?>
      </th>
      <td>
        <label class="switch">
          <input id="mpcs_options_remove_instructor_link" name="mpcs-options[remove-instructor-link]" class="" type="checkbox" value="1" <?php checked( 1, helpers\Options::val($options,'remove-instructor-link', 1) ); ?> />
          <span class="slider round"></span>
        </label>

      </td>
    </tr>

    <?php do_action('mpc_admin_general_options'); ?>
  </tbody>
</table>
