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
class V1 extends Updater
{
    /**
     * Text response when there's no update.
     *
     * @var string
     */
    const NO_NEW_UPDATES_RESPONSE = 'No new updates available';

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
        $challengeMD5 = $this->getChallengeMD5();
        $tempDirectory = $this->application->make(VolatileDirectory::class);
        $tempFile = $tempDirectory->getPath() . '/downloaded';
        $response = $this->performRequest(
            'app/update_secure',
            "db_md5={$fileMD5}&challenge_md5={$challengeMD5}&user_id={$userId}&edition_id=" . rawurlencode($this->configuration->getProductId()),
            $tempFile
        );
        $contentType = isset($response['headers']['content-type']) ? $response['headers']['content-type'] : '';
        if ($fileMD5 === static::MD5_INEXISTING_FILE) {
            $updateNeeded = true;
        } else {
            $fileMD5Header = isset($response['headers']['x-database-md5']) ? $response['headers']['x-database-md5'] : '';
            if ($fileMD5Header !== '') {
                $updateNeeded = strcasecmp($fileMD5Header, $fileMD5) !== 0;
            } else {
                if (stripos($contentType, 'text/plain') === 0) {
                    $bodyGetter = $response['bodyGetter'];
                    $responseText = $bodyGetter();
                    if ($responseText !== static::NO_NEW_UPDATES_RESPONSE) {
                        throw new HttpException($responseText);
                    }
                    $updateNeeded = false;
                } else {
                    $updateNeeded = true;
                }
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

    /**
     * Get the challenge code build from the current IP address and the license key.
     *
     * @return string
     */
    protected function getChallengeMD5()
    {
        $licenseKey = $this->configuration->getLicenseKey();
        $myIP = $this->getCurrentIpAddressForMaxmind();

        return md5($licenseKey . $myIP);
    }

    /**
     * Get the current IP address as seen from MaxMind servers.
     *
     * @throws \Concrete\Package\MaxmindGeolocator\Exception\HttpException in case of HTTP communication problems
     *
     * @return string
     */
    protected function getCurrentIpAddressForMaxmind()
    {
        $cacheItem = null;
        if ($this->cache !== null) {
            $cacheItem = $this->cache->getItem('maxmind_geolocator.updater.myip');
        }
        if ($cacheItem === null || $cacheItem->isMiss()) {
            $result = $this->performTextRequest('app/update_getipaddr');
            if ($cacheItem !== null) {
                $cacheItem->set($result)->setTTL(static::CACHE_LIFETIME)->save();
            }
        } else {
            $result = $cacheItem->get();
        }

        return $result;
    }
}
