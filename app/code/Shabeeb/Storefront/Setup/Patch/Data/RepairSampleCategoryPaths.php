<?php
declare(strict_types=1);

namespace Shabeeb\Storefront\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * The sample categories were saved with their parent's path ("1/2") instead of "1/2/<id>",
 * which hides them from the category tree and menu. Rewrite the path the same way the core
 * category resource does in _savePath(), only for level-2 children whose path lacks their id.
 */
class RepairSampleCategoryPaths implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('catalog_category_entity');
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['c' => $table], ['entity_id', 'path'])
                ->join(['p' => $table], 'p.entity_id = c.parent_id', ['parent_path' => 'path'])
                ->where('c.level = ?', 2)
        );
        foreach ($rows as $row) {
            $expected = $row['parent_path'] . '/' . $row['entity_id'];
            if ($row['path'] !== $expected) {
                $connection->update($table, ['path' => $expected], ['entity_id = ?' => (int)$row['entity_id']]);
            }
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [AddSampleCatalog::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
