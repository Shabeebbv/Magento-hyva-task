<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Setup\Patch\Data;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Visual demo: show the locally saved sofa photo as the sample product's image on the listing and product pages.
 * Photo by Max Vakhtbovych, https://www.pexels.com/photo/7018400/ (Pexels license).
 * The original bed images stay in the gallery, disabled, so this can be reverted by re-enabling them.
 * Skipped when the photo is not present in pub/media.
 */
class UseSofaDemoImage implements DataPatchInterface
{
    private const SKU = 'SF-BED-K180';
    private const SOURCE = 'catalog/product/shabeeb/sofa-pexels-7018400.jpg';
    private const ROLES = ['image', 'small_image', 'thumbnail'];

    public function __construct(
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Filesystem $filesystem
    ) {
    }

    public function apply(): self
    {
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$media->isFile(self::SOURCE)) {
            return $this;
        }

        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function () use ($media): void {
            $this->storeManager->setCurrentStore(0);
            $product = $this->productRepository->get(self::SKU, true, 0);
            $product->setStoreId(0);

            $gallery = $product->getMediaGallery();
            foreach ($gallery['images'] ?? [] as $key => $image) {
                if (str_contains((string)$image['file'], 'sofa-pexels-7018400')) {
                    return;
                }
                $gallery['images'][$key]['disabled'] = 1;
            }
            $product->setMediaGallery($gallery);
            $product->addImageToMediaGallery($media->getAbsolutePath(self::SOURCE), self::ROLES, false, false);
            $this->productRepository->save($product);
        });

        return $this;
    }

    public static function getDependencies(): array
    {
        return [RefreshStorefrontContent::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
