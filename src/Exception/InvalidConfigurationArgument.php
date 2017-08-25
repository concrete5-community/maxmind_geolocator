<?php
namespace MaxmindGeolocator\Exception;

class InvalidConfigurationArgument extends Exception
{
    /**
     * The name of the invalid argument.
     *
     * @var string
     */
    protected $argumentName;

    /**
     * The value of the invalid argument.
     *
     * @var mixed
     */
    protected $argumentValue;

    /**
     * Initialize the instance.
     *
     * @param string $argumentName the name of the invalid argument
     * @param mixed $argumentValue the value of the invalid argument
     */
    public function __construct($argumentName, $argumentValue)
    {
        $this->argumentName = $argumentName;
        $this->argumentValue = $argumentValue;
        parent::__construct(t('Invalid value for the argument %s', $argumentName));
    }

    /**
     * Get the name of the invalid argument.
     *
     * @return string
     */
    public function getArgumentName()
    {
        return $this->argumentName;
    }

    /**
     * Get the value of the invalid argument.
     *
     * @return mixed
     */
    public function getArgumentValue()
    {
        return $this->argumentValue;
    }
}
