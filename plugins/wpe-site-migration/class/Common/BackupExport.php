<?php

namespace DeliciousBrains\WPMDB\Common;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\Common\Sql\TableHelper;
use WP_Error;

class BackupExport
{

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var TableHelper
     */
    private $table_helper;
    /**
     * @var Http
     */
    private $http;
    /**
     * @var Table
     */
    private $table;
    /**
     * @var FormData
     */
    private $form_data;
    /**
     * @var Properties
     */
    private $props;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var MigrationStateManager
     */
    private $migration_state_manager;
    /**
     * @var Manager
     */
    private $queue_manager;

    public function __construct(
        Settings $settings,
        Filesystem $filesystem,
        TableHelper $table_helper,
        Http $http,
        FormData $form_data,
        Table $table,
        Properties $properties,
        MigrationStateManager $migration_state_manager,
        Manager $queue_manager
    ) {
        $this->props                   = $properties;
        $this->settings                = $settings->get_settings();
        $this->filesystem              = $filesystem;
        $this->table_helper            = $table_helper;
        $this->http                    = $http;
        $this->form_data               = $form_data;
        $this->table                   = $table;
        $this->migration_state_manager = $migration_state_manager;
        $this->queue_manager           = $queue_manager;
    }

    public function register()
    {
        add_filter('wpmdb_backup_header_included_tables', array($this, 'backup_header_included_tables'));
        add_filter('wpmdb_enqueue_stage', array($this, 'filter_enqueue_stage'), 10, 2);
    }

    /**
     * Delete an export file.
     *
     * @param string $filename
     * @param bool   $is_backup
     *
     * @return bool|WP_Error
     */
    public function delete_export_file($filename, $is_backup)
    {
        $dump_file = $this->table_helper->format_dump_name($filename);

        if (is_wp_error($dump_file)) {
            return $dump_file;
        }

        if (true === $is_backup) {
            $dump_file = preg_replace('/.gz$/', '', $dump_file);
        }

        $dump_file = Filesystem::get_upload_info() . DIRECTORY_SEPARATOR . $dump_file;

        if (false === $this->filesystem->file_exists($dump_file)) {
            return new WP_Error('wp-migrate-db-export-not-found', __('MySQL export file not found.', 'wp-migrate-db'));
        }

        if (false === $this->filesystem->unlink($dump_file)) {
            return new WP_Error(
                'wp-migrate-db-export-not-found',
                __('Could not delete the MySQL export file.', 'wp-migrate-db')
            );
        }

        return true;
    }

    /**
     * Determine which tables to backup (if required).
     *
     * @param array $profile
     * @param array $prefixed_tables
     * @param array $all_tables
     *
     * @return mixed|void
     */
    public static function get_tables_to_backup($profile, $prefixed_tables, $all_tables)
    {
        $tables_to_backup = array();

        switch ($profile['backup_option']) {
            case 'backup_only_with_prefix':
                $tables_to_backup = $prefixed_tables;
                break;
            case 'backup_selected':
                $selected_tables = ! empty($profile['select_backup']) ? $profile['select_backup'] : $profile['select_tables'];

                /**
                 * When tables to migrate is tables with prefix, select_tables
                 * might be empty. Intersecting it with remote/local tables
                 * throws notice/warning and won't backup the file either.
                 */
                if ('migrate_only_with_prefix' === $profile['table_migrate_option'] || empty($selected_tables)) {
                    $tables_to_backup = $prefixed_tables;
                } else {
                    $tables_to_backup = array_intersect($selected_tables, $all_tables);
                }
                break;
            case 'backup_manual_select':
                $tables_to_backup = array_intersect($profile['select_backup'], $all_tables);
                break;
        }

        return apply_filters('wpmdb_tables_to_backup', $tables_to_backup, $profile);
    }

    /**
     * Updates the database backup header with the tables that were backed up.
     *
     * @param array $included_tables
     *
     * @return mixed|WP_Error
     */
    public function backup_header_included_tables($included_tables)
    {
        $state_data = $this->migration_state_manager->set_post_data();

        if (is_wp_error($state_data)) {
            return $state_data;
        }

        if ('backup' === $state_data['stage']) {
            $form_data = $this->form_data->getFormData();

            $included_tables = $this->get_tables_to_backup(
                $form_data,
                $this->table->get_tables('prefix'),
                $this->table->get_tables()
            );
        }

        return $included_tables;
    }

    /**
     * Get file name and url for backup file.
     *
     * @return array
     */
    public function setup_backups()
    {
        // TODO: Maybe pass `date('YmdHis')` to each `get_sql_dump_info()` call to ensure same name used?
        $dump_filename = wp_basename($this->table->get_sql_dump_info('backup', 'path'));
        $dump_filename = substr($dump_filename, 0, -4);

        return [
            $dump_filename,
            $this->table->get_sql_dump_info('backup', 'url'),
        ];
    }

    /**
     * Enqueues all backup tables to the queue and return their total bytes.
     *
     * @return int|WP_Error
     */
    public function enqueue_backup_tables_to_queue()
    {
        $bytes             = 0;
        $current_migration = $this->form_data->getCurrentMigrationData();

        if (isset($current_migration['backup_tables_selected'])) {
            foreach ($current_migration['backup_tables_selected'] as $table) {
                // Don't enqueue temporary tables
                if (strpos($table, $this->props->temp_prefix) === false) {
                    $enqueued = $this->queue_manager->enqueue_backup_table($table);

                    if (is_wp_error($enqueued)) {
                        return new WP_Error(
                            'enqueue-backup-table-error',
                            sprintf(
                                __(
                                    'Could not add table "%1$s" to queue for backup.<br>Database Error: %2$s',
                                    'wp-migrate-db'
                                ),
                                $table,
                                $enqueued->get_error_message()
                            )
                        );
                    } else {
                        $bytes += $this->table->get_table_size_in_bytes($table);
                    }
                }
            }
        }

        return $bytes;
    }

    /**
     * Handle enqueue_stage filter to enqueue all backup tables to the queue and return their total bytes.
     *
     * @param array|WP_Error $progress
     * @param string         $stage
     *
     * @return array|WP_Error
     * @handles wpmdb_enqueue_stage
     */
    public function filter_enqueue_stage($progress, $stage)
    {
        if ('backup' !== $stage) {
            return $progress;
        }

        if (is_wp_error($progress)) {
            return $progress;
        }

        $initialized_bytes = $this->enqueue_backup_tables_to_queue();

        if (is_wp_error($initialized_bytes)) {
            return $initialized_bytes;
        }

        $progress['initialized_bytes'] = $initialized_bytes;
        $progress['complete']          = true;

        return $progress;
    }
}
