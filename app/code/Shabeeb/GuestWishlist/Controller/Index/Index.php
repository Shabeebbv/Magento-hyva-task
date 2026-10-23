<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Guest wish list page; signed-in customers are sent to the native wishlist.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly CustomerSession $customerSession
    ) {
    }

    public function execute(): ResultInterface
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('wishlist');
        }

        return $this->pageFactory->create();
    }
}
