<?php

namespace FullscreenInteractive\SilverStripeXero;

use SilverStripe\Core\Environment;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;

class XeroSiteConfigExtension extends DataExtension
{
    private static $db = [
        'XeroTenantId' => 'Varchar(200)',
        'XeroAccessToken' => 'Varchar(200)',
        'XeroRefreshToken' => 'Varchar(200)'
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
                    '<a href="%s">Link to a xero account</a>',
                    XeroFactory::singleton()->getRedirectUri()
                ))
        ]);

        if ($this->owner->XeroAccessToken) {
            $provider = XeroFactory::singleton()->getProvider();
            $tenants = [];

            foreach ($provider->getTenants($this->owner->XeroAccessToken) as $tenant) {
                $tenants[$tenant->id] = $tenant->name;
            }

            $fields->addFieldsToTab('Root.Xero', [
                DropdownField::create(
                    'XeroTenantId',
                    'XeroTenantId',
                    $tenants
                )
            ]);
        }
    }
}
