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

use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Cron\Model\ScheduleFactory;
use Magento\PageCache\Model\Cache\Type as Cache;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /** @var DataHelper */
    private $dataHelper;

    /** @var ScheduleFactory */
    private $scheduleFactory;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /** @var AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var ModuleDataSetupInterface */
    private $setup;

    /** @var Cache */
    private $cache;

    /**
     * InstallData constructor.
     * @param DataHelper $dataHelper
     * @param ScheduleFactory $scheduleFactory
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ModuleDataSetupInterface $setup
     */
    public function __construct(
        DataHelper $dataHelper,
        ScheduleFactory $scheduleFactory,
        EavSetupFactory $eavSetupFactory,
        AttributeRepositoryInterface $attributeRepository,
        Cache $cache
    ) {
        $this->dataHelper          = $dataHelper;
        $this->scheduleFactory     = $scheduleFactory;
        $this->eavSetupFactory     = $eavSetupFactory;
        $this->attributeRepository = $attributeRepository;
        $this->cache               = $cache;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if ($context instanceof ModuleContextInterface) {
            $this->setup = $setup;

            $this->installAttributes();
            $this->scheduleDataInstall();
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['config']);
        }
    }

    public function installAttributes()
    {
        $this->installIgnoreDataFeedAttribute();
        $this->installDfwParentIdsAttribute();
    }

    public function installIgnoreDataFeedAttribute()
    {
        $properties = [
            'type'                     => 'int',
            'label'                    => 'Ignore In DataFeedWatch',
            'input'                    => 'select',
            'source'                   => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'sort_order'               => 100,
            'global'                   => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group'                    => 'General Information',
            'visible'                  => true,
            'required'                 => false,
            'visible_on_front'         => false,
            'is_html_allowed_on_front' => false,
            'is_configurable'          => false,
            'searchable'               => false,
            'filterable'               => false,
            'comparable'               => false,
            'unique'                   => false,
            'user_defined'             => true,
            'default'                  => 0,
            'is_user_defined'          => false,
            'used_in_product_listing'  => false,
        ];

        $this->createAttribute('ignore_datafeedwatch', $properties);
    }

    /**
     * @param string $attributeCode
     * @param array $attributeProperties
     * @param string $entityType
     */
    public function createAttribute($attributeCode, array $attributeProperties, $entityType = Product::ENTITY)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        try {
            $this->attributeRepository->get(Product::ENTITY, $attributeCode);
        } catch (NoSuchEntityException $e) {
            $eavSetup->addAttribute(
                $entityType,
                $attributeCode,
                $attributeProperties
            );
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['eav']);
        }
    }

    public function installDfwParentIdsAttribute()
    {
        $properties = [
            'type'                     => 'varchar',
            'label'                    => 'dfw_parent_ids',
            'input'                    => 'text',
            'sort_order'               => 110,
            'global'                   => ScopedAttributeInterface::SCOPE_STORE,
            'group'                    => 'General Information',
            'visible'                  => true,
            'required'                 => false,
            'visible_on_front'         => false,
            'is_html_allowed_on_front' => false,
            'is_configurable'          => false,
            'searchable'               => false,
            'filterable'               => false,
            'comparable'               => false,
            'unique'                   => false,
            'user_defined'             => true,
            'default'                  => '',
            'is_user_defined'          => false,
            'used_in_product_listing'  => false,
        ];
        $this->createAttribute('dfw_parent_ids', $properties);
    }

    public function scheduleDataInstall()
    {
        $currentTimestamp = time();
        $createdAt        = strftime('%Y-%m-%d %H:%M:00', $currentTimestamp);
        $scheduledAt      = strftime('%Y-%m-%d %H:%M:00', $currentTimestamp + 120);
        $schedule         = $this->scheduleFactory->create();
        $schedule->setJobCode('datafeedwatch_connector_installer')
                 ->setCreatedAt($createdAt)
                 ->setScheduledAt($scheduledAt)->setStatus(\Magento\Cron\Model\Schedule::STATUS_PENDING)->save();

        $scheduledAt = strftime('%Y-%m-%d %H:%M:00', $currentTimestamp + 240);
        $schedule    = $this->scheduleFactory->create();
        $schedule->setJobCode('datafeedwatch_connector_fill_updated_at_table')
                 ->setCreatedAt($createdAt)
                 ->setScheduledAt($scheduledAt)->setStatus(\Magento\Cron\Model\Schedule::STATUS_PENDING)->save();

        $this->dataHelper->setInstallationIncomplete();
    }
}
