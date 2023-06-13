<?php

namespace Concrete\Package\MaxmindGeolocator\Job;

use Concrete\Core\Job\Job;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\MaxmindGeolocator\Updater;

class UpdateMaxmindDatabase extends Job
{
    /**
     * {@inheritdoc}
     *
     * @see Job::getJobName()
     */
    public function getJobName()
    {
        return t('Update MaxMind database');
    }

    /**
     * {@inheritdoc}
     *
     * @see Job::getJobDescription()
     */
    public function getJobDescription()
    {
        return t('Update the MaxMind geolocation database.');
    }

    /**
     * {@inheritdoc}
     *
     * @see Job::run()
     */
    public function run()
    {
        $app = Application::getFacadeApplication();
        $updater = $app->make(Updater::class);

        return $updater->update() ? t('The database has been updated.') : t('The database was already up-to-date.');
    }
}
