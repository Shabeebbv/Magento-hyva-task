<?php
declare(strict_types=1);

namespace Shabeeb\GuestWishlist\Block;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Framework\Pricing\Render as PriceRender;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Shabeeb\GuestWishlist\Model\GuestItemStorage;
use Shabeeb\GuestWishlist\Model\GuestToken;

/**
 * Lists the products in the current guest's wish list.
 */
class GuestList extends Template
{
    /** @var Product[]|null */
    private ?array $products = null;

    public function __construct(
        Context $context,
        private readonly GuestToken $guestToken,
        private readonly GuestItemStorage $storage,
        private readonly CollectionFactory $collectionFactory,
        private readonly Visibility $visibility,
        private readonly ImageHelper $imageHelper,
        private readonly CartHelper $cartHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return Product[] newest first
     */
    public function getProducts(): array
    {
        if ($this->products !== null) {
            return $this->products;
        }
        $this->products = [];
        $token = $this->guestToken->get();
        if ($token === null) {
            return $this->products;
        }
        $ids = array_map(
            'intval',
            array_column($this->storage->getItems($token, (int)$this->_storeManager->getStore()->getId()), 'product_id')
        );
        if (!$ids) {
            return $this->products;
        }
        $collection = $this->collectionFactory->create()
            ->addIdFilter($ids)
            ->addStoreFilter()
            ->addAttributeToSelect(['name', 'small_image', 'url_key'])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->setVisibility($this->visibility->getVisibleInSiteIds())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();
        $byId = [];
        foreach ($collection as $product) {
            $byId[(int)$product->getId()] = $product;
        }
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $this->products[] = $byId[$id];
            }
        }

        return $this->products;
    }

    public function getImageUrl(Product $product): string
    {
        return $this->imageHelper->init($product, 'category_page_grid')->getUrl();
    }

    public function getPriceHtml(Product $product): string
    {
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender instanceof PriceRender) {
            return '';
        }

        return $priceRender->render('final_price', $product, ['zone' => PriceRender::ZONE_ITEM_LIST]);
    }

    public function getAddToCartUrl(Product $product): string
    {
        return $this->cartHelper->getAddUrl($product);
    }

    public function getRemoveUrl(): string
    {
        return $this->getUrl('guestwishlist/item/remove');
    }
}
