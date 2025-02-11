<?php

declare(strict_types=1);

namespace PrestaShop\Module\OAuthSignIn\Controllers;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuthSignInConfigurationController extends FrameworkBundleAdminController
{
    public function index(Request $request): Response
    {
        $textFormDataHandler = $this->get('prestashop.module.oauthsignin.form.oauthsignin_form_data_handler');

        $textForm = $textFormDataHandler->getForm();
        $textForm->handleRequest($request);

        if ($textForm->isSubmitted() && $textForm->isValid()) {
            $errors = $textFormDataHandler->save($textForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update', 'Modules.Oauthsignin.Admin', []));

                return $this->redirectToRoute('o_auth_sign_in');
            }

            $this->flashErrors($errors);
        }

        return $this->render('@Modules/oauthsignin/views/templates/admin/form.html.twig', [
            'oAuthSignInConfigurationForm' => $textForm->createView()
        ]);
    }
}
