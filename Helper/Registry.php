<?php
/**
 * Created by Q-Solutions Studio
 * Date: 20.09.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as ProductAttributeCollectionCollection;

/**
 * Class Registry
 * @package DataFeedWatch\Connector\Helper
 */
class Registry extends AbstractHelper
{
    const ALL_CATEGORIES_ARRAY_KEY      = 'all_categories_array';
    const ALL_SUPER_ATTRIBUTES_KEY      = 'all_super_attributes_array';
    const ALL_IMPORTABLE_ATTRIBUTES_KEY = 'all_importable_attributes';
    const ALL_ATTRIBUTE_COLLECTION_KEY  = 'all_attribute_collection';
    const DFW_STATUS_ATTRIBUTE_KEY      = 'dfw_status_attribute';
    const DFW_UPDATED_AT_ATTRIBUTE_KEY  = 'dfw_updated_at_attribute';
    const DFW_VISIBILITY_ATTRIBUTE_KEY  = 'dfw_visibility_at_attribute';

    /** @var \Magento\Framework\Registry  */
    public $registry;

    /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection  */
    public $categoryCollection;

    /** @var ProductAttributeCollectionCollection  */
    public $attributeCollection;

    /**
     * Registry constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
     * @param ProductAttributeCollectionCollection $attributeCollection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection,
        ProductAttributeCollectionCollection $attributeCollection
    ) {
        $this->registry             = $registry;
        $this->categoryCollection   = $categoryCollection;
        $this->attributeCollection  = $attributeCollection;
        parent::__construct($context);
    }

    /**
     * @param string $storeId
     */
    public function initImportRegistry($storeId)
    {
        $this->registerCategories($storeId);
        $this->registerStatusAttribute();
        $this->registerUpdatedAtAttribute();
        $this->registerVisibilityAttribute();
        $this->registerSuperAttributes();
        $this->registerInheritableAttributes();
        $this->registerAttributeCollection();
    }

    /**
     * @param string $storeId
     */
    public function registerCategories($storeId)
    {
        $registry = $this->registry->registry(self::ALL_CATEGORIES_ARRAY_KEY);
        if (empty($registry)) {
            $categories = $this->categoryCollection
                              ->addNameToResult()
                              ->setStoreId($storeId)
                              ->addFieldToFilter('level', ['gt' => 1])
                              ->getItems();

            $this->registry->register(self::ALL_CATEGORIES_ARRAY_KEY, $categories);
        }
    }

    public function registerStatusAttribute()
    {
        $registry = $this->registry->registry(self::DFW_STATUS_ATTRIBUTE_KEY);
        if (empty($registry)) {
            $statusAttribute = clone $this->attributeCollection;
            $statusAttribute = $statusAttribute->addFieldToFilter('attribute_code', 'status')->getFirstItem();
            $this->registry->register(self::DFW_STATUS_ATTRIBUTE_KEY, $statusAttribute);
        }
    }

    public function registerUpdatedAtAttribute()
    {
        $registry = $this->registry->registry(self::DFW_UPDATED_AT_ATTRIBUTE_KEY);
        if (empty($registry)) {
            $updatedAtAttribute = clone $this->attributeCollection;
            $updatedAtAttribute = $updatedAtAttribute->addFieldToFilter('attribute_code', 'updated_at')->getFirstItem();
            $this->registry->register(self::DFW_UPDATED_AT_ATTRIBUTE_KEY, $updatedAtAttribute);
        }
    }

    public function registerVisibilityAttribute()
    {
        $registry = $this->registry->registry(self::DFW_VISIBILITY_ATTRIBUTE_KEY);
        if (empty($registry)) {
            $visibilityAttribute = clone $this->attributeCollection;
            $visibilityAttribute = $visibilityAttribute->addFieldToFilter('attribute_code', 'visibility');
            $visibilityAttribute = $visibilityAttribute->getFirstItem();
            $this->registry->register(self::DFW_VISIBILITY_ATTRIBUTE_KEY, $visibilityAttribute);
        }
    }

    public function registerSuperAttributes()
    {
        $registry = $this->registry->registry(self::ALL_SUPER_ATTRIBUTES_KEY);
        if (empty($registry)) {
            $superAttributes = clone $this->attributeCollection;
            $superAttributes = $superAttributes->getItems();
            $this->registry->register(self::ALL_SUPER_ATTRIBUTES_KEY, $superAttributes);
        }
    }

    public function registerInheritableAttributes()
    {
        $registry = $this->registry->registry(self::ALL_IMPORTABLE_ATTRIBUTES_KEY);
        if (empty($registry)) {
            $importableAttributes = clone $this->attributeCollection;
            $importableAttributes = $importableAttributes->addFieldToFilter('import_to_dfw', 1);
            $this->registry->register(self::ALL_IMPORTABLE_ATTRIBUTES_KEY, $importableAttributes);
        }
    }

    public function registerAttributeCollection()
    {
        $registry = $this->registry->registry(self::ALL_ATTRIBUTE_COLLECTION_KEY);
        if (empty($registry)) {
            $attributeCollection = clone $this->attributeCollection;
            $attributeCollection->addVisibleFilter();
            foreach ($attributeCollection as $key => $attribute) {
                if (!$this->isAttributeInheritable($attribute) || !$this->isAttributeImportable($attribute)) {
                    $attributeCollection->removeItemByKey($key);
                }
            }
            $this->registry->register(self::ALL_ATTRIBUTE_COLLECTION_KEY, $attributeCollection);
        }
    }

    /**
     * @return bool
     */
    public function isStatusAttributeInheritable()
    {
        $attribute = $this->registry->registry(self::DFW_STATUS_ATTRIBUTE_KEY);
        return $attribute->getInheritance();

    }

    /**
     * @return bool
     */
    public function isUpdatedAtAttributeInheritable()
    {
        return $this->isAttributeInheritable($this->registry->registry(self::DFW_UPDATED_AT_ATTRIBUTE_KEY));
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function isAttributeInheritable($attribute)
    {
        return in_array(
            $attribute->getInheritance(),
            [
                (string) \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance::PARENT_OPTION_ID,
                (string) \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance::CHILD_THEN_PARENT_OPTION_ID,
            ]
        );
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function isAttributeImportable($attribute)
    {
        return (int)$attribute->getImportToDfw() === 1;
    }
}
