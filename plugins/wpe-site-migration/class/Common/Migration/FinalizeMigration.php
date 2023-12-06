<?php

namespace DeliciousBrains\WPMDB\Common\Migration;

use DeliciousBrains\WPMDB\Common\Error\HandleRemotePostError;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\Common\Sql\TableHelper;
use DeliciousBrains\WPMDB\Common\Util\Util;
use WP_Error;

class FinalizeMigration
{

    public $state_data;
    /**
     * @var MigrationStateManager
     */
    private $migration_state_manager;
    /**
     * @var Table
     */
    private $table;
    /**
     * @var Http
     */
    private $http;
    /**
     * @var Properties
     */
    private $props;
    /**
     * @var TableHelper
     */
    private $table_helper;
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
     * @var FormData
     */
    private $form_data;
    /**
     * @var MigrationHelper
     */
    private $migration_helper;

    public function __construct(
        MigrationStateManager $migration_state_manager,
        Table $table,
        Http $http,
        TableHelper $table_helper,
        Helper $http_helper,
        Util $util,
        RemotePost $remote_post,
        FormData $form_data,
        Properties $properties,
        MigrationHelper $migration_helper
    ) {
        $this->migration_state_manager = $migration_state_manager;
        $this->table                   = $table;
        $this->http                    = $http;
        $this->props                   = $properties;
        $this->table_helper            = $table_helper;
        $this->http_helper             = $http_helper;
        $this->util                    = $util;
        $this->remote_post             = $remote_post;
        $this->form_data               = $form_data;
        $this->migration_helper        = $migration_helper;

        add_filter('wpmdb_process_stage', [$this, 'filter_process_stage'], 10, 3);
        add_filter('wpmdb_enqueue_stage', [$this, 'filter_enqueue_stage'], 10, 2);
        add_action('wpmdb_after_finalize_migration', [$this, 'remove_migration_data']);
        add_action('wpmdb_cancellation', [$this, 'remove_migration_data']);
    }

    /**
     * After table migration, delete old tables and rename new tables removing the temporary prefix.
     *
     * @return void
     */
    public function ajax_finalize_migration()
    {
        $_POST = $this->http_helper->convert_json_body_to_post();

        $this->http->end_ajax($this->finalize_migration());
    }

    /**
     * Finalize Export by moving file to specified destination.
     *
     * @param array $state_data
     *
     * @return bool|WP_Error
     */
    private function finalize_export($state_data)
    {
        if (is_wp_error($state_data)) {
            return $state_data;
        }

        if ( ! is_array($state_data) || empty($state_data['dump_path'])) {
            return new WP_Error(
                'missing-dump-path',
                __('Could not find temporary file path for exported data.', 'wp-migrate-db')
            );
        }

        if ( ! file_exists($state_data['dump_path'])) {
            return new WP_Error(
                'invalid-dump-path',
                __('Could not find temporary file with exported data.', 'wp-migrate-db')
            );
        }

        // No need to move file?
        if (empty($state_data['export_dest']) || 'ORIGIN' === $state_data['export_dest']) {
            return true;
        }

        if ( ! rename($state_data['dump_path'], $state_data['export_dest'])) {
            return new WP_Error('rename-export-file-error', __('Unable to move exported file.', 'wp-migrate-db'));
        }

        return true;
    }

    /**
     * After table migration, delete old tables and rename new tables removing the temporary prefix.
     *
     * @param array|false $state_data
     *
     * @return mixed|WP_Error
     */
    public function finalize_migration($state_data = false)
    {
        $key_rules = array(
            'action' => 'key',
            'prefix' => 'string',
            'tables' => 'string',
            'nonce'  => 'key',
        );

        $key_rules = apply_filters('wpmdb_finalize_key_rules', $key_rules);

        $state_data = Persistence::setPostData($key_rules, __METHOD__, WPMDB_MIGRATION_STATE_OPTION, $state_data);

        if (is_wp_error($state_data)) {
            return $state_data;
        }

        if ( ! isset($state_data['site_details'])) {
            return new WP_Error(
                'wpmdb_finalize_failed',
                __('Unable to finalize the migration, migration state empty.')
            );
        }

        global $wpdb;

        do_action('wpmdb_finalize_migration', $state_data); // Fires on local site

        if ('push' === $state_data['intent']) {
            do_action('wpmdb_migration_complete', 'push', $state_data['url']);
            $data                 = $this->http_helper->filter_post_elements(
                $state_data,
                array(
                    'url',
                    'form_data',
                    'site_details',
                    'tables',
                    'temp_prefix',
                )
            );
            $data['form_data']    = base64_encode($data['form_data']);
            $data['site_details'] = base64_encode(serialize($data['site_details']));

            $data['action']   = 'wpmdb_remote_finalize_migration';
            $data['intent']   = $state_data['intent'];
            $data['prefix']   = $wpdb->base_prefix;
            $data['type']     = 'push';
            $data['location'] = Util::home_url();
            $data['sig']      = $this->http_helper->create_signature($data, $state_data['key']);
            $data['stage']    = $state_data['stage'];
            $ajax_url         = $this->util->ajax_url();
            $response         = $this->remote_post->post($ajax_url, $data, __FUNCTION__);
            $return           = HandleRemotePostError::handle('wpmdb-remote-finalize-failed', $response);
        } else {
            $return = $this->local_finalize_migration($state_data);
        }

        // Fire post finalization actions
        do_action('wpmdb_after_finalize_migration', $state_data);

        return $return;
    }

    /**
     * Internal function for finalizing a migration.
     *
     * @param mixed $state_data
     *
     * @return bool|WP_Error
     */
    public function local_finalize_migration($state_data = false)
    {
        $state_data = ! $state_data ? Persistence::getStateData() : $state_data;

        if ('savefile' === $state_data['intent']) {
            return $this->finalize_export($state_data);
        }

        if (in_array($state_data['intent'], ['push', 'pull'])) {
            $intent_type                      = isset($state_data['type']) ? $state_data['type'] : $state_data['intent'];
            $state_data['destination_prefix'] = ('push' === $intent_type) ? $state_data['site_details']['remote']['prefix'] : $state_data['site_details']['local']['prefix'];
            $state_data['source_prefix']      = ('push' === $intent_type) ? $state_data['site_details']['local']['prefix'] : $state_data['site_details']['remote']['prefix'];
        }

        $temp_prefix              = isset($state_data['temp_prefix']) ? $state_data['temp_prefix'] : $this->props->temp_prefix;
        $temp_tables              = array();
        $type                     = $state_data['intent'];
        $alter_table_name         = $this->table->get_alter_table_name();
        $before_finalize_response = apply_filters('wpmdb_before_finalize_migration', $state_data['intent']);

        if (is_wp_error($before_finalize_response)) {
            return $before_finalize_response;
        }

        if (isset($state_data['type']) && 'push' === $state_data['type']) {
            $type = 'push';
        }

        $tables = $this->get_tables($state_data);

        if ('find_replace' === $state_data['intent'] || 'import' === $state_data['intent']) {
            $location = Util::home_url();
        } else {
            $location = (isset($state_data['location'])) ? $state_data['location'] : $state_data['url'];
        }

        if ('import' === $state_data['intent']) {
            $temp_tables = $this->table->get_tables('temp');
            $tables      = array();

            foreach ($temp_tables as $key => $temp_table) {
                if ($alter_table_name === $temp_table) {
                    unset($temp_tables[$key]);
                    continue;
                }

                $tables[] = substr($temp_table, strlen($temp_prefix));
            }
        } else {
            foreach ($tables as $table) {
                $temp_tables[] = $temp_prefix . apply_filters(
                        'wpmdb_finalize_target_table_name',
                        $table,
                        $state_data
                    );
            }
        }

        $sql = "SET FOREIGN_KEY_CHECKS=0;\n";

        $sql .= $this->table->get_preserved_options_queries($state_data, $temp_tables, $type);
        $sql .= $this->table->get_preserved_usermeta_queries($state_data, $temp_tables, $type);

        foreach ($temp_tables as $table) {
            if ( ! $this->table->table_exists($table)) {
                return new WP_Error(
                    'finalize-table-missing',
                    sprintf(__('Table "%s" missing.', 'wp-migrate-db'), $table)
                );
            }

            $sql .= 'DROP TABLE IF EXISTS ' . $this->table_helper->backquote(
                    substr(
                        $table,
                        strlen($temp_prefix)
                    )
                ) . ';';
            $sql .= "\n";
            $sql .= 'RENAME TABLE ' . $this->table_helper->backquote($table) . ' TO ' . $this->table_helper->backquote(
                    substr(
                        $table,
                        strlen($temp_prefix)
                    )
                ) . ';';
            $sql .= "\n";
        }

        $sql .= $this->table->get_alter_queries($state_data);
        $sql .= 'DROP TABLE IF EXISTS ' . $this->table_helper->backquote($alter_table_name) . ";\n";

        $process_chunk_result = $this->table->process_chunk($sql);

        if (true !== $process_chunk_result) {
            return $process_chunk_result;
        }

        if ( ! isset($state_data['location']) && ! in_array($state_data['intent'], array('find_replace', 'import'))) {
            $data           = array();
            $data['action'] = 'wpmdb_fire_migration_complete';
            $data['url']    = Util::home_url();
            $data['sig']    = $this->http_helper->create_signature($data, $state_data['key']);
            $ajax_url       = $this->util->ajax_url();
            $response       = $this->remote_post->post($ajax_url, $data, __FUNCTION__);

            $this->util->display_errors();
            $decoded_response = json_decode($response, true);

            if ( ! isset($decoded_response['success']) || ! $decoded_response['success']) {
                return new WP_Error(
                    'wpmdb-remote-finalize-failed',
                    $response
                );
            }
        }

        do_action('wpmdb_migration_complete', $type, $location);

        return true;
    }

    /**
     * Convert string of table names to array, changes prefix if needed.
     *
     * @param array $state_data
     *
     * @return array of tables
     **/
    private function get_tables($state_data)
    {
        if (empty($state_data['tables'])) {
            return [];
        }

        $source_tables = is_string($state_data['tables']) ? explode(',', $state_data['tables']) : $state_data['tables'];

        $source_prefix      = $state_data['source_prefix'];
        $destination_prefix = $state_data['destination_prefix'];
        if ($source_prefix === $destination_prefix || (isset($state_data['mst_select_subsite']) && '1' === $state_data['mst_select_subsite'])) {
            return $source_tables;
        }

        return Util::change_tables_prefix($source_tables, $source_prefix, $destination_prefix);
    }

    /**
     * Handle process_stage filter to finalize a migration.
     *
     * @param array  $progress data from slice of processing, has values for 'processed_bytes' and 'complete'.
     * @param string $stage
     * @param string $migration_id
     *
     * @return array|WP_Error
     * @handles wpmdb_process_stage
     */
    public function filter_process_stage($progress, $stage, $migration_id)
    {
        if ('finalize' !== $stage) {
            return $progress;
        }

        if (is_wp_error($progress)) {
            return $progress;
        }

        $current_migration = StateFactory::create('current_migration')->load_state($migration_id)->get_state();

        if (is_wp_error($current_migration)) {
            return $current_migration;
        }

        $state_data = [];

        if ( ! empty($current_migration['databaseEnabled']) && ! empty($current_migration['tables_selected'])) {
            $state_data['tables'] = join(',', $current_migration['tables_selected']);
        }
        if (in_array($current_migration['intent'], ['push', 'pull'])) {
            $state_data['prefix'] = StateFactory::create('local_site')->load_state($migration_id)->get('this_prefix');
        }

        if ( ! empty($current_migration['selected_existing_profile'])) {
            $state_data['profileID'] = $current_migration['selected_existing_profile'];
        }

        if ( ! empty($current_migration['profile_type'])) {
            $state_data['profileType'] = $current_migration['profile_type'];
        }

        $result = $this->finalize_migration($state_data);

        if (is_wp_error($result)) {
            return $result;
        }

        $progress['complete'] = true;

        return $progress;
    }

    /**
     * Handle enqueue_stage filter to return finalization stage size in bytes.
     *
     * @param array|WP_Error $progress
     * @param string         $stage
     *
     * @return array|WP_Error
     * @handles wpmdb_enqueue_stage
     */
    public function filter_enqueue_stage($progress, $stage)
    {
        if ('finalize' !== $stage) {
            return $progress;
        }

        if (is_wp_error($progress)) {
            return $progress;
        }

        $progress['initialized_bytes'] = 0;
        $progress['complete']          = true;

        return $progress;
    }

    /**
     * Cleans up sensitive data migration data on finalization.
     *
     * @handles wpmdb_after_finalize_migration
     * @handles wpmdb_cancellation
     * @return void
     */
    public function remove_migration_data()
    {
        //Remove basic auth info
        delete_site_option(WPMDB_SITE_BASIC_AUTH_OPTION);
    }
}
