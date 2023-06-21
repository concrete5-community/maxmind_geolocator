<?php

namespace Concrete\Package\MaxmindGeolocator\Task\UpdateMaxmindDatabase;

use Concrete\Core\Application\Application;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\CommandTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends AbstractController
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Command\Task\Controller\ControllerInterface::getName()
     */
    public function getName(): string
    {
        return t('Update MaxMind database');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Command\Task\Controller\ControllerInterface::getDescription()
     */
    public function getDescription(): string
    {
        return t('Update the MaxMind geolocation database.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Command\Task\Controller\ControllerInterface::getTaskRunner()
     */
    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $command = $this->app->make(Command::class);

        return new CommandTaskRunner($task, $command, t('The database is now up-to-date.'));
    }
}
