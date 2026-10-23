<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Sample furniture catalog: one "Furniture" category and one simple product with original placeholder images.
 * Existing SKUs/categories are left untouched, so the patch is safe to re-run.
 */
class AddSampleCatalog implements DataPatchInterface
{
    private const IMPORT_DIR = 'import/shabeeb';

    public const CATEGORIES = [
        'furniture' => 'Furniture',
    ];

    public const PRODUCTS = [
        ['SF-BED-K180', 'Serene Hydraulic Bed 180x200cm', 'serene-bed', 'furniture', 3599, null,
            'A modern king-size bed with a channel-tufted headboard in warm sand linen and a slatted base included.',
            ['Mattress size 180 × 200 cm (not included)', 'Overall W 192 × L 215 × H 120 cm', 'Sprung slatted base included', 'Linen-blend upholstery']],
    ];

    public function __construct(
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryLinkManagementInterface $categoryLinkManagement,
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Filesystem $filesystem
    ) {
    }

    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $categoryIds = $this->createCategories();
            foreach (self::PRODUCTS as $data) {
                $this->createProduct($data, $categoryIds);
            }
        });

        return $this;
    }

    /**
     * @return array<string, int> url key => category id
     */
    private function createCategories(): array
    {
        $this->storeManager->setCurrentStore(0);
        $rootId = (int)$this->storeManager->getStore(1)->getRootCategoryId();
        $ids = [];
        $position = 1;
        foreach (self::CATEGORIES as $urlKey => $name) {
            $existing = $this->categoryFactory->create()->getCollection()
                ->addAttributeToFilter('url_key', $urlKey)
                ->addAttributeToFilter('parent_id', $rootId)
                ->getFirstItem();
            if ($existing->getId()) {
                $ids[$urlKey] = (int)$existing->getId();
                continue;
            }
            $category = $this->categoryFactory->create()
                ->setName($name)
                ->setUrlKey($urlKey)
                ->setParentId($rootId)
                ->setIsActive(true)
                ->setIncludeInMenu(true)
                ->setIsAnchor(true)
                ->setPosition($position++)
                ->setStoreId(0);
            $category->setPath($this->categoryRepository->get($rootId)->getPath());
            $ids[$urlKey] = (int)$this->categoryRepository->save($category)->getId();
        }

        return $ids;
    }

    private function createProduct(array $data, array $categoryIds): void
    {
        [$sku, $name, $slug, $categoryKey, $price, $specialPrice, $intro, $details] = $data;
        try {
            $this->productRepository->get($sku);
            return;
        } catch (NoSuchEntityException) {
            // create below
        }

        $description = self::buildDescription($intro, $details);

        $product = $this->productFactory->create();
        $product->setSku($sku)
            ->setName($name)
            ->setUrlKey($slug)
            ->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($product->getDefaultAttributeSetId())
            ->setWebsiteIds([(int)$this->storeManager->getWebsite(true)->getId()])
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setPrice($price)
            ->setTaxClassId(0)
            ->setWeight(10)
            ->setShortDescription($intro)
            ->setDescription($description)
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 50, 'is_in_stock' => 1])
            ->setQuantityAndStockStatus(['qty' => 50, 'is_in_stock' => 1])
            ->setStoreId(0);
        if ($specialPrice !== null) {
            $product->setSpecialPrice($specialPrice);
        }

        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $source = dirname(__DIR__, 3) . '/fixtures/images/';
        for ($i = 1; $i <= 3; $i++) {
            $file = sprintf('%s-%d.jpg', $slug, $i);
            $target = self::IMPORT_DIR . '/' . $file;
            $media->writeFile($target, (string)file_get_contents($source . $file));
            $product->addImageToMediaGallery(
                $media->getAbsolutePath($target),
                $i === 1 ? ['image', 'small_image', 'thumbnail'] : null,
                true,
                false
            );
        }

        $saved = $this->productRepository->save($product);
        $this->categoryLinkManagement->assignProductToCategories($saved->getSku(), [$categoryIds[$categoryKey]]);
    }

    public static function buildDescription(string $intro, array $details): string
    {
        return '<p>' . htmlspecialchars($intro) . '</p><ul>'
            . implode('', array_map(static fn (string $d): string => '<li>' . htmlspecialchars($d) . '</li>', $details))
            . '</ul>';
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
