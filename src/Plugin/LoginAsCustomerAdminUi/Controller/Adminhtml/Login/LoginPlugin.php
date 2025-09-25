<?php

declare(strict_types=1);

namespace Rapidez\Compadre\Plugin\LoginAsCustomerAdminUi\Controller\Adminhtml\Login;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\LoginAsCustomerAdminUi\Controller\Adminhtml\Login\Login;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;
use Magento\LoginAsCustomerApi\Api\Data\AuthenticationDataInterface;
use Magento\LoginAsCustomerApi\Api\Data\AuthenticationDataInterfaceFactory;
use Magento\LoginAsCustomerApi\Api\DeleteAuthenticationDataForUserInterface;
use Magento\LoginAsCustomerApi\Api\IsLoginAsCustomerEnabledForCustomerInterface;
use Magento\LoginAsCustomerApi\Api\SaveAuthenticationDataInterface;
use Magento\LoginAsCustomerApi\Api\SetLoggedAsCustomerCustomerIdInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rapidez\Compadre\Model\Config;

class LoginPlugin
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_LoginAsCustomer::login';

    /**
     * @var RequestInterface
     */
    private $_request;

    /**
     * @var Share
     */
    private $share;

    /**
     * @var SetLoggedAsCustomerCustomerIdInterface
     */
    private $setLoggedAsCustomerCustomerId;

    /**
     * @var IsLoginAsCustomerEnabledForCustomerInterface
     */
    private $isLoginAsCustomerEnabled;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param ConfigInterface $config
     * @param AuthenticationDataInterfaceFactory $authenticationDataFactory
     * @param SaveAuthenticationDataInterface $saveAuthenticationData
     * @param DeleteAuthenticationDataForUserInterface $deleteAuthenticationDataForUser
     * @param Url $url
     * @param Share|null $share
     * @param SetLoggedAsCustomerCustomerIdInterface|null $setLoggedAsCustomerCustomerId
     * @param IsLoginAsCustomerEnabledForCustomerInterface|null $isLoginAsCustomerEnabled
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        protected Context $context,
        protected Session $authSession,
        protected StoreManagerInterface $storeManager,
        protected CustomerRepositoryInterface $customerRepository,
        protected ConfigInterface $config,
        protected AuthenticationDataInterfaceFactory $authenticationDataFactory,
        protected SaveAuthenticationDataInterface $saveAuthenticationData,
        protected DeleteAuthenticationDataForUserInterface $deleteAuthenticationDataForUser,
        protected resultFactory $resultFactory,
        protected Url $url,
        protected Config $rapidezConfig,
        protected TokenFactory $tokenFactory,
        protected ClientFactory $clientFactory,
        ?Share $share = null,
        ?SetLoggedAsCustomerCustomerIdInterface $setLoggedAsCustomerCustomerId = null,
        ?IsLoginAsCustomerEnabledForCustomerInterface $isLoginAsCustomerEnabled = null,
    ) {
        $this->_request = $context->getRequest();
        $this->share = $share ?? ObjectManager::getInstance()->get(Share::class);
        $this->setLoggedAsCustomerCustomerId = $setLoggedAsCustomerCustomerId
            ?? ObjectManager::getInstance()->get(SetLoggedAsCustomerCustomerIdInterface::class);
        $this->isLoginAsCustomerEnabled = $isLoginAsCustomerEnabled
            ?? ObjectManager::getInstance()->get(IsLoginAsCustomerEnabledForCustomerInterface::class);
    }

    /**
     * Login as customer
     *
     * @return ResultInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function aroundExecute(Login $subject, callable $proceed): ResultInterface
    {
        $messages = [];

        $customerId = (int)$this->_request->getParam('customer_id');
        if (!$customerId) {
            $customerId = (int)$this->_request->getParam('entity_id');
        }

        $isLoginAsCustomerEnabled = $this->isLoginAsCustomerEnabled->execute($customerId);
        if (!$isLoginAsCustomerEnabled->isEnabled()) {
            foreach ($isLoginAsCustomerEnabled->getMessages() as $message) {
                $messages[] = __($message);
            }

            return $this->prepareJsonResult($messages);
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            $messages[] = __('Customer with this ID no longer exists.');
            return $this->prepareJsonResult($messages);
        }

        if ($this->config->isStoreManualChoiceEnabled()) {
            $storeId = (int)$this->_request->getParam('store_id');
            if (empty($storeId)) {
                $messages[] = __('Please select a Store to login in.');
                return $this->prepareJsonResult($messages);
            }
        } elseif ($this->share->isGlobalScope()) {
            $storeId = (int)$this->storeManager->getDefaultStoreView()->getId();
        } else {
            $storeId = (int)$customer->getStoreId();
        }

        if (!$this->rapidezConfig->getLoginAsCustomerConfig('override', $storeId)) {
            return $proceed();
        }

        $adminUser = $this->authSession->getUser();
        $userId = (int)$adminUser->getId();

        /** @var AuthenticationDataInterface $authenticationData */
        $authenticationData = $this->authenticationDataFactory->create(
            [
                'customerId' => $customerId,
                'adminId' => $userId,
                'extensionAttributes' => null,
            ]
        );

        $this->deleteAuthenticationDataForUser->execute($userId);
        $this->saveAuthenticationData->execute($authenticationData);
        $this->setLoggedAsCustomerCustomerId->execute($customerId);

        $redirectUrl = $this->getLoginProceedRedirectUrl($customerId, $storeId);

        if (!$redirectUrl) {
            return $proceed();
        }

        return $this->prepareJsonResult($messages, $redirectUrl);
    }

    /**
     * Get login proceed redirect url
     *
     * @param string $secret
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    private function getLoginProceedRedirectUrl(int $customerId, int $storeId): ?string
    {
        $token = $this->tokenFactory->create()->createCustomerToken($customerId)->getToken();

        /** @var Client $client */
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => $this->rapidezConfig->getRapidezUrl($storeId)
        ]]);

        $response = $client->request(
            'POST',
            '/api/get-login-as-customer-url',
            [
                'json' => [
                    'token' => $token,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]
        );

        /** @var ?string $url */
        $url = json_decode((string)$response->getBody(), false)?->url;

        return $url;
    }

    /**
     * Prepare JSON result
     *
     * @param array $messages
     * @param string|null $redirectUrl
     * @return JsonResult
     */
    private function prepareJsonResult(array $messages, ?string $redirectUrl = null)
    {
        /** @var JsonResult $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $jsonResult->setData([
            'redirectUrl' => $redirectUrl,
            'messages' => $messages,
        ]);

        return $jsonResult;
    }
}
