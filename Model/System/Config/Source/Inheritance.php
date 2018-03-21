<?php
/**
 * Created by Q-Solutions Studio
 * Date: 22.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Inheritance implements ArrayInterface
{
    const CHILD_OPTION_ID                = 1;
    const CHILD_OPTION_LABEL             = 'Child';
    const PARENT_OPTION_ID               = 2;
    const PARENT_OPTION_LABEL            = 'Parent';
    const CHILD_THEN_PARENT_OPTION_ID    = 3;
    const CHILD_THEN_PARENT_OPTION_LABEL = 'Child Then Parent';
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CHILD_OPTION_ID,
                'label' => __('Child'),
            ],
            [
                'value' => self::PARENT_OPTION_ID,
                'label' => __('Parent'),
            ],
            [
                'value' => self::CHILD_THEN_PARENT_OPTION_ID,
                'label' => __('Child Then Parent'),
            ],
        ];
    }
    
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::CHILD_OPTION_ID             => __('Child'),
            self::PARENT_OPTION_ID            => __('Parent'),
            self::CHILD_THEN_PARENT_OPTION_ID => __('Child Then Parent'),
        ];
    }
}
