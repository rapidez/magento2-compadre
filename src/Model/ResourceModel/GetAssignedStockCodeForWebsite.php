<?php

namespace Rapidez\Compadre\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class GetAssignedStockCodeForWebsite
{
    public function __construct(
        private ResourceConnection $resourceConnection
    ) {
    }

    public function execute(int $stockId): string|null
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock');

        $select = $connection->select()
            ->from($tableName, ['name'])
            ->where('stock_id = ?', $stockId);

        $result = $connection->fetchCol($select);

        if (count($result) === 0) {
            return null;
        }

        return reset($result);
    }
}