<?php

namespace FullscreenInteractive\SilverStripeXero;

use Calcinai\OAuth2\Client\Provider\Xero;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;
use XeroPHP\Application;

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


    public function setupApplication()
    {
        $config = SiteConfig::current_site_config();
        $accessToken = $config->XeroAccessToken;

        $this->tenants = unserialize($config->XeroTenants);

        $this->application = new \XeroPHP\Application(
            $accessToken,
            $config->XeroTenantId
        );
    }


    public function renewToken()
    {
        $provider = $this->getProvider();
        $config = SiteConfig::current_site_config();

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

        return $config->XeroTokenRefreshExpires;
    }


    public function getApplication(): ?Application
    {
        if (!$this->application) {
            $this->setupApplication();
        }

        return $this->application;
    }


    public function getProvider(): Xero
    {
        return new \Calcinai\OAuth2\Client\Provider\Xero([
            'clientId'          => Environment::getEnv('XERO_CLIENT_ID'),
            'clientSecret'      => Environment::getEnv('XERO_CLIENT_SECRET'),
            'redirectUri'       => $this->getRedirectUri()
        ]);
    }


    public function getRedirectUri(): string
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            'connectXero'
        );
    }


    public function getUnlinkUri(): string
    {
        $token = SecurityToken::inst()->getSecurityID();

        return Controller::join_links(
            Director::absoluteBaseURL(),
            'connectXero/unlink?SecurityID='. $token
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
