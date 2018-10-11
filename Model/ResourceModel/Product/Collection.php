<?php
/**
 * Created by Q-Solutions Studio
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Jakub Winkler <jwinkler@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model\ResourceModel\Product;

use DataFeedWatch\Connector\Helper\Registry;
use DataFeedWatch\Connector\Model\ResourceModel\Product\Collection\Db;

/**
 * Class Collection
 * @package DataFeedWatch\Connector\Model\ResourceModel\Product
 */
class Collection extends Db
{
    /** @var Registry  */
    public $registryHelper;

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
     * @return $this|Db
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
     */
    public function applyFiltersOnCollection($options)
    {
        $this->optionsFilters = $options;

        $this->setFlag('has_stock_status_filter', true);
        $this->joinRelationTable();
        $this->applyStoreFilter();
        $this->registryHelper->initImportRegistry($this->getStoreId());
        $this->joinQty();
        $this->addFinalPrice();
        $this->addUrlRewrite();
        $this->applyUpdatedAtFilter();
        $this->applyTypeFilter();
        $this->addAttributeToSelect('status');
        $this->addAttributeToSelect('price');
        $this->addAttributeToSelect('special_price');
        $this->applyStatusFilter();
        $this->setPage($this->optionsFilters['page'], $this->optionsFilters['per_page']);
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
     */
    public function applyStatusFilter()
    {
        if (!isset($this->optionsFilters['status'])) {
            return $this;
        }

        $statusInharitance = $this->registryHelper->isStatusAttributeInheritable();
        $statusAttributeId = $this->getAttribute('status')->getId();

        /** add status filter - for main entity */
        if ($statusInharitance == 1 || $statusInharitance == 3) {
            $this->getSelect()->joinLeft(
                ['product_status' => 'catalog_product_entity_int'],
                'product_status.entity_id =  `e`.entity_id and product_status.attribute_id = ' . $statusAttributeId,
                ['product_status' => 'value']
            );
            /** apply status filter for product and its parent values **/
            $this->getSelect()->where('
                     (product_status.value = ' . $this->optionsFilters['status'] . ')'
            );
        } else {
            $this->getSelect()->joinLeft(
                ['product_status' => 'catalog_product_entity_int'],
                'product_status.entity_id =  `e`.entity_id and product_status.attribute_id = ' . $statusAttributeId,
                ['product_status' => 'value']
            );

            /** add status filter - parent status */
            $this->getSelect()->joinLeft(
                ['parent_status' => 'catalog_product_entity_int'],
                'parent_status.entity_id = catalog_product_relation.parent_id and parent_status.attribute_id = ' . $statusAttributeId,
                ['parent_status' => 'value']
            );

            /** apply status filter for product and its parent values **/
            $this->getSelect()->where('
                 (type_id <> "simple" and product_status.value = "' . $this->optionsFilters['status'] . '") or 
                 (parent_status.value = ' . $this->optionsFilters['status'] . ' and type_id="simple" and parent_id is not null) or 
                 (product_status.value = ' . $this->optionsFilters['status'] . ' and type_id="simple" and parent_id is null)'
            );
        }

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
        } else {
            $this->getSelect()->where('updated_at >= ?', $this->optionsFilters['updated_at']);
        }

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
