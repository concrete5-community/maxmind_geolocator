<?php

namespace Concrete\Package\MaxmindGeolocator\Task\UpdateMaxmindDatabase;

use Concrete\Core\Foundation\Command\Command as AbstractCommand;

defined('C5_EXECUTE') or die('Access Denied.');

class Command extends AbstractCommand
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Foundation\Command\Command::getHandler()
     * @see \Concrete\Core\Foundation\Command\HandlerAwareCommandInterface::getHandler()
     */
    public static function getHandler(): string
    {
        return Handler::class;
    }
}
