# silverstripe-xero

## Maintainer Contact

* Will Rossiter <will@fullscreen.io>

## Installation

> composer require "fullscreeninteractive/silverstripe-xero"

## Documentation

Provides a lightweight wrapper around `calcinai/xero-php` with additional
Silverstripe support for authenication and connecting applications via oauth.

To setup register a Xero Application and define your clientId and clientSecret
as environment variables.

```yaml
XERO_CLIENT_ID='123'
XERO_CLIENT_SECRET='123'
```

Once those API keys are available, a new tab under the `Settings` admin will
appear for connecting to Xero. Follow the prompts to link the selected account
to your Silverstripe website.

## Renewing the Access Token

Access tokens last 30 days, a scheduled queued job (`RefreshXeroTokenJob`) is
provided which renews the access token on a regular basis.

## Setting up the application in Xero

1. Head to https://developer.xero.com/app/manage/
1. Create a new `Web App`
1. Set the `Redirect URI` to be `https://www.yoursite.com/connectXero`

Note the `connectXero` endpoint in Silverstripe is restricted to `ADMIN` only
users.

## Interacting with the API

```php
/** @var \XeroPHP\Application **/
$app = XeroFactory::singleton()->getApplication();
```

Integrating with the API is done via https://github.com/calcinai/xero-php.
Consult that page for further information for creating invoices etc.

#### Creating a Xero Contact from a Member

```php
<?php

use Psr\Log\LoggerInterface;
use SilverStripe\ORM\DataExtension;
use FullscreenInteractive\SilverStripeXero\XeroFactory;
use SilverStripe\Core\Injector\Injector;

class XeroMemberExtension extends DataExtension
{
    private static $db = [
        'XeroContactID' => 'Varchar'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->makeFieldReadonly('XeroContactID');
    }

    public function getXeroContact()
    {
        try {
            $xero = XeroFactory::singleton()->getApplication();
        } catch (Throwable $e) {
            $xero = null;
        }

        if ($xero) {
            try {
                $contact = null;

                if ($id = $this->owner->XeroContactID) {
                    $contact = $xero->loadByGUID('Accounting\\Contact', $id);
                }

                if (!$contact) {
                    $existing = $xero->load('Accounting\\Contact')
                        ->where('EmailAddress!=null AND EmailAddress.Contains("' . trim($this->owner->Email) . '")')
                        ->execute();

                    if (count($existing) > 1) {
                        $contact = $existing->offsetGet(0);
                    }
                }

                if (!$contact) {
                    // create the record
                    $contact = new \XeroPHP\Models\Accounting\Contact();
                    $contact->setName($this->owner->Name);
                    $contact->setFirstName($this->owner->FirstName);
                    $contact->setLastName($this->owner->Surname);
                    $contact->setEmailAddress($this->owner->Email);

                    try {
                        $xero->save($contact);
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Already assigned to') !== false) {
                            $contact->setName($this->owner->Name . ' ' . date('Y-m-d'));

                            try {
                                $xero->save($contact);
                            } catch (Exception $e) {
                                Injector::inst()->get(LoggerInterface::class)->warning($e);
                            }
                        }
                    }
                }

                return $contact;
            } catch (Exception $e) {
                Injector::inst()->get(LoggerInterface::class)->error($e);
            }
        }
    }
}
```
