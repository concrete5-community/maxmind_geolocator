<?php

namespace Concrete\Package\MaxmindGeolocator\Console\Command;

use Concrete\Core\Console\Command;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\MaxmindGeolocator\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

defined('C5_EXECUTE') or die('Access Denied.');

class UpdateDatabaseCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('geo:maxmind:update')
            ->setDescription('Update the MaxMind database used to geolocate IP addresses')
            ->setHelp(
                <<<'EOT'
You should run this command on a regular basis in order to keep the MaxMind database up-to-date.

The database will be updated only if necessary.

Returns codes:
  0 the operation compleded successfully
  1 errors occurred
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = Application::getFacadeApplication();
        $updater = $app->make(Updater::class);
        if ($output->getVerbosity() >= OutputInterface::OUTPUT_NORMAL) {
            $output->write('Updating the MaxMind database... ');
        }
        if ($updater->update()) {
            $output->writeln('the database has been updated.');
        } else {
            $output->writeln('the database was already up-to-date.');
        }
    }
}
