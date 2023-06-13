<?php

namespace Concrete\Package\MaxmindGeolocator\Exception;

class InvalidProductIdException extends Exception
{
    /**
     * The invalid product ID.
     *
     * @var string|mixed
     */
    protected $productId;

    /**
     * Initialize the instance.
     *
     * @param string|mixed $productId the invalid product ID
     */
    public function __construct($productId)
    {
        $this->productId = $productId;
        $message = t('Invalid MaxMind product ID');
        if (is_string($productId) && $productId !== '') {
            $message .= " ({$productId})";
        }
        parent::__construct($message);
    }

    /**
     * Get the invalid product ID.
     *
     * @return string|string
     */
    public function getProductId()
    {
        return $this->argumentName;
    }
}
