<?php

namespace DeliciousBrains\WPMDB\Common\Queue\Jobs;

use DeliciousBrains\WPMDB\Common\Queue\Job;

/**
 * Class WPMDB_Job
 *
 * @package WPMDB\Queue\Jobs
 */
class File_Job extends Job
{
    /**
     * @var array
     */
    public $file;

    /**
     * Create a file job.
     *
     * @param array $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Handle job logic.
     */
    public function handle()
    {
        return true;
    }
}
