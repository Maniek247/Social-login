<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class OAuthSignIn extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'oauthsignin';
        $this->author = 'Adam Mańko';
        $this->version = '1.1.0';
        $this->tab = 'front_office_features';
        $this->need_instance = false;
        
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Social login', [], 'Modules.Oauthsignin.Oauthsignin');
        $this->description = $this->trans(
        'Module enabling quick login via popular providers like Google or Facebook',
        [],
        'Modules.Oauthsignin.Oauthsignin');
        $this->confirmUninstall = '';
    
        $this->ps_versions_compliancy = [
            "min" => '8.0.0.0',
            "max" => '9.99.99'
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
        return parent::uninstall()
            && Configuration::deleteByName('OAUTH_GOOGLE_ENABLED')
            && Configuration::deleteByName('OAUTH_GOOGLE_CLIENT_ID')
            && Configuration::deleteByName('OAUTH_GOOGLE_CLIENT_SECRET')
            && Configuration::deleteByName('OAUTH_GOOGLE_BUTTON_SHAPE')
            && Configuration::deleteByName('OAUTH_GOOGLE_BUTTON_THEME')
            && Configuration::deleteByName('OAUTH_FACEBOOK_ENABLED')
            && Configuration::deleteByName('OAUTH_FACEBOOK_APP_ID')
            && Configuration::deleteByName('OAUTH_FACEBOOK_API_VERSION')
            && Configuration::deleteByName('OAUTH_FACEBOOK_BUTTON_SHAPE');
    }

    /**
     * Redirects to the module's configuration page in back office
     *
     * @return void
     */
    public function getContent()
    {
        $route = $this->get('router')->generate('o_auth_sign_in');
        Tools::redirectAdmin($route);
    }  
    
    /**
     * @return bool
     */
    public function isUsingNewTranslationSystem()
    {
    return true;
    }

    /**
     * Renders the widget containing OAuth login buttons.
     * If the template is not in cache, it assigns the variables
     * from getWidgetVariables() to Smarty.
     *
     * @param string $hookName
     * @param array $configuration
     *
     * @return string The rendered HTML
     */
    public function renderWidget($hookName, array $configuration)
    {
        if (!$this->isCached('module:oauthsignin/views/templates/hook/displayAfterLoginForm.tpl', 
            $this->getCacheId())) {

            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }
        
        return $this->fetch('module:oauthsignin/views/templates/hook/displayAfterLoginForm.tpl', $this->getCacheId());

    }

    /**
     * Retrieves an array of variables required to properly display
     * login buttons in the login form.
     *
     * @param string $hookName
     * @param array $configuration
     *
     * @return array Data used in the .tpl
     */
    public function getWidgetVariables($hookName, array $configuration)
    {
        $googleBtnShape = Configuration::get('OAUTH_GOOGLE_BUTTON_SHAPE');
        $googleBtnTheme = Configuration::get('OAUTH_GOOGLE_BUTTON_THEME');
        $fbBtnShape = Configuration::get('OAUTH_FACEBOOK_BUTTON_SHAPE');

        $enableGoogle = Configuration::get('OAUTH_GOOGLE_ENABLED');
        $enableFacebook = Configuration::get('OAUTH_FACEBOOK_ENABLED');
        $googleBtnText = $this->trans(
        'Sign in with Google',
        [],
        'Modules.Oauthsignin.Oauthsignin');

        $clientId = Configuration::get('OAUTH_GOOGLE_CLIENT_ID');
        $clientSecret = Configuration::get('OAUTH_GOOGLE_CLIENT_SECRET');
        $googleRedirectUrl = $this->context->link->getModuleLink('oauthsignin', 'googlecallback', [], true);

        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($googleRedirectUrl);
        $client->addScope('email');
        $client->addScope('profile');

        // Generating authorization URL linked to Google button
        $googleLoginUrl = $client->createAuthUrl();

        return [
            'google_login_url' => $googleLoginUrl,
            'enable_google' => $enableGoogle,
            'google_btn_text' => $googleBtnText,
            'google_btn_shape' => $googleBtnShape,
            'google_btn_theme' => $googleBtnTheme,
            'enable_facebook' => $enableFacebook,
            'fb_btn_shape' => $fbBtnShape
        ];
    }

    /**
     * Hook triggered after the login form is displayed.
     * Renders the widget containing social login buttons.
     *
     * @param array $params
     *
     * @return string HTML code of the rendered widget
     */
    public function hookDisplayCustomerLoginFormAfter($params)
    {
        return $this->renderWidget('displayCustomerLoginFormAfter', $params);
    }

    /**
     * Hook that registers required assets (JS/CSS) in the header,
     * such as the Facebook SDK and the custom Facebook OAuth script.
     *
     * @return void
     */
    public function hookHeader()
    {
        $fbAppId = Configuration::get('OAUTH_FACEBOOK_APP_ID');
        $fbApiVersion = Configuration::get('OAUTH_FACEBOOK_API_VERSION');
        $fbRedirectUrl = $this->context->link->getModuleLink('oauthsignin', 'facebookcallback', [], true);

        $context = Context::getContext();
        $idLang = $context->language->id;
        $language = new Language($idLang);
        $localLang = $language->getLocale();
        $localLang = str_replace('-', '_', $localLang);

        $this->context->controller->registerStylesheet(
            'oauthsignin-style',
            'modules/' . $this->name . '/views/css/oauth.css',
            [ 'media' => 'all', 'priority' => 150 ]
        );

        $this->context->controller->registerJavascript(
            'facebook-jssdk',
            'https://connect.facebook.net/' . $localLang . '/sdk.js#xfbml=1&version=' . $fbApiVersion . '&appId=' . $fbAppId,
            [
                'server' => 'remote',
                'position' => 'bottom',
                'priority' => 150,
                'attributes' => 'async defer crossorigin="anonymous"',
            ]
        );

        $this->context->controller->registerJavascript(
            'facebook-authentication',
            'modules/' . $this->name . '/views/js/fboauth.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        // variables for javascript
        Media::addJsDef([
            'fbAppId' => $fbAppId,
            'fbApiVersion' => $fbApiVersion,
            'fbRedirectUrl' => $fbRedirectUrl,
            'translateFB'       => [
            'notAuthorized' => $this->trans(
            'User did not authorize the application',
            [],
            'Modules.Oauthsignin.Oauthsignin'),
            'unknownError'  => $this->trans(
            'Unknown error, please try again',
            [],
            'Modules.Oauthsignin.Oauthsignin')]
        ]);
    }
}
