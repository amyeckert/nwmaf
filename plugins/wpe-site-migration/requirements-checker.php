<?php

/**
 * Class WPMDB_PHP_Checker
 *
 * Check's to see if a site's PHP version is below self::$min_php, disables WP Migrate Lite/Pro/WPESM if PHP is below minimum
 * Check's to see if a site's WP version is below self::$min_wp, disables WP Migrate Lite/Pro/WPESM if WP is below minimum
 *
 * To increase the required PHP version, change the self::$min_php value here and update
 * the WPMDB_MINIMUM_PHP_VERSION constant in setup-plugin.php
 *
 * To increase the required WP version, change the self::$min_wp value here and update
 * the WPMDB_MINIMUM_WP_VERSION constant in setup-plugin.php
 *
 *
 * For which activation hooks to use:
 *
 * @see https://pento.net/2014/02/18/dont-let-your-plugin-be-activated-on-incompatible-sites/
 */
class WPMDB_Requirements_Checker
{

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public static $php_doc_link;

    /**
     * @var string
     */
    public static $min_php;

    /**
     * @var string
     */
    public static $min_wp;

    /**
     * @var string
     */
    private $template = '
		<div class="notice notice-warning is-dismissible">
				<p>%s</p>
		</div>';

    /**
     * @param string $path
     * @param string $min_php
     * @param string $min_wp
     */
    public function __construct($path, $min_php, $min_wp)
    {
        $this->path         = $path;
        self::$min_wp       = $min_wp;
        self::$min_php      = $min_php; // To increase the minimum PHP required, change this value _AND_ WPMDB_MINIMUM_PHP_VERSION in the main plugin files
        self::$php_doc_link = 'https://deliciousbrains.com/wp-migrate-db-pro/doc/upgrading-php/';

        add_action('admin_init', array($this, 'maybe_deactivate_plugin'));
    }

    /**
     * Checks if the server is running a compatible php version
     *
     * @return bool
     */
    private function is_php_compatible()
    {
        return version_compare(PHP_VERSION, self::$min_php, '>=') || ! is_plugin_active(plugin_basename($this->path));
    }

    /**
     * Checks if the site is running a compatible WordPress version
     *
     * @return bool
     */
    private function is_wp_compatible()
    {
        return version_compare(get_bloginfo('version'), self::$min_wp,
                '>=') || ! is_plugin_active(plugin_basename($this->path));
    }

    /**
     * Deactivate the plugin if running on an unsupported version of WP or PHP
     *
     * @return void
     */
    public function maybe_deactivate_plugin()
    {
        $deactivate = false;

        if ( ! $this->is_php_compatible()) {
            $deactivate = true;
            add_action('admin_notices', [$this, 'incompatible_php_version_notice']);
        }

        if ( ! $this->is_wp_compatible()) {
            $deactivate = true;
            add_action('admin_notices', [$this, 'incompatible_wp_version_notice']);
        }

        if (true === $deactivate) {
            deactivate_plugins(plugin_basename($this->path));

            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    /**
     * Displays incompatible PHP admin notice
     *
     * @handles admin_notices
     * @return void
     */
    public function incompatible_php_version_notice()
    {
        $message = sprintf(__('%s requires PHP version %s or higher to run and has been deactivated. You are currently running version %s.',
            'wp-migrate-db'), WPMDB_PLUGIN_TITLE, self::$min_php, PHP_VERSION);

        echo sprintf($this->template, $message);
    }

    /**
     * Displays incompatible WP admin notice
     *
     * @handles admin_notices
     * @return void
     */
    public function incompatible_wp_version_notice()
    {
        $message = sprintf(__('%s requires WordPress version %s or higher to run and has been deactivated. You are currently running version %s.',
            'wp-migrate-db'), WPMDB_PLUGIN_TITLE, self::$min_wp, get_bloginfo('version'));

        echo sprintf($this->template, $message);
    }
}
