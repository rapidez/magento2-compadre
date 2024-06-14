<?php
declare(strict_types=1);

namespace Rapidez\Compadre\Model\Resolver\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Catalog\Api\Data\ProductInterface;
use Rapidez\Compadre\Model\Config;

class Backorder implements ResolverInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepositoryInterface,
        protected Config $config
    ) {
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!$this->config->isFieldExposed('qty_backordered')) {
            return null;
        }

        /** @var Item $cartItem */
        $cartItem = $value['model'];

        // If used, grab the first configuration of a configurable item and use that
        $configuredItems = $cartItem['qty_options'] ?? [];
        if ($configuredItems) {
            /** @var Option $item */
            $item = reset($configuredItems);
            $backorderStatus = $item?->getProduct()?->getExtensionAttributes()?->getStockItem()?->getBackorders() ?? 0;
            if ($backorderStatus != 2) {
                return 0;
            }
            return $item['backorders'] ?? 0;
        }

        /** @var ProductInterface $product */
        $product = $this->productRepositoryInterface->get($cartItem->getSku());
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        if (!$stockItem || $stockItem->getBackorders() != 2) {
            return 0;
        }

        $difference = $cartItem->getQty() - $stockItem->getQty();
        if ($difference <= 0) {
            return 0;
        }

        return $difference;
    }
}
