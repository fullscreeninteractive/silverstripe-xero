<?php

use FullscreenInteractive\SilverStripeXero\XeroFactory;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Permission;
use XeroPHP\Models\Accounting\Invoice;

class DebugInvoice extends BuildTask
{
    public function run($request)
    {
        $invoiceId = $request->getVar('invoiceId');

        if (Permission::check('ADMIN')) {
            $xero = XeroFactory::singleton()->getApplication();

            print_r($xero->loadByGUID(Invoice::class, $invoiceId));
        }
    }
}
