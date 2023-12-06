<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport;

class FileTransportResponse
{
    /**
     * @var bool
     */
    public $success;

    /**
     * @var string
     */
    public $body;

    /**
     * @var int
     */
    public $code;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var array
     */
    private $decoded_body;

    /**
     * @param string $body
     * @param int    $code
     */
    public function __construct($body, $code)
    {
        $this->code = $code;
        $this->body = $body;

        $this->decoded_body = json_decode($body, true);

        if ( ! empty($this->decoded_body['success'])) {
            $this->success = (bool)$this->decoded_body['success'];
        }

        if ( ! empty($this->decoded_body['data'])) {
            $this->data = $this->decoded_body['data'];
        }
    }

    /**
     * Returns true if the decoded response has a wpmdb_error property.
     *
     * @return bool
     */
    public function has_error()
    {
        return ! empty($this->decoded_body['wpmdb_error']);
    }

    /**
     * Returns the msg property of the decoded response.
     *
     * @return mixed|null
     */
    public function error_message()
    {
        if ($this->has_error()) {
            if ( ! empty($this->decoded_body['msg'])) {
                return $this->decoded_body['msg'];
            }

            return sprintf('File transport failed with code %s', $this->code);
        }

        return null;
    }
}
