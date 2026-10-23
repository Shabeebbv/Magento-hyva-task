<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Psr\Log\LoggerInterface;

/**
 * Wishlist operations shared by the controllers and the login merge.
 * A product appears at most once per wishlist: re-adding an existing product is a no-op.
 */
class WishlistManager
{
    public const GUEST_ITEM_LIMIT = 100;

    public function __construct(
        private readonly GuestToken $guestToken,
        private readonly GuestItemStorage $storage,
        private readonly WishlistFactory $wishlistFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Load a product the shopper may add: enabled, visible and assigned to the current website.
     *
     * @throws NoSuchEntityException
     */
    public function getAddableProduct(int $productId): ProductInterface
    {
        $store = $this->storeManager->getStore();
        $product = $this->productRepository->getById($productId, false, (int)$store->getId());
        if ((int)$product->getStatus() !== Status::STATUS_ENABLED
            || !$product->isVisibleInSiteVisibility()
            || !in_array((int)$store->getWebsiteId(), array_map('intval', $product->getWebsiteIds()), true)
        ) {
            throw NoSuchEntityException::singleField('id', $productId);
        }

        return $product;
    }

    /**
     * @return bool false when the product was already in the customer's wishlist
     * @throws LocalizedException
     */
    public function addForCustomer(int $customerId, ProductInterface $product, float $qty): bool
    {
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        if ($this->containsProduct($wishlist, (int)$product->getId())) {
            return false;
        }
        $result = $wishlist->addNewItem($product, new DataObject(['qty' => $qty]));
        if (is_string($result)) {
            throw new LocalizedException(__($result));
        }
        $wishlist->save();

        return true;
    }

    /**
     * @return bool false when the product was already in the guest wishlist
     * @throws LocalizedException
     */
    public function addForGuest(ProductInterface $product, float $qty): bool
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $token = $this->guestToken->getOrCreate();
        if ($this->storage->count($token, $storeId) >= self::GUEST_ITEM_LIMIT) {
            throw new LocalizedException(__('Your wish list is full. Sign in to save more items.'));
        }

        return $this->storage->add($token, (int)$product->getId(), $storeId, $qty);
    }

    public function removeForGuest(int $productId): bool
    {
        $token = $this->guestToken->get();
        if ($token === null) {
            return false;
        }

        return $this->storage->remove($token, $productId, (int)$this->storeManager->getStore()->getId()) > 0;
    }

    public function getGuestCount(): int
    {
        $token = $this->guestToken->get();

        return $token === null ? 0 : $this->storage->count($token, (int)$this->storeManager->getStore()->getId());
    }

    /**
     * Move guest items into the customer's wishlist, skipping products already there.
     * Guest rows are deleted only after every row was processed, so a failure can be retried
     * on the next login without creating duplicates.
     *
     * @return array{added: int, skipped: int}
     */
    public function mergeGuestIntoCustomer(int $customerId, string $token): array
    {
        $rows = $this->storage->getItems($token);
        $result = ['added' => 0, 'skipped' => 0];
        if (!$rows) {
            return $result;
        }

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        foreach ($rows as $row) {
            $productId = (int)$row['product_id'];
            if ($this->containsProduct($wishlist, $productId)) {
                $result['skipped']++;
                continue;
            }
            try {
                $product = $this->productRepository->getById($productId, false, (int)$row['store_id']);
                $added = $wishlist->addNewItem($product, new DataObject(['qty' => (float)$row['qty']]));
                if (is_string($added)) {
                    throw new LocalizedException(__($added));
                }
                $result['added']++;
            } catch (\Exception $e) {
                $result['skipped']++;
                $this->logger->warning(
                    'Shabeeb_GuestWishlist: could not merge product ' . $productId . ': ' . $e->getMessage()
                );
            }
        }
        $wishlist->save();
        $this->storage->deleteAll($token);

        return $result;
    }

    public function getCustomerCount(int $customerId): int
    {
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId);

        return $wishlist->getId() ? (int)$wishlist->getItemCollection()->getSize() : 0;
    }

    /**
     * Checks the table directly: the item collection hides out-of-stock or invisible items,
     * which would let a duplicate row slip through.
     */
    private function containsProduct(Wishlist $wishlist, int $productId): bool
    {
        if (!$wishlist->getId()) {
            return false;
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('wishlist_item'), ['wishlist_item_id'])
            ->where('wishlist_id = ?', (int)$wishlist->getId())
            ->where('product_id = ?', $productId)
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }
}
