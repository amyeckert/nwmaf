<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\WPMDBRestAPIServer;
use WP_REST_Request;

class ApplicationStateController
{

    /**
     * @var WPMDBRestAPIServer
     */
    private $rest_API_server;

    /**
     * @var Http
     */
    private $http;

    /**
     * @param WPMDBRestAPIServer $rest_API_server
     * @param Http               $http
     */
    public function __construct(WPMDBRestAPIServer $rest_API_server, HTTP $http)
    {
        $this->rest_API_server = $rest_API_server;
        $this->http            = $http;
    }

    /**
     * Register the service provider hook
     *
     * @return void
     */
    public function register()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register the REST routes for the state controller.
     *
     * @return void
     */
    public function register_routes()
    {
        $this->rest_API_server->registerRestRoute('/state/(?P<migration_id>[\S]+)/(?P<state_identifier>[\S]+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_state']
        ]);

        $this->rest_API_server->registerRestRoute('/state/(?P<migration_id>[\S]+)', [
            'methods'  => 'PUT',
            'callback' => [$this, 'update_state'],
            'args'     => [
                'state' => [
                    'description' => esc_html__('Array of application state branches.',
                        'wp-migrate-db'),
                    'type'        => 'object',
                    'required'    => true,
                ],
            ],
        ]);
    }

    /**
     * Get the state by state identifier and migration id
     *
     * @return void
     */
    public function get_state(WP_REST_Request $request)
    {
        $params = $request->get_url_params();

        $state_identifier = sanitize_text_field($params['state_identifier']);
        $state            = StateFactory::create($state_identifier);

        if (isset($params['migration_id'])) {
            $migration_id = sanitize_text_field($params['migration_id']);
            $this->http->end_ajax($state->load_state($migration_id)->get_state());
        }

        $this->http->end_ajax($state->get_initial_state());
    }

    /**
     * Update the state by state identifier and migration id
     *
     * @param WP_REST_Request $request
     *
     * @return void
     */
    public function update_state(WP_REST_Request $request)
    {
        $data         = $request->get_json_params();
        $url_params   = $request->get_url_params();
        $migration_id = sanitize_text_field($url_params['migration_id']);
        $return       = [];

        if (is_array($data['state'])) {
            foreach ($data['state'] as $state_identifier => $state_data) {
                $state = StateFactory::create($state_identifier);
                $state->load_state($migration_id)->update_state($state_data);
                $return[$state_identifier] = $state->get_state();
            }
        }

        $this->http->end_ajax($return);
    }
}
