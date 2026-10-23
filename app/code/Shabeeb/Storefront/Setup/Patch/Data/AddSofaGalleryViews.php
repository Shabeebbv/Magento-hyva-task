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
 * Visual demo: adds three more views of the same sofa (same Pexels shoot by Max Vakhtbovych, Pexels license)
 * after the main photo set by UseSofaDemoImage, so the product page gallery has four images.
 * https://www.pexels.com/photo/7018401/, /7018389/, /7018406/
 * Files missing from pub/media are skipped; files already in the gallery are not added twice.
 */
class AddSofaGalleryViews implements DataPatchInterface
{
    private const SKU = 'SF-BED-K180';
    private const SOURCES = [
        'catalog/product/shabeeb/sofa-pexels-7018401.jpg',
        'catalog/product/shabeeb/sofa-pexels-7018389.jpg',
        'catalog/product/shabeeb/sofa-pexels-7018406.jpg',
    ];

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
        $sources = array_filter(self::SOURCES, [$media, 'isFile']);
        if (!$sources) {
            return $this;
        }

        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function () use ($media, $sources): void {
            $this->storeManager->setCurrentStore(0);
            $product = $this->productRepository->get(self::SKU, true, 0);
            $product->setStoreId(0);

            $existing = array_map(
                static fn (array $image): string => (string)$image['file'],
                $product->getMediaGallery('images') ?? []
            );
            $added = false;
            foreach ($sources as $source) {
                $name = pathinfo($source, PATHINFO_FILENAME);
                if (array_filter($existing, static fn (string $file): bool => str_contains($file, $name))) {
                    continue;
                }
                $product->addImageToMediaGallery($media->getAbsolutePath($source), null, false, false);
                $added = true;
            }
            if ($added) {
                $this->productRepository->save($product);
            }
        });

        return $this;
    }

    public static function getDependencies(): array
    {
        return [UseSofaDemoImage::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
