<?php

namespace FullscreenInteractive\SilverStripeXero;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;

class XeroController extends Controller
{
    public function index()
    {
        $url = self::join_links(Director::absoluteBaseURL() . 'xero');

        $provider = XeroFactory::singleton()->getProvider();

        if (!isset($_GET['code'])) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => 'openid offline_access email profile accounting.contacts accounting.transactions'
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

            $obj = SiteConfig::current_site_config();
            $obj->XeroAccessToken = $token->getToken();

            $refresh = $token->getRefreshToken();

            if ($refresh) {
                $obj->XeroRefreshToken = $refresh;
            }

            $obj->write();
            $tenants = $provider->getTenants($token);

            foreach ($tenants as $tenant) {
                $id = $tenant->id;

                $obj->XeroTenantId = $id;
                $obj->write();

                return $this->redirect('admin/settings/?doneGlobal=1');
            }
        }
    }
}
