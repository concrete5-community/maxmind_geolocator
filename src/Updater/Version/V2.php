<?php

namespace MaxmindGeolocator\Updater\Version;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\File\Service\VolatileDirectory;
use MaxmindGeolocator\Exception\InvalidConfigurationArgument;
use MaxmindGeolocator\Updater\Updater;
use Zend\Http\Client\Exception\RuntimeException as ZendRuntimeException;
use Zend\Http\Header\ContentLength;
use Zend\Http\Header\ContentType;
use Zend\Http\Header\HeaderInterface;

/**
 * Updater (version 1).
 */
class V2 extends Updater
{
    /**
     * {@inheritdoc}
     *
     * @see \MaxmindGeolocator\Updater\Updater::update()
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
        $contentType = $response->getHeaders()->get('Content-Type');
        if ($fileMD5 === static::MD5_INEXISTING_FILE) {
            $updateNeeded = true;
        } elseif ($response->getStatusCode() === 304) {
            $updateNeeded = false;
        } else {
            $fileMD5Header = $response->getHeaders()->get('X-Database-MD5');
            if ($fileMD5Header instanceof HeaderInterface) {
                $updateNeeded = strcasecmp($fileMD5Header->getFieldValue(), $fileMD5) !== 0;
            } else {
                if ($contentType instanceof ContentType && $contentType->getMediaType() === 'text/plain') {
                    throw new ZendRuntimeException(trim($response->getBody()));
                }
                $updateNeeded = true;
            }
        }
        if ($updateNeeded === false) {
            $result = false;
        } else {
            if ($contentType instanceof ContentType && $contentType->getMediaType() !== 'application/gzip') {
                throw new ZendRuntimeException($contentType->getMediaType());
            }
            $downloadSize = is_file($tempFile) ? @filesize($tempFile) : 0;
            if ($downloadSize < 1) {
                throw new \Exception(t('No data downloaded'));
            }
            $contentLengthHeader = $response->getHeaders()->get('Content-Length');
            if ($contentLengthHeader instanceof ContentLength && $contentLengthHeader->getFieldValue() != $downloadSize) {
                throw new \Exception(t('Invalid size of downloaded data: expected %1$s bytes, received %2$s', $contentLengthHeader->getFieldValue(), $downloadSize));
            }
            $this->decodeGzipFile($tempFile, $filename, $tempDirectory);
            $result = true;
        }
        unset($response);

        return $result;
    }
}
