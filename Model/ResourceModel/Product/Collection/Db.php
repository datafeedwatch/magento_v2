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
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Customer\Api\GroupManagementInterface;

class Db extends Collection
{
    const INHERITED_STATUS_TABLE_ALIAS               = 'inherited_status';
    const INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE = 'inherited_status_default_store';
    const ORIGINAL_STATUS_TABLE_ALIAS                = 'original_status';
    const ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE  = 'status_default_store';
    const ORIGINAL_VISIBILITY_TABLE_ALIAS            = 'original_visibility';
    const VISIBILITY_TABLE_ALIAS_DEFAULT_STORE       = 'visibility_default_store';
    const MIXED_STATUS_COLUMN_ALIAS                  = 'filter_status';
    const PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS = 'parent_configurable_attributes';
    const PARENT_RELATIONS_TABLE_ALIAS               = 'parent_relation';
    const UPDATED_AT_TABLE_ALIAS                     = 'custom_updated_at';
    const CATALOGRULE_DATE_COLUMN_ALIAS              = 'rule_date';

    /** @var string $filterStatusCondition */
    public $filterStatusCondition;
    /** @var array $optionsFilters */
    public $optionsFilters;
    /** @var  string $ruleDateSelect */
    public $ruleDateSelect;

    public $registryHelper;
    public $registry;
    public $storeManager;
    public $productCollectionFactory;
    public $typeConfigurable;
    public $cron;

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
     */
    public function __construct(
        Registry $registryHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeConfigurable,
        \DataFeedWatch\Connector\Cron\FillUpdatedAtTable $cron,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        $connection = null
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
            $connection
        );
    }

    public function buildFilterStatusCondition()
    {
        $childString     = 'IFNULL(%1$s.value, %3$s.value)';
        $parentString    = 'IFNULL(%2$s.value, %4$s.value)';
        if ($this->registryHelper->isStatusAttributeInheritable()) {
            $inheritString = "IFNULL({$childString}, {$parentString})";
                $string = 'IF(IFNULL(%5$s.value, %6$s.value) = ' . Visibility::VISIBILITY_NOT_VISIBLE
                                          . ', ' . $parentString . ', ' . $inheritString . ')';
        } else {
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
    public function joinVisibilityTable($tableAlias = self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, $storeId = '0')
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
     * @return $this
     */
    public function joinRelationTable()
    {
        $this->getSelect()->columns(['parent_id' =>  $this->getParentIdSubselect()]);

        return $this;
    }

    /**
     * @param $tableAlias
     *
     * @return bool
     * @throws \Zend_Db_Select_Exception
     */
    public function isTableAliasAdded($tableAlias)
    {
        $tables         = $this->getSelect()->getPart(\Zend_Db_Select::FROM);
        $currentAliases = array_keys($tables);

        return in_array($tableAlias, $currentAliases);
    }

    /**
     * @return mixed
     */
    public function getVisibilityTable()
    {
        return $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY)
                              ->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinVisibilityTableStatement($tableAlias, $storeId)
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
    public function getJoinVisibilityAttributeStatement($tableAlias, $storeId = '0')
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
     * @return $this
     */
    public function addRuleDate()
    {
        /** @var \DataFeedWatch\Connector\Cron\FillUpdatedAtTable $cron */
        $cron = $this->cron;
        $cron->execute();

//        $condition = $this->getUpdatedAtCondition();
        $select    = $this->_resource->getConnection()->select();
        $select->from(
            [self::UPDATED_AT_TABLE_ALIAS => $this->_resource->getTableName('datafeedwatch_updated_products')],
            [sprintf('COALESCE(%1$s.updated_at, 0)', self::UPDATED_AT_TABLE_ALIAS)]
        );
//        $select->where($condition);
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
    public function getUpdatedAtCondition()
    {
        $condition = '(parent_id IS NOT NULL
        AND %1$s.dfw_prod_id IN (parent_id)
        OR %1$s.dfw_prod_id = %2$s.entity_id)';
        $condition = sprintf(
            $condition,
            self::UPDATED_AT_TABLE_ALIAS,
            self::MAIN_TABLE_ALIAS
        );

        return $condition;
    }

    /**
     * @return string
     */
    public function getParentIdSubselect()
    {
        return '(select GROUP_CONCAT(DISTINCT parent_id) from ' . $this->getTable('catalog_product_relation')
               . ' where child_id = e.entity_id group by child_id)';
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function joinQty()
    {
        $this->joinTable(
            $this->_resource->getTableName('cataloginventory_stock_item'),
            'product_id = entity_id',
            [
                'qty' => 'qty',
                'stock_status' => 'is_in_stock',
            ],
            '{{table}}.stock_id = 1',
            'left'
        );

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     * @throws \Zend_Db_Select_Exception
     */
    public function joinInheritedStatusTable($tableAlias = self::INHERITED_STATUS_TABLE_ALIAS, $storeId = '0')
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
    public function getStatusTable()
    {
        return $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY)->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinInheritedStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id IN (' . $this->getParentIdSubselect() . ') and %2$s',
            $tableAlias,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinStatusAttributeStatement($tableAlias, $storeId = '0')
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
    public function joinOriginalStatusTable($tableAlias = self::ORIGINAL_STATUS_TABLE_ALIAS, $storeId = '0')
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
    public function getJoinOriginalStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }
}
