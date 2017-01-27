<?php
/**
 * Created by Q-Solutions Studio
 * Date: 21.09.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model;

use Magento\Catalog\Model\Product as coreProduct;

class Product
    extends coreProduct
{
    /** @var array $importData */
    protected $importData = array();
    protected $dataHelper;
    protected $registryHelper;
    protected $priceCurrency;
    protected $catalogHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataService,
        \Magento\Catalog\Model\Product\Url $url,
        \Magento\Catalog\Model\Product\Link $productLink,
        \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory,
        \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
        \Magento\Catalog\Model\Product\OptionFactory $catalogProductOptionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $catalogProductStatus,
        \Magento\Catalog\Model\Product\Media\Config $catalogProductMediaConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Catalog\Model\ResourceModel\Product $resource,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $resourceCollection,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\Product\Image\CacheFactory $imageCacheFactory,
        \Magento\Catalog\Model\ProductLink\CollectionProvider $entityCollectionProvider,
        \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
        \Magento\Catalog\Api\Data\ProductLinkExtensionFactory $productLinkExtensionFactory,
        \Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool $mediaGalleryEntryConverterPool,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor,
        \DataFeedWatch\Connector\Helper\Data $dataHelper,
        \DataFeedWatch\Connector\Helper\Registry $registryHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Helper\Data $catalogHelper,
        array $data = []
    ) {
        $this->dataHelper                   = $dataHelper;
        $this->registryHelper               = $registryHelper;
        $this->priceCurrency                = $priceCurrency;
        $this->catalogHelper                = $catalogHelper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $metadataService,
            $url,
            $productLink,
            $itemOptionFactory,
            $stockItemFactory,
            $catalogProductOptionFactory,
            $catalogProductVisibility,
            $catalogProductStatus,
            $catalogProductMediaConfig,
            $catalogProductType,
            $moduleManager,
            $catalogProduct,
            $resource,
            $resourceCollection,
            $collectionFactory,
            $filesystem,
            $indexerRegistry,
            $productFlatIndexerProcessor,
            $productPriceIndexerProcessor,
            $productEavIndexerProcessor,
            $categoryRepository,
            $imageCacheFactory,
            $entityCollectionProvider,
            $linkTypeProvider,
            $productLinkFactory,
            $productLinkExtensionFactory,
            $mediaGalleryEntryConverterPool,
            $dataObjectHelper,
            $joinProcessor,
            $data
        );
    }

    protected function _construct() {
        $this->_init('DataFeedWatch\Connector\Model\ResourceModel\Product');
    }

    /**
     * @return array
     */
    public function getDataToImport()
    {
        /** @var \DataFeedWatch\Connector\Model\Product $parent */
        $parent = $this->getParent();
        if ($this->registryHelper->isStatusAttributeInheritable()) {
            $this->setStatus($this->getFilterStatus());
        }
        $date = $this->getRuleDate();
        $date = new \DateTime($date);
        $this->setUpdatedAt($date->format('Y-m-d H:i:s'));
        $this->fillAllAttributesData();
        $this->importData['product_id']                 = $this->getId();
        $this->importData['sku']                        = $this->getSku();
        $this->importData['product_type']               = $this->getTypeId();
        $this->importData['quantity']                   = (int) $this->getQty();
        $this->importData['currency_code']              = $this->getStore()->getCurrentCurrencyCode();
        $this->importData['price']                      = $this->getImportPrice(false);
        $this->importData['price_with_tax']             = $this->getImportPrice(true);
        $this->importData['special_price']              = $this->getImportSpecialPrice(false);
        $this->importData['special_price_with_tax']     = $this->getImportSpecialPrice(true);
        $this->importData['special_from_date']          = $this->getSpecialFromDate();
        $this->importData['special_to_date']            = $this->getSpecialToDate();
        $this->importData['image_url']                  = $this->getBaseImageUrl();
        $this->importData['product_url']                = $this->getProductUrl();
        $this->importData['product_url_rewritten']      = $this->getProductUrl();
        $this->importData['is_in_stock']                = (int) $this->getQuantityAndStockStatus()['is_in_stock'];
        $this->getCategoryPathToImport();
        $this->setDataToImport($this->getCategoriesNameToImport(false));

        if (!empty($parent)) {
            $this->importData['parent_id']                      = $parent->getId();
            $this->importData['parent_sku']                     = $parent->getSku();
            $this->importData['parent_price']                   = $parent->getImportPrice(false);
            $this->importData['parent_price_with_tax']          = $parent->getImportPrice(true);
            $this->importData['parent_special_price']           = $parent->getImportSpecialPrice(false);
            $this->importData['parent_special_price_with_tax']  = $parent->getImportSpecialPrice(true);
            $this->importData['parent_special_from_date']       = $parent->getSpecialFromDate();
            $this->importData['parent_special_to_date']         = $parent->getSpecialToDate();
            $this->importData['parent_url']                     = $parent->getProductUrl();

            if ($this->dataHelper->isProductUrlInherited()) {
                $this->importData['product_url'] = $this->importData['parent_url'];
            }
            $this->setDataToImport($parent->getCategoriesNameToImport(true));
            if ($parent->isConfigurable()) {
                $this->importData['variant_spac_price']             = $this->getVariantSpacPrice(false);
                $this->importData['variant_spac_price_with_tax']    = $this->getVariantSpacPrice(true);
                $this->importData['variant_name']                   = $this->getName();
                $this->getDfwDefaultVariant();
            }
        }

        $this->getExcludedImages();
        $this->setDataToImport($this->getAdditionalImages($this->importData['image_url'], false));
        if (!empty($parent)) {
            if ($this->dataHelper->isImageUrlInherited()) {
                $this->importData['image_url'] = $parent->getBaseImageUrl();
            }
            $this->setDataToImport($parent->getAdditionalImages($this->importData['image_url'], true));
        }

        return $this->importData;
    }

    public function isConfigurable() {
        return $this->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
    }

    /**
     * @return $this
     */
    protected function fillAllAttributesData() {
        $productAttributes = array_keys($this->getAttributes());
        $attributeCollection = $this->_registry->registry(\DataFeedWatch\Connector\Helper\Registry::ALL_IMPORTABLE_ATTRIBUTES_KEY);
        foreach ($attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (empty($attributeCode) || !in_array($attributeCode, $productAttributes)) {
                continue;
            }
            $value = $attribute->getFrontend()->getValue($this);
            if ($attribute->getBackendType() === 'int' && $value === 'N/A') {
                $value = '';
            }
            if ($value instanceof \Magento\Framework\Phrase) {
                $value = $value->getText();
            }
            $this->importData[$attributeCode] = $value;
        }

        return $this;
    }

    /**
     * @param bool $withTax
     * @return float
     */
    protected function getImportPrice($withTax = false) {
        $price = round($this->priceCurrency->convert($this->getFinalPrice()), 2);
        return $this->catalogHelper->getTaxPrice($this, $price, $withTax);
    }

    /**
     * @param bool $withTax
     * @return float
     */
    protected function getImportSpecialPrice($withTax = false) {
        return $this->catalogHelper->getTaxPrice($this, $this->getSpecialPrice(), $withTax);
    }

    /**
     * @return string|null
     */
    protected function getBaseImageUrl() {
        $this->load('image');
        $image = $this->getImage();
        if ($image !== 'no_selection' && !empty($image)) {

            return $this->getMediaConfig()->getMediaUrl($image);
        }

        return null;
    }

    /**
     * @return $this
     */
    protected function getCategoryPathToImport() {
        $index = '';
        $categoriesCollection = $this->_registry->registry(\DataFeedWatch\Connector\Helper\Registry::ALL_CATEGORIES_ARRAY_KEY);
        foreach ($this->getCategoryCollection()->addNameToResult() as $category) {

            $categoryName = array();
            $path = $category->getPath();
            foreach (explode('/', $path) as $categoryId) {
                if (isset($categoriesCollection[$categoryId])) {
                    $categoryName[] = $categoriesCollection[$categoryId]->getName();
                }
            }
            if (!empty($categoryName)) {
                $key = 'category_path' . $index;
                $this->importData[$key] = implode(' > ', $categoryName);
                $index++;
            }
        }

        return $this;
    }

    /**
     * @param bool $isParent
     * @return array
     */
    protected function getCategoriesNameToImport($isParent = false) {
        $index = '';
        $names = array();
        foreach ($this->getCategoryCollection()->addNameToResult() as $category) {
            $key            = $isParent ? 'category_parent_name' : 'category_name';
            $key            .= $index++;
            $names[$key]    = $category->getName();
        }

        return $names;
    }

    /**
     * @param array $data
     */
    protected function setDataToImport($data) {
        foreach ($data as $key => $value) {
            $this->importData[$key] = $value;
        }
    }

    /**
     * @param bool $withTax
     * @return float
     */
    protected function getVariantSpacPrice($withTax = false) {

        $finalPrice = $this->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount();

        return $this->catalogHelper->getTaxPrice($this, $finalPrice, $withTax);
    }

    /**
     * @return $this
     */
    protected function getDfwDefaultVariant() {
        $parent = $this->getParent();
        if (empty($parent)) {
            return $this;
        }

        $superAttributes = $this->_registry->registry(\DataFeedWatch\Connector\Helper\Registry::ALL_SUPER_ATTRIBUTES_KEY);
        $parentSuperAttributes                      = $parent->getData('super_attribute_ids');
        $parentSuperAttributes                      = explode(',', $parentSuperAttributes);
        $this->importData['dfw_default_variant']    = 1;
        foreach ($parentSuperAttributes as $superAttributeId) {
            if (!isset($superAttributes[$superAttributeId])) {
                continue;
            }
            $superAttribute = $superAttributes[$superAttributeId];
            $defaultValue   = $superAttribute->getDefaultValue();
            if (!empty($defaultValue) && $defaultValue !== $this->getData($superAttribute->getAttributeCode())) {
                $this->importData['dfw_default_variant'] = 0;

                return $this;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function getExcludedImages() {
        $this->load('media_gallery');
        $gallery    = $this->getMediaGallery('images');
        $index      = 1;
        foreach ($gallery as $image) {
            if ($image['disabled']) {
                $imageUrl               = $this->getMediaConfig()->getMediaUrl($image['file']);
                $key                    = 'image_url_excluded' . $index++;
                $this->importData[$key] = $imageUrl;
            }
        }

        return $this;
    }

    /**
     * @param null|string $importedBaseImage
     * @param bool $isParent
     * @return array
     */
    protected function getAdditionalImages($importedBaseImage = null, $isParent = false) {
        if (empty($importedBaseImage)) {
            $importedBaseImage = $this->getBaseImageUrl();
        }
        $this->load('media_gallery');
        $gallery            = $this->getMediaGalleryImages();

        $index              = 1;
        $additionalImages   = array();
        foreach ($gallery as $image) {
            $imageUrl = $image->getUrl();
            if ($imageUrl !== $importedBaseImage && $imageUrl !== 'no_selection' && !empty($imageUrl)) {
                $key                    = $isParent ? 'parent_additional_image_url' : 'product_additional_image_url';
                $key                    .= $index++;
                $additionalImages[$key] = $imageUrl;
            }
        }

        return $additionalImages;
    }



    /**
     * @return $this
     */
    public function getParentAttributes()
    {
        $parent = $this->getParent();
        if (empty($parent)) {

            return $this;
        }
        $allAttributes = $this->_registry->registry(\DataFeedWatch\Connector\Helper\Registry::ALL_ATTRIBUTE_COLLECTION_KEY);
        foreach ($allAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            switch ($attribute->getInheritance()) {
                case (string) \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance::CHILD_THEN_PARENT_OPTION_ID:
                    $productData = $this->getData($attributeCode);
                    if (empty($productData) || $this->shouldChangeVisibilityForProduct($attribute)) {
                        $parentData = $parent->getData($attributeCode);
                        $this->setData($attributeCode, $parentData);
                    }
                    break;
                case (string) \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance::PARENT_OPTION_ID:
                    $parentData = $parent->getData($attributeCode);
                    if ($attributeCode === 'meta_title') {
                    }
                    $this->setData($attributeCode, $parentData);
                    break;
            }
        }

        return $this;
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function shouldChangeVisibilityForProduct($attribute)
    {
        $attributeCode = $attribute->getAttributeCode();

        return $attributeCode === 'visibility'
               && (int)$this->getData($attributeCode) === \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE;
    }
}