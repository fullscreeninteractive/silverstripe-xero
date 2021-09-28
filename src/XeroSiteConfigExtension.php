<?php

namespace FullscreenInteractive\SilverStripeXero;

use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;

class XeroSiteConfigExtension extends DataExtension
{
    private static $db = [
        'XeroTenantId' => 'Varchar(200)',
        'XeroAccessToken' => 'Text',
        'XeroRefreshToken' => 'Text',
        'XeroTokenRefreshExpires' => 'Datetime',
        'XeroTenants' => 'Text'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if (!Permission::check('ADMIN')) {
            return;
        }

        if (!Environment::getEnv('XERO_CLIENT_ID') || !Environment::getEnv('XERO_CLIENT_SECRET')) {
            return;
        }

        $fields->addFieldsToTab('Root.Xero', [
            ReadonlyField::create('XeroAccessToken', 'Access token')
                ->setDescription(sprintf(
                    '<a href="%s" target="_blank">Link to a new xero account</a>',
                    XeroFactory::singleton()->getRedirectUri()
                ))
        ]);

        if ($this->owner->XeroAccessToken) {
            $tenants = [];

            try {
                $tenantRecords = XeroFactory::singleton()->getTenants($this->owner->XeroAccessToken);

                foreach ($tenantRecords as $tenant) {
                    $tenants[$tenant->tenantId] = $tenant->tenantName;
                }

                $fields->addFieldsToTab('Root.Xero', [
                    DropdownField::create(
                        'XeroTenantId',
                        'Xero Tenant',
                        $tenants
                    )
                ]);
            } catch (Exception $e) {
                $fields->addFieldsToTab('Root.Xero', LiteralField::create(
                    'XeroError',
                    'An error has occured connecting to xero: '. $e->getMessage()
                ));
            }
        }
    }
}
