<?php

namespace MaxmindGeolocator;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Geolocator\GeolocatorService;
use Concrete\Core\Package\PackageService;
use GeoIp2\Database\Reader;
use MaxmindGeolocator\Exception\InvalidConfigurationArgument;
use MaxmindGeolocator\Exception\MaxmindDatabaseUnavailable;

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
        $this->app->bind(Updater\Configuration::class, function (Application $app) {
            $glService = $app->make(GeolocatorService::class);
            $geolocator = $glService->getByHandle('maxmind_geoip2');
            $data = $geolocator->getGeolocatorConfiguration();
            $configuration = new Updater\Configuration();
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
            $configuration->setMaxmindProtocolVersion($data['maxmind-protocol-version']);
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

        $this->app->bind(Updater\Updater::class, function (Application $app) {
            $configuration = $app->make(Updater\Configuration::class);
            $maxmMindProtocolVersion = $configuration->getMaxmindProtocolVersion();
            if ($maxmMindProtocolVersion === null) {
                if (strlen($configuration->getLicenseKey()) <= 12) {
                    $maxmMindProtocolVersion = Updater\Configuration::MMPROTOCOLVERSION_1;
                } else {
                    $maxmMindProtocolVersion = Updater\Configuration::MMPROTOCOLVERSION_2;
                }
            }
            switch ($maxmMindProtocolVersion) {
                case Updater\Configuration::MMPROTOCOLVERSION_1:
                    $result = $app->build(Updater\Version\V1::class, [$configuration]);
                    break;
                case Updater\Configuration::MMPROTOCOLVERSION_2:
                default:
                    $result = $app->build(Updater\Version\V2::class, [$configuration]);
                    break;
            }
            $result->setCache($app->make('cache/expensive'));

            return $result;
        });

        $this->app->bind(Reader::class, function (Application $app) {
            $configuration = $app->make(Updater\Configuration::class);
            $databasePath = $configuration->getDatabasePath();
            if ($databasePath === '') {
                throw new InvalidConfigurationArgument('DatabasePath');
            }
            if (!is_file($databasePath)) {
                throw new MaxmindDatabaseUnavailable('The MaxMind database file has not yet been downloaded');
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
