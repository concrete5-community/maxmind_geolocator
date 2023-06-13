<?php

namespace Concrete\Package\MaxmindGeolocator\Updater\Version;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\File\Service\VolatileDirectory;
use Concrete\Package\MaxmindGeolocator\Exception\HttpException;
use Concrete\Package\MaxmindGeolocator\Exception\InvalidConfigurationArgument;
use Concrete\Package\MaxmindGeolocator\Updater;

/**
 * Updater (version 1).
 */
class V2 extends Updater
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\MaxmindGeolocator\Updater::update()
     */
    public function update()
    {
        $userId = $this->configuration->getUserId();
        if ($userId === null) {
            throw new UserMessageException(t('The MaxMind user ID is not configured.'));
        }
        if ($this->configuration->getLicenseKey() === '') {
            throw new UserMessageException(t('The MaxMind license key is not configured.'));
        }
        $filename = $this->configuration->getDatabasePath();
        if ($filename === '') {
            throw new InvalidConfigurationArgument('databasePath', $filename);
        }
        $fileMD5 = $this->getCurrentLocalDatabaseMD5();
        $tempDirectory = $this->application->make(VolatileDirectory::class);
        $tempFile = $tempDirectory->getPath() . '/downloaded';
        $response = $this->performRequest(
            'geoip/databases/' . rawurldecode($this->configuration->getProductId()) . '/update',
            "db_md5={$fileMD5}",
            $tempFile,
            [
                (string) $userId,
                $this->configuration->getLicenseKey(),
            ],
            static function ($statusCode) use ($fileMD5) {
                return ($statusCode >= 200 && $statusCode < 300) || ($fileMD5 !== static::MD5_INEXISTING_FILE && $statusCode === 304);
            }
        );
        $contentType = isset($response['headers']['content-type']) ? $response['headers']['content-type'] : '';
        if ($fileMD5 === static::MD5_INEXISTING_FILE) {
            $updateNeeded = true;
        } elseif ($response['statusCode'] === 304) {
            $updateNeeded = false;
        } else {
            $fileMD5Header = isset($response['headers']['x-database-md5']) ? $response['headers']['x-database-md5'] : '';
            if ($fileMD5Header !== '') {
                $updateNeeded = strcasecmp($fileMD5Header, $fileMD5) !== 0;
            } else {
                if (stripos($contentType, 'text/plain') === 0) {
                    $bodyGetter = $response['bodyGetter'];
                    throw new HttpException(trim($bodyGetter()));
                }
                $updateNeeded = true;
            }
        }
        if ($updateNeeded === false) {
            $result = false;
        } else {
            if (stripos($contentType, 'application/gzip') !== 0) {
                throw new HttpException($contentType);
            }
            $downloadSize = is_file($tempFile) ? @filesize($tempFile) : 0;
            if ($downloadSize < 1) {
                throw new \Exception(t('No data downloaded'));
            }
            $contentLength = isset($response['headers']['content-length']) ? $response['headers']['content-length'] : '';
            $contentLength = is_numeric($contentLength) ? (int) $contentLength : null;
            if ($contentLength !== null && $contentLength !== $downloadSize) {
                throw new \Exception(t('Invalid size of downloaded data: expected %1$s bytes, received %2$s', $contentLength, $downloadSize));
            }
            $this->decodeGzipFile($tempFile, $filename, $tempDirectory);
            $result = true;
        }
        unset($response);

        return $result;
    }
}
