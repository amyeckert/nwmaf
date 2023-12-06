<?php

namespace DeliciousBrains\WPMDB\Common\MigrationPersistence;

use DeliciousBrains\WPMDB\Common\Exceptions\SanitizationFailureException;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\Sanitize;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\WPMDBDI;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use WP_Error;

/**
 * Class Persistence
 *
 * Class to get and set migration state and migration settings during/before/after ajax requests
 *
 * Each AJAX request should update state and store in the DB
 *
 * Supersedes MigrationState
 *
 * @package DeliciousBrains\WPMDB\Common\MigrationPersistance
 */
class Persistence
{

    public $state_data;

    public static function saveStateData($data, $key = WPMDB_MIGRATION_STATE_OPTION)
    {
        return update_site_option($key, $data);
    }

    public static function getFromStateData($key, $state_key = WPMDB_MIGRATION_STATE_OPTION)
    {
        $state_data = self::getStateData($state_key);
        if (!isset($state_data[$key])) {
            return false;
        }

        return $state_data[$key];
    }

    /**
     * Returns the `wpmdb_migration_state` option if it exists, otherwise returns a sanitized $_POST array
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function getStateData($key = WPMDB_MIGRATION_STATE_OPTION)
    {
        $state_data = get_site_option($key);

        if (false === $state_data) {
            $filtered = filter_var_array($_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            return $filtered;
        }

        return $state_data;
    }

    public static function getRemoteStateData($key = WPMDB_REMOTE_MIGRATION_STATE_OPTION)
    {
        $state_data = get_site_option($key);

        if (false === $state_data) {
            return filter_var_array($_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        return $state_data;
    }

    public static function saveMigrationOptions($options)
    {
        update_site_option(WPMDB_MIGRATION_OPTIONS_OPTION, $options);

        return $options;
    }

    public static function getMigrationOptions()
    {
        return get_site_option(WPMDB_MIGRATION_OPTIONS_OPTION);
    }

    public static function storeRemoteResponse($response)
    {
        return update_site_option(WPMDB_REMOTE_RESPONSE_OPTION, $response);
    }

    /**
     * Stores value of WPE auth cookie stored in site options
     * @param string $cookie
     * @return bool
     **/
    public static function storeRemoteWPECookie($cookie)
    {
        return update_site_option(WPMDB_WPE_REMOTE_COOKIE_OPTION, $cookie);
    }

    /**
     * Get value of WPE auth cookie stored in site options
     *
     * @return bool
     **/
    public static function getRemoteWPECookie()
    {
        return get_site_option(WPMDB_WPE_REMOTE_COOKIE_OPTION);
    }

     /**
     * Remove WPE auth cookie stored in site options
     *
     * @return bool
     **/
    public static function removeRemoteWPECookie()
    {
        return delete_site_option(WPMDB_WPE_REMOTE_COOKIE_OPTION);
    }

     /**
     * Stores value of migration stats in site options
     * @param array $stats
     * @return bool
     **/
    public static function storeMigrationStats($stats)
    {
        return update_site_option(WPMDB_MIGRATION_STATS_OPTION, $stats);
    }

    /**
     * Get value of migration stats stored in site options
     *
     * @return array
     **/
    public static function getMigrationStats()
    {
        return get_site_option(WPMDB_MIGRATION_STATS_OPTION, []);
    }

     /**
     * Remove migration stats stored in site options
     *
     * @return bool
     **/
    public static function removeMigrationStats()
    {
        return delete_site_option(WPMDB_MIGRATION_STATS_OPTION);
    }

    /**
     * Parse the given or global post data.
     *
     * @param string|array      $fields    The keys in the data (if data is an array) and the sanitization rule(s) to apply for each key.
     * @param string            $context   Additional context data for messages etc.
     * @param string            $key       Site option key to get existing data from and optionally save back to.
     * @param string|array|bool $post_data Data to be parsed, or false to only parse $_POST.
     * @param bool              $sanitize  Should the fields be sanitized? Default yes.
     * @param bool              $save      Should the parsed data be saved back to the site option? Default yes.
     *
     * @return mixed|WP_Error
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function setPostData(
        $fields,
        $context,
        $key = WPMDB_MIGRATION_STATE_OPTION,
        $post_data = false,
        $sanitize = true,
        $save = true
    ) {
        $util = WPMDBDI::getInstance()->get(Util::class);
        $util->set_time_limit();

        $state_data = false;
        $post_data  = ! $post_data ? $_POST : $post_data;

        if ($sanitize) {
            $state_data = self::sanitizeFields($fields, $context, $post_data);

            if (is_wp_error($state_data)) {
                return $state_data;
            }
        } elseif ($post_data) {
            $state_data = $post_data;
        }

        if ( ! $state_data) {
            return false;
        }

        if (is_wp_error($state_data)) {
            return $state_data;
        }

        $existing_data = get_site_option($key);
        if ( ! empty($existing_data) && is_array($existing_data)) {
            $state_data = array_merge($existing_data, $state_data);
        }

        //Make sure $state_data['site_details']['remote'] is set
        // @TODO refactor
        if (empty($state_data['site_details']['remote']) && isset($state_data['site_details']['local'])) {
            $migration_helper                     = WPMDBDI::getInstance()->get(MigrationHelper::class);
            $state_data['site_details']['remote'] = $migration_helper->siteDetails()['site_details'];
        }

        if ($save) {
            update_site_option($key, $state_data);
        }

        return $state_data;
    }

    /**
     * Parse the given or global post data for a remote post.
     *
     * @param string|array $fields
     * @param string       $context
     * @param string       $key
     * @param bool         $post_data
     * @param bool         $sanitize
     * @param bool         $save
     *
     * @return mixed|WP_Error
     */
    public static function setRemotePostData(
        $fields,
        $context,
        $key = WPMDB_REMOTE_MIGRATION_STATE_OPTION,
        $post_data = false,
        $sanitize = true,
        $save = true
    ) {
        try {
            return self::setPostData($fields, $context, $key, $post_data, $sanitize, $save);
        } catch (Exception $e) {
            return new WP_Error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Remove options from the site_meta/wp_options table
     *
     * @param string $key
     */
    public static function cleanupStateOptions($key = WPMDB_MIGRATION_STATE_OPTION)
    {
        delete_site_option($key);
        delete_site_option(WPMDB_MIGRATION_OPTIONS_OPTION);
        delete_site_option(WPMDB_REMOTE_RESPONSE_OPTION);
    }

    /**
     * Sanitize fields of post data.
     *
     * @param string|array $fields
     * @param string       $context
     * @param string|array $post_data
     *
     * @return mixed
     */
    public static function sanitizeFields($fields, $context, $post_data)
    {
        try {
            $state_data = Sanitize::sanitize_data($post_data, $fields, $context);
        } catch (SanitizationFailureException $exception) {
            return new WP_Error('sanitization_error', $exception->getMessage());
        }

        return $state_data;
    }

    public static function getStateDataByIntent($intent)
    {
        if ('pull' === $intent) {
            return self::getRemoteStateData();
        }

        return self::getStateData();
    }

    public static function storeLocalSiteBasicAuth($username, $password)
    {
        $credentials = base64_encode($username . ':' . $password);
        update_site_option(WPMDB_SITE_BASIC_AUTH_OPTION, $credentials);
    }

    public static function getLocalSiteBasicAuth()
    {
        return get_site_option(WPMDB_SITE_BASIC_AUTH_OPTION);
    }

}
