<?php
declare(strict_types=1);

namespace Shabeeb\ReactCheckout\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Gives the React checkout what it needs from the server: the shopper's cart ID and a few URLs.
 */
class CheckoutConfig implements ArgumentInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly QuoteIdMaskResource $quoteIdMaskResource,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder,
        private readonly Json $json
    ) {
    }

    /**
     * JSON for the data-config attribute of the React mount element.
     */
    public function getConfigJson(): string
    {
        $store = $this->storeManager->getStore();

        return $this->json->serialize([
            'cartId' => $this->getMaskedCartId(),
            'isLoggedIn' => $this->customerSession->isLoggedIn(),
            'graphqlUrl' => $this->urlBuilder->getDirectUrl('graphql'),
            'storeCode' => $store->getCode(),
            'currency' => $store->getCurrentCurrencyCode(),
            'homeUrl' => $this->urlBuilder->getUrl(''),
            'cartUrl' => $this->urlBuilder->getUrl('checkout/cart'),
            'loginUrl' => $this->urlBuilder->getUrl('customer/account/login'),
        ]);
    }

    /**
     * The cart in the shopper's session is known by its database id; GraphQL needs the "masked" id.
     * Carts made on the storefront (not through GraphQL) have no masked id yet, so create one.
     */
    private function getMaskedCartId(): string
    {
        $quoteId = (int) $this->checkoutSession->getQuoteId();
        if (!$quoteId) {
            return '';
        }

        $maskedId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
        if ($maskedId === '') {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->setQuoteId($quoteId);
            $this->quoteIdMaskResource->save($quoteIdMask);
            $maskedId = (string) $quoteIdMask->getMaskedId();
        }

        return $maskedId;
    }
}
