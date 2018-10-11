<?php
/**
 * Created by Q-Solutions Studio
 * Date: 31.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Plugin;

use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\TestFramework\Inspection\Exception;

/**
 * Class ConfigPlugin
 * @package DataFeedWatch\Connector\Plugin
 */
class ConfigPlugin
{
    /** @var DataHelper */
    public $dataHelper;

    /**
     * ConfigPlugin constructor.
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Config\Model\Config $config
     */
    public function beforeSave(\Magento\Config\Model\Config $config)
    {
        $productUrlXpath = DataHelper::PRODUCT_URL_CUSTOM_INHERITANCE_XPATH;
        $imageUrlXpath   = DataHelper::IMAGE_URL_CUSTOM_INHERITANCE_XPATH;
        
        if ($this->hasConfigDataChanged($config, $productUrlXpath)
            || $this->hasConfigDataChanged($config, $imageUrlXpath)
        ) {
            $this->dataHelper->updateLastInheritanceUpdateDate();
        }
    }
    
    /**
     * @param \Magento\Config\Model\Config $config
     * @param string                       $xpath
     *
     * @return bool
     */
    private function hasConfigDataChanged($config, $xpath)
    {
        
        return $config->getConfigDataValue($xpath) !== $this->getConfigDataFromXpath($config, $xpath);
    }

    /**
     * @param $config
     * @param $xpath
     * @return mixed|null
     */
    private function getConfigDataFromXpath($config, $xpath)
    {
        $xpath = explode('/', $xpath);
        if (!is_array($xpath)) {
            return null;
        }
        
        if (count($xpath) === 3) {
            unset($xpath[0]);
        }
        
        try {
            $group      = reset($xpath);
            $field      = end($xpath);
            $configPath = $config->getGroups();
            if (is_array($configPath) && array_key_exists($group, $configPath)) {
                $configPath = $configPath[$group];
            } else {
                return null;
            }
            if (is_array($configPath) && array_key_exists('fields', $configPath)) {
                $configPath = $configPath['fields'];
            } else {
                return null;
            }
            if (is_array($configPath) && array_key_exists($field, $configPath)) {
                $configPath = $configPath[$field];
            } else {
                return null;
            }
            
            if (is_array($configPath) && array_key_exists('value', $configPath)) {
                return $configPath['value'];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
