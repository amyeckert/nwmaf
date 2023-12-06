<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\CurrentMigrationState;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Util\Util;

/**
 * Background Migration
 *
 * Base class for registered migrations.
 */
abstract class BackgroundMigration
{
    /**
     * Type (intent) of the subclass.
     *
     * Must be overridden by each subclass.
     *
     * @var string
     */
    protected static $type = 'background-migration';

    /**
     * Holds the background migration process that performs the tasks.
     *
     * @var BackgroundMigrationProcess
     */
    protected $background_process;

    /**
     * The error log, accessible to background process.
     *
     * @var ErrorLog
     */
    public $error_log;

    /**
     * Current migration state.
     *
     * @var CurrentMigrationState
     */
    private $current_migration_state;

    /**
     * Instantiate a Background Migration.
     *
     * @param ErrorLog $error_log
     */
    public function __construct(
        ErrorLog $error_log
    ) {
        $this->error_log          = $error_log;
        $this->background_process = $this->get_background_process_class();

        add_filter(
            'wpmdb_register_background_migrations',
            [$this, 'filter_register_background_migrations']
        );
    }

    /**
     * Get the type (intent) of the migration.
     *
     * @return string
     */
    public static function get_type()
    {
        return static::$type;
    }

    /**
     * Register this background migration.
     *
     * @param BackgroundMigration[] $migrations
     *
     * @return BackgroundMigration[]
     */
    public function filter_register_background_migrations($migrations)
    {
        $migrations[static::get_type()] = $this;

        return $migrations;
    }

    /**
     * Is the background migration active?
     *
     * @return bool
     */
    public function is_active()
    {
        return $this->background_process->is_active();
    }

    /**
     * Start a background migration.
     *
     * @return void
     *
     * Note: Dynamically called by `\DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationManager::perform_action()`.
     */
    public function handle_start()
    {
        if ($this->is_active()) {
            return;
        }

        $task = $this->create_task();

        $this->background_process->push_to_queue($task)->save()->dispatch();

        do_action('wpmdb_migration_started', $this->get_current_migration_state()->get('migration_id'));
    }

    /**
     * Cancel a background migration.
     *
     * @return void
     *
     * Note: Dynamically called by `\DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationManager::perform_action()`.
     */
    public function handle_cancel()
    {
        if ( ! $this->is_active() || $this->background_process->is_cancelled()) {
            return;
        }

        $this->background_process->cancel();

        do_action('wpmdb_track_migration_cancel');
    }

    /**
     * Toggle pause or resume a background migration.
     *
     * @return void
     *
     * Note: Dynamically called by `\DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationManager::perform_action()`.
     */
    public function handle_pause_resume()
    {
        if ( ! $this->is_active() || $this->background_process->is_cancelled()) {
            return;
        }

        if ($this->background_process->is_paused()) {
            $this->background_process->resume();
        } else {
            $this->background_process->pause();
        }
    }

    /**
     * Create initial migration state and set up batch item that references it.
     *
     * @return array
     */
    protected function create_task()
    {
        // Initial progress and target total bytes elements for grand total.
        $task = array(
            'started_by'  => get_current_user_id(),
            'started_at'  => time(),
            'initialized' => false,
            'total'       => array(
                'processed_bytes' => 0,
                'target_bytes'    => 0,
            ),
        );

        // Associate current migration state with task item via migration_id.
        $migration_id = $this->current_migration_state->get('migration_id');

        if (empty($migration_id)) {
            // Should never happen, but task will bail when it hits an empty array.
            return array();
        }

        $task['migration_id'] = $migration_id;

        // Add initial progress and target total bytes elements for each stage.
        foreach ($this->current_migration_state->get('stages') as $stage) {
            $task['stages'][] = array(
                'stage'       => $stage,
                'initialized' => false,
                'processed'   => false,
                'total'       => array(
                    'processed_bytes' => 0,
                    'target_bytes'    => 0,
                ),
            );
        }

        return $task;
    }

    /**
     * Get information about the migration.
     *
     * @return array
     */
    public function get_info()
    {
        // The current task should be the first queued item in the first (and only) batch item.
        $current_task = $this->background_process->get_batches(1);

        if (empty($current_task[0]->data[0])) {
            $current_task = false;
        } else {
            $current_task = $current_task[0]->data[0];
        }

        return [
            // TODO: Could add translatable text entries for name, status text etc coming from abstract/overridden functions.
            'type'          => static::get_type(),
            'is_active'     => $this->background_process->is_active(),
            'is_queued'     => $this->background_process->is_queued(),
            'is_processing' => $this->background_process->is_processing(),
            'is_paused'     => $this->background_process->is_paused(),
            'is_cancelled'  => $this->background_process->is_cancelled(),
            'current_task'  => $current_task,
        ];
    }

    /**
     * Set current migration state from Migration ID.
     *
     * @param string $migration_id
     *
     * @return bool
     */
    public function set_current_migration_state($migration_id)
    {
        $state                         = StateFactory::create('current_migration');
        $this->current_migration_state = $state->load_state($migration_id);

        // Is loaded state different from skeleton initial migration state?
        return ! empty(Util::array_diff_assoc_recursive(
            $this->current_migration_state->get_state(),
            $state->get_initial_state()
        ));
    }

    /**
     * Get the migration's current state.
     *
     * @return CurrentMigrationState
     */
    public function get_current_migration_state()
    {
        return $this->current_migration_state;
    }

    /**
     * Get background process class.
     *
     * @return BackgroundMigrationProcess|null
     */
    abstract protected function get_background_process_class();
}
