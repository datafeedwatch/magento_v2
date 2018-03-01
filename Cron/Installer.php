<?php
/**
 * Created by Q-Solutions Studio
 * Date: 28.02.18
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Cron;

use DataFeedWatch\Connector\Model\Api\User;
use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\PageCache\Model\Cache\Type as Cache;

class Installer
{
    /** @var User */
    protected $user;

    /** @var DataHelper */
    private $dataHelper;

    /** @var Cache */
    private $cache;

    /**
     * Installer constructor.
     * @param User $user
     * @param DataHelper $dataHelper
     * @param Cache $cache
     */
    public function __construct(
        User $user,
        DataHelper $dataHelper,
        Cache $cache
    ) {
        $this->user       = $user;
        $this->dataHelper = $dataHelper;
        $this->cache      = $cache;
    }

    public function execute()
    {
        $this->user->loadDfwUser();
        $this->user->createDfwUser();
        $this->dataHelper->restoreOriginalAttributesConfig();
        $this->dataHelper->setInstallationComplete();
        $types = ['config', 'collections', 'eav', 'config_api', 'config_api2'];
        foreach ($types as $type) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, [$type]);
        }

        return $this;
    }
}
