<?php

namespace DeliciousBrains\WPMDB\Common\Cli;

use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationManager;
use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Migration\FinalizeMigration;
use DeliciousBrains\WPMDB\Common\Migration\InitiateMigration;
use DeliciousBrains\WPMDB\Common\Migration\MigrationHelper;
use DeliciousBrains\WPMDB\Common\Migration\MigrationManager;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Profile\ProfileImporter;
use DeliciousBrains\WPMDB\Common\Properties\DynamicProperties;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use WP_CLI;
use WP_CLI\ExitException;
use WP_Error;
use WP_User;

class Cli
{

	/**
	 * Migration profile.
	 *
	 * @var array
	 */
	protected $profile;

	/**
	 * Data to post during migration.
	 *
	 * @var array
	 */
	protected $post_data = array();
	/**
	 * @var FormData
	 */
	protected $form_data;
	/**
	 * @var Util
	 */
	protected $util;
	/**
	 * @var Table
	 */
	protected $table;
	/**
	 * @var ErrorLog
	 */
	protected $error_log;
	/**
	 * @var InitiateMigration
	 */
	protected $initiate_migration;
	/**
	 * @var FinalizeMigration
	 */
	protected $finalize_migration;
	/**
	 * @var Helper
	 */
	protected $http_helper;
	/**
	 * @var MigrationManager
	 */
	protected $migration_manager;
	/**
	 * @var MigrationStateManager
	 */
	protected $migration_state_manager;
    /**
     * @var Properties
     */
    protected $properties;
	/**
	 * @var ProfileImporter
	 */
	protected $profile_importer;
	/**
	 * @var DynamicProperties
	 */
	private $dynamic_properties;

    /**
     * @var BackgroundMigrationManager
     */
    protected $background_migration_manager;

    /**
     * @var MigrationHelper
     */
    protected $migration_helper;

    public function __construct(
        FormData $form_data,
        Util $util,
        Table $table,
        ErrorLog $error_log,
        InitiateMigration $initiate_migration,
        FinalizeMigration $finalize_migration,
        Helper $http_helper,
        MigrationManager $migration_manager,
        MigrationStateManager $migration_state_manager,
        Properties $properties,
        BackgroundMigrationManager $background_migration_manager,
        MigrationHelper $migration_helper
    ) {
        $this->form_data                    = $form_data;
        $this->util                         = $util;
        $this->table                        = $table;
        $this->error_log                    = $error_log;
        $this->initiate_migration           = $initiate_migration;
        $this->finalize_migration           = $finalize_migration;
        $this->http_helper                  = $http_helper;
        $this->migration_manager            = $migration_manager;
        $this->migration_state_manager      = $migration_state_manager;
        $this->properties                   = $properties;
        $this->background_migration_manager = $background_migration_manager;
        $this->migration_helper             = $migration_helper;
        $this->dynamic_properties           = DynamicProperties::getInstance();
        $this->profile_importer             = new ProfileImporter($this->table);
    }

    public function register()
    {
        add_filter('wpmdb_cli_profile_before_migration', array($this, 'maybe_set_user'), 10);
        add_filter('wpmdb_cli_profile_before_migration', array($this, 'maybe_add_tables_stage'), 20);
        add_filter('wpmdb_cli_filter_before_cli_initiate_migration', array($this, 'filter_cli_filter_before_cli_initiate_migration'), 20, 2);
        add_filter('wpmdb_cli_tables_to_migrate', array($this, 'filter_non_database_migration_tables'), 99, 2);
    }

	/**
	 * Checks profile data before CLI migration.
	 *
	 * @param array|int|string $profile Profile array, ID or name.
	 *
	 * @return array|WP_Error
	 */
	public function pre_cli_migration_check($profile)
	{
		$profile = apply_filters('wpmdb_cli_profile_before_migration', $profile);

		if (is_wp_error($profile)) {
			return $profile;
		}

		if (is_array($profile)) {
			Persistence::cleanupStateOptions();
			$profile = $this->form_data->parse_and_save_migration_form_data(json_encode($profile));
		}

		if (!isset($profile['current_migration']['stages'])) {
			$profile['current_migration']['stages'] = array('tables');
		}

        // We always finish off with a finalize stage.
        $profile['current_migration']['stages'][] = 'finalize';

		$profile['current_migration']['migration_id'] = Util::uuidv4();

		return $profile;
	}

    /**
     * Performs CLI migration with given profile data.
     *
     * @param int|array $profile Profile key or array.
     * @param array     $assoc_args
     *
     * @return bool|WP_Error Returns true if succeed or WP_Error if failed.
     * @throws ExitException
     */
    public function cli_migration($profile, $assoc_args = array())
    {
        $this->util->set_time_limit();

        $this->profile = $this->pre_cli_migration_check($profile);

        if (is_wp_error($this->profile)) {
            return $this->profile;
        }

        // At this point, $profile has been checked and retrieved into $this->profile, so should not be used in this function any further.
        if (empty($this->profile)) {
            return $this->cli_error(
                __('Profile not found or unable to be generated from params.', 'wp-migrate-db-cli')
            );
        }
        unset($profile);

        if (empty($this->profile['current_migration']['migration_id'])) {
            return $this->cli_error(__('Could not find generated migration ID.', 'wp-migrate-db-cli'));
        }

        // Post data should not have been set at all yet.
        if ( ! empty($this->post_data)) {
            return $this->cli_error(__('Unexpected data set for current migration state.', 'wp-migrate-db-cli'));
        }

        // Assume the first stage is migrate, it'll be changed later as necessary.
        $this->post_data['stage'] = 'migrate';

        if ('savefile' === $this->profile['action']) {
            $this->post_data['intent'] = 'savefile';
            if ( ! empty($this->profile['export_dest'])) {
                $this->post_data['export_dest'] = $this->profile['export_dest'];
            } else {
                $this->post_data['export_dest'] = 'ORIGIN';
            }
        }

        if ('find_replace' === $this->profile['action']) {
            $this->post_data['intent'] = 'find_replace';
        }

        if ('import' === $this->profile['action']) {
            $this->post_data['intent'] = 'import';

            if ( ! isset($this->profile['import_file'])) {
                if (isset($assoc_args['import-file'])) {
                    $this->profile['import_file'] = $assoc_args['import-file'];
                } else {
                    return $this->cli_error(
                        __('Missing path to import file. Use --import-file=/path/to/import.sql.gz', 'wp-migrate-db')
                    );
                }
            }
        }

        if (
            isset($this->profile['current_migration']['intent']) &&
            'backup_local' === $this->profile['current_migration']['intent']
        ) {
            $this->post_data['intent'] = 'savefile';
        }

        // Ensure local site_details available.
        Persistence::saveMigrationOptions($this->profile);
        $local_site = StateFactory::create('local_site')->load_state($this->profile['current_migration']['migration_id']);
        $local_site->update_state($this->migration_helper->siteDetails());
        $this->profile = Persistence::getMigrationOptions();
        $this->post_data['site_details']['local'] = $local_site->get('site_details');

        $this->profile = apply_filters(
            'wpmdb_cli_filter_before_cli_initiate_migration',
            $this->profile,
            $this->post_data
        );

        if (is_wp_error($this->profile)) {
            return $this->profile;
        }

        if (is_wp_error($this->post_data)) {
            return $this->post_data;
        }

        $this->profile = apply_filters('wpmdb_cli_filter_before_migration', $this->profile, $this->post_data);

        // Before initiating the migration, ensure current options and state are saved.
        Persistence::saveMigrationOptions($this->profile);
        Persistence::saveStateData($this->post_data);

        do_action('wpmdb_cli_before_migration', $this->post_data, $this->profile);

        $result = $this->cli_initiate_migration();

        if (is_wp_error($result)) {
            return $result;
        }

        // Just in case filter has clobbered previously set profile value.
        if (empty($this->profile['current_migration']['migration_id'])) {
            return $this->cli_error(__('Could not find generated migration ID.', 'wp-migrate-db-cli'));
        }

        do_action('wpmdb_cli_migration_id_ready', $this->profile['current_migration']['migration_id']);

        // TODO: Trigger in-line import of file here and move post_data stage forward to 'find_replace' if appropriate.

        return $this->background_migration_manager->perform_action(
            $this->profile['current_migration']['intent'],
            'start',
            $this->profile['current_migration']['migration_id']
        );
    }

	/**
	 * Return instance of WP_Error.
	 *
	 * @param  string $message Error message.
	 *
	 * @return WP_Error.
	 */
	function cli_error($message)
	{
		return new WP_Error('wpmdb_cli_error', self::cleanup_message($message));
	}

	/**
	 * Cleanup message, replacing <br> with \n and removing HTML.
	 *
	 * @param  string $message Error message.
	 *
	 * @return string $message.
	 */
	static function cleanup_message($message)
	{
		$message = html_entity_decode($message, ENT_QUOTES);
		$message = preg_replace('#<br\s*/?>#', "\n", $message);
		$message = trim(strip_tags($message));

		return $message;
	}

    /**
     * Initiates migration and verifies result
     *
     * @return array|WP_Error
     */
    public function cli_initiate_migration()
    {
        do_action('wpmdb_cli_before_initiate_migration', $this->profile);

        $migration_args                          = $this->post_data;
        $migration_args['form_data']             = json_encode($this->profile);
        $migration_args['site_details']['local'] = $this->util->site_details();

        if (empty($migration_args['migration_id']) && ! empty($this->profile['current_migration']['migration_id'])) {
            $migration_args['migration_id'] = $this->profile['current_migration']['migration_id'];
        }

        // Clean out old temporary tables before new ones created.
        $this->table->delete_temporary_tables($this->properties->temp_prefix);

        $this->post_data = apply_filters('wpmdb_cli_initiate_migration_args', $migration_args, $this->profile);

        if (is_wp_error($this->post_data)) {
            return $this->post_data;
        }

        $this->post_data['site_details'] = json_encode($this->post_data['site_details']);

        $response = $this->initiate_migration->initiate_migration($this->post_data);

        if ( ! is_wp_error($response)) {
            $response = apply_filters('wpmdb_cli_initiate_migration_response', $response);
        }

        return $response;
    }

	/**
	 * Determine which tables to migrate
	 *
	 * @return array|WP_Error
	 */
	function get_tables_to_migrate()
	{
		$tables_to_migrate = $this->table->get_tables('prefix');

		return apply_filters('wpmdb_cli_tables_to_migrate', $tables_to_migrate, $this->profile, $this->post_data['stage']);
	}

	/**
	 * Returns array of CLI options that are unknown to plugin and addons.
	 *
	 * @param array $assoc_args
	 *
	 * @return array
	 */
	public function get_unknown_args($assoc_args = array())
	{
		$unknown_args = array();

		if (empty($assoc_args)) {
			return $unknown_args;
		}

		$known_args = array(
			'action',
			'export_dest',
			'find',
			'replace',
			'regex-find',
			'regex-replace',
			'case-sensitive-find',
			'case-sensitive-replace',
			'exclude-spam',
			'gzip-file',
			'exclude-post-revisions',
			'skip-replace-guids',
			'include-transients',
            'exclude-database'
		);

		$known_args   = apply_filters('wpmdb_cli_filter_get_extra_args', $known_args);
		return array_diff(array_keys($assoc_args), $known_args);
	}

	/**
	 * Get profile data from CLI args.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return array|WP_Error
	 */
	public function get_profile_data_from_args($args, $assoc_args)
	{
		$name          = null;
		$export_dest   = null;
		$create_backup = '0';
		$cli_profile   = true;

		$unknown_args = $this->get_unknown_args($assoc_args);

		if (!empty($unknown_args)) {
			$message = __('Parameter errors: ', 'wp-migrate-db-cli');
			foreach ($unknown_args as $unknown_arg) {
				$message .= "\n " . sprintf(__('unknown %s parameter', 'wp-migrate-db-cli'), '--' . $unknown_arg);
			}

			return $this->cli_error($message);
		}

		foreach ($assoc_args as $key => $value) {
			if (empty($value)) {
				WP_CLI::warning(__('--' . $key . ' parameter needs a value.', 'wp-migrate-db-cli'));
			}
		}

		if (empty($assoc_args['action'])) {
			return $this->cli_error(__('Missing action parameter', 'wp-migrate-db-cli'));
		}

		if ('savefile' === $assoc_args['action'] && !empty($assoc_args['export_dest'])) {
			$export_dest = $assoc_args['export_dest'];
		}

		$action = $assoc_args['action'];

		// --find=<old> and --replace=<new> and --regex-find=<regex> and --regex-replace=<string>
		$replace_old   = array();
		$replace_new   = array();
		$regex = array();
		$case_sensitive = array();

        if(!empty($assoc_args['regex-find'])) {
            $regex_search = $assoc_args['regex-find'];

            if(!Util::is_regex_pattern_valid($regex_search)){
                return $this->cli_error(__('Please make sure Regular Expression find & replace pattern is valid', 'wp-migrate-db-cli'));
            }

            if (('find_replace' === $assoc_args['action']) && empty($assoc_args['regex-replace'])) {
                return $this->cli_error(__('Missing Regex find and replace values.', 'wp-migrate-db-cli'));
            }

            $replace_old[] = $regex_search;
            $regex[count($replace_old)] = true;
        }

        if (!empty($assoc_args['regex-replace'])) {
            $regex_replace = $assoc_args['regex-replace'];
            if (('find_replace' === $assoc_args['action']) && empty($assoc_args['regex-find'])) {
                return $this->cli_error(__('Missing Regex find and replace values.', 'wp-migrate-db-cli'));
            }
            $replace_new[] = $regex_replace;
        }

        if (!empty($assoc_args['case-sensitive-find'])) {
            $case_sensitive_search = $this->extract_argument('case-sensitive-find', $assoc_args);
            if (('find_replace' === $assoc_args['action']) && empty($assoc_args['case-sensitive-replace'])) {
                return $this->cli_error(__('Missing case sensitive find and replace values.', 'wp-migrate-db-cli'));
            }

            $replace_old_count = count($replace_old);
            $i = $replace_old_count === 0 ? 1 : $replace_old_count+1;
            $replace_old = array_merge($replace_old, $case_sensitive_search);

            foreach ($case_sensitive_search as $value) {
                $case_sensitive[$i] = true;
                $i++;
            }
        }

        if (!empty($assoc_args['case-sensitive-replace'])) {
            $case_sensitive_replace = $this->extract_argument('case-sensitive-replace', $assoc_args);
            if (('find_replace' === $assoc_args['action']) && empty($assoc_args['case-sensitive-find'])) {
                return $this->cli_error(__('Missing case sensitive find and replace values.', 'wp-migrate-db-cli'));
            }
            $replace_new = array_merge($replace_new, $case_sensitive_replace);
        }

        if (!empty($assoc_args['find'])) {
            $replace_old = array_merge($replace_old, str_getcsv($assoc_args['find']));
        } else if (('find_replace' === $assoc_args['action']) && empty($regex_replace) && empty($regex_search) && empty($case_sensitive_search) && empty($case_sensitive_replace)) {
            if (empty($assoc_args['replace'])) {

                return $this->cli_error(__('Missing find and replace values.', 'wp-migrate-db-cli'));
            }

            return $this->cli_error(__('Find value is required.', 'wp-migrate-db-cli'));
        }

        if (!empty($assoc_args['replace'])) {
            $replace_new = array_merge($replace_new, str_getcsv($assoc_args['replace']));
        } else {
            if ('find_replace' === $assoc_args['action'] && empty($regex_replace) && empty($regex_search) && empty($case_sensitive_search) && empty($case_sensitive_replace)) {
                return $this->cli_error(__('Replace value is required.', 'wp-migrate-db-cli'));
            }
        }

		if (count($replace_old) !== count($replace_new)) {
			return $this->cli_error(sprintf(__('%1$s and %2$s must contain the same number of values', 'wp-migrate-db-cli'), '--find', '--replace'));
		}

		// --exclude-spam
		$exclude_spam = (int)isset($assoc_args['exclude-spam']);

		// --gzip-file
		$gzip_file = (int)isset($assoc_args['gzip-file']);

		$select_post_types  = $this->table->get_post_types();
		$exclude_post_types = '0';

		// --exclude-post-revisions
		if (!empty($assoc_args['exclude-post-revisions'])) {
		    $select_post_types  = ['revision']; // This gets flipped around in ProfileImporter::profileFormat().
		    $exclude_post_types = '1';
		}

		// --skip-replace-guids
		$replace_guids = 1;
		if (isset($assoc_args['skip-replace-guids'])) {
			$replace_guids = 0;
		}

		$select_tables        = array();
		$table_migrate_option = 'migrate_only_with_prefix';

		// --include-transients.
		$exclude_transients = intval(!isset($assoc_args['include-transients']));

		//cleanup filename for exports
		if (!empty($export_dest)) {
			if ($gzip_file) {
				if ('gz' !== pathinfo($export_dest, PATHINFO_EXTENSION)) {
					if ('sql' === pathinfo($export_dest, PATHINFO_EXTENSION)) {
						$export_dest .= '.gz';
					} else {
						$export_dest .= '.sql.gz';
					}
				}
			} elseif ('sql' !== pathinfo($export_dest, PATHINFO_EXTENSION)) {
				$export_dest = preg_replace('/(\.sql)?(\.gz)?$/i', '', $export_dest) . '.sql';
			}

			// ensure export destination is writable
			if (!@touch($export_dest)) {
				return $this->cli_error(sprintf(__('Cannot write to file "%1$s". Please ensure that the specified directory exists and is writable.', 'wp-migrate-db-cli'), $export_dest));
			}

            // We can now grab the file's full absolute path
            // so that background migrations know where it really is
            // if the given path is relative.
            $export_dest = realpath($export_dest);
        }

        $databaseEnabled = true;
        if ( ! empty($assoc_args['exclude-database'])) {
            $databaseEnabled = false;
        }

        // --preview
        $preview = !empty($assoc_args['preview']);

        $profile = compact(
            'action',
            'replace_old',
            'table_migrate_option',
            'replace_new',
            'select_tables',
            'exclude_post_types',
            'select_post_types',
            'replace_guids',
            'exclude_spam',
            'gzip_file',
            'exclude_transients',
            'export_dest',
            'create_backup',
            'name',
            'cli_profile',
            'regex',
            'case_sensitive',
            'databaseEnabled',
            'preview'
        );

        $home = preg_replace('/^https?:/', '', home_url());
        $path = esc_html(addslashes(Util::get_absolute_root_file_path()));

		$old_profile = apply_filters('wpmdb_cli_filter_get_profile_data_from_args', $profile, $args, $assoc_args);

		if (is_wp_error($old_profile)) {
			return $old_profile;
		}

		$new_profile = $this->profile_importer->profileFormat($old_profile, $home, $path);
		return array_merge($old_profile, $new_profile);
	}

    /**
     * Extract comma delimited arguments for an option as an array.
     *
     * @param string $argument
     * @param array  $assoc_args
     *
     * @return array|null
     */
	private function extract_argument($argument, $assoc_args) {
	    if(!empty($assoc_args[$argument])) {
	        return str_getcsv($assoc_args[$argument]);
        }
	    return null;
    }

    /**
     * Checks if a database migration is turned off for the current migration profile.
     *
     * @param array $profile
     *
     * @return bool
     */
    public function is_non_database_migration($profile)
    {
        return $profile['current_migration']['databaseEnabled'] === false && in_array($profile['action'], ['push', 'pull']);
    }


    /**
     * If the current migration is a non database migration, it filters the provided tables and returns an empty array.
     * hooks on: wpmdb_cli_tables_to_migrate.
     *
     * @param string[] $tables
     * @param array $profile
     *
     * @return array
     */
    public function filter_non_database_migration_tables($tables, $profile)
    {
        if ($this->is_non_database_migration($profile)) {
            return [];
        }

        return $tables;
    }

    /**
     * Adds the tables stage to the current migration.
     *
     * @param array $profile
     *
     * @return array
     */
    public function maybe_add_tables_stage($profile)
    {
        if (is_wp_error($profile) || ! is_array($profile)) {
            return $profile;
        }

        if ( ! isset($profile['current_migration']['stages'])) {
            $profile['current_migration']['stages'] = [];
        }

        if (
            ! $this->is_non_database_migration($profile) &&
            ! array_key_exists('tables', array_flip($profile['current_migration']['stages']))
        ) {
            $profile['current_migration']['stages'][] = 'tables';
        }

        return $profile;
    }

    /**
     * Ensure current migration's tables selected is up-to-date.
     *
     * @param array $profile
     * @param array $post_data
     *
     * @return array|WP_Error
     *
     * @handles wpmdb_cli_filter_before_cli_initiate_migration
     */
    public function filter_cli_filter_before_cli_initiate_migration($profile, $post_data)
    {
        if (is_wp_error($profile)) {
            return $profile;
        }

        $source_tables_ok = $this->check_source_select_tables($profile);

        if (is_wp_error($source_tables_ok)) {
            return $source_tables_ok;
        }

        $tables_to_migrate = $this->get_tables_to_migrate();

        if (is_wp_error($tables_to_migrate)) {
            return $tables_to_migrate;
        }

        $profile['current_migration']['tables_selected'] = $tables_to_migrate;
        $profile['select_tables']                        = $profile['current_migration']['tables_selected'];

        return $profile;
    }

    /**
     * Check for tables specified in migration profile that do not exist in the source database.
     *
     * @param array $profile
     *
     * @return true|WP_Error
     */
    protected function check_source_select_tables($profile)
    {
        if (is_wp_error($profile)) {
            return $profile;
        }

        if ( ! empty($profile['select_tables']) && 'import' !== $profile['action']) {
            $source_tables = apply_filters(
                'wpmdb_cli_filter_source_tables',
                $this->table->get_tables(),
                $profile
            );

            // TODO: What if there are no source tables?
            if ( ! empty($source_tables)) {
                // Return error if selected tables do not exist in source database
                $nonexistent_tables = array();
                foreach ($profile['select_tables'] as $table) {
                    if ( ! in_array($table, $source_tables)) {
                        $nonexistent_tables[] = $table;
                    }
                }

                if ( ! empty($nonexistent_tables)) {
                    $local_or_remote = ('pull' === $profile['action']) ? 'remote' : 'local';

                    return $this->cli_error(sprintf(
                        __('The following table(s) do not exist in the %1$s database: %2$s', 'wp-migrate-db-cli'),
                        $local_or_remote,
                        implode(', ', $nonexistent_tables)
                    ));
                }
            }
        }

        return true;
    }

    /**
     * Maybe set the current user if not specified in CLI args.
     *
     * This is needed to ensure the last_migration record is created somewhere
     * and can be retrieved by the UI.
     *
     * Does not touch the given $profile.
     *
     * @param array $profile
     *
     * @return array
     *
     * @handles wpmdb_cli_profile_before_migration
     */
    public function maybe_set_user($profile)
    {
        if (empty(get_current_user_id())) {
            /**
             * @var WP_User[] $users
             */
            $users = get_users([
                'capability' => 'export',
                'orderby'    => 'ID',
                'number'     => 1,
            ]);

            if ( ! empty($users[0]->ID)) {
                wp_set_current_user($users[0]->ID);
            }
        }

        return $profile;
    }
}
