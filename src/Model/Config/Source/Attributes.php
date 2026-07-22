<?php

namespace Rapidez\Compadre\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class Attributes implements ArrayInterface
{
    public function __construct(
        protected CollectionFactory $attributeCollectionFactory
    ) {}

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->attributeCollectionFactory->create();
        $collection->addVisibleFilter();

        foreach ($collection as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel() ?: $attribute->getAttributeCode(),
            ];
        }

        usort($options, fn($a, $b) => strcmp((string) $a['label'], (string) $b['label']));

        return $options;
    }
}
