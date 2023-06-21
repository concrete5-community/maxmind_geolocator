<?php

namespace Concrete\Package\MaxmindGeolocator\Exception;

defined('C5_EXECUTE') or die('Access Denied.');

class MaxmindDatabaseUnavailable extends Exception
{
    /**
     * Initialize the instance.
     */
    public function __construct()
    {
        parent::__construct(t('The MaxMind database file can\'t be found.'));
    }
}
