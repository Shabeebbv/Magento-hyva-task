<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\DuplicateException;

/**
 * Persistence for guest wishlist rows (table shabeeb_guest_wishlist_item).
 */
class GuestItemStorage
{
    private const TABLE = 'shabeeb_guest_wishlist_item';

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @return array<int, array{product_id: string, store_id: string, qty: string}> newest first
     */
    public function getItems(string $token, ?int $storeId = null): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), ['product_id', 'store_id', 'qty'])
            ->where('guest_token = ?', $token)
            ->order(['added_at DESC', 'item_id DESC']);
        if ($storeId !== null) {
            $select->where('store_id = ?', $storeId);
        }

        return $connection->fetchAll($select);
    }

    public function count(string $token, int $storeId): int
    {
        return count($this->getItems($token, $storeId));
    }

    /**
     * @return bool false when the product was already in the guest wishlist
     */
    public function add(string $token, int $productId, int $storeId, float $qty): bool
    {
        try {
            $this->resource->getConnection()->insert(
                $this->resource->getTableName(self::TABLE),
                ['guest_token' => $token, 'product_id' => $productId, 'store_id' => $storeId, 'qty' => $qty]
            );
        } catch (DuplicateException) {
            return false;
        }

        return true;
    }

    public function remove(string $token, int $productId, int $storeId): int
    {
        return $this->resource->getConnection()->delete(
            $this->resource->getTableName(self::TABLE),
            ['guest_token = ?' => $token, 'product_id = ?' => $productId, 'store_id = ?' => $storeId]
        );
    }

    public function deleteAll(string $token): int
    {
        return $this->resource->getConnection()->delete(
            $this->resource->getTableName(self::TABLE),
            ['guest_token = ?' => $token]
        );
    }
}
