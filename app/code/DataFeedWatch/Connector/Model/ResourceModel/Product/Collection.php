<?php
/**
 * Created by Q-Solutions Studio
 * Date: 20.09.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model\ResourceModel\Product;

use DataFeedWatch\Connector\Model\ResourceModel\Product\Collection\Db;

class Collection extends Db
{
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    protected function _construct()
    {
        $this->_init('DataFeedWatch\Connector\Model\Product', 'DataFeedWatch\Connector\Model\ResourceModel\Product');
        $this->_initTables();
    }
    
    /**
     * @param bool $joinLeft
     *
     * @return $this
     */
    protected function _productLimitationPrice($joinLeft = true)
    {
        parent::_productLimitationPrice($joinLeft);
        
        return $this;
    }
    
    /**
     * @param array $options
     * @return $this
     */
    public function applyFiltersOnCollection($options)
    {
        $this->apiLogger->debug($options);
        $this->optionsFilters = $options;
        $this->setFlag('has_stock_status_filter', true);
        $this->applyStoreFilter();
        $this->registryHelper->initImportRegistry($this->getStoreId());
        $this->joinVisibilityTable(Db::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, '0');
        $this->joinVisibilityTable(Db::ORIGINAL_VISIBILITY_TABLE_ALIAS, $this->getStoreId());
        $this->fillParentIds();
        $this->addAttributeToSelect('dfw_parent_ids');
        $this->joinParentIdsTable(Db::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE, '0');
        $this->joinParentIdsTable(Db::ORIGINAL_PARENT_IDS_TABLE_ALIAS, $this->getStoreId());
        $this->addRuleDate();
        $this->applyTypeFilter();
        $this->joinQty();
        $this->addFinalPrice();
        $this->addUrlRewrite();
        $this->applyStatusFilter();
        $this->applyUpdatedAtFilter();
        $this->addAttributeToSelect('ignore_datafeedwatch');
        $this->addAttributeToFilter('ignore_datafeedwatch', [['null' => true], ['neq' => 1]], 'left');

        $this->setPage($this->optionsFilters['page'], $this->optionsFilters['per_page']);
        $this->sqlLogger->debug($this->getSelect()->__toString());
        
        return $this;
    }

    /**
     * @return $this
     */
    protected function applyStoreFilter()
    {
        if (isset($this->optionsFilters['store'])) {
            $store          = $this->_storeManager->getStore($this->optionsFilters['store']);
            $StoreColumn    = sprintf('IFNULL(null, %s) as store_id', $store->getId());
            $this->setStoreId($store->getId());
            $this->addStoreFilter($store);
            $this->getSelect()->columns($StoreColumn);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyStatusFilter()
    {
        if (!isset($this->optionsFilters['status'])) {
            return $this;
        }

        if ($this->registryHelper->isStatusAttributeInheritable()) {
            $this->buildFilterStatusCondition();
            $this->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS, $this->getStoreId())
                 ->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0')
                 ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS, $this->getStoreId())
                 ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0');
            $this->getSelect()->where($this->filterStatusCondition . ' = ?', $this->optionsFilters['status']);
        } else {
            $this->addAttributeToFilter('status', $this->optionsFilters['status']);
        }

        return $this;
    }

    public function fillParentIds()
    {
        $this->storeManager->setCurrentStore(\Magento\Store\Model\Store::ADMIN_CODE);

        $collection = clone $this->productCollection;
        foreach ($collection as $product) {
            $parentIds = $this->typeConfigurable->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                $product->setDfwParentIds(current($parentIds));
                $product->getResource()->saveAttribute($product, 'dfw_parent_ids');
            }
        }
        $this->storeManager->setCurrentStore($this->getStoreId());

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyTypeFilter()
    {
        if (isset($this->optionsFilters['type'])) {
            $this->addAttributeToFilter('type_id', ['in' => $this->optionsFilters['type']]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function applyUpdatedAtFilter()
    {
        if (!isset($this->optionsFilters['from_date'])) {
            return $this;
        }

        $this->getSelect()->where($this->ruleDateSelect . ' >= ?', $this->optionsFilters['from_date']);

        return $this;
    }
    
    /**
     * @return $this
     */
    public function applyInheritanceLogic()
    {
        $this->addParentData();
        foreach ($this->getItems() as $product) {
            $parent = $product->getParent();
            if (!empty($parent)) {
                $product->getParentAttributes();
            }
        }
        
        return $this;
    }

    /**
     * @return $this
     */
    protected function addParentData()
    {
        $parentCollection = $this->getParentProductsCollection();
        $parentCollection = $parentCollection->getItems();
        foreach ($this->getItems() as $product) {
            $parentId = $product->getParentId();
            $parentId = explode(',', $parentId);
            if (is_array($parentId)) {
                $parentId = current($parentId);
            }

            if (empty($parentId) || !isset($parentCollection[$parentId])) {
                continue;
            }
            $product->setParent($parentCollection[$parentId]);
        }

        return $this;
    }
    
    /**
     * @return mixed
     */
    protected function getParentProductsCollection()
    {
        $parentCollection = clone $this;
        $parentCollection->_reset();
        $parentCollection->addAttributeToSelect('*')
            ->addUrlRewrite()
            ->joinQty()
            ->addFinalPrice();
        $store = $this->storeManager->getStore($this->optionsFilters['store']);
        $StoreColumn    = sprintf('IFNULL(null, %s) as store_id', $store->getId());
        $parentCollection->setStoreId($store->getId());
        $parentCollection->addStoreFilter($store);
        $parentCollection->getSelect()->columns($StoreColumn);
        $parentCollection->getSelect()->joinLeft(
            [
                self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS =>
                    $this->_resource->getTableName('catalog_product_super_attribute'),
            ],
            sprintf('%s.product_id = e.entity_id', self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS),
            [
                'super_attribute_ids' =>
                    sprintf('GROUP_CONCAT(DISTINCT %s.attribute_id)', self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS),
            ]
        );

        $parentCollection->getSelect()->joinRight(
            [self::PARENT_RELATIONS_TABLE_ALIAS => $this->_resource->getTableName('catalog_product_relation')],
            sprintf('%s.parent_id = e.entity_id', self::PARENT_RELATIONS_TABLE_ALIAS),
            ['parent_id' => sprintf('%s.parent_id', self::PARENT_RELATIONS_TABLE_ALIAS)]
        )->group('e.entity_id');

        return $parentCollection;
    }
}
