<?php

namespace DeliciousBrains\WPMDB\Common\Queue\Jobs;

use DeliciousBrains\WPMDB\Common\Queue\Job;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\WPMDBDI;

class Backup_Table_Job extends Job
{
    /**
     * @var string
     */
    public $table;

    /**
     * @var int
     */
    public $bytes;

    /**
     * @var int
     */
    public $rows;

    public function __construct($table)
    {
        $this->table = $table;
        $this->bytes = WPMDBDI::getInstance()->get(Table::class)->get_table_size_in_bytes($table);
        $this->rows  = WPMDBDI::getInstance()->get(Table::class)->get_table_row_count($table);
    }

    /**
     * Handle job logic.
     */
    public function handle()
    {
        return true;
    }
}
