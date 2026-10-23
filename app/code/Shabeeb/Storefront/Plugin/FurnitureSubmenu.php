<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Plugin;

use Magento\Framework\Data\Tree\Node;
use Magento\Theme\Block\Html\Topmenu;

/**
 * Adds a "Sofa" entry under the Furniture menu item that opens the existing Furniture listing,
 * so the dropdown works without creating another category.
 */
class FurnitureSubmenu
{
    private const PARENT_URL_SUFFIX = '/furniture.html';
    private const NODE_ID = 'shabeeb-furniture-sofa';

    public function beforeGetHtml(Topmenu $subject, $outermostClass = '', $childrenWrapClass = '', $limit = 0): void
    {
        foreach ($subject->getMenu()->getChildren() as $node) {
            $url = (string)$node->getUrl();
            if (!str_ends_with($url, self::PARENT_URL_SUFFIX) || $node->getChildren()->searchById(self::NODE_ID)) {
                continue;
            }
            $node->addChild(new Node(
                [
                    'id' => self::NODE_ID,
                    'name' => __('Sofa'),
                    'url' => $url,
                    'has_active' => false,
                    'is_active' => false,
                    'is_category' => false,
                    'is_parent_active' => false,
                ],
                'id',
                $node->getTree(),
                $node
            ));
        }
    }
}
