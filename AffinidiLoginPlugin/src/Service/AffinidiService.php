<?php declare(strict_types=1);

namespace AffinidiLoginPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Affinidi\HybridauthProvider\AffinidiProvider;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class AffinidiService
{
    private $container;
    private $adapter;
    private $accountService;
    private SystemConfigService $systemConfigService;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function __construct(AccountService $accountService, SystemConfigService $systemConfigService)
    {
        $this->accountService = $accountService;
        $this->systemConfigService = $systemConfigService;
        $config = $this->getConfig();
        $this->adapter = new AffinidiProvider($config);

    }

    private function getConfig()
    {
        // Reading from hybridauth config file
        // $config = include __DIR__ . '/../Resources/config/hybridauth.php';
        // $config = $config["affinidi"]

        //$allPluginConfig = $this->systemConfigService->all();
        $issuer = $this->systemConfigService->get('AffinidiLoginPlugin.config.AffinidiIssuer');
        $config = [
            'callback' => $this->systemConfigService->get('core.app.shopId.app_url') . '/affinidi/callback',
            'keys' => [
                'id' => $this->systemConfigService->get('AffinidiLoginPlugin.config.AffinidiClientID'),
                'secret' => $this->systemConfigService->get('AffinidiLoginPlugin.config.AffinidiClientSecret')
            ],
            'endpoints' => [
                'api_base_url' => $issuer,
                'authorize_url' => $issuer . '/oauth2/auth',
                'access_token_url' => $issuer . '/oauth2/token',
            ]
        ];

        //dd($config);

        return $config;

    }

    public function loginAndRegister($context)
    {

        //Customer already logged in
        $customer = $context->getCustomer();
        if ($customer) {
            return $customer;
        }
        //check user is already connected to SSO
        $isConnected = $this->adapter->isConnected();
        if ($isConnected) {
            //logout and try connecting again
            $this->logout();
        }

        //Initiates login or does code-exchange flow if callback url has code
        $this->adapter->authenticate();
        //below code executes only when its callback url, otherwise it redirects to SSO login page

        //Get user profile from the ID Token
        $userProfile = $this->adapter->getUserProfile();
        //dd($userProfile);

        //Creates customer in shopware
        $customer = $this->registerCustomer($userProfile, $context);
        //dd($customer);

        return $customer;
    }

    public function loginCustomer(CustomerEntity $customer, SalesChannelContext $context)
    {
        $token = $this->accountService->loginById($customer->getId(), $context);
        //dd($token);
        return new ContextTokenResponse($token);
    }

    public function logout()
    {
        $this->adapter->disconnect();
    }


    private function registerCustomer($userProfile, $context): CustomerEntity
    {
        $customerRepository = $this->container->get('customer.repository');
        $email = $userProfile->email;

        // Check if customer already exists
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $existingCustomer = $customerRepository->search($criteria, $context->getContext())->first();

        if ($existingCustomer) {
            // If the customer already exists, return the existing customer entity
            return $existingCustomer;
        }

        $customerId = Uuid::randomHex();
        $customerNumber = $userProfile->identifier;
        $addressId = Uuid::randomHex();

        $customerData = [
            'id' => $customerId,
            'salesChannelId' => $context->getSalesChannel()->getId(),
            'customerNumber' => $customerNumber,
            'email' => $email,
            'firstName' => $userProfile->firstName,
            'lastName' => $userProfile->lastName,
            'password' => Uuid::randomHex(),
            'salutationId' => $this->getSalutationId($context->getContext()),
            'defaultPaymentMethodId' => $context->getPaymentMethod()->getId(),
            'groupId' => $this->getCustomerGroupId($context->getContext()),
            'defaultBillingAddress' => [
                'id' => $addressId,
                'customerId' => $customerId,
                'firstName' => $userProfile->firstName,
                'lastName' => $userProfile->lastName,
                'street' => $userProfile->address,
                'zipcode' => $userProfile->zip,
                'city' => $userProfile->city,
                //'countryId' => $userProfile->country, // Need map from country to ISO
                'countryId' => $this->getCountryId($context->getContext()),
            ],
            'defaultShippingAddressId' => $addressId,
            'defaultBillingAddressId' => $addressId,
        ];

        $customerRepository->create([$customerData], $context->getContext());

        return $customerRepository->search($criteria, $context->getContext())->first();

    }

    private function getSalutationId(Context $context): string
    {
        $salutationRepository = $this->container->get('salutation.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', 'mr')); // Or another salutation key
        return $salutationRepository->search($criteria, $context)->first()->getId();
    }

    private function getCountryId(Context $context): string
    {
        $countryRepository = $this->container->get('country.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'IN'));
        return $countryRepository->search($criteria, $context)->first()->getId();
    }

    private function getCustomerGroupId(Context $context): string
    {
        $groupName = 'affinidi';
        $customerGroupRepository = $this->container->get('customer_group.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $groupName));
        $customerGroup = $customerGroupRepository->search($criteria, $context)->first();

        if ($customerGroup === null) {
            $customerGroupId = Uuid::randomHex();
            $newGroupData = [
                'id' => $customerGroupId,
                'name' => $groupName,
            ];

            $customerGroupRepository->create([$newGroupData], $context);
            $customerGroup = $customerGroupRepository->search($criteria, $context)->first();
        }

        return $customerGroup->getId();
    }

}