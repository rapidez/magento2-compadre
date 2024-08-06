<?php

declare(strict_types=1);

namespace Rapidez\Compadre\Model\Resolver\Inventory;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;

class ResolveStockItem
{

    public function __construct(
        private StockStatusRepositoryInterface $stockStatusRepository
    ) {}

    public function resolve(ProductInterface $product)
    {
        /** @var StockStatusInterface $stockStatus */
        $stockStatus = $this->stockStatusRepository->get($product->getId());
        /** @var StockItemInterface $stockItem */
        $stockItem = $stockStatus->getStockItem();

        return [
            'in_stock' => $stockItem->getIsInStock(),
            'qty' => $stockItem->getQty(),
            'min_sale_qty' => $stockItem->getMinSaleQty(),
            'max_sale_qty' => $stockItem->getMaxSaleQty(),
            'qty_increments' => $stockItem->getQtyIncrements() === false ? 1 : $stockItem->getQtyIncrements()
        ];
    }
}
