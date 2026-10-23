<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Shabeeb\GuestWishlist\Model\WishlistManager;

/**
 * "shabeeb-wishlist" section for the header link: one count/url for guests and customers.
 */
class Wishlist implements SectionSourceInterface
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly WishlistManager $wishlistManager,
        private readonly UrlInterface $url
    ) {
    }

    public function getSectionData(): array
    {
        if ($this->customerSession->isLoggedIn()) {
            return [
                'count' => $this->wishlistManager->getCustomerCount((int)$this->customerSession->getCustomerId()),
                'url' => $this->url->getUrl('wishlist'),
            ];
        }

        return [
            'count' => $this->wishlistManager->getGuestCount(),
            'url' => $this->url->getUrl('guestwishlist'),
        ];
    }
}
