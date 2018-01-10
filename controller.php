<?php
namespace Concrete\Package\MaxmindGeolocator;

use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Package;
use Exception;
use MaxmindGeolocator\Console\Command\UpdateDatabaseCommand;
use MaxmindGeolocator\ServiceProvider;
use MaxmindGeolocator\Updater\Updater;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'maxmind_geolocator';

    protected $appVersionRequired = '8.2.2b2';

    protected $pkgVersion = '0.9.0';

    protected $pkgAutoloaderRegistries = [
        'src' => 'MaxmindGeolocator',
    ];

    public function getPackageName()
    {
        return t('MaxMind GeoIP2 geolocator.');
    }

    public function getPackageDescription()
    {
        return t('This package installs a local geolocator using the MaxMing GeoIP2 data.');
    }

    public function install()
    {
        parent::install();
        $this->installXml();
        $this->registerServiceProvider();
        try {
            $this->app->make(Updater::class)->update();
        } catch (Exception $x) {
        }
    }

    public function upgrade()
    {
        $this->installXml();
        parent::upgrade();
    }

    private function installXml()
    {
        $contentImporter = $this->app->make(ContentImporter::class);
        $contentImporter->importContentFile($this->getPackagePath() . '/install.xml');
    }

    public function on_start()
    {
        $this->registerServiceProvider();
        if ($this->app->isRunThroughCommandLineInterface()) {
            $this->registerConsoleCommands();
        }
    }

    /**
     * Register the service classes.
     */
    private function registerServiceProvider()
    {
        $provider = $this->app->make(ServiceProvider::class);
        $provider->register();
    }

    /**
     * Register the console commands.
     */
    private function registerConsoleCommands()
    {
        $console = $this->app->make('console');
        $console->add(new UpdateDatabaseCommand());
    }
}