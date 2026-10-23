<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Shabeeb\GuestWishlist\Model\GuestToken;
use Shabeeb\GuestWishlist\Model\WishlistManager;

/**
 * On customer_login, merge the guest wish list into the customer's wishlist.
 * Errors are logged and never block the login; guest rows are kept for a retry.
 */
class MergeGuestWishlist implements ObserverInterface
{
    public function __construct(
        private readonly GuestToken $guestToken,
        private readonly WishlistManager $wishlistManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $token = $this->guestToken->get();
        $customer = $observer->getEvent()->getCustomer();
        if ($token === null || !$customer || !$customer->getId()) {
            return;
        }

        try {
            $result = $this->wishlistManager->mergeGuestIntoCustomer((int)$customer->getId(), $token);
            $this->guestToken->delete();
            $this->logger->info(sprintf(
                'Shabeeb_GuestWishlist: merged guest wish list into customer %d (added %d, skipped %d)',
                $customer->getId(),
                $result['added'],
                $result['skipped']
            ));
        } catch (\Exception $e) {
            $this->logger->error('Shabeeb_GuestWishlist: merge failed: ' . $e->getMessage());
        }
    }
}
