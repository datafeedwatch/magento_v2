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
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData
    implements InstallDataInterface
{
    /** @var EavSetupFactory */
    private $eavSetupFactory;
    
    /** @var AttributeRepositoryInterface */
    private $attributeRepository;
    
    /** @var DataHelper */
    private $dataHelper;
    
    /** @var \DataFeedWatch\Connector\Model\Api\User */
    private $apiUser;
    
    /** @var \DataFeedWatch\Connector\Cron\FillUpdatedAtTable */
    private $cron;
    
    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollection;
    
    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable */
    private $configurable;
    
    /** @var ModuleDataSetupInterface */
    private $setup;
    
    /**
     * @param EavSetupFactory                                                            $eavSetupFactory
     * @param AttributeRepositoryInterface                                               $attributeRepository
     * @param DataHelper                                                                 $dataHelper
     * @param \DataFeedWatch\Connector\Model\Api\User                                    $apiUser
     * @param \DataFeedWatch\Connector\Cron\FillUpdatedAtTable                           $cron
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory             $productCollection
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable
     * @param ModuleDataSetupInterface                                                   $setup
     */
    public function __construct(EavSetupFactory $eavSetupFactory,
                                AttributeRepositoryInterface $attributeRepository,
                                DataHelper $dataHelper,
                                \DataFeedWatch\Connector\Model\Api\User $apiUser,
                                \DataFeedWatch\Connector\Cron\FillUpdatedAtTable $cron,
                                \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
                                \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable,
                                ModuleDataSetupInterface $setup) {
        $this->eavSetupFactory     = $eavSetupFactory;
        $this->attributeRepository = $attributeRepository;
        $this->dataHelper          = $dataHelper;
        $this->apiUser             = $apiUser;
        $this->cron                = $cron;
        $this->productCollection   = $productCollection;
        $this->configurable        = $configurable;
        $this->setup               = $setup;
    }
    
    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        $this->setup = $setup;
        
        $this->installApiUser();
        $this->installAttributes();
        $this->dataHelper->restoreOriginalAttributesConfig();
        $this->cron->execute();
    }
    
    protected function installApiUser() {
        $this->apiUser->loadDfwUser();
        $this->apiUser->createDfwUser();
    }
    
    protected function installAttributes() {
        $this->installIgnoreDataFeedAttribute();
        $this->installDfwParentIdsAttribute();
    }
    
    protected function installIgnoreDataFeedAttribute() {
        $properties = [
            'type'                     => 'int',
            'label'                    => 'Ignore In DataFeedWatch',
            'input'                    => 'select',
            'source'                   => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
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
    
    protected function installDfwParentIdsAttribute() {
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

        $productCollection = $this->productCollection->create();
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productCollection as $product) {
            $parentIds = $this->configurable->getParentIdsByChild($product->getId());
            if (!empty($parentIds)) {
                $product->setDfwParentIds(current($parentIds));
                $product->getResource()->saveAttribute($product, 'dfw_parent_ids');
            }
        }
    }
    
    /**
     * @param string $attributeCode
     * @param array  $attributeProperties
     * @param string $entityType
     */
    protected function createAttribute($attributeCode, array $attributeProperties, $entityType = Product::ENTITY) {
        $this->setup->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        try {
            $this->attributeRepository->get(Product::ENTITY, $attributeCode);
        } catch (NoSuchEntityException $e) {
            $eavSetup->addAttribute(
                $entityType,
                $attributeCode, $attributeProperties
            );
        }
        $avConfig = ObjectManager::getInstance()->get(Config::class);
        $avConfig->clear();
        $this->setup->endSetup();
    }
}
