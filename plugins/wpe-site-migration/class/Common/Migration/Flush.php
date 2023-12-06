<?php

namespace DeliciousBrains\WPMDB\Common\Migration;

use DeliciousBrains\WPMDB\Common\Error\HandleRemotePostError;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Util\Util;
use WP_Error;

class Flush
{

    /**
     * @var Helper
     */
    private $http_helper;
    /**
     * @var Util
     */
    private $util;
    /**
     * @var RemotePost
     */
    private $remote_post;
    /**
     * @var Http
     */
    private $http;

    public function __construct(
        Helper $helper,
        Util $util,
        RemotePost $remote_post,
        Http $http
    ) {
        $this->http_helper = $helper;
        $this->util        = $util;
        $this->remote_post = $remote_post;
        $this->http        = $http;
    }

    public function register()
    {
        add_action('wp_ajax_nopriv_wpmdb_flush', array($this, 'ajax_nopriv_flush',));
        add_action('wp_ajax_wpmdb_flush', array($this, 'ajax_flush'));
        add_action('wpmdb_after_finalize_migration', [$this, 'flush']);
    }

    /**
     * Handles the request to flush caches and cleanup migration when pushing or not migrating user tables.
     *
     * @return void
     */
    public function ajax_flush()
    {
        $this->http->check_ajax_referer('flush');

        $this->ajax_nopriv_flush();
    }

    /**
     * Handles the request to flush caches and cleanup migration when pushing or pulling with user tables being migrated.
     *
     * @return void
     */
    public function ajax_nopriv_flush()
    {
        $this->http->end_ajax($this->flush());
    }

    /**
     * Flush caches and cleanup migration when pushing or pulling with user tables being migrated.
     *
     * @param bool|array $state_data
     *
     * @handles wpmdb_migration_complete
     * @return void
     */
    public function flush($state_data = false)
    {
        $state_data = ! $state_data ? Persistence::getStateData() : $state_data;

        if ('push' === $state_data['intent']) {
            $data           = array();
            $data['action'] = 'wpmdb_remote_flush';
            $data['sig']    = $this->http_helper->create_signature($data, $state_data['key']);
            $ajax_url       = $this->util->ajax_url();
            $response       = $this->remote_post->post($ajax_url, $data, __FUNCTION__);
            HandleRemotePostError::handle('wpmdb-remote-flush-failed', $response);
        } else {
            $this->flush_local();
        }

    }

    /**
     * Flushes the cache and rewrite rules.
     *
     * @return bool
     */
    public function flush_local()
    {
        // flush rewrite rules to prevent 404s and other oddities
        wp_cache_flush();
        global $wp_rewrite;
        $endpoints = $wp_rewrite->endpoints;
        $wp_rewrite->init();
        $wp_rewrite->endpoints = $endpoints;
        flush_rewrite_rules(); // default true = hard refresh, recreates the .htaccess file

        return true;
    }
}
