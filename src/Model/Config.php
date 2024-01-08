<?php

namespace Rapidez\Compadre\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH = 'rapidez/';

    private array $exposedGraphQlFields;

    public function __construct(protected ScopeConfigInterface $scopeConfig)
    {}

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGraphQlConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . 'graphql/' . $code, $storeId);
    }

    public function getExposedGraphQlFields(): ?array
    {
        return $this->exposedGraphQlFields ??= explode(',', $this->getGraphQlConfig('expose') ?? '');
    }

    public function isFieldExposed($field): bool
    {
        return $this->getExposedGraphQlFields() && in_array($field, $this->getExposedGraphQlFields());
    }
}
