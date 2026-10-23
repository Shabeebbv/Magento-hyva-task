<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Storefront redesign content: product title and description, Furniture category intro and layout.
 * URLs (serene-bed.html, furniture.html) and the product price are left unchanged. The homepage stays Magento's default.
 */
class RefreshStorefrontContent implements DataPatchInterface
{
    private const SKU = 'SF-BED-K180';
    private const PRODUCT_NAME = 'Serene Hydraulic Bed 180x200cm';

    private const SHORT_DESCRIPTION = 'A king-size upholstered bed with a channel-tufted headboard and a gas-lift base '
        . 'that opens smoothly to reveal generous storage beneath the mattress.';

    private const DESCRIPTION = <<<'HTML'
<div class="shabeeb-description">
<p>The Serene bed pairs a softly padded, channel-tufted headboard with a clean, low-profile frame in warm sand linen. Lift the mattress platform and twin gas pistons hold it open, giving you a full-size storage space for bedding, cases and seasonal clothes.</p>
<h3>Highlights</h3>
<ul>
<li>Hydraulic lift-up base with full-length storage</li>
<li>Channel-tufted headboard with deep padding</li>
<li>Sprung slatted platform for even mattress support</li>
<li>Linen-blend upholstery in a warm sand tone</li>
</ul>
<h3>Specifications</h3>
<dl class="shabeeb-specs">
<div><dt>Mattress size</dt><dd>180 × 200 cm (not included)</dd></div>
<div><dt>Overall size</dt><dd>W 192 × L 215 × H 120 cm</dd></div>
<div><dt>Storage</dt><dd>Gas-lift base, full length</dd></div>
<div><dt>Upholstery</dt><dd>Linen blend, sand</dd></div>
<div><dt>Frame</dt><dd>Engineered wood with solid pine supports</dd></div>
<div><dt>Care</dt><dd>Vacuum regularly; spot clean with a damp cloth</dd></div>
</dl>
</div>
HTML;

    private const CATEGORY_DESCRIPTION = '<p>Considered pieces for calm, modern homes.</p>';

    public function __construct(
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $this->storeManager->setCurrentStore(0);

            $product = $this->productRepository->get(self::SKU, true, 0);
            $product->setStoreId(0)
                ->setName(self::PRODUCT_NAME)
                ->setShortDescription(self::SHORT_DESCRIPTION)
                ->setDescription(self::DESCRIPTION)
                ->setMetaTitle(self::PRODUCT_NAME);
            $this->productRepository->save($product);

            $categoryId = $this->categoryFactory->create()->getCollection()
                ->setStoreId(0)
                ->addAttributeToFilter('url_key', 'furniture')
                ->getFirstItem()
                ->getId();
            if ($categoryId) {
                $category = $this->categoryRepository->get((int)$categoryId, 0);
                $category->setStoreId(0)
                    ->setDescription(self::CATEGORY_DESCRIPTION)
                    ->setPageLayout('1column');
                $this->categoryRepository->save($category);
            }
        });

        return $this;
    }

    public static function getDependencies(): array
    {
        return [SimplifySampleCatalog::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
