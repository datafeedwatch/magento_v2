<?php
/**
 * Created by Q-Solutions Studio
 * Date: 30.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Logger\Handler;

use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger;

abstract class Base extends BaseHandler
{
    /** @var DataHelper */
    private $dataHelper;
    
    protected $dirName = 'base';
    
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;
    
    /**
     * File name
     * @var string
     */
    protected $fileName = BP . '/var/log/DataFeedWatch_Connector/base.log';
    
    /**
     * @param DriverInterface $filesystem
     * @param DataHelper      $dataHelper
     * @param null            $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        DataHelper $dataHelper,
        $filePath = null
    ) {
        $this->dataHelper = $dataHelper;
        $this->fileName   = sprintf('%s/var/log/DataFeedWatch_Connector/%s/%s.log', BP, $this->dirName, date('Y-m-d'));
        parent::__construct($filesystem, $filePath);
    }
    
    public function write(array $record)
    {
        if ($this->dataHelper->isDebugModeEnabled()) {
            parent::write($record);
        }
    }
}
