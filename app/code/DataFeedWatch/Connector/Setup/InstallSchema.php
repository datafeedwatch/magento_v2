<?php
/**
 * Created by Q-Solutions Studio
 * Date: 22.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema
    implements InstallSchemaInterface
{
    /** @var \Magento\Framework\Setup\SchemaSetupInterface */
    protected $setup;
    
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;
    
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup,
                            \Magento\Framework\Setup\ModuleContextInterface $context) {
        $this->setup      = $setup;
        $this->connection = $this->setup->getConnection();
        
        $this->extendsCatalogEavAttributeTable();
        $this->createUpdatedProductsTable();
    }
    
    protected function extendsCatalogEavAttributeTable() {
        $table = $this->setup->getTable('catalog_eav_attribute');
        
        $this->createCanConfigureInheritanceColumn($table);
        $this->createInheritanceColumn($table);
        $this->createCanConfigureImportColumn($table);
        $this->createImportToDfwColumn($table);
    }
    
    /**
     * @param string $table
     */
    protected function createCanConfigureInheritanceColumn($table) {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Can configure inheritance field? 1 - YES, 0 - NO',
        ];
        $this->addColumn($table, 'can_configure_inheritance', $properties);
    }
    
    /**
     * @param       $table
     * @param       $columnName
     * @param array $properties
     */
    protected function addColumn($table, $columnName, array $properties) {
        if (!$this->connection->tableColumnExists($table, $columnName)) {
            $this->setup->startSetup();
            $this->connection->addColumn($table, $columnName, $properties);
            $this->setup->endSetup();
        }
    }
    
    /**
     * @param string $table
     */
    protected function createInheritanceColumn($table) {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Inheritance: 1 - Child, 2 - Parent, 3 - Child Then Parent',
        ];
        $this->addColumn($table, 'inheritance', $properties);
    }
    
    /**
     * @param string $table
     */
    protected function createCanConfigureImportColumn($table) {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Can configure import field? 1 - YES, 0 - NO',
        ];
        $this->addColumn($table, 'can_configure_import', $properties);
    }
    
    /**
     * @param string $table
     */
    protected function createImportToDfwColumn($table) {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Should import attribute? 1 - YES, 0 - NO',
        ];
        $this->addColumn($table, 'import_to_dfw', $properties);
    }
    
    protected function createUpdatedProductsTable() {
        $table = $this->setup->getTable('datafeedwatch_updated_products');
        if (!$this->connection->isTableExists($table)) {
            $this->setup->startSetup();
            $updatedProductsTable = $this->connection->newTable($table)
                                                     ->addColumn('dfw_prod_id',
                                                         Table::TYPE_INTEGER,
                                                         null,
                                                         [
                                                             'identity' => true,
                                                             'unsigned' => true,
                                                             'nullable' => false,
                                                             'primary'  => true,
                                                         ], 'Product ID')
                                                     ->addColumn('updated_at',
                                                         Table::TYPE_TIMESTAMP,
                                                         null,
                                                         [
                                                             'nullable' => true,
                                                         ],
                                                         'Updated At')
                                                     ->setComment('Updated Products Table');
            $this->connection->createTable($updatedProductsTable);
            $this->setup->endSetup();
        }
    }
}
