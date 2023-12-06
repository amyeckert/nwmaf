<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

use DeliciousBrains\WPMDB\Common\Exceptions\UnknownStateProperty;

/**
 * Class ApplicationStateAbstract
 *
 * @package DeliciousBrains\WPMDB\Common\MigrationState
 */
abstract class ApplicationStateAbstract implements ApplicationStateInterface
{

    /**
     * The repository to use for persisting the state
     *
     * @var ApplicationStateRepositoryInterface
     */
    protected $repository;

    /**
     * The state branch identifier
     *
     * @var string
     */
    protected $state_identifier = null;

    /**
     * The state array
     *
     * @var array
     */
    protected $state = [];

    /**
     * @param ApplicationStateRepositoryInterface $application_state_repository
     */
    public function __construct(
        ApplicationStateRepositoryInterface $application_state_repository
    ) {
        $this->repository = $application_state_repository;

        $this->state = $this->get_initial_state();
    }

    /**
     * Get the initial state
     *
     * @return array
     */
    public function get_initial_state()
    {
        return [];
    }

    /**
     * Load the state from the repository
     *
     * @param string $migration_id
     *
     * @return $this
     */
    public function load_state($migration_id)
    {
        //State loading logic goes here.
        $state_data = $this->repository->get($this->state_identifier);

        if ($state_data !== null) {
            $this->state = $state_data;
        }

        return $this;
    }

    /**
     * Bulk update the state
     *
     * @param array $properties Properties to update, key => value
     *
     * @return void
     */
    public function update_state($properties = [])
    {
        $this->state = array_merge($this->state, $properties);
        $this->repository->update($this->state, $this->state_identifier);
    }

    /**
     * Set a property in the state
     *
     * @param string $property Property to set
     * @param mixed  $value    Value to set
     *
     * @return void
     */
    public function set($property, $value, $safe = true)
    {
        if ( ! isset($this->state[$property]) && $safe === true) {
            throw new UnknownStateProperty(sprintf('Unknown state property %s in %s state branch.',
                $property,
                $this->state_identifier));
        }

        $this->state[$property] = $value;
    }

    /**
     * Get a property from the state
     *
     * @param string $property Property to get
     *
     * @return mixed
     */
    public function get($property)
    {
        if ( ! isset($this->state[$property])) {
            throw new UnknownStateProperty(sprintf('Unknown state property %s in %s state branch.',
                $property,
                $this->state_identifier));
        }

        return $this->state[$property];
    }

    /**
     * Get the state array
     *
     * @return array
     */
    public function get_state()
    {
        return $this->state;
    }
}
