<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

class BackgroundFindReplaceProcess extends BackgroundMigrationProcess
{
    /**
     * @inheritdoc
     */
    protected $action = 'find_replace';

    /**
     * @inheritdoc
     */
    protected function stage_processed($progress, $stage, $item)
    {
        $complete = parent::stage_processed($progress, $stage, $item);

        // Pause at the end of a dry-run.
        if ($complete && 'tables' === $stage['stage'] && $this->preview()) {
            $this->pause();
        }

        return $complete;
    }
}
