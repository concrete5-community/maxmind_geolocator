<?php
namespace MaxmindGeolocator\Updater;

use MaxmindGeolocator\Exception\InvalidConfigurationArgument;

/**
 * Updater configuration.
 */
class Configuration
{
    /**
     * The value to be used when there's no user id.
     *
     * @var int
     */
    const NO_USER_ID = 0;

    /**
     * The value to be used when there's no license key.
     *
     * @var string
     */
    const NO_LICENSE_KEY = '000000000000';

    /**
     * The protocol to be used for communications.
     *
     * @var string
     */
    protected $protocol = 'http';

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
    protected $userId = null;

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
     * Get the protocol to be used for communications.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Set the protocol to be used for communications.
     *
     * @param string $protocol
     *
     * @throws InvalidConfigurationArgument
     *
     * @return $this
     */
    public function setProtocol($protocol)
    {
        $p = is_string($protocol) ? strtolower(trim($protocol)) : '';
        if (!in_array($p, [
            'http',
            'https',
        ])) {
            throw new InvalidConfigurationArgument('protocol', $protocol);
        }
        $this->protocol = $p;

        return $this;
    }

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
     * @throws InvalidConfigurationArgument
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
     * @throws InvalidConfigurationArgument
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
}
