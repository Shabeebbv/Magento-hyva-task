<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Setup\Patch\Data;

use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Puts the "home" CMS page back to Magento's default values (see Magento_Cms CreateDefaultPages).
 */
class RestoreDefaultHomePage implements DataPatchInterface
{
    public function __construct(
        private readonly GetPageByIdentifierInterface $getPageByIdentifier,
        private readonly PageRepositoryInterface $pageRepository
    ) {
    }

    public function apply(): self
    {
        $page = $this->getPageByIdentifier->execute('home', 0);
        $page->setTitle('Home page')
            ->setContentHeading('Home Page')
            ->setPageLayout('1column')
            ->setContent("<p>CMS homepage content goes here.</p>\r\n");
        $this->pageRepository->save($page);

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
