<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Controller\Item;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Shabeeb\GuestWishlist\Model\WishlistManager;

/**
 * Add to wish list for guests (server-side guest list) and customers (native wishlist).
 * Form key validation is applied by the framework to POST actions.
 */
class Add implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly RedirectInterface $redirect,
        private readonly ManagerInterface $messageManager,
        private readonly CustomerSession $customerSession,
        private readonly WishlistManager $wishlistManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Redirect
    {
        $result = $this->redirectFactory->create()->setUrl($this->redirect->getRefererUrl());
        $qty = max(1.0, (float)$this->request->getParam('qty', 1));

        try {
            $product = $this->wishlistManager->getAddableProduct((int)$this->request->getParam('product'));
            $added = $this->customerSession->isLoggedIn()
                ? $this->wishlistManager->addForCustomer((int)$this->customerSession->getCustomerId(), $product, $qty)
                : $this->wishlistManager->addForGuest($product, $qty);
            $name = $product->getName();
            $added
                ? $this->messageManager->addSuccessMessage(__('%1 has been added to your Wish List.', $name))
                : $this->messageManager->addNoticeMessage(__('%1 is already in your Wish List.', $name));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('We can\'t find the product you requested.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('We can\'t add the item to your Wish List right now.'));
        }

        return $result;
    }
}
