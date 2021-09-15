<?php

namespace FullscreenInteractive\SilverStripeXero;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\SiteConfig\SiteConfig;

class XeroFactory
{
    use Injectable;

    /**
     * @var \XeroPHP\Application
     */
    protected $application;

    /**
     * Setup
     */
    public function setupApplication()
    {
        $provider = $this->getProvider();
        $config = SiteConfig::current_site_config();

        $newAccessToken = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $config->XeroRefreshToken
        ]);

        $config->XeroAccessToken = $newAccessToken->getToken();

        $refresh = $newAccessToken->getRefreshToken();

        if ($refresh) {
            $config->XeroRefreshToken = $refresh;
        }

        $config->write();
    }

    /**
     * @return \XeroPHP\Application
     */
    public function getApplication()
    {
        if (!$this->application) {
            $this->setupApplication();
        }

        return $this->application;
    }

    /**
     * @return \Calcinai\OAuth2\Client\Provider\Xero
     */
    public function getProvider()
    {
        return new \Calcinai\OAuth2\Client\Provider\Xero([
            'clientId'          => Environment::getEnv('XERO_CLIENT_ID'),
            'clientSecret'      => Environment::getEnv('XERO_CLIENT_SECRET'),
            'redirectUri'       => $this->getRedirectUri()
        ]);
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            'connectXero'
        );
    }
}
