<?php
declare(strict_types=1);

namespace PrestaShop\Module\OAuthSignIn\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Context;

/**
 * Configuration is used to save data to configuration table and retrieve from it.
 */
final class OAuthSignInDataConfiguration implements DataConfigurationInterface
{
    public const OAUTH_GOOGLE_ENABLED = 'OAUTH_GOOGLE_ENABLED';
    public const OAUTH_GOOGLE_CLIENT_ID = 'OAUTH_GOOGLE_CLIENT_ID';
    public const OAUTH_GOOGLE_CLIENT_SECRET = 'OAUTH_GOOGLE_CLIENT_SECRET';
    public const OAUTH_GOOGLE_BUTTON_SHAPE = 'OAUTH_GOOGLE_BUTTON_SHAPE';
    public const OAUTH_GOOGLE_BUTTON_THEME = 'OAUTH_GOOGLE_BUTTON_THEME';

    public const OAUTH_FACEBOOK_ENABLED = 'OAUTH_FACEBOOK_ENABLED';
    public const OAUTH_FACEBOOK_APP_ID = 'OAUTH_FACEBOOK_APP_ID';
    public const OAUTH_FACEBOOK_API_VERSION = 'OAUTH_FACEBOOK_API_VERSION';
    public const OAUTH_FACEBOOK_BUTTON_SHAPE = 'OAUTH_FACEBOOK_BUTTON_SHAPE';

    public const CONFIG_MAXLENGTH = 255;
    public const CONFIG_API_MAXLENGTH = 10;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ConfigurationInterface $configuration, TranslatorInterface $translator)
    {
        $this->configuration = $configuration;
        $this->translator = $translator;
    }

    public function getConfiguration(): array
    {
        $return = [];
        
        //generating redirect link viewed in module back office form field
        $context = Context::getContext();
        $googleRedirectUrl = $context->link->getModuleLink('oauthsignin', 'googlecallback', [], true);
        $facebookRedirectUrl = $context->link->getModuleLink('oauthsignin', 'facebookcallback', [], true);

        $return = [
            'enable_google' => $this->configuration->get(self::OAUTH_GOOGLE_ENABLED),
            'google_client_id' => $this->configuration->get(self::OAUTH_GOOGLE_CLIENT_ID),
            'google_client_secret' => $this->configuration->get(self::OAUTH_GOOGLE_CLIENT_SECRET),
            'google_btn_shape' => $this->configuration->get(self::OAUTH_GOOGLE_BUTTON_SHAPE),
            'google_btn_theme' => $this->configuration->get(self::OAUTH_GOOGLE_BUTTON_THEME),
            'google_redirect_url' => $googleRedirectUrl,
            'enable_facebook' => $this->configuration->get(self::OAUTH_FACEBOOK_ENABLED),
            'fb_app_id' => $this->configuration->get(self::OAUTH_FACEBOOK_APP_ID),
            'fb_api_version' => $this->configuration->get(self::OAUTH_FACEBOOK_API_VERSION),
            'fb_btn_shape' => $this->configuration->get(self::OAUTH_FACEBOOK_BUTTON_SHAPE),
            'fb_redirect_url' => $facebookRedirectUrl
        ];

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = $this->validateConfiguration($configuration);

        if (!empty($errors)) {
            return $errors;
        }

        $enableGoogle = isset($configuration['enable_google']) && (bool)($configuration['enable_google']);
        $enableFacebook = isset($configuration['enable_facebook']) && (bool)($configuration['enable_facebook']);

        // Google
        $this->configuration->set(self::OAUTH_GOOGLE_ENABLED, $enableGoogle);
        if ($enableGoogle){
            $googleClientId = trim($configuration['google_client_id'] ?? '');
            $googleClientSecret = trim($configuration['google_client_secret'] ?? '');
            $googleBtnShape = $configuration['google_btn_shape'];
            $googleBtnTheme = $configuration['google_btn_theme'];

            $this->configuration->set(self::OAUTH_GOOGLE_CLIENT_ID, $googleClientId);
            $this->configuration->set(self::OAUTH_GOOGLE_CLIENT_SECRET, $googleClientSecret);
            $this->configuration->set(self::OAUTH_GOOGLE_BUTTON_SHAPE, $googleBtnShape);
            $this->configuration->set(self::OAUTH_GOOGLE_BUTTON_THEME, $googleBtnTheme);
        }
            
        // Facebook
        $this->configuration->set(self::OAUTH_FACEBOOK_ENABLED, $enableFacebook);
        if ($enableFacebook) {
            $fbAppId = trim($configuration['fb_app_id'] ?? '');
            $fbApiVersion = $configuration['fb_api_version'];
            $fbBtnShape = $configuration['fb_btn_shape'];

            $this->configuration->set(self::OAUTH_FACEBOOK_APP_ID, $fbAppId);
            $this->configuration->set(self::OAUTH_FACEBOOK_API_VERSION, $fbApiVersion);
            $this->configuration->set(self::OAUTH_FACEBOOK_BUTTON_SHAPE, $fbBtnShape);
        }

        return [];
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): array
    {
        $errors = [];

        $enableGoogle = isset($configuration['enable_google']) && (bool)($configuration['enable_google']);
        $enableFacebook = isset($configuration['enable_facebook']) && (bool)($configuration['enable_facebook']);

        if ($enableGoogle) {
            $googleClientId = trim($configuration['google_client_id'] ?? '');
            $googleClientSecret = trim($configuration['google_client_secret'] ?? '');

            if (empty($googleClientId)) {
                $errors[] = $this->translator->trans('Google Client ID cannot be empty', [], 'Modules.Oauthsignin.Admin');
            } elseif (strlen($googleClientId) > self::CONFIG_MAXLENGTH) {
                $errors[] = $this->translator->trans('Google Client ID is too long', [], 'Modules.Oauthsignin.Admin');
            }

            if (empty($googleClientSecret)) {
                $errors[] = $this->translator->trans('Google Client Secret cannot be empty', [], 'Modules.Oauthsignin.Admin');
            } elseif (strlen($googleClientSecret) > self::CONFIG_MAXLENGTH) {
                $errors[] = $this->translator->trans('Google Client Secret is too long', [], 'Modules.Oauthsignin.Admin');
            }
        }

        if ($enableFacebook) {
            $fbAppId = trim($configuration['fb_app_id'] ?? '');
            $fbApiVersion = trim($configuration['fb_api_version'] ?? '');

            if (empty($fbAppId)) {
                $errors[] = $this->translator->trans('Facebook App ID cannot be empty', [], 'Modules.Oauthsignin.Admin');
            } elseif (strlen($fbAppId) > self::CONFIG_MAXLENGTH) {
                $errors[] = $this->translator->trans('Facebook App ID is too long', [], 'Modules.Oauthsignin.Admin');
            }

            if (empty($fbApiVersion)) {
                $errors[] = $this->translator->trans('Facebook API version cannot be empty', [], 'Modules.Oauthsignin.Admin');
            } elseif (strlen($fbApiVersion) > self::CONFIG_API_MAXLENGTH) {
                $errors[] = $this->translator->trans('Facebook API version is too long', [], 'Modules.Oauthsignin.Admin');
            }
        }

        return $errors;
    }

}
