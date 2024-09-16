<?php

namespace Rapidez\Compadre\Model\Resolver\Quote\Data;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\GraphQl\Model\Query\Context;

class SalesRuleLabel implements ResolverInterface
{
    public function __construct(
        protected RuleRepositoryInterface $ruleRepository
    )
    {}

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var Item */
        $cartItem = $value['model'];

        $labels = [];

        foreach (explode(',', (string) $cartItem->getAppliedRuleIds()) as $key=>$ruleId) {
            /** @var RuleInterface $rule */
            $rule = $this->ruleRepository->getById((int) $ruleId);
            
            // @phpstan-ignore-next-line
            $store = $context->getExtensionAttributes()->getStore();
            $storeId = $store->getId();

            foreach($rule->getStoreLabels() as $storeLabel) {
                if ((int) $storeLabel->getStoreId() === (int) $storeId) {
                    $storeLabel = $storeLabel->getStoreLabel();
                    break;
                }
            }
            
            $labels[] = [
                'name' => $rule->getName(),
                'description' => $rule->getDescription(),
                'discount_amount' => $rule->getDiscountAmount(),
                'from_date' => $rule->getFromDate(),
                'to_date' => $rule->getToDate(),
                'store_label' => $storeLabel ?? null,
            ];
        }

        return $labels;
    }
}