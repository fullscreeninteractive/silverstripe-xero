<?php

namespace FullscreenInteractive\SilverStripeXero;

use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

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

        $this->addMessage('Token refreshed until: '. date('d/m/Y H:i:s', $date));

        $this->isComplete = true;
    }
}
