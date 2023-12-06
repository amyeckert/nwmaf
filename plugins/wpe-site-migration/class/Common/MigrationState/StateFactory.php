<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

use DeliciousBrains\WPMDB\Common\Exceptions\InvalidStateIdentifier;
use DeliciousBrains\WPMDB\Common\MigrationState\Addons\MediaFilesState;
use DeliciousBrains\WPMDB\Common\MigrationState\Addons\ThemePluginFilesState;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\CurrentMigrationState;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\LocalSiteState;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\RemoteSiteState;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\SearchReplaceState;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\SiteMigrationState;
use DeliciousBrains\WPMDB\WPMDBDI;

class StateFactory
{
    /**
     * Create a new state object.
     *
     * @param string $state_identifier State branch identifier.
     *
     * @throws \UnexpectedValueException
     */
    public static function create($state_identifier)
    {
        $repository = WPMDBDI::getInstance()->get(ApplicationStateFormDataRepository::class);

        switch ($state_identifier) {
            case 'current_migration':
                return new CurrentMigrationState($repository);
            case 'theme_plugin_files':
                return new ThemePluginFilesState($repository);
            case 'media_files':
                return new MediaFilesState($repository);
            case 'local_site':
                return new LocalSiteState($repository);
            case 'remote_site':
                return new RemoteSiteState($repository);
            case 'search_replace':
                return new SearchReplaceState($repository);
            case 'site_migration':
                return new SiteMigrationState($repository);
            default:
                throw new InvalidStateIdentifier();
        }
    }
}
