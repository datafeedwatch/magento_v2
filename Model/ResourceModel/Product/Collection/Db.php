<?php
/**
 * Created by Q-Solutions Studio
 * Date: 20.09.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model\ResourceModel\Product\Collection;

use DataFeedWatch\Connector\Helper\Registry;
use DataFeedWatch\Connector\Model\System\Config\Source\Inheritance;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\Customer\Api\GroupManagementInterface;

class Db extends Collection
{
    const INHERITED_STATUS_TABLE_ALIAS               = 'inherited_status';
    const INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE = 'inherited_status_default_store';
    const ORIGINAL_STATUS_TABLE_ALIAS                = 'original_status';
    const ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE  = 'status_default_store';
    const ORIGINAL_VISIBILITY_TABLE_ALIAS            = 'original_visibility';
    const VISIBILITY_TABLE_ALIAS_DEFAULT_STORE       = 'visibility_default_store';
    const PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE       = 'dfw_parent_ids_default_store';
    const ORIGINAL_PARENT_IDS_TABLE_ALIAS            = 'original_dfw_parent_ids';
    const MIXED_STATUS_COLUMN_ALIAS                  = 'filter_status';
    const PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS = 'parent_configurable_attributes';
    const PARENT_RELATIONS_TABLE_ALIAS               = 'parent_relation';
    const UPDATED_AT_TABLE_ALIAS                     = 'custom_updated_at';
    const CATALOGRULE_DATE_COLUMN_ALIAS              = 'rule_date';

    /** @var string $filterStatusCondition */
    protected $filterStatusCondition;
    /** @var array $optionsFilters */
    protected $optionsFilters;
    /** @var  string $ruleDateSelect */
    protected $ruleDateSelect;

    protected $registryHelper;
    protected $registry;
    protected $storeManager;
    protected $productCollectionFactory;
    protected $typeConfigurable;
    protected $cron;

    /**
     * Db constructor.
     * @param Registry $registryHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeConfigurable
     * @param \DataFeedWatch\Connector\Cron\FillUpdatedAtTable $cron
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param GroupManagementInterface $groupManagement
     * @param null $connection
     * @param null $productLimitationFactory
     * @param null $metadataPool
     */
    public function __construct(
        Registry $registryHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeConfigurable,
        \DataFeedWatch\Connector\Cron\FillUpdatedAtTable $cron,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory, \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager, \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource, \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Stdlib\DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        $connection = null,
        $productLimitationFactory = null,
        $metadataPool = null
    ) {

        $this->registryHelper           = $registryHelper;
        $this->registry                 = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->typeConfigurable         = $typeConfigurable;
        $this->cron                     = $cron;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection,
            $productLimitationFactory,
            $metadataPool
        );
    }

    protected function buildFilterStatusCondition()
    {
        $childString     = 'IFNULL(%1$s.value, %3$s.value)';
        $parentString    = 'IFNULL(%2$s.value, %4$s.value)';
        $enable          = Status::STATUS_ENABLED;
        $statusAttribute = $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY);
        switch ($statusAttribute->getInheritance()) {
            case (string) Inheritance::CHILD_THEN_PARENT_OPTION_ID:
                $inheritString          = "IFNULL({$childString}, {$parentString})";
                $notVisibleIndividually = "IF({$childString} <> {$enable}, {$childString}, {$parentString})";
                $string                 = 'IF(IFNULL(%5$s.value, %6$s.value) = ' . Visibility::VISIBILITY_NOT_VISIBLE
                                          . ', ' . $notVisibleIndividually . ', ' . $inheritString . ')';
                break;
            case (string) Inheritance::PARENT_OPTION_ID:
                $string = 'IFNULL(' . $parentString . ', ' . $childString . ')';
                break;
            default:
                $string = $childString;
        }
        $this->filterStatusCondition = sprintf(
            $string,
            self::ORIGINAL_STATUS_TABLE_ALIAS,
            self::INHERITED_STATUS_TABLE_ALIAS,
            self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE,
            self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE,
            self::ORIGINAL_VISIBILITY_TABLE_ALIAS,
            self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinVisibilityTable($tableAlias = self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getVisibilityTable()],
            $this->getJoinVisibilityTableStatement($tableAlias, $storeId),
            ['value']
        );

        return $this;
    }

    /**
     * @param $tableAlias
     *
     * @return bool
     * @throws \Zend_Db_Select_Exception
     */
    protected function isTableAliasAdded($tableAlias)
    {
        $tables         = $this->getSelect()->getPart(\Zend_Db_Select::FROM);
        $currentAliases = array_keys($tables);

        return in_array($tableAlias, $currentAliases);

    }

    /**
     * @return mixed
     */
    protected function getVisibilityTable()
    {
        return $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY)
                              ->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinVisibilityTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias,
            $this->getJoinVisibilityAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinVisibilityAttributeStatement($tableAlias, $storeId = '0')
    {
        $visibilityAttribute = $this->registry->registry(Registry::DFW_VISIBILITY_ATTRIBUTE_KEY);
        return sprintf(
            '%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias,
            $visibilityAttribute->getId(),
            $storeId
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinParentIdsTable($tableAlias = self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getParentIdsTable()],
            $this->getJoinParentIdsTableStatement($tableAlias, $storeId),
            ['value']
        );
        $this->getSelect()->columns(
            sprintf(
                'IFNULL(%1$s.value, %2$s.value) as parent_id',
                self::ORIGINAL_PARENT_IDS_TABLE_ALIAS,
                self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE
            )
        );

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getParentIdsTable()
    {
        return $this->registry->registry(Registry::DFW_PARENT_ID_ATTRIBUTE_KEY)
                              ->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinParentIdsTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias,
            $this->getJoinParentIdsAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinParentIdsAttributeStatement($tableAlias, $storeId = '0')
    {
        $attribute = $this->registry->registry(Registry::DFW_PARENT_ID_ATTRIBUTE_KEY);
        return sprintf(
            '%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias,
            $attribute->getId(),
            $storeId
        );
    }

    /**
     * @return $this
     */
    protected function addRuleDate()
    {
        /** @var \DataFeedWatch\Connector\Cron\FillUpdatedAtTable $cron */
        $cron = $this->cron;
        $cron->execute();

        $condition = $this->getUpdatedAtCondition();
        $select    = $this->_resource->getConnection()->select();
        $select->from(
            [self::UPDATED_AT_TABLE_ALIAS => $this->_resource->getTableName('datafeedwatch_updated_products')],
            [sprintf('COALESCE(%1$s.updated_at, 0)', self::UPDATED_AT_TABLE_ALIAS)]
        );
        $select->where($condition);
        $select->limit(1);

        $this->ruleDateSelect = sprintf(
            'GREATEST(IFNULL((%s), 0), COALESCE(%2$s.updated_at, 0))',
            $select->__toString(),
            self::MAIN_TABLE_ALIAS
        );
        $this->getSelect()->columns([self::CATALOGRULE_DATE_COLUMN_ALIAS => new \Zend_Db_Expr($this->ruleDateSelect)]);

        return $this;
    }

    /**
     * @return string
     */
    protected function getUpdatedAtCondition()
    {
        $condition = '(IFNULL(%3$s.value, %4$s.value) IS NOT NULL 
        AND %1$s.dfw_prod_id IN (IFNULL(%3$s.value, %4$s.value)) 
        OR %1$s.dfw_prod_id = %2$s.entity_id)';
        $condition = sprintf(
            $condition,
            self::UPDATED_AT_TABLE_ALIAS,
            self::MAIN_TABLE_ALIAS,
            self::ORIGINAL_PARENT_IDS_TABLE_ALIAS,
            self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE
        );

        return $condition;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function joinQty()
    {
        $this->joinField(
            'qty',
            $this->_resource->getTableName('cataloginventory_stock_status'),
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        );
        $this->joinField(
            'stock_status',
            $this->_resource->getTableName('cataloginventory_stock_status'),
            'stock_status',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        );

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinInheritedStatusTable($tableAlias = self::INHERITED_STATUS_TABLE_ALIAS, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getStatusTable()],
            $this->getJoinInheritedStatusTableStatement($tableAlias, $storeId),
            [self::MIXED_STATUS_COLUMN_ALIAS => $this->filterStatusCondition]
        );

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getStatusTable()
    {
        return $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY)->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinInheritedStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id IN (IFNULL(%2$s.value, %3$s.value)) and %4$s',
            $tableAlias,
            self::ORIGINAL_PARENT_IDS_TABLE_ALIAS,
            self::PARENT_IDS_TABLE_ALIAS_DEFAULT_STORE,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinStatusAttributeStatement($tableAlias, $storeId = '0')
    {
        $statusAttribute = $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY);
        return sprintf(
            '%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias,
            $statusAttribute->getId(),
            $storeId
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     */
    protected function joinOriginalStatusTable($tableAlias = self::ORIGINAL_STATUS_TABLE_ALIAS, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getStatusTable()],
            $this->getJoinOriginalStatusTableStatement($tableAlias, $storeId),
            [self::MIXED_STATUS_COLUMN_ALIAS => $this->filterStatusCondition]
        );

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    protected function getJoinOriginalStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }
}
