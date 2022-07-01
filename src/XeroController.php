<?php

namespace FullscreenInteractive\SilverStripeXero;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;

class XeroController extends Controller
{
    private static $scope = 'openid offline_access email profile accounting.contacts accounting.contacts.read accounting.transactions accounting.transactions.read';

    private static $allowed_actions = [
        'index',
        'unlink'
    ];

    public function index()
    {
        $provider = XeroFactory::singleton()->getProvider();

        if (!isset($_GET['code'])) {
            $scope = $this->config()->get('scope');

            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => $scope
            ]);

            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authUrl);
            exit;
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);

            exit('Invalid state');
        } else {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            if (!Permission::check('ADMIN')) {
                return $this->httpError(401);
            }

            $config = SiteConfig::current_site_config();
            $config->XeroAccessToken = $token->getToken();

            $refresh = $token->getRefreshToken();

            if ($refresh) {
                $config->XeroRefreshToken = $refresh;
                $config->XeroTokenRefreshExpires = $token->getExpires();
            } else {
                $config->XeroRefreshToken = null;
                $config->XeroTokenRefreshExpires = null;
            }

            $tenants = $provider->getTenants($token);

            $config->XeroTenants = serialize($tenants);
            $config->write();

            if ($tenants) {
                $id = null;

                foreach ($tenants as $tenant) {
                    $id = $tenant->tenantId;

                    if (!$config->XeroTenantId) {
                        $config->XeroTenantId = $id;
                        $config->write();

                        return $this->redirect('admin/settings/?connectedXeroTo='. $id. '#Root_Xero');
                    }
                }

                if ($id) {
                    $config->XeroTenantId = $id;
                    $config->write();

                    return $this->redirect('admin/settings/?connectedXeroTo='. $id . '#Root_Xero');
                }
            }

            return $this->redirect('admin/settings/#Root_Xero');
        }
    }


    public function unlink()
    {
        if (!SecurityToken::inst()->checkRequest($this->request)) {
            return $this->httpError(400);
        }

        $config = SiteConfig::current_site_config();
        $config->XeroTenantId = null;
        $config->XeroTenants = null;
        $config->XeroTokenRefreshExpires = null;
        $config->XeroRefreshToken = null;
        $config->XeroAccessToken = null;
        $config->write();

        return $this->redirect('admin/settings/#Root_Xero');
    }
}
