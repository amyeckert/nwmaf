<?php
/**
 * Flexy_Breadcrumb_Activator Class
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       http://presstigers.com
 * @since      1.0.0
 * @package    Flexy_Breadcrumb
 * @subpackage Flexy_Breadcrumb/includes
 * @author     PressTigers <support@presstigers.com>
 */
class Flexy_Breadcrumb_Activator {

    /**
     * Add WP Options for Flexy Breadcrumb Settings.
     *
     * @since    1.0.0
     * @since    1.2.0  Added breadcrumb_front_url in array of option fbc_settings_options
     * @since    1.2.0  Changed add_option to update_option
     */
    public static function activate() {

        // Default Admin Settings
        $fbc_defaults = array(
            'breadcrumb_front_text' => __('Home', 'flexy-breadcrumb'),
            'breadcrumb_front_url' => get_home_url(),
            'breadcrumb_home_icon' => 'fa-home',
            'breadcrumb_separator' => '/',
            'breadcrumb_limit_style' => 'word',
            'breadcrumb_text_limit' => '4',
            'breadcrumb_end_text' => '...',
            'post_hierarchy' => 'post-category',
            'breadcrumb_text_color' => '#27272a',
            'breadcrumb_link_color' => '#337ab7',
            'breadcrumb_separate_color' => '#cccccc',
            'breadcrumb_background_color' => '#edeff0',
            'breadcrumb_font_size' => 16,
        );
        update_option('fbc_settings_options', $fbc_defaults);
    }
}