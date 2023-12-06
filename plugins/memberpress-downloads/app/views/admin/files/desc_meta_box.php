<?php
if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); }
?>
<div class="wrap mpdl-file-description-container">
  <input type="hidden" name="mpdl-file-nonce" value="<?php echo wp_create_nonce('mpdl-file-nonce' . wp_salt()); ?>" />
  <textarea id="mpdl-file-description" name="mpdl-file-description"><?php echo $file->filedesc; ?></textarea>
</div>
