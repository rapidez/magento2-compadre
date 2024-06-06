<?php

namespace Rapidez\Compadre\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

class Fields implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ...$this->getProductStockItemFields(),
        ];
    }

    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $option) {
            $array[$option['value']] = $option['label'] ?? $option['value'];
        }

        return $array;
    }

    public function getProductStockItemFields()
    {
        return [
            ['value' => 'in_stock', 'label' => __('stock_item -> in_stock')],
            ['value' => 'qty', 'label' => __('stock_item -> qty')],
            ['value' => 'min_sale_qty', 'label' => __('stock_item -> min_sale_qty')],
            ['value' => 'max_sale_qty', 'label' => __('stock_item -> max_sale_qty')],
            ['value' => 'qty_increments', 'label' => __('stock_item -> qty_increments')],
            ['value' => 'qty_backordered', 'label' => __('cart_item -> qty_backordered')],
        ];
    }
}
