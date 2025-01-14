<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use \Google_Client;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class OAuthSignIn extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'oauthsignin';
        $this->author = 'Adam Mańko';
        $this->version = '1.0';
        $this->need_instance = false;
        
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = 'OAuth2 Sign In Module';
        $this->description = 'Module provides users sign in with Google or Apple';
        $this->confirmUninstall = '';
    
        $this->ps_versions_compliancy = [
            "min" => "8.0",
            "max" => _PS_VERSION_
        ];
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayCustomerLoginFormAfter')
            && $this->registerHook('header');
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $route = $this->get('router')->generate('o_auth_sign_in');
        Tools::redirectAdmin($route);
    }  
    
    public function renderWidget($hookName, array $configuration)
    {
        if (!$this->isCached('module:oauthsignin/views/templates/hook/displayAfterLoginForm.tpl', 
            $this->getCacheId())) {

            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }
        
        return $this->fetch('module:oauthsignin/views/templates/hook/displayAfterLoginForm.tpl', $this->getCacheId());

    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $clientId = Configuration::get('OAUTH_GOOGLE_CLIENT_ID');
        $clientSecret = Configuration::get('OAUTH_GOOGLE_CLIENT_SECRET');
        //funkcja getModuleLink wymusza protokół https, to chyba spowoduje potencjalne błędy jeśli strona nie ma ssl
        $redirectUrl = $this->context->link->getModuleLink('oauthsignin', 'googlecallback', [], true);

        // 2) Creating Google_Client object
        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUrl);
        $client->addScope('email');
        $client->addScope('profile');

        // 3) Generating Authorization URL
        $googleLoginUrl = $client->createAuthUrl();

        return [
            'name' => 'Adam',
            'google_login_url' => $googleLoginUrl
        ];
    }

    public function hookDisplayCustomerLoginFormAfter($params)
    {
        return $this->renderWidget('displayCustomerLoginFormAfter', $params);
    }

    public function hookHeader($params)
    {
        //$isFbEnabled = Configuration::get('OAUTH_FACEBOOK_ENABLED');
        //if (!$isFbEnabled) {
        //    return;
        // }

        $fbAppId = Configuration::get('OAUTH_FACEBOOK_CLIENT_ID');
        $fbApiVersion = 'v21.0';

        

        $this->context->controller->registerStylesheet(
            'oauthsignin-style',
            'modules/' . $this->name . '/views/css/oauth.css',
            [ 'media' => 'all', 'priority' => 150 ]
        );

        $this->context->controller->registerJavascript(
            'facebook-authentication',
            'modules/' . $this->name . '/views/js/fboauth.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'fbAppId' => $fbAppId,
            'fbApiVersion' => $fbApiVersion
        ]);
    }
}
