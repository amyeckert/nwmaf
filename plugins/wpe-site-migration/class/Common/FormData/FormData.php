<?php

namespace DeliciousBrains\WPMDB\Common\FormData;

use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\WPMDBDI;

class FormData
{

    /**
     * @var array
     */
    private $accepted_fields;

    public function __construct()
    {
        $this->accepted_fields = array(
            'action',
            'save_computer',
            'gzip_file',
            'connection_info',
            'replace_old',
            'replace_new',
            'table_migrate_option',
            'select_tables',
            'replace_guids',
            'exclude_spam',
            'save_migration_profile',
            'save_migration_profile_option',
            'create_new_profile',
            'create_backup',
            'remove_backup',
            'keep_active_plugins',
            'select_post_types',
            'backup_option',
            'select_backup',
            'exclude_transients',
            'exclude_post_types',
            'exclude_post_revisions',
            'compatibility_older_mysql',
            'export_dest',
            'import_find_replace',
            'current_migration',
            'search_replace',
            'regex',
            'case_sensitive',
        );
    }

    public function form_data_compat($data)
    {
        $current_migration = $data['current_migration'];
        $advanced_options  = $current_migration['advanced_options_selected'];

        $return = [
            'action'                    => $current_migration['intent'],
            'select_tables'             => isset($current_migration['tables_selected']) ? $current_migration['tables_selected'] : [],
            'table_migrate_option'      => isset($current_migration['tables_option']) ? $current_migration['tables_option'] : '',
            'create_backup'             => isset($current_migration['backup_option']) && $current_migration['backup_option'] !== 'none' ? 1 : 0,
            'backup_option'             => isset($current_migration['backup_option']) ? $current_migration['backup_option'] : '',
            'select_backup'             => isset($current_migration['backup_tables_selected']) ? $current_migration['backup_tables_selected'] : [],
            'select_post_types'         => isset($current_migration['post_types_selected']) ? $current_migration['post_types_selected'] : [],
            'exclude_post_revisions'    => in_array('exclude_post_revisions', $advanced_options) ? '1' : '0',
            'replace_guids'             => in_array('replace_guids', $advanced_options) ? '1' : '0',
            'compatibility_older_mysql' => in_array('compatibility_older_mysql', $advanced_options) ? '1' : '0',
            'exclude_transients'        => in_array('exclude_transients', $advanced_options) ? '1' : '0',
            'exclude_spam'              => in_array('exclude_spam', $advanced_options) ? '1' : '0',
            'keep_active_plugins'       => in_array('keep_active_plugins', $advanced_options) ? '1' : '0',
            'gzip_file'                 => in_array('gzip_file', $advanced_options) ? '1' : '0',
            'exclude_post_types'        => '0',
        ];

        if (in_array($current_migration['intent'], array('push', 'pull'))) {
            $return['connection_info'] = isset($data['connection_info'], $data['connection_info']['connection_state']) ? $data['connection_info']['connection_state']['value'] : '';
        }

        if ($return['table_migrate_option'] === 'selected') {
            $return['table_migrate_option'] = 'migrate_select';
        }

        if ($current_migration['post_types_option'] !== 'all') {
            $return['exclude_post_types'] = 1;
        }

        if ($return['exclude_post_revisions'] === '1' && $current_migration['post_types_option'] === 'all') {
            $table                        = WPMDBDI::getInstance()->get(Table::class);
            $return['select_post_types']  = array_diff($table->get_post_types(), ['revision']);
            $return['exclude_post_types'] = 1;
        }

        //make sure revisions are included when user has selected post types to migrate but did not exclude revisions
        if (
            $return['exclude_post_revisions'] === '0' &&
            $return['exclude_post_types'] === 1 &&
            ! in_array('revision', $return['select_post_types'], true)
        ) {
            $return['select_post_types'][] = 'revision';
        }

        return $return;
    }

    /**
     * Returns validated and sanitized form data.
     *
     * @param array|string $data
     *
     * @return array|string
     */

    // @TODO - refactor usage
    public function parse_and_save_migration_form_data($data)
    {
        $form_data = json_decode($data, true);

        $this->accepted_fields = apply_filters('wpmdb_accepted_profile_fields', $this->accepted_fields);

        $form_data = array_intersect_key($form_data, array_flip($this->accepted_fields));

        $compat_form_data = $this->form_data_compat($form_data);

        if ( ! empty($compat_form_data) && is_array($compat_form_data)) {
            $form_data = array_merge($form_data, $compat_form_data);
        }

        $existing_form_data = Persistence::getMigrationOptions();

        if ( ! empty($existing_form_data)) {
            $form_data = array_merge($existing_form_data, $form_data);
        }

        Persistence::saveMigrationOptions($form_data);

        return $form_data;
    }

    /**
     * Get current form data.
     *
     * @return mixed|null
     */
    public function getFormData()
    {
        $saved_form_data = Persistence::getMigrationOptions();

        if (empty($saved_form_data)) {
            return null;
        }

        return $saved_form_data;
    }

    public function getCurrentMigrationData()
    {
        $form_data = $this->getFormData();

        if (isset($form_data['current_migration'])) {
            return $form_data['current_migration'];
        }

        return false;
    }

    /**
     * Get stages for current migration.
     *
     * @return false|array
     */
    public function getMigrationStages()
    {
        $current_migration = $this->getCurrentMigrationData();

        if ( ! $current_migration) {
            return false;
        }

        if (isset($current_migration['stages'])) {
            return $current_migration['stages'];
        }

        return false;
    }
}
