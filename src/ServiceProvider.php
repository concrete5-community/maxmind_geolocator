<?php

namespace MaxmindGeolocator;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Geolocator\GeolocatorService;
use Concrete\Core\Package\PackageService;
use GeoIp2\Database\Reader;
use MaxmindGeolocator\Exception\InvalidConfigurationArgument;
use MaxmindGeolocator\Exception\MaxMindDatabaseUnavailable;
use MaxmindGeolocator\Updater\Configuration;
use MaxmindGeolocator\Updater\Updater;

/**
 * Class that register the services.
 */
class ServiceProvider extends Provider
{
    /**
     * {@inheritdoc}
     *
     * @see Provider::register()
     */
    public function register()
    {
        $this->app->bind(Configuration::class, function (Application $app) {
            $glService = $app->make(GeolocatorService::class);
            $geolocator = $glService->getByHandle('maxmind_geoip2');
            $data = $geolocator->getGeolocatorConfiguration();
            $configuration = new Configuration();
            if (!empty($data['protocol'])) {
                $configuration->setProtocol($data['protocol']);
            }
            if (!empty($data['host'])) {
                $configuration->setHost($data['host']);
            }
            if (!empty($data['user-id'])) {
                $configuration->setUserId($data['user-id']);
            }
            if (!empty($data['license-key'])) {
                $configuration->setLicenseKey($data['license-key']);
            }
            if (!empty($data['product-id'])) {
                $configuration->setProductId($data['product-id']);
            }
            if (!empty($data['database-path']) && is_string($data['database-path'])) {
                $path = str_replace(DIRECTORY_SEPARATOR, '/', trim($data['database-path']));
                if ($path !== '') {
                    if (!preg_match('/^([a-z]:)?\//i', $path)) {
                        $path = DIR_FILES_UPLOADED_STANDARD . '/' . ltrim($path, '/');
                    }
                    $configuration->setDatabasePath($path);
                }
            }

            return $configuration;
        });

        $this->app->bind(Updater::class, function (Application $app) {
            $result = $app->build(Updater::class);
            $result->setCache($app->make('cache/expensive'));

            return $result;
        });

        $this->app->bind(Reader::class, function (Application $app) {
            $configuration = $app->make(Configuration::class);
            $databasePath = $configuration->getDatabasePath();
            if ($databasePath === '') {
                throw new InvalidConfigurationArgument('DatabasePath');
            }
            if (!is_file($databasePath)) {
                throw new MaxMindDatabaseUnavailable('The MaxMind database file has not yet been downloaded');
            }
            if (!class_exists(Reader::class, true)) {
                $packageService = $app->make(PackageService::class);
                $package = $packageService->getClass('maxmind_geolocator');
                require_once $package->getPackagePath() . '/vendor/autoload.php';
            }

            return new Reader($databasePath);
        });
    }
}
