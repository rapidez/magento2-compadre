<?php

namespace Rapidez\Compadre\Model\Resolver\Inventory;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use Rapidez\Compadre\Model\ResourceModel\GetAssignedStockCodeForWebsite;

class ResolveMsiStockItem
{
    public function __construct(
        private GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private SourceItemRepositoryInterface $sourceItemRepository,
        private GetAssignedStockCodeForWebsite $getAssignedStockCodeForWebsite
    ) {}

    public function resolve(ProductInterface $product, string $storeCode)
    {
        $scopeId = $this->getAssignedStockIdForWebsite->execute($storeCode);

        $sourceCode = $this->getAssignedStockCodeForWebsite->execute($scopeId);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $product->getSku())
            ->addFilter('source_code', $sourceCode)
            ->create();

        $stockItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        return reset($stockItems);
    }
}
