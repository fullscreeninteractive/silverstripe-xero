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
     * @var \Calcinai\OAuth2\Client\XeroTenant[]
     */
    protected $tenants = [];

    /**
     * Setup
     */
    public function setupApplication()
    {
        $provider = $this->getProvider();
        $config = SiteConfig::current_site_config();
        $refresh = !$config->XeroTokenRefreshExpires || $config->dbObject('XeroTokenRefreshExpires')->InPast();

        if ($refresh) {
            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $config->XeroRefreshToken
            ]);

            $accessToken = $newAccessToken->getToken();
            $refreshToken = $newAccessToken->getRefreshToken();

            $config->XeroRefreshToken = $refreshToken;
            $config->XeroAccessToken = $accessToken;
            $config->XeroTokenRefreshExpires = $newAccessToken->getExpires();

            $this->tenants = $provider->getTenants($newAccessToken);

            $config->XeroTenants = serialize($this->tenants);
            $config->write();
        } else {
            $accessToken = $config->XeroAccessToken;

            $this->tenants = unserialize($config->XeroTenants);
        }

        $this->application = new \XeroPHP\Application(
            $accessToken,
            $config->XeroTenantId
        );
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

    /**
     * @return \Calcinai\OAuth2\Client\XeroTenant[]
     */
    public function getTenants()
    {
        if (!$this->application) {
            $this->setupApplication();
        }

        return $this->tenants;
    }
}
