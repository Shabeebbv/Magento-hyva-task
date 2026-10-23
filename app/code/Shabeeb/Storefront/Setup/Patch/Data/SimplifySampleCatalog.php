<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Shrinks the first sample catalog (Living Room + Bedroom, 8 products) to the single "Furniture" category
 * and the bed defined in AddSampleCatalog. On a fresh install there is nothing to remove, so it only
 * re-applies the bed's data.
 */
class SimplifySampleCatalog implements DataPatchInterface
{
    private const REMOVED_SKUS = ['SF-SOFA-3S', 'SF-ARMC-01', 'SF-CTBL-01', 'SF-TVU-180', 'SF-NSTD-01', 'SF-WARD-3D', 'SF-DRSR-6D'];

    public function __construct(
        private readonly State $appState,
        private readonly Registry $registry,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryLinkManagementInterface $categoryLinkManagement,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $this->storeManager->setCurrentStore(0);
            $this->registry->register('isSecureArea', true, true);

            $furnitureId = $this->getFurnitureCategoryId();
            $this->updateProduct($furnitureId);
            foreach (self::REMOVED_SKUS as $sku) {
                try {
                    $this->productRepository->deleteById($sku);
                } catch (NoSuchEntityException) {
                    // already gone
                }
            }
            $bedroom = $this->findRootChild('bedroom');
            if ($bedroom !== null) {
                $this->categoryRepository->deleteByIdentifier($bedroom);
            }

            $this->registry->unregister('isSecureArea');
        });

        return $this;
    }

    /**
     * Reuses the old "Living Room" category (renamed) when present, so its id and URL rewrites are updated in place.
     */
    private function getFurnitureCategoryId(): int
    {
        $id = $this->findRootChild('furniture') ?? $this->findRootChild('living-room');
        $name = AddSampleCatalog::CATEGORIES['furniture'];
        $category = $this->categoryRepository->get($id, 0);
        if ($category->getName() !== $name || $category->getUrlKey() !== 'furniture') {
            $category->setStoreId(0)
                ->setName($name)
                ->setUrlKey('furniture')
                ->setPosition(1);
            $this->categoryRepository->save($category);
        }

        return $id;
    }

    private function updateProduct(int $categoryId): void
    {
        [$sku, $name, , , $price, , $intro, $details] = AddSampleCatalog::PRODUCTS[0];
        $product = $this->productRepository->get($sku, true, 0);
        $product->setStoreId(0)
            ->setName($name)
            ->setPrice($price)
            ->setSpecialPrice(null)
            ->setShortDescription($intro)
            ->setDescription(AddSampleCatalog::buildDescription($intro, $details));
        $this->productRepository->save($product);
        $this->categoryLinkManagement->assignProductToCategories($sku, [$categoryId]);
    }

    private function findRootChild(string $urlKey): ?int
    {
        $rootId = (int)$this->storeManager->getStore(1)->getRootCategoryId();
        $id = $this->categoryFactory->create()->getCollection()
            ->setStoreId(0)
            ->addAttributeToFilter('url_key', $urlKey)
            ->addAttributeToFilter('parent_id', $rootId)
            ->getFirstItem()
            ->getId();

        return $id ? (int)$id : null;
    }

    public static function getDependencies(): array
    {
        return [AddSampleCatalog::class, RepairSampleCategoryPaths::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
