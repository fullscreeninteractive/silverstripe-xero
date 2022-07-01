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

        $unlink = '';

        if ($this->owner->XeroAccessToken) {
            $unlink = sprintf(
                ' or <a href="%s"><small class="alert-danger">Unlink xero account</small></a>',
                XeroFactory::singleton()->getUnlinkUri()
            );
        }

        $token = $this->owner->XeroAccessToken;

        if (!$token) {
            $token = 'No account linked ';
        }

        $fields->addFieldsToTab('Root.Xero', [
            ReadonlyField::create('XeroAccess', 'Access token', substr($token, 0, 20).'....')
                ->setDescription(sprintf(
                    '<a href="%s" target="_blank">Link to a new xero account</a>%s',
                    XeroFactory::singleton()->getRedirectUri(),
                    $unlink
                ))
        ]);

        if ($this->owner->XeroAccessToken) {
            $tenants = [];

            try {
                $tenantRecords = XeroFactory::singleton()->getTenants($this->owner->XeroAccessToken);
                if ($tenantRecords) {
                    foreach ($tenantRecords as $tenant) {
                        $tenants[$tenant->tenantId] = $tenant->tenantName;
                    }
                }

                $fields->addFieldsToTab('Root.Xero', [
                    DropdownField::create(
                        'XeroTenantId',
                        'Xero tenant',
                        $tenants
                    )
                ]);
            } catch (Exception $e) {
                $fields->addFieldsToTab('Root.Xero', LiteralField::create(
                    'XeroError',
                    'An error has occured connecting to xero: '. $e->getMessage()
                ));
            }

            $fields->addFieldsToTab('Root.Xero', [
                ReadonlyField::create(
                    'XeroTokenRefreshExpires',
                    'Xero token expires',
                    $this->owner->XeroTokenRefreshExpires
                )->setDescription('Token will be refreshed automatically when it expires via a queued jobs')
            ]);
        }
    }
}
