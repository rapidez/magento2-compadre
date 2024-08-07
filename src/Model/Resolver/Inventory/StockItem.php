<?php

declare(strict_types=1);

namespace Rapidez\Compadre\Model\Resolver\Inventory;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Rapidez\Compadre\Model\Config;

class StockItem implements ResolverInterface
{
    public function __construct(
        protected ResolveStockItem $resolveStockItem,
        protected ObjectManagerInterface $objectManager,
        protected Config $config
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var ProductInterface $product */
        $product = $value['model'];

        /** @var array $stockItem */
        $stockItem = $this->resolveStockItem->resolve($product);

        if (interface_exists('Magento\InventoryApi\Api\SourceItemRepositoryInterface')) {
            $websiteCode = $context->getExtensionAttributes()->getStore()->getWebsite()->getCode();
            $resolve = $this->objectManager->get('Rapidez\Compadre\Model\Resolver\Inventory\ResolveMsiStockItem');
            $msiStock = $resolve->resolve($product, $websiteCode);

            $stockItem['qty'] = $msiStock->getQuantity();
            $stockItem['in_stock'] = $msiStock->getStatus();
        }

        return array_filter(
            $stockItem,
            fn ($key) => $this->config->isFieldExposed($key),
            ARRAY_FILTER_USE_KEY
        );
    }
}
