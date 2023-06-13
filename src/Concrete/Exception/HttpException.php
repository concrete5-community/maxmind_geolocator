<?php

namespace Concrete\Package\MaxmindGeolocator\Exception;

class HttpException extends Exception
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
