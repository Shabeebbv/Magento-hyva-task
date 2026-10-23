<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Controller\Item;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Shabeeb\GuestWishlist\Model\WishlistManager;

/**
 * Remove an item from the guest wish list. Customers use the native wishlist page.
 */
class Remove implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly RedirectInterface $redirect,
        private readonly ManagerInterface $messageManager,
        private readonly CustomerSession $customerSession,
        private readonly WishlistManager $wishlistManager
    ) {
    }

    public function execute(): Redirect
    {
        $result = $this->redirectFactory->create();
        if ($this->customerSession->isLoggedIn()) {
            return $result->setPath('wishlist');
        }

        $this->wishlistManager->removeForGuest((int)$this->request->getParam('product'))
            ? $this->messageManager->addSuccessMessage(__('The item has been removed from your Wish List.'))
            : $this->messageManager->addNoticeMessage(__('That item is not in your Wish List.'));

        return $result->setUrl($this->redirect->getRefererUrl());
    }
}
