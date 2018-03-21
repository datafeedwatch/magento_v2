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

    /**
     * UpgradeData constructor.
     * @param DataHelper $dataHelper
     * @param Cache $cache
     */
    public function __construct(
        DataHelper $dataHelper,
        Config $config,
        Cache $cache
    ) {
        $this->dataHelper   = $dataHelper;
        $this->config       = $config;
        $this->cache        = $cache;
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
    }

    public function version010To100()
    {
        $this->dataHelper->setInstallationComplete();
        $this->config->deleteConfig('datafeedwatch_connector/general/debug', 'default', 0);
        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['config']);
    }
}
