<?php

namespace Rapidez\Compadre\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    const XML_PATH = 'rapidez/';

    private array $exposedGraphQlFields;

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected StoreManagerInterface $storeManager
    ) {}

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . 'general/' . $code, $storeId);
    }

    public function getGraphQlConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . 'graphql/' . $code, $storeId);
    }

    public function getLoginAsCustomerConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . 'login_as_customer/' . $code, $storeId);
    }

    public function getExposedGraphQlFields(): ?array
    {
        return $this->exposedGraphQlFields ??= explode(',', $this->getGraphQlConfig('expose') ?? '');
    }

    public function isFieldExposed($field): bool
    {
        return $this->getExposedGraphQlFields() && in_array($field, $this->getExposedGraphQlFields());
    }

    public function getRapidezUrl($storeId = null): string
    {
        if (($rapidezUrl = $this->getGeneralConfig('rapidez_url', $storeId))) {
            return $rapidezUrl;
        }

        /** @var \Magento\Store\Model\Store $store */
        $store = ($storeId ? $this->storeManager->getStore($storeId) : $this->storeManager->getDefaultStoreView());

        return $store->getBaseUrl();
    }
}
