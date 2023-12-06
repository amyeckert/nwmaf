<?php
if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); }
?>
<div class="wrap mpdl-file-description-container">
  <input type="hidden" name="mpdl-file-nonce" value="<?php echo wp_create_nonce('mpdl-file-nonce' . wp_salt()); ?>" />
  <label for="mpdl-file-force-viewing-field">
      <input type="checkbox" id="mpdl-file-force-viewing-field" value="1" name="mpdl-file-force-viewing" <?php checked($file->force_viewing); ?> />
  </label>
  <span><?php echo __('Enabling this will allow users to view this file directly in the browser. <b>Only applicable to PDF or image file.</b>', 'memberpress-downloads'); ?></span>
</div>