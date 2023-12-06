<?php if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); } ?>

<div class="wrap mpdl-file-download-limit-container">
  <input type="hidden" name="mpdl-file-nonce" value="<?php echo wp_create_nonce('mpdl-file-nonce' . wp_salt()); ?>" />

  <input type="checkbox" name="mpdl-enable-download-limit" id="mpdl-enable-download-limit" value="1" <?php checked($file->enable_download_limit); ?>>
  <label for="mpdl-enable-download-limit"><?php _e('Enable Download Limit', 'memberpress-downloads'); ?></label>
  <?php MeprAppHelper::info_tooltip('mpdl-enable-download-limit',
    __('Enable Download Limit', 'memberpress-downloads'),
    __('Enable this option to allow download limits for the file.', 'memberpress-downloads')); ?>

  <div id="mpdl-download-limit-options">
    <div class="mpdl-download-limit-container">
      <label for="mpdl-download-limit"><?php _e('Download Limit:', 'memberpress-downloads'); ?></label>
      <?php MeprAppHelper::info_tooltip('mpdl-download-limit',
        __('Download Limit', 'memberpress-downloads'),
        __('The number of downloads that should be reached before this file can no longer be downloaded.', 'memberpress-downloads') . '<br/><br/>' . __('Set this option to 0 or leave it empty if you don\'t want download limits to take effect.', 'memberpress-downloads')); ?>
      <input type="number" name="mpdl-download-limit" id="mpdl-download-limit" min="0" value="<?php echo (int) $file->download_limit; ?>">
    </div>

    <div class="mpdl-user-limit-container">
      <label for="mpdl-user-limit"><?php _e('User Limit:', 'memberpress-downloads'); ?></label>
      <?php MeprAppHelper::info_tooltip('mpdl-user-limit',
        __('User Limit', 'memberpress-downloads'),
        __('The number of downloads that should be reached by a user before they can no longer download this file.', 'memberpress-downloads') . '<br/><br/>' . __('If user limits are enabled and the user accessing the file is a guest, they will always be redirected.', 'memberpress-downloads') . '<br/><br/>' . __('Set this option to 0 or leave it empty if you don\'t want user limits to take effect.', 'memberpress-downloads')); ?>
      <input type="number" name="mpdl-user-limit" id="mpdl-user-limit" min="0" value="<?php echo (int) $file->user_limit; ?>">
    </div>

    <div class="mpdl-download-limit-redirect-container">
      <label for="mpdl-download-limit-redirect"><?php _e('Redirect URL:', 'memberpress-downloads'); ?></label>
      <?php MeprAppHelper::info_tooltip('mpdl-download-limit-redirect',
        __('Redirect URL', 'memberpress-downloads'),
        __('URL where members will be redirected to if the download count has been exceeded. If no URL is provided, it\'ll default to the MemberPress unauthorized redirect URL (if enabled) or the MemberPress account page.', 'memberpress-downloads')); ?>
      <input type="text" name="mpdl-download-limit-redirect" id="mpdl-download-limit-redirect" value="<?php echo stripslashes($file->download_limit_redirect); ?>">
    </div>
  </div>
</div>