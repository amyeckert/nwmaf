<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;

class ApplicationStateFormDataRepository implements
    ApplicationStateRepositoryInterface
{

    /**
     * @var FormData
     */
    private $form_data;

    /**
     * @param FormData $form_data
     */
    public function __construct(FormData $form_data)
    {
        $this->form_data = $form_data;
    }

    /**
     * Retrieve the state from the database.
     *
     * @param string $state_branch
     * @param string $migration_id
     *
     * @return mixed|null
     */
    public function get($state_branch = null, $migration_id = null)
    {
        $form_data = $this->form_data->getFormData();

        if (isset($form_data[$state_branch])) {
            return $form_data[$state_branch];
        }

        return null;
    }

    /**
     * Commit the state to the database.
     *
     * @param array  $data
     * @param string $state_branch
     * @param string $migration_id
     *
     * @return array
     */
    public function update($data, $state_branch = null, $migration_id = null)
    {
        $form_data = $this->form_data->getFormData();

        if ($state_branch !== null) {
            $form_data[$state_branch] = $data;
        } else {
            $form_data = $data;
        }

        return Persistence::saveMigrationOptions($form_data);
    }
}
