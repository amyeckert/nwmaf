<?php

namespace DeliciousBrains\WPMDB\Common\Queue;

use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateDataContainer;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use WP_Error;
use wpdb;

class Manager
{

    public $queue;
    public $worker;
    public $connection;
    public $prefix;
    public $jobs_table;
    public $failures_table;

    /**
     * @var wpdb
     */
    public $wpdb;

    /**
     * @var Properties
     */
    private $properties;
    /**
     * @var StateDataContainer
     */
    private $state_data_container;
    /**
     * @var MigrationStateManager
     */
    private $migration_state_manager;
    /**
     * @var FormData
     */
    private $form_data;

    function __construct(
        Properties $properties,
        StateDataContainer $state_data_container,
        MigrationStateManager $migration_state_manager,
        FormData $form_data
    ) {
        $this->wpdb                    = $GLOBALS['wpdb'];
        $this->properties              = $properties;
        $this->state_data_container    = $state_data_container;
        $this->migration_state_manager = $migration_state_manager;
        $this->form_data               = $form_data;

        $this->prefix         = $this->properties->temp_prefix;
        $this->jobs_table     = $this->prefix . "queue_jobs";
        $this->failures_table = $this->prefix . "queue_failures";

        $this->connection = new Connection($GLOBALS['wpdb'], $properties->temp_prefix);
        $this->queue      = new Queue($this->connection, $this->prefix);
        $this->worker     = new Worker($this->connection, 1);
    }

    public function register()
    {
        add_filter('wpmdb_initiate_migration', [$this, 'ensure_tables_exist']);
        add_action('wpmdb_always_exclude_table', [$this, 'filter_always_exclude_table']);
        add_action('wpmdb_migration_complete', [$this, 'do_cleanup']);
        add_action('wpmdb_cancellation', [$this, 'do_cleanup']);
    }

    /**
     * Adds a file to the jobs queue.
     *
     * @param array  $file
     * @param string $stage
     *
     * @return int|WP_Error
     */
    public function enqueue_file($file, $stage)
    {
        switch ($stage) {
            case 'media_files':
                $result = $this->enqueue_job(new Jobs\Media_File_Job($file));
                break;
            case 'themes':
                $result = $this->enqueue_job(new Jobs\Theme_File_Job($file));
                break;
            case 'plugins':
                $result = $this->enqueue_job(new Jobs\Plugin_File_Job($file));
                break;
            case 'muplugins':
                $result = $this->enqueue_job(new Jobs\MUPlugin_File_Job($file));
                break;
            case 'others':
                $result = $this->enqueue_job(new Jobs\Other_File_Job($file));
                break;
            case 'core':
                $result = $this->enqueue_job(new Jobs\Core_File_Job($file));
                break;
            default:
                $result = $this->enqueue_job(new Jobs\File_Job($file));
        }

        return $result;
    }

    /**
     * Adds a table to the jobs queue.
     *
     * @param string $table
     *
     * @return int|WP_Error
     */
    public function enqueue_table($table)
    {
        return $this->enqueue_job(new Jobs\Table_Job($table));
    }

    /**
     * Adds a table backup to the jobs queue.
     *
     * @param string $table
     *
     * @return int|WP_Error
     */
    public function enqueue_backup_table($table)
    {
        return $this->enqueue_job(new Jobs\Backup_Table_Job($table));
    }

    /**
     * Adds a job to the queue.
     *
     * @param Job $job
     *
     * @return int|WP_Error
     */
    public function enqueue_job(Job $job)
    {
        return $this->queue->push($job);
    }

    function process()
    {
        return $this->worker->process();
    }

    /**
     * Ensure the queue tables exist.
     *
     * @param array $state_data
     *
     * @return array|WP_Error
     */
    public function ensure_tables_exist($state_data)
    {
        // ***+=== @TODO - revisit usage of parse_migration_form_data
        $form_data = $this->form_data->parse_and_save_migration_form_data($state_data['form_data']);

        $stages = $form_data['current_migration']['stages'];

        $allowed_migration_types = [
            'import',
            'tables',
            'theme_files',
            'plugin_files',
            'muplugin_files',
            'other_files',
            'core_files',
            'media_files',
        ];

        if (
            empty(array_intersect($stages, $allowed_migration_types))
        ) {
            return $state_data;
        }

        $result = $this->create_tables(true);

        if (is_wp_error($result)) {
            return $result;
        }

        return $state_data;
    }

    /**
     * Create the job and failures tables.
     *
     * @param bool $drop
     *
     * @return bool|WP_Error
     */
    public function create_tables($drop = false)
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $this->wpdb->hide_errors();
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->jobs_table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				job longtext NOT NULL,
				attempts tinyint(3) NOT NULL DEFAULT 0,
				reserved_at datetime DEFAULT NULL,
				available_at datetime NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id)
				) $charset_collate;";

        if ($drop) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$this->jobs_table}");
        }

        if ( ! $this->wpdb->query($sql)) {
            $msg = sprintf(
                __('Could not create queue jobs table, error: %s', 'wp-migrate-db'),
                $this->wpdb->last_error
            );

            return new WP_Error('create-jobs-table-error', $msg);
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$this->failures_table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				job longtext NOT NULL,
				error text DEFAULT NULL,
				failed_at datetime NOT NULL,
				PRIMARY KEY  (id)
				) $charset_collate;";

        if ($drop) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$this->failures_table}");
        }

        if ( ! $this->wpdb->query($sql)) {
            $msg = sprintf(
                __('Could not create queue failures table, error: %s', 'wp-migrate-db'),
                $this->wpdb->last_error
            );

            return new WP_Error('create-jobs-table-error', $msg);
        }

        return true;
    }

    /**
     * Drop the queue manager's custom tables.
     *
     * @return void
     */
    public function drop_tables()
    {
        $this->wpdb->hide_errors();

        $sql = "DROP TABLE IF EXISTS {$this->jobs_table}";
        $this->wpdb->query($sql);

        $sql = "DROP TABLE IF EXISTS {$this->failures_table}";
        $this->wpdb->query($sql);
    }

    /**
     * Wrapper for DatabaseConnection::jobs()
     *
     * @return int
     */

    public function count_jobs()
    {
        return $this->connection->jobs();
    }

    /**
     *
     * @param     $count
     * @param int $offset
     *
     * @return array|null|object
     *
     */
    public function delete_data_from_queue($count = 99999999)
    {
        $sql = "DELETE FROM {$this->jobs_table} LIMIT {$count}";

        $results = $this->wpdb->query($sql);

        return $results;
    }

    public function truncate_queue()
    {
        $sql = "TRUNCATE TABLE {$this->jobs_table}";

        $results = $this->wpdb->query($sql);

        return $results;
    }

    /**
     * Get list of jobs in queue
     *
     * @param int  $limit
     * @param int  $offset
     * @param bool $raw if true, method will return serialized instead of instantiated objects
     *
     * @return array|WP_Error
     */
    public function list_jobs($limit = 9999999, $offset = 0, $raw = false)
    {
        return $this->connection->list_jobs($limit, $offset, $raw);
    }

    /**
     * Filter the list of tables that should be excluded from any migration operations.
     *
     * @param array $tables
     *
     * @return array
     */
    public function filter_always_exclude_table($tables)
    {
        return array_merge(
            $tables,
            array($this->jobs_table, $this->failures_table)
        );
    }

    /**
     * When a migration completes or cancels, cleanup.
     *
     * @return void
     */
    public function do_cleanup()
    {
        // Currently just cleanup the custom tables.
        $this->drop_tables();
    }
}
