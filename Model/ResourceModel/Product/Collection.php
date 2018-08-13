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

/**
 * Class Collection
 * @package DataFeedWatch\Connector\Model\ResourceModel\Product
 */
class Collection extends Db
{
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    public function _construct()
    {
        $this->_init(
            \DataFeedWatch\Connector\Model\Product::class,
            \Magento\Catalog\Model\ResourceModel\Product::class
        );
        $this->_initTables();
    }
    
    /**
     * @param bool $joinLeft
     *
     * @return $this
     */
    public function _productLimitationPrice($joinLeft = true)
    {
        parent::_productLimitationPrice($joinLeft);
        
        return $this;
    }

    /**
     * @param $options
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Select_Exception
     */
    public function applyFiltersOnCollection($options)
    {
        $this->optionsFilters = $options;
        
        $this->setFlag('has_stock_status_filter', true);
//        $this->joinRelationTable();
        $this->applyStoreFilter();
        $this->registryHelper->initImportRegistry($this->getStoreId());
//        $this->joinVisibilityTable(Db::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, '0');
//        $this->joinVisibilityTable(Db::ORIGINAL_VISIBILITY_TABLE_ALIAS, $this->getStoreId());
        $this->addRuleDate();
        $this->joinQty();
        $this->addFinalPrice();
        $this->addUrlRewrite();
        $this->applyStatusFilter();
        $this->applyUpdatedAtFilter();
        $this->applyTypeFilter();
        $this->addAttributeToSelect('status');
        $this->addAttributeToSelect('price');
        $this->addAttributeToSelect('special_price');
        $this->addAttributeToFilter('ignore_datafeedwatch',
            [
                ['null' => true],
                ['neq' => 1]
            ],
            'left'
        );

        $this->setPage($this->optionsFilters['page'], $this->optionsFilters['per_page']);
        var_dump($this->getSelect()->assemble());
        
        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function applyStoreFilter()
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
     * @throws \Zend_Db_Select_Exception
     */
    public function applyStatusFilter()
    {
        if (!isset($this->optionsFilters['status'])) {
            return $this;
        }
//
//        if ($this->registryHelper->isStatusAttributeInheritable()) {
//            $this->buildFilterStatusCondition();
//            $this->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS, $this->getStoreId())
//                 ->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0')
//                 ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS, $this->getStoreId())
//                 ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0');
//            $this->getSelect()->where($this->filterStatusCondition . ' = ?', $this->optionsFilters['status']);
//        } else {
            $this->addAttributeToFilter('status', $this->optionsFilters['status']);
//        }

        return $this;
    }

    /**
     * @return $this
     */
    public function applyTypeFilter()
    {
        if (isset($this->optionsFilters['type'])) {
            $this->addAttributeToFilter('type_id', ['in' => $this->optionsFilters['type']]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function applyUpdatedAtFilter()
    {
        if (!isset($this->optionsFilters['updated_at'])) {
            return $this;
        }

        $this->getSelect()->where($this->ruleDateSelect . ' >= ?', $this->optionsFilters['updated_at']);

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * @param $parentProductCollection
     * @param $parentId
     * @return null
     */
    private function isParentProductSet($parentProductCollection, $parentId)
    {
        $parentProductSet = null;

        foreach ($parentProductCollection as $parentProduct) {
            if ($parentId == $parentProduct->getId()) {
                $parentProductSet = $parentProduct;
                break;
            }
        }

        return $parentProductSet;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addParentData()
    {
        /** @var  $parentCollection */
        $parentCollection = $this->getParentProductsCollection();
        $parentCollection->setPage(1, null);

        /** @var  $parentCollection */
        $parentCollection = $parentCollection->getItems();

        foreach ($this->getItems() as $product) {
            $parentId = (int)$product->getParentId();
            $parentId = explode(',', $parentId);
            if (is_array($parentId)) {
                $parentId = current($parentId);
            }
            $parentId = !is_numeric($parentId) ? 0 : (string)$parentId;
            $parentProduct = $this->isParentProductSet($parentCollection, $parentId);

            if (empty($parentId) || !$parentProduct) {
                continue;
            }
            $product->setParent($parentProduct);
        }

        return $this;
    }

    /**
     * @return Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getParentProductsCollection()
    {
        $parentCollection = clone $this;
        $parentCollection->_reset();
        $parentCollection->addAttributeToSelect('*')
            ->addUrlRewrite()
            ->joinQty()
            ->addFinalPrice();
        $store = $this->_storeManager->getStore($this->optionsFilters['store']);
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
