<?php

namespace FullscreenInteractive\SilverStripeXero;

use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

class RefreshXeroTokenJob extends AbstractQueuedJob
{
    public function getTitle()
    {
        return 'Refresh Xero token';
    }


    public function process()
    {
        $xero = XeroFactory::create();
        $date = $xero->renewToken();

        $this->addMessage('Token refreshed until: ' . date('d/m/Y H:i:s', $date));

        $job = new RefreshXeroTokenJob();

        /** @var QueuedJobService */
        $service = Injector::inst()->get(QueuedJobService::class);

        $service->queueJob(
            $job,
            date('Y-m-d H:i:s', strtotime("+10 minutes"))
        );

        $this->isComplete = true;
    }
}
