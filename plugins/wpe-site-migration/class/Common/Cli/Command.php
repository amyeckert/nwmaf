<?php

namespace DeliciousBrains\WPMDB\Common\Cli;

use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use Exception;
use WP_CLI;
use WP_CLI\ExitException;
use WP_Error;

/**
 * Migrate your database. Export full sites including media, themes, and plugins. Find and replace content with support for serialized data.
 */
class Command
{
    /**
     * Register our commands.
     *
     * @throws Exception
     */
    public static function register()
    {
        WP_CLI::add_command('migratedb', self::class);
        WP_CLI::add_command('migrate', self::class);
    }

    /**
     * Export local DB to file.
     *
     * ## OPTIONS
     *
     * <output-file>
     * : A file path to export to. Filename will be modified to end in .sql or
     * .sql.gz if necessary.
     *
     * [--find=<strings>]
     * : A comma separated list of strings to find when performing a string find
     * and replace across the database.
     *
     *     Table names should be quoted as needed, i.e. when using a comma in the
     *     find/replace string.
     *
     *     The --replace=<strings> argument should be used in conjunction to specify
     *     the replace values for the strings found using this argument. The number
     *     of strings specified in this argument should match the number passed into
     *     --replace=<strings> argument.
     *
     * [--replace=<strings>]
     * : A comma separated list of replace value strings to implement when
     * performing a string find & replace across the database.
     *
     *     Should be used in conjunction with the --find=<strings> argument, see it's
     *     documentation for further explanation of the find & replace functionality.
     *
     * [--case-sensitive-find]
     * : A comma separated list of strings to find when performing a string find
     * and replace across the database.
     *
     * [--case-sensitive-replace]
     * : A comma separated list of replace value strings to implement when
     * performing a string find & replace across the database.
     *
     * [--exclude-post-revisions]
     * : Exclude post revisions from export.
     *
     * [--skip-replace-guids]
     * : Do not perform a find & replace on the guid column in the wp_posts table.
     *
     * [--exclude-spam]
     * : Exclude spam comments.
     *
     * [--gzip-file]
     * : GZip compress export file.
     *
     * [--include-transients]
     * : Include transients (temporary cached data).
     *
     * ## EXAMPLES
     *
     *     wp migratedb export ./migratedb.sql \
     *        --find=http://dev.bradt.ca,/Users/bradt/home/bradt.ca
     *        --replace=http://bradt.ca,/home/bradt.ca
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @throws ExitException
     */
    public function export($args, $assoc_args)
    {
        $assoc_args['action']      = 'savefile';
        $assoc_args['export_dest'] = Util::sanitize_file_path($args[0]);

        if (empty($assoc_args['export_dest'])) {
            WP_CLI::error(Cli::cleanup_message(__('You must provide a destination filename.', 'wp-migrate-db-cli')));
        }

        $profile = $this->_get_profile_data_from_args($args, $assoc_args);

        if (is_wp_error($profile)) {
            WP_CLI::error($profile);
        }

        $this->_perform_cli_migration($profile);
    }

    /**
     * Run a find/replace on the database.
     *
     * ## OPTIONS
     *
     * [--find=<strings>]
     * : A comma separated list of strings to find when performing a string find
     * and replace across the database.
     *
     *     Table names should be quoted as needed, i.e. when using a comma in the
     *     find/replace string.
     *
     *     The --replace=<strings> argument should be used in conjunction to specify
     *     the replace values for the strings found using this argument. The number
     *     of strings specified in this argument should match the number passed into
     *     --replace=<strings> argument.
     *
     * [--replace=<strings>]
     * : A comma separated list of replace value strings to implement when
     * performing a string find & replace across the database.
     *
     *     Should be used in conjunction with the --find=<strings> argument, see it's
     *     documentation for further explanation of the find & replace functionality.
     *
     * [--case-sensitive-find]
     * : A comma separated list of strings to find when performing a string find
     * and replace across the database.
     *
     * [--case-sensitive-replace]
     * : A comma separated list of replace value strings to implement when
     * performing a string find & replace across the database.
     *
     * [--exclude-post-revisions]
     * : Exclude post revisions from the find & replace.
     *
     * [--skip-replace-guids]
     * : Do not perform a find & replace on the guid column in the wp_posts table.
     *
     * [--exclude-spam]
     * : Exclude spam comments.
     *
     * [--include-transients]
     * : Include transients (temporary cached data).
     *
     * ## EXAMPLES
     *
     *     wp migratedb find-replace
     *        --find=http://dev.bradt.ca,/Users/bradt/home/bradt.ca
     *        --replace=http://bradt.ca,/home/bradt.ca
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @subcommand find-replace
     * @throws ExitException
     */
    public function find_replace($args, $assoc_args)
    {
        $assoc_args['action'] = 'find_replace';

        $profile = $this->_get_profile_data_from_args($args, $assoc_args);

        if (is_wp_error($profile)) {
            WP_CLI::error($profile);
        }

        $this->_perform_cli_migration($profile);
    }

    /**
     * Get profile data from CLI args.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return array|WP_Error
     * @throws ExitException
     */
    protected function _get_profile_data_from_args($args, $assoc_args)
    {
        $wpmdb_cli = $this->_get_cli_instance();

        return $wpmdb_cli->get_profile_data_from_args($args, $assoc_args);
    }

    /**
     * Perform CLI migration.
     *
     * @param mixed $profile Profile key or array
     *
     * @return void
     * @throws ExitException
     */
    protected function _perform_cli_migration($profile)
    {
        $wpmdb_cli = $this->_get_cli_instance();

        // TODO: Implement `[--background]` to enable immediate exit with background migration continuing.
        // TODO: Implement `status [--follow]` to see current migration status and optionally continue to follow it.
        $result = $wpmdb_cli->cli_migration($profile);

        if (is_wp_error($result)) {
            WP_CLI::error(Cli::cleanup_message($result->get_error_message()));
        }
    }

    /**
     * Get an appropriate instance of the CLI class.
     *
     * This function has a side effect of instantiating the plugin global if not already set up.
     *
     * @return Cli|false|mixed|null
     * @throws ExitException
     */
    protected function _get_cli_instance()
    {
        $wpmdb_cli = null;

        if (function_exists('wpmdb_pro_cli')) {
            if (function_exists('wp_migrate_db_pro_cli_addon')) {
                $wpmdb_cli = wp_migrate_db_pro_cli_addon();
            } else {
                $wpmdb_cli = wpmdb_pro_cli();
            }
        } elseif (function_exists('wpmdb_cli')) {
            $wpmdb_cli = wpmdb_cli();
        }

        // If no valid instance retrieved, bail.
        if (empty($wpmdb_cli)) {
            WP_CLI::error(
                __('WP Migrate CLI class not available.', 'wp-migrate-db-cli')
            );
        }

        return $wpmdb_cli;
    }
}
