<?php

namespace Rapidez\Compadre\Model\Resolver\Quote\Data;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Quote\Model\Quote\Item;

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

        foreach (explode(',', (string) $cartItem->getAppliedRuleIds()) as $ruleId) {
            /** @var RuleInterface $rule */
            $rule = $this->ruleRepository->getById((int) $ruleId);

            $labels[] = $rule->getName();
        }

        return $labels;
    }
}