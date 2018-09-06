<?php
/**
 * Created by Q-Solutions Studio
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Jakub Winkler <jwinkler@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model;

use DataFeedWatch\Connector\Helper\Registry;
use DataFeedWatch\Connector\Api\ConnectorInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Downloadable\Model\Product\Type as DownloadableType;

/**
 * Class Connector
 * @package DataFeedWatch\Connector\Model
 */
class Connector implements ConnectorInterface
{
    const MODULE_NAME = 'DataFeedWatch_Connector';

    /** @var \Magento\Framework\Module\ModuleListInterface  */
    public $moduleList;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    public $storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    public $scopeConfig;

    /** @var ResourceModel\Product\CollectionFactory  */
    public $productCollectionFactory;

    /** @var \DataFeedWatch\Connector\Helper\Data  */
    public $dataHelper;

    /** @var Api\User  */
    public $dfwApiUser;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime  */
    public $dateTime;

    /** @var \Magento\Framework\Stdlib\DateTime\Timezone  */
    public $timeZone;

    /** @var Registry  */
    public $registryHelper;

    /**
     * Connector constructor.
     * @param Registry $registryHelper
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \DataFeedWatch\Connector\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timeZone
     * @param Api\User $dfwApiUser
     */
    public function __construct(
        Registry $registryHelper,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \DataFeedWatch\Connector\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \DataFeedWatch\Connector\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $timeZone,
        Api\User $dfwApiUser
    ) {
        $this->registryHelper           = $registryHelper;
        $this->moduleList               = $moduleList;
        $this->dateTime                 = $dateTime;
        $this->timeZone                 = $timeZone;
        $this->scopeConfig              = $context->getScopeConfig();
        $this->storeManager             = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dataHelper               = $dataHelper;
        $this->dfwApiUser               = $dfwApiUser;
    }
    
    /**
     * {@inheritdoc}
     */
    public function version()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * {@inheritdoc}
     */
    public function gmtOffset()
    {
        return $this->dateTime->getGmtOffset('hours');
    }

    /**
     * {@inheritdoc}
     */
    public function stores()
    {
        $storeViews = $this->getStoresArray();

        return [$storeViews];
    }

    /**
     * {@inheritdoc}
     */
    public function products($store = null, $type = [], $status = null, $per_page = 100, $page = 1)
    {
        $options = [];
        $this->filterOptions($options, $store, $type, $status, null, null, $per_page, $page);
        $collection = $this->getProductCollection($options);
        $collection->applyInheritanceLogic();
        return $this->processProducts($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function productCount($store = null, $type = [], $status = null, $per_page = 100, $page = 1)
    {
        $options = [];
        $this->filterOptions($options, $store, $type, $status, null, null, $per_page, $page);
        $collection = $this->getProductCollection($options);

        return (int) $collection->getSize();
    }

    /**
     * {@inheritdoc}
     */
    public function updatedProducts(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $updatedAt = null,
        $per_page = 100,
        $page = 1
    ) {
        $options = [];
        $this->filterOptions($options, $store, $type, $status, $timezone, $updatedAt, $per_page, $page);

        if (!$this->isFromDateEarlierThanConfigDate($options)) {
            $collection = $this->getProductCollection($options);
            $collection->applyInheritanceLogic();
            return $this->processProducts($collection);
        } else {
            return $this->products(
                $options['store'],
                $options['type'],
                $options['status'],
                $options['per_page'],
                $options['page']
            );
        }

    }

    /**
     * {@inheritdoc}
     */
    public function updatedProductCount(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $from_date = null,
        $per_page = 100,
        $page = 1
    ) {
        $this->filterOptions($options, $store, $type, $status, $timezone, $from_date, $per_page, $page);
        if (!$this->isFromDateEarlierThanConfigDate($options)) {
            $collection = $this->getProductCollection($options);
            $amount     = (int)$collection->getSize();
        } else {
            unset($options['updated_at']);
            $amount = $this->productCount(
                $options['store'],
                $options['type'],
                $options['status'],
                $options['per_page'],
                $options['page']
            );
        }

        return $amount;
    }

    /**
     * {@inheritdoc}
     */
    public function productIds(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $from_date = null,
        $per_page = 100,
        $page = 1
    ) {
        $options = [];
        $this->filterOptions($options, $store, $type, $status, $timezone, $from_date, $per_page, $page);
        $collection = $this->getProductCollection($options);

        return $collection->getColumnValues('entity_id');
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($token = null)
    {
        return $this->dfwApiUser->revokeDfwUserAccessTokens($token);
    }

    /**
     * @return array
     */
    public function getStoresArray()
    {
        $storeViews = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    $storeViews[$store->getCode()] = [
                        'Website'       => $website->getName(),
                        'Store'         => $group->getName(),
                        'Store View'    => $store->getName(),
                    ];
                }
            }
        }

        return $storeViews;
    }

    /**
     * @param $options
     * @return ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductCollection($options)
    {
        /** @var \DataFeedWatch\Connector\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->applyFiltersOnCollection($options);
        return $collection;
    }

    /**
     * @param string[] $options
     * @param string $store
     * @param string[] $type
     * @param string $status
     * @param string $timezone
     * @param string $from_date
     * @param int $per_page
     * @param int $page
     */
    public function filterOptions(
        &$options,
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $from_date = null,
        $per_page = 100,
        $page = 1
    ) {
        if (empty($options) || !is_array($options)) {
            $options = [];
        }
        if ($store !== null && is_string($store)) {
            $options['store'] = $store;
        }
        if ($type !== null && is_array($type)) {
            $options['type'] = $type;
        }
        if ($status !== null && is_string($status) || is_int($status)) {
            $options['status'] = $status;
        }
        if ($timezone !== null && is_string($timezone)) {
            $options['timezone'] = $timezone;
        }
        if ($from_date !== null && is_string($from_date)) {
            $options['updated_at'] = $from_date;
        }
        $options['per_page'] = (int)$per_page;
        $options['page'] = (int)$page;
        $options['status'] = $status;

        $this->builtInFiltering($options);
    }

    public function builtInFiltering(&$options)
    {
        $this->filterStoreOption($options);

        if (isset($options['type'])) {
            $this->filterTypeOption($options);
        }

        if (isset($options['status'])) {
            $this->filterStatusOption($options);
        }

        if (isset($options['updated_at'])) {
            $this->filterFromDateOption($options);
        }
    }

    /**
     * @param $options
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function filterStoreOption(&$options)
    {
        $existingStoreViews = array_keys($this->getStoresArray());
        if (isset($options['store']) && !in_array($options['store'], $existingStoreViews)) {
            $options['store'] = $this->storeManager->getStore()->getCode();
        } elseif (!isset($options['store'])) {
            $options['store'] = $this->storeManager->getStore()->getCode();
        }
        $this->storeManager->setCurrentStore($options['store']);
    }
    
    /**
     * @param array $options
     */
    public function filterTypeOption(&$options)
    {
        $types          = $options['type'];
        $magentoTypes   = [
            ProductType::TYPE_SIMPLE,
            ProductType::TYPE_BUNDLE,
            ConfigurableType::TYPE_CODE,
            GroupedType::TYPE_CODE,
            ProductType::TYPE_VIRTUAL,
            DownloadableType::TYPE_DOWNLOADABLE,
        ];
        $types = array_map('strtolower', $types);
        $types = array_intersect($types, $magentoTypes);
        if ((
        in_array(ProductType::TYPE_BUNDLE, $types)
        || in_array(ConfigurableType::TYPE_CODE, $types)
        || in_array(GroupedType::TYPE_CODE, $types)
        ) && !in_array(ProductType::TYPE_SIMPLE, $types)) {
            $types[] = ProductType::TYPE_SIMPLE;
        }
        if (!empty($types)) {
            $options['type'] = $types;
        } else {
            unset($options['type']);
        }
    }

    /**
     * @param array $options
     */
    public function filterStatusOption(&$options)
    {
        $status = (int)$options['status'];
        if ($status) {
            if ($status == 1) {
                $options['status'] = Status::STATUS_ENABLED;
            } else {
                $options['status'] = Status::STATUS_DISABLED;
            }
        } else {
            $options['status'] = null;
        }
    }

    /**
     * @param array $options
     */
    public function filterFromDateOption(&$options)
    {
        if (!isset($options['timezone'])) {
            $options['timezone'] = null;
        }
        $options['updated_at'] = $this->dateTime->date(null, $options['updated_at']);
    }

    /**
     * @param $collection
     *
     * @return array
     */
    public function processProducts($collection)
    {
        $products = [];
        foreach ($collection as $product) {
            $products[] = $product->getDataToImport();
        }

        return $products;
    }

    /**
     * @param array $options
     * @return bool
     */
    public function isFromDateEarlierThanConfigDate($options)
    {
        if (!isset($options['updated_at'])) {
            return false;
        }

        if ($this->dataHelper->getLastInheritanceUpdateDate() == null) {
            return true;
        }

        // parse dates to timestamps
        return strtotime($options['updated_at']) < strtotime($this->dataHelper->getLastInheritanceUpdateDate());
    }
}
