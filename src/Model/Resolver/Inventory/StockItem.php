<?php

declare(strict_types=1);

namespace Rapidez\Compadre\Model\Resolver\Inventory;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Rapidez\Compadre\Model\Config;

class StockItem implements ResolverInterface {

    public function __construct(
        private StockStatusRepositoryInterface $stockStatusRepository,
        protected Config $config
    )
    {
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var ProductInterface $product */
        $product = $value['model'];
        /** @var StockStatusInterface $stockStatus */
        $stockStatus = $this->stockStatusRepository->get($product->getId());
        /** @var StockItemInterface $stockItem */
        $stockItem = $stockStatus->getStockItem();

        return $this->getResolvedFields($stockItem);
    }

    public function getResolvedFields(StockItemInterface $stockItem): array
    {
        return array_filter(
            [
                'in_stock'          => $stockItem->getIsInStock(),
                'qty'               => $stockItem->getQty(),
                'min_sale_qty'      => $stockItem->getMinSaleQty(),
                'max_sale_qty'      => $stockItem->getMaxSaleQty(),
                'qty_increments'    => $stockItem->getQtyIncrements() === false ? 1 : $stockItem->getQtyIncrements()
            ],
            fn ($key) => $this->config->isFieldExposed($key),
            ARRAY_FILTER_USE_KEY
        );
    }
}
