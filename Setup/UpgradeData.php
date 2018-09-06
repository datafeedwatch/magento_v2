<?php
/**
 * Created by Q-Solutions Studio
 * Date: 28.02.18
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Setup;

use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\PageCache\Model\Cache\Type as Cache;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /** @var DataHelper */
    private $dataHelper;

    /** @var ModuleDataSetupInterface */
    private $setup;

    /** @var Cache */
    private $cache;

    /** @var Config */
    private $config;

    /** @var EavSetup */
    private $eavSetup;

    /**
     * UpgradeData constructor.
     * @param DataHelper $dataHelper
     * @param Config $config
     * @param Cache $cache
     * @param EavSetup $eavSetup
     */
    public function __construct(
        DataHelper $dataHelper,
        Config $config,
        Cache $cache,
        EavSetup $eavSetup
    ) {
        $this->dataHelper = $dataHelper;
        $this->config     = $config;
        $this->cache      = $cache;
        $this->eavSetup   = $eavSetup;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->setup = $setup;
        if (version_compare($context->getVersion(), '0.1.0') === 0) {
            $this->version010To100();
        }
        if (version_compare($context->getVersion(), '1.2.0') === 0) {
            $this->version110To120();
        }
    }

    /**
     *
     */
    public function version010To100()
    {
        $this->config->deleteConfig('datafeedwatch_connector/general/debug', 'default', 0);
        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['config']);
    }

    /**
     *
     */
    public function version110To120()
    {
        $this->eavSetup->removeAttribute(Product::ENTITY, 'dfw_parent_ids');
    }
}
