<?php

namespace DeliciousBrains\WPMDB\SiteMigration;


use DeliciousBrains\WPMDB\Common\Alerts\AlertInterface;
use DeliciousBrains\WPMDB\Common\Alerts\Email\EmailAlert;
use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration;
use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationManager;
use DeliciousBrains\WPMDB\Common\Compatibility\CompatibilityManager;
use DeliciousBrains\WPMDB\Common\Error\Logger;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Migration\MigrationManager;
use DeliciousBrains\WPMDB\Common\Plugin\Assets;
use DeliciousBrains\WPMDB\Common\Plugin\Menu;
use DeliciousBrains\WPMDB\Common\Plugin\PluginManagerBase;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Upgrades\PluginUpdateManager;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Pro\RemoteUpdates\RemoteUpdatesManager;
use DeliciousBrains\WPMDB\SiteMigration\Addon\Addon;
use DeliciousBrains\WPMDB\SiteMigration\Addon\AddonsFacade;
use DeliciousBrains\WPMDB\Pro\BackgroundMigration\BackgroundPush;
use DeliciousBrains\WPMDB\Pro\Migration\Connection\Local;
use DeliciousBrains\WPMDB\Pro\Migration\Connection\Remote;
use DeliciousBrains\WPMDB\Pro\Migration\FinalizeComplete;
use DeliciousBrains\WPMDB\Pro\UI\Template;
use DeliciousBrains\WPMDB\SiteMigration\Plugin\PluginManager;
use DeliciousBrains\WPMDB\SiteMigration\Plugin\Scrubber;
use DeliciousBrains\WPMDB\SiteMigration\Settings\Settings as WPE_Settings;
use DeliciousBrains\WPMDB\WPMDBDI;
use DI\DependencyException;
use DI\NotFoundException;

class RegisterWPE
{

    /**
     * @var MigrationManager
     */
    private $migration_manager;
    /**
     * @var UsageTracking
     */
    private $usage_tracking;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Template
     */
    private $template;
    /**
     * @var License
     */
    private $license;
    /**
     * @var Addon
     */
    private $addon;
    /**
     * @var Menu
     */
    private $menu;
    /**
     * @var FinalizeComplete
     */
    private $finalize_complete;
    /**
     * @var Local
     */
    private $local_connection;
    /**
     * @var Remote
     */
    private $remote_connection;
    /**
     * @var Migration\Tables\Remote
     */
    private $remote_table;
    /**
     * @var AddonsFacade|mixed
     */
    private $addons_facade;
     /**
     * @var PluginManager
     */
    protected $plugin_manager;
    /**
     * @var Scrubber
     */
    protected $scrubber;
     /**
     * @var WPE_Settings
     */
    protected $wpe_settings;
    /**
     * @var BackgroundMigration[]
     */
    protected $background_migrations = [];
    /**
     * @var AlertInterface
     */
    protected $alerts;

    /**
     * @var PluginUpdateManager|mixed
     */
    protected $update_manager;

    /**
     * @var RemoteUpdatesManager|mixed
     */
    protected $remote_updates_manager;

    /**
     * Register components.
     *
     * TODO: Why does this class exist?
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function register()
    {
        $container = WPMDBDI::getInstance();

        $filesystem = $container->get(Filesystem::class);
        $filesystem->register();
        $container->set(
            Menu::class,
            new Menu(
                $container->get(Util::class),
                $container->get(Properties::class),
                $container->get(PluginManagerBase::class),
                $container->get(Assets::class),
                $container->get(CompatibilityManager::class)
            )
        );

        $this->plugin_manager         = $container->get(PluginManager::class);
        $this->scrubber               = $container->get(Scrubber::class);
        $this->remote_table           = $container->get(\DeliciousBrains\WPMDB\Pro\Migration\Tables\Remote::class);
        $this->local_connection       = $container->get(Local::class);
        $this->remote_connection      = $container->get(Remote::class);
        $this->finalize_complete      = $container->get(FinalizeComplete::class);
        $this->migration_manager      = $container->get(MigrationManager::class);
        $this->template               = $container->get(Template::class);
        $this->license                = $container->get(License::class);
        $this->addon                  = $container->get(Addon::class);
        $this->addons_facade          = $container->get(AddonsFacade::class);
        $this->menu                   = $container->get(Menu::class);
        $this->usage_tracking         = $container->get(\DeliciousBrains\WPMDB\Pro\UsageTracking::class);
        $this->logger                 = $container->get(Logger::class);
        $this->wpe_settings           = $container->get(WPE_Settings::class);
        $this->alerts                 = $container->get(EmailAlert::class);
        $this->update_manager         = $container->get(PluginUpdateManager::class);
        $this->remote_updates_manager = $container->get(RemoteUpdatesManager::class);

        // Register other class actions and filters
        $this->addons_facade->register();
        $this->local_connection->register();
        $this->remote_connection->register();
        $this->remote_table->register();
        $this->finalize_complete->register();
        $this->migration_manager->register();
        $this->template->register();
        $this->license->register();
        $this->addon->register();
        $this->menu->register();
        $this->usage_tracking->register();
        $this->logger->register();
        $this->plugin_manager->register();
        $this->scrubber->register();
        $this->wpe_settings->register();
        $this->alerts->register();
        $this->remote_updates_manager->register();
        $this->update_manager->register();

        // Plugin specific background migrations.
        $this->background_migrations[BackgroundPush::get_type()] = $container->get(BackgroundPush::class);

        // Register background migration manager after migrations so that they register themselves with it.
        $background_migration_manager = $container->get(BackgroundMigrationManager::class);
        $background_migration_manager->register();
    }
}
