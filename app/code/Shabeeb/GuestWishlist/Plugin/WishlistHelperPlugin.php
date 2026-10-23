<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Plugin;

use Magento\Framework\UrlInterface;
use Magento\Wishlist\Helper\Data;

/**
 * Point "Add to Wish List" buttons at guestwishlist/item/add instead of wishlist/index/add,
 * which requires login. The posted data (product, uenc) is kept unchanged.
 */
class WishlistHelperPlugin
{
    public function __construct(
        private readonly UrlInterface $url
    ) {
    }

    public function afterGetAddParams(Data $subject, string $result): string
    {
        $params = json_decode($result, true);
        if (!is_array($params) || !isset($params['action'])) {
            return $result;
        }
        $params['action'] = $this->url->getUrl('guestwishlist/item/add');

        return json_encode($params);
    }
}
