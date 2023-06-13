<?php

namespace Concrete\Package\MaxmindGeolocator\Updater;

use Concrete\Package\MaxmindGeolocator\Exception\InvalidConfigurationArgument;

/**
 * Updater configuration.
 */
class Configuration
{
    /**
     * MaxMind protocol version 1 (like for geoipupdate < 3.1.1).
     *
     * @var int
     */
    const MMPROTOCOLVERSION_1 = 1;

    /**
     * MaxMind protocol version 1 (like for geoipupdate >= 3.1.1).
     *
     * @var int
     */
    const MMPROTOCOLVERSION_2 = 2;

    /**
     * The host to be used for communications.
     *
     * @var string
     */
    protected $host = 'updates.maxmind.com';

    /**
     * The MaxMind user ID.
     *
     * @var int|null
     */
    protected $userId;

    /**
     * The MaxMind license key.
     *
     * @var string
     */
    protected $licenseKey = '';

    /**
     * The MaxMind product ID.
     *
     * @var string
     */
    protected $productId = '';

    /**
     * The path to the local database.
     *
     * @var string
     */
    protected $databasePath = '';

    /**
     * The MaxMind protocol version.
     *
     * @var int|null
     */
    protected $maxmindProtocolVersion;

    /**
     * Get the host to be used for communications.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the host to be used for communications.
     *
     * @param string $host
     *
     * @throws \Concrete\Package\MaxmindGeolocator\Exception\InvalidConfigurationArgument
     *
     * @return $this
     */
    public function setHost($host)
    {
        $h = is_string($host) ? trim($host) : '';
        if ($h === '' || strpos('/', $host) !== false) {
            throw new InvalidConfigurationArgument('host', $host);
        }
        $this->host = $host;

        return $this;
    }

    /**
     * Get the MaxMind user ID.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the MaxMind user ID.
     *
     * @param int|null $userId
     *
     * @throws \Concrete\Package\MaxmindGeolocator\Exception\InvalidConfigurationArgument
     *
     * @return $this
     */
    public function setUserId($userId)
    {
        if ($userId === null || $userId === '') {
            $this->userId = null;
        } elseif (is_int($userId)) {
            $this->userId = $userId;
        } elseif (is_string($userId) && is_numeric($userId)) {
            $this->userId = (int) $userId;
        } else {
            throw new InvalidConfigurationArgument('userId', $userId);
        }

        return $this;
    }

    /**
     * Get the MaxMind license key.
     *
     * @return string
     */
    public function getLicenseKey()
    {
        return $this->licenseKey;
    }

    /**
     * Set the MaxMind license key.
     *
     * @param string $licenseKey
     *
     * @return $this
     */
    public function setLicenseKey($licenseKey)
    {
        $this->licenseKey = trim((string) $licenseKey);

        return $this;
    }

    /**
     * Get the MaxMind product ID.
     *
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set the MaxMind product ID.
     *
     * @param string $productId
     *
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = trim((string) $productId);

        return $this;
    }

    /**
     * Get the path to the local database.
     *
     * @return string
     */
    public function getDatabasePath()
    {
        return $this->databasePath;
    }

    /**
     * Set the path to the local database.
     *
     * @param string $databasePath
     *
     * @return $this
     */
    public function setDatabasePath($databasePath)
    {
        $this->databasePath = trim((string) $databasePath);

        return $this;
    }

    /**
     * Get the MaxMind protocol version.
     *
     * @return int|null
     */
    public function getMaxmindProtocolVersion()
    {
        return $this->maxmindProtocolVersion;
    }

    /**
     * Set the path to the local database.
     *
     * @param int|string|null $value
     *
     * @throws \Concrete\Package\MaxmindGeolocator\Exception\InvalidConfigurationArgument
     *
     * @return $this
     */
    public function setMaxmindProtocolVersion($value)
    {
        $s = (string) $value;
        if ($s === '') {
            $this->maxmindProtocolVersion = null;
        } else {
            $i = (int) $value;
            if ($s !== (string) $i || !in_array($i, [self::MMPROTOCOLVERSION_1, self::MMPROTOCOLVERSION_2], true)) {
                throw new InvalidConfigurationArgument('maxmindProtocolVersion', $value);
            }
            $this->maxmindProtocolVersion = $i;
        }

        return $this;
    }
}
