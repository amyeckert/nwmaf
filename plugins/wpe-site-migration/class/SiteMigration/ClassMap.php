<?php

namespace DeliciousBrains\WPMDB\SiteMigration;

use DeliciousBrains\WPMDB\Common\Alerts\AlertInterface;
use DeliciousBrains\WPMDB\Common\Alerts\Email\EmailAlert;
use DeliciousBrains\WPMDB\Common\Upgrades\PluginUpdateManager;
use DeliciousBrains\WPMDB\Common\Upgrades\UpgradeRoutinesManager;
use DeliciousBrains\WPMDB\Pro\RemoteUpdates\RemoteUpdatesManager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\TransportManager;
use DeliciousBrains\WPMDB\Pro\UsageTracking;
use DeliciousBrains\WPMDB\SiteMigration\Addon\Addon;
use DeliciousBrains\WPMDB\Common\Error\Logger;
use DeliciousBrains\WPMDB\Common\Filesystem\RecursiveScanner;
use DeliciousBrains\WPMDB\SiteMigration\Addon\AddonsFacade;
use DeliciousBrains\WPMDB\Common\MF\MediaFilesLocal;
use DeliciousBrains\WPMDB\Pro\BackgroundMigration\BackgroundPush;
use DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms\Platforms;
use DeliciousBrains\WPMDB\Pro\MF\MediaFilesRemote;
use DeliciousBrains\WPMDB\Pro\Migration\Flush;
use DeliciousBrains\WPMDB\Pro\Migration\Connection;
use DeliciousBrains\WPMDB\Pro\Migration\FinalizeComplete;
use DeliciousBrains\WPMDB\Pro\Migration\Tables\Local;
use DeliciousBrains\WPMDB\Pro\Migration\Tables\Remote;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Common\Queue\QueueHelper;
use DeliciousBrains\WPMDB\Common\TPF\ThemePluginFilesLocal;
use DeliciousBrains\WPMDB\Pro\TPF\ThemePluginFilesRemote;
use DeliciousBrains\WPMDB\Common\TPF\TransferCheck;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Chunker;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Excludes;
use DeliciousBrains\WPMDB\Common\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\IncrementalSizeController;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Payload;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\PluginHelper;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\TransferManager;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;
use DeliciousBrains\WPMDB\Pro\Transfers\Sender;
use DeliciousBrains\WPMDB\Pro\UI\Template;
use DeliciousBrains\WPMDB\Pro\MF\Manager as MF_Manager;
use DeliciousBrains\WPMDB\Pro\TPF\Manager as TPF_Manager;
use DeliciousBrains\WPMDB\SiteMigration\Plugin\PluginManager;
use DeliciousBrains\WPMDB\SiteMigration\Plugin\Scrubber;
use DeliciousBrains\WPMDB\SiteMigration\Settings\Settings as WPE_Settings;
use DeliciousBrains\WPMDB\SiteMigration\TPF\ThemePluginFilesAddon;

class ClassMap extends \DeliciousBrains\WPMDB\ClassMap
{

    public $license;
    public $addon;
    public $template;
    public $usage_tracking;
    public $logger;
    public $finalize_complete;
    public $transfers_util;
    public $transfers_chunker;
    public $transfers_payload;
    public $transfers_receiver;
    public $transfers_sender;
    public $transfers_excludes;
    public $queue_manager;
    public $transfers_manager;
    public $transfers_file_processor;
    public $common_flush;
    public $media_files_addon;
    public $media_files_addon_remote;
    public $media_files_addon_local;
    public $tp_addon_finalize;
    public $tp_addon;
    public $tp_addon_transfer_check;
    public $tp_addon_local;
    public $tp_addon_remote;
    public $media_files_manager;
    public $theme_plugin_manager;
    public $plugin_manager;

    /**
     * @var Remote
     */
    public $remote_tables;
    /**
     * @var Local
     */
    public $local_tables;
    /**
     * @var Connection\Remote
     */
    public $remote_connection;
    /**
     * @var Connection\Local
     */
    public $local_connection;
    /**
     * @var PluginHelper
     */
    public $transfers_plugin_helper;
    /**
     * @var QueueHelper
     */
    public $transfers_queue_helper;

    /**
     * @var RecursiveScanner
     */
    public $recursive_scanner;

    /**
     * @var AddonsFacade
     */
    public $addons_facade;

    /**
     * @var IncrementalSizeController
     */
    private $incremental_size_controller;

    /**
     * @var Platforms
     */
    private $hosting_platform;

    /**
     * @var BackgroundPush
     */
    public $background_push;

    /**
     * @var WPE_Settings
     */
    public $wpe_settings;

    /**
     * @var AlertInterface
     */
    public $alerts;

    /**
     * @var Scrubber
     */
    public $scrubber;

    /**
     * @var PluginUpdateManager
     */
    public $update_manager;

    /**
     * @var RemoteUpdatesManager
     */
    public $remote_updates_manager;

    /**
     * @var TransportManager
     */
    public $transport_manager;

    public function __construct()
    {
        $this->hosting_platform = new Platforms();

        $this->wpe_settings = new WPE_Settings();

        parent::__construct();

        $this->tp_addon = new ThemePluginFilesAddon(
            $this->addon,
            $this->properties,
            $this->filesystem,
            $this->profile_manager,
            $this->util,
            $this->transfers_util,
            $this->tp_addon_finalize
        );

        $this->addon = new Addon(
            $this->error_log,
            $this->settings,
            $this->properties
        );

        $this->common_flush = new \DeliciousBrains\WPMDB\Common\Migration\Flush(
            $this->http_helper,
            $this->util,
            $this->remote_post,
            $this->http
        );

        $this->flush = new Flush(
            $this->http_helper,
            $this->util,
            $this->remote_post,
            $this->http
        );

        $this->upgrade_routines_manager = new UpgradeRoutinesManager(
            $this->assets,
            $this->profile_manager
        );

        $this->plugin_manager = new PluginManager(
            $this->settings,
            $this->assets,
            $this->util,
            $this->table,
            $this->http,
            $this->filesystem,
            $this->multisite,
            $this->properties,
            $this->migration_helper,
            $this->WPMDBRestAPIServer,
            $this->http_helper,
            $this->notice,
            $this->profile_manager,
            $this->upgrade_routines_manager
        );

        $this->scrubber = new Scrubber();

        $this->template = new Template(
            $this->settings,
            $this->util,
            $this->profile_manager,
            $this->filesystem,
            $this->table,
            $this->notice,
            $this->form_data,
            $this->properties
        );

        $this->license = new License();

        $this->usage_tracking = new UsageTracking(
            $this->settings,
            $this->filesystem,
            $this->error_log,
            $this->template,
            $this->form_data,
            $this->properties,
            $this->license
        );

        $this->logger = new Logger();

        $this->local_connection = new Connection\Local(
            $this->http,
            $this->http_helper,
            $this->properties,
            $this->remote_post,
            $this->util,
            $this->WPMDBRestAPIServer,
            $this->license
        );

        $this->remote_connection = new Connection\Remote(
            $this->scrambler,
            $this->http,
            $this->http_helper,
            $this->properties,
            $this->util,
            $this->table,
            $this->form_data,
            $this->settings,
            $this->filesystem,
            $this->multisite,
            $this->backup_export,
            $this->license
        );

        $this->local_tables = new Local();

        $this->finalize_complete = new FinalizeComplete(
            $this->scrambler,
            $this->migration_state_manager,
            $this->http,
            $this->http_helper,
            $this->properties,
            $this->error_log,
            $this->migration_manager,
            $this->form_data,
            $this->finalize_migration,
            $this->settings,
            $this->WPMDBRestAPIServer,
            $this->flush
        );

        $this->remote_tables = new Remote(
            $this->scrambler,
            $this->settings,
            $this->http,
            $this->http_helper,
            $this->table_helper,
            $this->properties,
            $this->form_data,
            $this->migration_manager,
            $this->table,
            $this->backup_export
        );

        // Transfers classes
        $this->transfers_util = new Util(
            $this->filesystem,
            $this->http,
            $this->error_log,
            $this->http_helper,
            $this->remote_post,
            $this->settings,
            $this->migration_state_manager,
            $this->util
        );

        $this->transfers_chunker = new Chunker(
            $this->transfers_util
        );

        $this->transfers_payload = new Payload(
            $this->transfers_util,
            $this->transfers_chunker,
            $this->filesystem,
            $this->http,
            $this->util
        );

        $this->transfers_receiver = new Receiver(
            $this->transfers_util,
            $this->transfers_payload,
            $this->settings,
            $this->error_log,
            $this->filesystem
        );

        $this->transport_manager = new TransportManager();

        $this->transfers_sender = new Sender(
            $this->transfers_util,
            $this->transfers_payload,
            $this->transport_manager
        );

        $this->transfers_excludes = new Excludes();

        $this->queue_manager = new Manager(
            $this->properties,
            $this->state_data_container,
            $this->migration_state_manager,
            $this->form_data
        );

        $this->incremental_size_controller = new IncrementalSizeController();

        //remove FSE
        $this->transfers_manager = new TransferManager(
            $this->queue_manager,
            $this->transfers_payload,
            $this->transfers_util,
            $this->incremental_size_controller,
            $this->http_helper,
            $this->transfers_receiver,
            $this->transfers_sender,
            $this->full_site_export,
            $this->transport_manager
        );

        $this->recursive_scanner = new RecursiveScanner(
            $this->filesystem,
            $this->transfers_util
        );

        $this->transfers_file_processor = new FileProcessor(
            $this->filesystem,
            $this->http,
            $this->recursive_scanner
        );

        $this->transfers_plugin_helper = new PluginHelper(
            $this->filesystem,
            $this->properties,
            $this->http,
            $this->http_helper,
            $this->settings,
            $this->migration_state_manager,
            $this->scrambler,
            $this->transfers_file_processor,
            $this->transfers_util,
            $this->queue_manager,
            $this->queue_manager,
            $this->state_data_container,
            $this->transfers_sender,
            $this->transfers_receiver
        );

        $this->transfers_queue_helper = new QueueHelper(
            $this->filesystem,
            $this->http,
            $this->http_helper,
            $this->transfers_util,
            $this->queue_manager,
            $this->util
        );

        /* Start MF Section */
        $this->media_files_addon_local = new MediaFilesLocal(
            $this->form_data,
            $this->http,
            $this->util,
            $this->http_helper,
            $this->WPMDBRestAPIServer,
            $this->transfers_manager,
            $this->transfers_util,
            $this->transfers_file_processor,
            $this->transfers_queue_helper,
            $this->queue_manager,
            $this->transfers_plugin_helper,
            $this->profile_manager
        );

        $this->media_files_addon_remote = new MediaFilesRemote(
            $this->transfers_plugin_helper
        );

        $this->media_files_manager = new MF_Manager();
        /* End MF Section */

        /* Start TPF Section */
        $this->tp_addon_transfer_check = new TransferCheck(
            $this->form_data,
            $this->http,
            $this->error_log
        );

        $this->tp_addon_local = new ThemePluginFilesLocal(
            $this->transfers_util,
            $this->util,
            $this->transfers_file_processor,
            $this->queue_manager,
            $this->transfers_manager,
            $this->migration_state_manager,
            $this->http,
            $this->filesystem,
            $this->tp_addon_transfer_check,
            $this->WPMDBRestAPIServer,
            $this->http_helper,
            $this->transfers_queue_helper
        );

        $this->tp_addon_remote = new ThemePluginFilesRemote(
            $this->transfers_util,
            $this->transfers_file_processor,
            $this->queue_manager,
            $this->transfers_manager,
            $this->transfers_receiver,
            $this->http,
            $this->http_helper,
            $this->migration_state_manager,
            $this->settings,
            $this->properties,
            $this->transfers_sender,
            $this->filesystem,
            $this->scrambler,
            $this->transfers_plugin_helper
        );

        $this->theme_plugin_manager = new TPF_Manager();
        /* End TPF Section */

        $this->addons_facade = new AddonsFacade([
            $this->media_files_manager,
            $this->theme_plugin_manager,
        ]);

        // Instantiate background migrations.
        $this->background_push = new BackgroundPush($this->error_log);

        $this->alerts         = new EmailAlert();
        $this->update_manager = new PluginUpdateManager($this->properties);

        $this->remote_updates_manager = new RemoteUpdatesManager(
            $this->http_helper,
            $this->http,
            $this->remote_post,
            $this->WPMDBRestAPIServer,
            $this->migration_state_manager,
            $this->properties,
            $this->settings,
            $this->util,
            $this->license
        );
    }
}
