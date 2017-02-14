<?php
/**
 * Created by Q-Solutions Studio
 * Date: 18.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model;

use DataFeedWatch\Connector\Api\ConnectorInterface;

class Connector implements ConnectorInterface
{
    const MODULE_NAME = 'DataFeedWatch_Connector';
    
    protected $moduleList;
    protected $logger;
    protected $storeManager;
    protected $scopeConfig;
    protected $productCollection;
    protected $dataHelper;
    protected $dfwApiUser;

    /**
     * Connector constructor.
     *
     * @param \Magento\Framework\App\Helper\Context              $context
     * @param \Magento\Framework\Module\ModuleListInterface      $moduleList
     * @param \DataFeedWatch\Connector\Logger\Api                $logger
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param ResourceModel\Product\Collection                   $productCollection
     * @param \DataFeedWatch\Connector\Helper\Data               $dataHelper
     * @param Api\User                                           $dfwApiUser
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \DataFeedWatch\Connector\Logger\Api $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \DataFeedWatch\Connector\Model\ResourceModel\Product\Collection $productCollection,
        \DataFeedWatch\Connector\Helper\Data $dataHelper,
        \DataFeedWatch\Connector\Model\Api\User $dfwApiUser
    ) {

        $this->moduleList           = $moduleList;
        $this->logger               = $logger;
        $this->scopeConfig          = $context->getScopeConfig();
        $this->storeManager         = $storeManager;
        $this->productCollection    = $productCollection;
        $this->dataHelper           = $dataHelper;
        $this->dfwApiUser           = $dfwApiUser;
    }
    
    /**
     * {@inheritdoc}
     */
    public function version()
    {
        $this->logger->debug('datafeedwatch.version');
        $version = $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
        $this->logger->debug($version);
        
        return $version;
    }

    /**
     * {@inheritdoc}
     */
    public function gmtOffset()
    {
        $this->logger->debug('datafeedwatch.gmtOffset');
        $timeZone = $this->scopeConfig->getValue('general/locale/timezone');
        $timeZone = new \DateTimeZone($timeZone);
        $time     = new \DateTime('now', $timeZone);
        $offset   = (int)($timeZone->getOffset($time) / 3600);
        $this->logger->debug($offset);

        return $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function stores()
    {
        $this->logger->debug('datafeedwatch.stores');
        $storeViews = $this->getStoresArray();
        $this->logger->debug($storeViews);

        return [$storeViews];
    }

    /**
     * {@inheritdoc}
     */
    public function products($store = null, $type = [], $status = null, $perPage = 100, $page = 1)
    {
        $this->logger->debug('datafeedwatch.products');
        $options = [];
        $this->filterOptions($options, $store, $type, $status, null, null, $perPage, $page);
        $collection = $this->getProductCollection($options);
        $collection->applyInheritanceLogic();

        return $this->processProducts($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function productCount($store = null, $type = [], $status = null, $perPage = 100, $page = 1)
    {
        $this->logger->debug('datafeedwatch.productCount');
        $options = [];
        $this->filterOptions($options, $store, $type, $status, null, null, $perPage, $page);
        $collection = $this->getProductCollection($options);
        $amount     = (int) $collection->getSize();
        $this->logger->debug(sprintf('datafeedwatch.productCount %d', $amount));

        return $amount;
    }

    /**
     * {@inheritdoc}
     */
    public function updatedProducts(
        $store = null, 
        $type = [], 
        $status = null, 
        $timezone = null, 
        $fromDate = null, 
        $perPage = 100, 
        $page = 1
    ) {
        $this->logger->debug('datafeedwatch.updatedProducts');
        $options = [];
        $this->filterOptions($options, $store, $type, $status, $timezone, $fromDate, $perPage, $page);
        if (!$this->isFromDateEarlierThanConfigDate($options)) {
            $collection = $this->getProductCollection($options);
            $collection->applyInheritanceLogic();

            return $this->processProducts($collection);
        } else {
            $this->logger->debug('datafeedwatch.updatedProducts -> datafeedwatch.products');

            return $this->products($options);
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
        $fromDate = null, 
        $perPage = 100, 
        $page = 1
    ) {
        $this->logger->debug('datafeedwatch.updatedProductCount');
        $options = [];
        $this->filterOptions($options, $store, $type, $status, $timezone, $fromDate, $perPage, $page);
        if (!$this->isFromDateEarlierThanConfigDate($options)) {
            $collection = $this->getProductCollection($options);
            $amount     = (int) $collection->getSize();
            $this->logger->debug(sprintf('datafeedwatch.updatedProductCount %d', $amount));
        } else {
            $this->logger->debug('datafeedwatch.updatedProductCount -> datafeedwatch.productCount');
            $amount = $this->productCount($options);
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
        $fromDate = null, 
        $perPage = 100, 
        $page = 1
    ) {
        $options = [];
        $this->logger->debug('datafeedwatch.productIds');
        $this->filterOptions($options, $store, $type, $status, $timezone, $fromDate, $perPage, $page);
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
    protected function getStoresArray()
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
     * @param array $options
     * @return \DataFeedWatch\Connector\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection($options)
    {
        /** @var \DataFeedWatch\Connector\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollection->addAttributeToSelect('*');
        $collection->applyFiltersOnCollection($options);
        return $collection;
    }

    /**
     * @param string[] $options
     * @param string $store
     * @param string[] $type
     * @param string $status
     * @param string $timezone
     * @param string $fromDate
     * @param int  $perPage
     * @param int  $page
     */
    public function filterOptions(
        &$options, 
        $store = null, 
        $type = [], 
        $status = null, 
        $timezone = null, 
        $fromDate = null, 
        $perPage = 100, 
        $page = 1
    ) {
        if ($store !== null && is_string($store)) {
            $options['store'] = $store;
        }
        if ($type !== null && is_array($type)) {
            $options['type'] = $type;
        }
        if ($status !== null && is_string($status)) {
            $options['status'] = $status;
        }
        if ($timezone !== null && is_string($timezone)) {
            $options['timezone'] = $timezone;
        }
        if ($fromDate !== null && is_string($fromDate)) {
            $options['from_date'] = $fromDate;
        }
        $options['per_page'] = (int)$perPage;
        $options['page'] = (int)$page;

        $this->logger->debug($options);

        $this->filterStoreOption($options);

        if (isset($options['type'])) {
            $this->filterTypeOption($options);
        }

        if (isset($options['status'])) {
            $this->filterStatusOption($options);
        }

        if (isset($options['timezone'])) {
            $this->filterTimeZoneOption($options);
        }

        if (isset($options['from_date'])) {
            $this->filterFromDateOption($options);
        }
    }

    /**
     * @param array $options
     */
    public function filterStoreOption(&$options)
    {
        $existingStoreViews = array_keys($this->getStoresArray());
        if (isset($options['store']) && !in_array($options['store'], $existingStoreViews)) {
            $message = 'The store view %s does not exist. Default store will be applied';
            $this->logger->debug(sprintf($message, $options['store']));
            $options['store'] = $this->storeManager->getStore()->getCode();
        } elseif (!isset($options['store'])) {
            $message = 'The store not specified. Default store has been applied';
            $this->logger->debug($message);
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
            \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
            \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
            \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
            \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE,
        ];
        $types = array_map('strtolower', $types);
        $types = array_intersect($types, $magentoTypes);
        if (!empty($types)) {
            $options['type'] = $types;
        } else {
            $this->logger->debug('The type below does not exist');
            $this->logger->debug($options['type']);
            unset($options['type']);
        }
    }

    /**
     * @param array $options
     */
    public function filterStatusOption(&$options)
    {
        $status = (string) $options['status'];
        if ($status === '0') {
            $options['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
        } elseif ($status === '1') {
            $options['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
        } else {
            $message = 'The status %s does not exist';
            $this->logger->debug(sprintf($message, $options['status']));
            unset($options['status']);
        }
    }

    /**
     * @param array $options
     */
    public function filterTimeZoneOption(&$options)
    {
        try {
            $options['timezone'] = new \DateTimeZone($options['timezone']);
        } catch (\Exception $e) {
            $this->logger->debug(sprintf('%s timezone is wrong', $options['timezone']));
            $options['timezone'] = null;
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
        try {
            $options['from_date'] = new \DateTime($options['from_date'], $options['timezone']);
        } catch (\Exception $e) {
            $options['from_date'] = new \DateTime();
        }
        $options['from_date'] = $options['from_date']->format('Y-m-d H:i:s');
    }

    /**
     * @param $collection
     *
     * @return array
     */
    protected function processProducts($collection)
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
    protected function isFromDateEarlierThanConfigDate($options)
    {
        $this->logger->debug('START: Model/Api.php->isFromDateEarlierThanConfigDate()');
        if (!isset($options['from_date'])) {
            $this->logger->debug('$options[\'from_date\'] is not set');
            $this->logger->debug('END: Model/Api.php->isFromDateEarlierThanConfigDate()');

            return false;
        }
        $this->logger->debug('$options[\'from_date\']');
        $this->logger->debug($options['from_date']);
        $this->logger->debug('$this->dataHelper->getLastInheritanceUpdateDate()');
        $this->logger->debug($this->dataHelper->getLastInheritanceUpdateDate());
        $this->logger->debug('result');
        if ($options['from_date'] < $this->dataHelper->getLastInheritanceUpdateDate()) {
            $this->logger->debug('$options[\'from_date\'] < $this->dataHelper->getLastInheritanceUpdateDate()');
        } else {
            $this->logger->debug('From date is equal or greater');
        }
        $this->logger->debug('END: Model/Api.php->isFromDateEarlierThanConfigDate()');

        return $options['from_date'] < $this->dataHelper->getLastInheritanceUpdateDate();
    }
}
