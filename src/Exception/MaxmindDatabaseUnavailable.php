<?php

namespace MaxmindGeolocator\Exception;

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
