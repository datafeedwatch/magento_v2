<?php
/**
 * Created by Q-Solutions Studio
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Jakub Winkler <jwinkler@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model\System\Config\Source;

/**
 * Class Mode
 * @package DataFeedWatch\Connector\Model\System\Config\Source
 */
class Mode extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /** @var int  */
    const DATAFEED_CONNECTOR_MODE_LIVE = 0;

    /** @var int  */
    const DATAFEED_CONNECTOR_MODE_TEST = 1;

    /** @var string  */
    const XPATH_DATAFEED_CONNECTOR_MODE = 'datafeedwatch_connector/general/mode';

    /** @var string  */
    const XPATH_DATAFEED_CONNECTOR_TEST_URL = 'datafeedwatch_connector/general/test_url';


    /** @var array $_options */
    protected $_options;

    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => self::DATAFEED_CONNECTOR_MODE_LIVE,  'label' => __('Live')],
                ['value' => self::DATAFEED_CONNECTOR_MODE_TEST, 'label' => __('Testing')],
            ];
        }
        return $this->_options;
    }

    /**
     * @return array
     */
    final public function toOptionArray(): array
    {
        return $this->getAllOptions();
    }
}
