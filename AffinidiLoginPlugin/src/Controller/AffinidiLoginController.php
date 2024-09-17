<?php declare(strict_types=1);

namespace AffinidiLoginPlugin\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use AffinidiLoginPlugin\Service\AffinidiService;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class AffinidiLoginController extends StorefrontController
{
    private GenericPageLoader $pageLoader;
    private $affinidiService;

    public function __construct(GenericPageLoader $pageLoader, AffinidiService $affinidiService)
    {
        $this->pageLoader = $pageLoader;
        $this->affinidiService = $affinidiService;
    }

    #[Route(path: '/affinidi/login', name: 'frontend.affinidi.login', methods: ['GET'])]
    public function login(Request $request, SalesChannelContext $context): Response
    {
        $customer = $this->affinidiService->loginAndRegister($context);
        //dd($customer);

        $this->affinidiService->loginCustomer($customer, $context);

        return $this->redirectToRoute('frontend.account.home.page');
    }


    #[Route(path: '/affinidi/logout', name: 'frontend.affinidi.logout', methods: ['GET'])]
    public function logout(Request $request)
    {
        $this->affinidiService->logout();
        return new RedirectResponse('/account/login');
    }

    #[Route(path: '/affinidi/callback', name: 'frontend.affinidi.callback', methods: ['GET'])]
    public function callback(Request $request, SalesChannelContext $context): Response
    {
        $customer = $this->affinidiService->loginAndRegister($context);
        if ($customer) {
            $this->affinidiService->loginCustomer($customer, $context);
            return $this->redirectToRoute('frontend.account.home.page');
        }
        return new RedirectResponse('/account/login');
    }
}
