<?php
/*
Plugin Name: Login or Logout Menu Item
Description: Adds a new Menu item which dynamically changes from login to logout depending on the current users logged in status.
Version: 1.2.3
Plugin URI: https://caseproof.com/
Author: cartpauj
Text Domain: lolmi
Domain Path: /i18n
*/

/*
Thanks goes to Juliobox for his work on the BAW Login/Logout Menu plugin on which this is based
*/

/*
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
*/

if(!defined('ABSPATH')) { die("Hey yo, why you cheatin?"); }

function lolmi_load_textdomain() {
  $path = basename(dirname(__FILE__)) . '/i18n';

  load_plugin_textdomain('lolmi', false, $path);
}
add_action('plugins_loaded', 'lolmi_load_textdomain');

function lolmi_add_nav_menu_metabox() {
  add_meta_box('lolmi', __('Login/Logout', 'lolmi'), 'lolmi_nav_menu_metabox', 'nav-menus', 'side', 'default');
}
add_action('admin_head-nav-menus.php', 'lolmi_add_nav_menu_metabox');

function lolmi_nav_menu_metabox($object) {
  global $nav_menu_selected_id;

  $elems = array(
    '#lolmilogin#' => __('Log In', 'lolmi'),
    '#lolmilogout#' => __('Log Out', 'lolmi'),
    '#lolmiloginout#' => __('Log In', 'lolmi').'|'.__('Log Out', 'lolmi')
  );
  
  class lolmiLogItems {
    public $db_id = 0;
    public $object = 'lolmilog';
    public $object_id;
    public $menu_item_parent = 0;
    public $type = 'custom';
    public $title;
    public $url;
    public $target = '';
    public $attr_title = '';
    public $classes = array();
    public $xfn = '';
  }

  $elems_obj = array();

  foreach($elems as $value => $title) {
    $elems_obj[$title]              = new lolmiLogItems();
    $elems_obj[$title]->object_id		= esc_attr($value);
    $elems_obj[$title]->title			  = esc_attr($title);
    $elems_obj[$title]->url			    = esc_attr($value);
  }

  $walker = new Walker_Nav_Menu_Checklist(array());

  ?>
  <div id="login-links" class="loginlinksdiv">
    <div id="tabs-panel-login-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
      <ul id="login-linkschecklist" class="list:login-links categorychecklist form-no-clear">
        <?php echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $elems_obj), 0, (object) array('walker' => $walker)); ?>
      </ul>
    </div>
    <p class="button-controls">
      <span class="add-to-menu">
        <input type="submit"<?php disabled($nav_menu_selected_id, 0); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'lolmi'); ?>" name="add-login-links-menu-item" id="submit-login-links" />
        <span class="spinner"></span>
      </span>
    </p>
  </div>
  <?php
}

function lolmi_nav_menu_type_label($menu_item) {
  $elems = array('#lolmilogin#', '#lolmilogout#', '#lolmiloginout#');
  if(isset($menu_item->object, $menu_item->url) && 'custom' == $menu_item->object && in_array($menu_item->url, $elems)) {
    $menu_item->type_label = __('Dynamic Link', 'lolmi');
  }

  return $menu_item;
}
add_filter('wp_setup_nav_menu_item', 'lolmi_nav_menu_type_label');

function lolmi_loginout_title($title) {
	$titles = explode('|', $title);

	if(!is_user_logged_in()) {
		return esc_html(isset($titles[0])?$titles[0]:__('Log In', 'lolmi'));
	} else {
		return esc_html(isset($titles[1]) ? $titles[1] : __('Log Out', 'lolmi'));
	}
}

function lolmi_setup_nav_menu_item($item) {
	global $pagenow;

	if($pagenow != 'nav-menus.php' && !defined('DOING_AJAX') && isset($item->url) && strstr($item->url, '#lolmi') != '') {
		$login_page_url       = get_option('lolmi_login_page_url', wp_login_url());
    $logout_redirect_url  = get_option('lolmi_logout_redirect_url', home_url());

		switch($item->url) {
			case '#lolmilogin#':
        $item->url = $login_page_url;
        break;
			case '#lolmilogout#':
        $item->url = wp_logout_url($logout_redirect_url);
        break;
			default: //Should be #lolmiloginout#
        $item->url = (is_user_logged_in()) ? wp_logout_url($logout_redirect_url) : $login_page_url;
        $item->title = lolmi_loginout_title($item->title);
		}
	}

	return $item;
}
add_filter('wp_setup_nav_menu_item', 'lolmi_setup_nav_menu_item');

function lolmi_login_redirect_override($redirect_to, $request, $user) {
  if(!is_a($user, 'WP_User') || user_can($user, 'manage_options')) {
    return $redirect_to;
  }

  $login_redirect_url = get_option('lolmi_login_redirect_url', $redirect_to);
  return $login_redirect_url;
}
add_filter('login_redirect', 'lolmi_login_redirect_override', 11, 3);

function lolmi_settings_page() {
  $login_page_url       = get_option('lolmi_login_page_url', wp_login_url());
  $login_redirect_url   = get_option('lolmi_login_redirect_url', home_url());
  $logout_redirect_url  = get_option('lolmi_logout_redirect_url', home_url());
  ?>
    <div class="wrap">
      <div class="icon32"></div>
      <h2><?php _e('Login or Logout Menu Item - Settings', 'lolmi'); ?></h2>
      <div class="lolmi_spacer" style="height:25px;"></div>

      <?php if(isset($_GET['menu-saved'])): ?>
        <div id="message" class="updated notice notice-success is-dismissible below-h2">
          <p><?php _e('Settings saved.', 'lolmi'); ?></p>
        </div>
        <div class="lolmi_spacer" style="height:25px;"></div>
      <?php endif; ?>

      <form action="" method="post">
        <label for="login_page_url"><?php _e('Login Page URL', 'lolmi'); ?></label><br/>
        <small><?php _e('URL where your login page is found.', 'lolmi'); ?></small><br/>
        <input type="text" id="login_page_url" name="login_page_url" value="<?php echo $login_page_url; ?>" style="min-width:250px;width:60%;" /><br/><br/>

        <label for="login_redirect_url"><?php _e('Login Redirect URL', 'lolmi'); ?></label><br/>
        <small><?php _e('URL to redirect a user to after logging in. Note: Some other plugins may override this URL.', 'lolmi'); ?></small><br/>
        <input type="text" id="login_redirect_url" name="login_redirect_url" value="<?php echo $login_redirect_url; ?>" style="min-width:250px;width:60%;" /><br/><br/>

        <label for="logout_redirect_url"><?php _e('Logout Redirect URL', 'lolmi'); ?></label><br/>
        <small><?php _e('URL to redirect a user to after logging out. Note: Some other plugins may override this URL.', 'lolmi'); ?></small><br/>
        <input type="text" id="logout_redirect_url" name="logout_redirect_url" value="<?php echo $logout_redirect_url; ?>" style="min-width:250px;width:60%;" /><br/><br/>

        <?php wp_nonce_field('the_nonce'); ?>
        <input type="submit" id="settings_submit" name="settings_submit" value="<?php _e('Save Settings', 'lolmi'); ?>" class="button button-primary" />
      </form>
    </div>
  <?php
}

function lolmi_setup_menus() {
  add_options_page('Login/Logout Settings', 'Login or Logout', 'manage_options', 'login-logout-settings', 'lolmi_settings_page');
}
add_action('admin_menu', 'lolmi_setup_menus');

function lolmi_save_settings() {
  if(!isset($_GET['page']) || $_GET['page'] != 'login-logout-settings') { return; }

  if(isset($_POST['settings_submit'])) {
    if(!current_user_can('manage_options')) { die("Cheating eh?"); }
    if(!check_admin_referer('the_nonce')) { die("Invalid Submission, try again."); }

    $login_page_url       = (isset($_POST['login_page_url']) && !empty($_POST['login_page_url'])) ? $_POST['login_page_url'] : wp_login_url();
    $login_redirect_url   = (isset($_POST['login_redirect_url']) && !empty($_POST['login_redirect_url'])) ? $_POST['login_redirect_url'] : home_url();
    $logout_redirect_url  = (isset($_POST['logout_redirect_url']) && !empty($_POST['logout_redirect_url'])) ? $_POST['logout_redirect_url'] : home_url();

    update_option('lolmi_login_page_url', esc_url_raw($login_page_url));
    update_option('lolmi_login_redirect_url', esc_url_raw($login_redirect_url));
    update_option('lolmi_logout_redirect_url', esc_url_raw($logout_redirect_url));

    $_GET['menu-saved'] = true;
  }
}
add_action('admin_init', 'lolmi_save_settings');
