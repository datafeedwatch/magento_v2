<?php
/**
 * Created by Q-Solutions Studio
 * Date: 31.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Setup;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\TestFramework\Inspection\Exception;

/**
 * Class Uninstall
 * @package DataFeedWatch\Connector\Setup
 */
class Uninstall implements UninstallInterface
{
    /** @var \DataFeedWatch\Connector\Model\Api\User */
    private $apiUser;
    
    /** @var \Magento\Eav\Setup\EavSetup */
    private $eavSetup;
    
    /**
     * @param \Magento\Eav\Setup\EavSetup             $eavSetup
     * @param \DataFeedWatch\Connector\Model\Api\User $apiUser
     */
    public function __construct(
        \Magento\Eav\Setup\EavSetup $eavSetup,
        \DataFeedWatch\Connector\Model\Api\User $apiUser
    ) {
        $this->apiUser  = $apiUser;
        $this->eavSetup = $eavSetup;
    }
    
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if ($context instanceof ModuleContextInterface) {
            $connection = $setup->getConnection();

            $this->apiUser->deleteUserAndRole();

            $attributeCode = 'ignore_datafeedwatch';
            $this->eavSetup->removeAttribute(Product::ENTITY, $attributeCode);

            $table      = $setup->getTable('catalog_eav_attribute');
            $columnName = 'can_configure_inheritance';
            if ($connection->tableColumnExists($table, $columnName)) {
                $setup->startSetup();
                $connection->dropColumn($table, $columnName);
                $setup->endSetup();
            }
            $columnName = 'inheritance';
            if ($connection->tableColumnExists($table, $columnName)) {
                $setup->startSetup();
                $connection->dropColumn($table, $columnName);
                $setup->endSetup();
            }
            $columnName = 'can_configure_import';
            if ($connection->tableColumnExists($table, $columnName)) {
                $setup->startSetup();
                $connection->dropColumn($table, $columnName);
                $setup->endSetup();
            }
            $columnName = 'import_to_dfw';
            if ($connection->tableColumnExists($table, $columnName)) {
                $setup->startSetup();
                $connection->dropColumn($table, $columnName);
                $setup->endSetup();
            }

            $table = $setup->getTable('datafeedwatch_updated_products');
            if ($connection->isTableExists($table)) {
                $setup->startSetup();
                $connection->dropTable($table);
                $setup->endSetup();
            }
        }
    }
}
