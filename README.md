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

```
XERO_CLIENT_ID='123'
XERO_CLIENT_SECRET='123'
```

Once those API keys are available, a new tab under the `Settings` admin will
appear for connecting to Xero. Follow the prompts to link the selected account
to your Silverstripe website.

## Interacting with the API

```
/** @var \XeroPHP\Application **/
$app = XeroFactory::singleton()->getApplication();
```

Integrating with the API is done via https://github.com/calcinai/xero-php.
Consult that page for further information for creating invoices etc.

