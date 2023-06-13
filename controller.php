<?php

namespace Concrete\Package\MaxmindGeolocator;

use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Package;
use Exception;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'maxmind_geolocator';

    protected $appVersionRequired = '8.3.0';

    protected $pkgVersion = '1.1.0';

    public function getPackageName()
    {
        return t('Geolocation with MaxMind GeoIP2');
    }

    public function getPackageDescription()
    {
        return t('This package installs a local geolocator using the MaxMind GeoIP2 data.');
    }

    public function install()
    {
        $this->registerAutoload();
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

    public function on_start()
    {
        $this->registerAutoload();
        $this->registerServiceProvider();
        if ($this->app->isRunThroughCommandLineInterface()) {
            $this->registerConsoleCommands();
        }
    }

    private function installXml()
    {
        $contentImporter = $this->app->make(ContentImporter::class);
        $contentImporter->importContentFile($this->getPackagePath() . '/install.xml');
    }

    private function registerAutoload()
    {
        $autoloader = $this->getPackagePath() . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
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
        $console->add(new Console\Command\UpdateDatabaseCommand());
    }
}
