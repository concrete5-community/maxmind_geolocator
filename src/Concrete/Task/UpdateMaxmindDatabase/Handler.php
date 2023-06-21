<?php

namespace Concrete\Package\MaxmindGeolocator\Task\UpdateMaxmindDatabase;

use Concrete\Core\Command\Task\Output\OutputAwareInterface;
use Concrete\Core\Command\Task\Output\OutputInterface;
use Concrete\Core\Command\Task\Output\NullOutput;
use Concrete\Package\MaxmindGeolocator\Updater;

defined('C5_EXECUTE') or die('Access Denied.');

class Handler implements OutputAwareInterface
{
    /**
     * @var \Concrete\Core\Command\Task\Output\OutputInterface
     */
    protected $output;

    /**
     * @var \Concrete\Package\MaxmindGeolocator\Updater
     */
    protected $updater;

    public function __construct(Updater $updater)
    {
        $this->output = new NullOutput();
        $this->updater = $updater;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Command\Task\Output\OutputAwareInterface::setOutput()
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function __invoke(Command $command)
    {
        if ($this->updater->update()) {
            $this->output->write(t('The database has been updated.'));
        } else {
            $this->output->write(t('The database was already up-to-date.'));
        }
    }
}
