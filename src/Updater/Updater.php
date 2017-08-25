<?php
namespace MaxmindGeolocator\Updater;

use Concrete\Core\Application\Application;
use Concrete\Core\Cache\Cache;
use Concrete\Core\File\Service\VolatileDirectory;
use Concrete\Core\Http\Client\Client as HttpClient;
use MaxmindGeolocator\Exception\InvalidConfigurationArgument;
use MaxmindGeolocator\Exception\InvalidProductIdException;
use Zend\Http\Client\Exception\RuntimeException as ZendRuntimeException;
use Zend\Http\Header\ContentLength;
use Zend\Http\Header\ContentType;
use Zend\Http\Header\HeaderInterface;

/**
 * Updater.
 */
class Updater
{
    /**
     * MD5 code to be used when the local database does not exist.
     *
     * @var string
     */
    const MD5_INEXISTING_FILE = '00000000000000000000000000000000';

    /**
     * Text response when there's no update.
     *
     * @var string
     */
    const NO_NEW_UPDATES_RESPONSE = 'No new updates available';

    /**
     * Duration of the cached items (in seconds).
     *
     * @var int
     */
    const CACHE_LIFETIME = 3600;

    /**
     * The Application instance.
     *
     * @var Application
     */
    protected $application;

    /**
     * The updater configuration.
     *
     * @var Configuration
     */
    protected $configuration;

    /**
     * The HTTP client.
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * The cache to be used.
     *
     * @var Cache|null
     */
    protected $cache;

    /**
     * Initialize the instance.
     *
     * @param Configuration $configuration the updater configuration
     * @param HttpClient $httpClient the HTTP client
     */
    public function __construct(Configuration $configuration, Application $application, HttpClient $httpClient)
    {
        $this->configuration = $configuration;
        $this->application = $application;
        $this->httpClient = $httpClient;
    }

    /**
     * Get the updater configuration.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set the updater configuration.
     *
     * @param Configuration $configuration
     *
     * @return $this
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get the cache to be used.
     *
     * @return Cache|null
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache to be used.
     *
     * @param Cache|null $cache
     *
     * @return $this
     */
    public function setCache(Cache $cache = null)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Perform a request for a resource.
     *
     * @param string $path
     * @param string $querystring
     * @param string $saveToFilename
     *
     * @throws ZendRuntimeException
     *
     * @return \Zend\Http\Response
     */
    protected function performRequest($path, $querystring = '', $saveToFilename = '')
    {
        $uri = $this->configuration->getProtocol() . '://' . $this->configuration->getHost() . '/' . ltrim($path, '/');
        if ($querystring !== '' && $querystring !== '?') {
            $uri .= '?' . ltrim($querystring, '?');
        }
        $this->httpClient->reset();
        if ($saveToFilename) {
            $this->httpClient->setOptions([
                'storeresponse' => false,
                'outputstream' => $saveToFilename,
            ]);
        }
        $this->httpClient->setUri($uri);
        $response = $this->httpClient->send();
        if (!$response->isSuccess()) {
            $failureReason = $response->getReasonPhrase();
            $contentType = $response->getHeaders()->get('Content-Type');
            if ($contentType instanceof ContentType && $contentType->getMediaType() === 'text/plain') {
                $s = trim($response->getBody());
                if ($s !== '') {
                    $failureReason = $s;
                }
            }
            throw new ZendRuntimeException($failureReason);
        }

        return $response;
    }

    /**
     * Perform a request for a text resource.
     *
     * @param string $path
     * @param string $querystring
     *
     * @throws ZendRuntimeException
     *
     * @return string
     */
    protected function performTextRequest($path, $querystring = '')
    {
        $response = $this->performRequest($path, $querystring);
        $contentType = $response->getHeaders()->get('Content-Type');
        if ($contentType instanceof ContentType && $contentType->getMediaType() !== 'text/plain') {
            throw new ZendRuntimeException(t('Invalid data received: %s', $contentType->getMediaType()));
        }

        return trim($response->getBody());
    }

    /**
     * Get the MaxMind filename for a specific product.
     *
     * @param string $productId The MaxMind product ID (for instance: 'GeoLite2-City')
     *
     * @throws ZendRuntimeException in case of HTTP communication problems
     *
     * @return string
     */
    protected function getMaxmindFilename()
    {
        $productId = $this->configuration->getProductId();
        if ($productId === '') {
            throw new InvalidProductIdException($productId);
        }
        $cacheItem = null;
        if ($this->cache !== null) {
            $cacheItem = $this->cache->getItem('maxmind_geolocator.updater.maxmind_filename@' . $productId);
        }
        if ($cacheItem === null || $cacheItem->isMiss()) {
            $result = $this->performTextRequest('app/update_getfilename', 'product_id=' . rawurlencode($productId));
            if ($cacheItem !== null) {
                $cacheItem->set($result)->setTTL(static::CACHE_LIFETIME)->save();
            }
        } else {
            $result = $cacheItem->get();
        }

        return $result;
    }

    /**
     * Get the challenge code build from the current IP address and the license key.
     *
     * @return string
     */
    protected function getChallengeMd5()
    {
        $licenseKey = $this->configuration->getLicenseKey();
        if ($licenseKey === '') {
            $c = $this->configuration;
            $licenseKey = $c::NO_LICENSE_KEY;
        }
        $myIP = $this->getCurrentIpAddressForMaxmind();

        return md5($licenseKey . $myIP);
    }

    /**
     * Get the current IP address as seen from MaxMind servers.
     *
     * @throws ZendRuntimeException in case of HTTP communication problems
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

    /**
     * Decode GZip-encode data to file.
     *
     * @param string $compressedFilename
     * @param string $uncompressedFilename
     * @param VolatileDirectory $tmp
     */
    protected function decodeGzipFile($compressedFilename, $uncompressedFilename, VolatileDirectory $tmp)
    {
        if (!function_exists('gzopen')) {
            throw new \Exception('Missing ZLIB extension');
        }
        $compressedHandle = @fopen($compressedFilename, 'rb');
        if ($compressedHandle === false) {
            throw new \Exception('Failed to open gzip file');
        }
        $header = @fread($compressedHandle, 2);
        $gzipSize = 0;
        if (@fseek($compressedHandle, -4, SEEK_END) === 0) {
            $d = (string) @fread($compressedHandle, 4);
            if (strlen($d) === 4) {
                $d = @unpack('V', $d);
                if (is_array($d)) {
                    $gzipSize = array_shift($d);
                }
            }
        }
        @fclose($compressedHandle);
        if ($header !== "\x1F\x8B" || 0) {
            throw new \Exception('The downloaded data is not in gzip format');
        }
        $compressedHandle = @gzopen($compressedFilename, 'rb');
        if ($compressedHandle === false) {
            throw new \Exception('Failed to open gzip file');
        }
        $unzippedFilename = $tmp->getPath() . '/decompressed';
        $unzippedHandle = @fopen($unzippedFilename, 'wb');
        if ($unzippedHandle === false) {
            @gzclose($compressedHandle);
            throw new \Exception('Failed to create a temporary file');
        }
        while (($chunk = @gzread($compressedHandle, 4096)) !== '') {
            if (@fwrite($unzippedHandle, $chunk) === false) {
                @fclose($unzippedHandle);
                @gzclose($compressedHandle);
                throw new \Exception('Failed to write to a temporary file');
            }
        }
        @fclose($unzippedHandle);
        @gzclose($compressedHandle);
        if (@filesize($unzippedFilename) !== $gzipSize) {
            throw new \Exception('Decompressed data size mismatch');
        }
        if (@rename($unzippedFilename, $uncompressedFilename) !== true) {
            throw new \Exception('Failed to move decompressed file');
        }
    }

    /**
     * Check if a GeoIP2 database needs to be updated: if so, update it.
     *
     * @throws \Zend\Http\Client\Exception\RuntimeException in case of HTTP communication problems
     *
     * @return bool
     */
    public function update()
    {
        $filename = $this->configuration->getDatabasePath();
        if ($filename === '') {
            throw new InvalidConfigurationArgument('databasePath', $filename);
        }
        if (@is_file($filename)) {
            $fileMd5 = @md5_file($filename);
            if ($fileMd5 === false) {
                throw new InvalidConfigurationArgument('databasePath', $filename);
            }
        } else {
            $fileMd5 = static::MD5_INEXISTING_FILE;
        }
        //$fileMd5 = strrev($fileMd5);
        $challengeMd5 = $this->getChallengeMd5();
        $userId = $this->configuration->getUserId();
        if ($userId === null) {
            $c = $this->configuration;
            $userId = $c::NO_USER_ID;
        }
        $tempDirectory = $this->application->make(VolatileDirectory::class);
        /* @var VolatileDirectory $tempDirectory */
        $tempFile = $tempDirectory->getPath() . '/downloaded';
        $response = $this->performRequest(
            'app/update_secure',
            "db_md5={$fileMd5}&challenge_md5={$challengeMd5}&user_id={$userId}&edition_id=" . rawurlencode($this->configuration->getProductId()),
            $tempFile
        );
        if ($fileMd5 === static::MD5_INEXISTING_FILE) {
            $updateNeeded = true;
        } else {
            $fileMd5Header = $response->getHeaders()->get('X-Database-MD5');
            if ($fileMd5Header instanceof HeaderInterface) {
                $updateNeeded = strcasecmp($fileMd5Header->getFieldValue(), $fileMd5) !== 0;
            } else {
                $contentType = $response->getHeaders()->get('Content-Type');
                if ($contentType instanceof ContentType && $contentType->getMediaType() === 'text/plain') {
                    $responseText = trim($response->getBody());
                    if ($responseText !== static::NO_NEW_UPDATES_RESPONSE) {
                        throw new ZendRuntimeException($responseText);
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

        return $result;
    }
}
