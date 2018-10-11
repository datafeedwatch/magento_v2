<?php
/**
 * Created by Q-Solutions Studio
 * Date: 22.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Observer\Adminhtml;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddAttributeFields
 * @package DataFeedWatch\Connector\Observer\Adminhtml
 */
class AddAttributeFields implements ObserverInterface
{
    /** @var \Magento\Config\Model\Config\Source\YesnoFactory */
    private $yesNoFactory;

    /** @var \Magento\Framework\Registry */
    private $registry;

    /** @var \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance */
    private $inheritance;
    
    /**
     * @param \Magento\Config\Model\Config\Source\YesnoFactory                $yesnoFactory
     * @param \Magento\Framework\Registry                                     $registry
     * @param \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance $inheritance
     */
    public function __construct(
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \Magento\Framework\Registry $registry,
        \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance $inheritance
    ) {
        
        $this->yesNoFactory = $yesnoFactory;
        $this->registry     = $registry;
        $this->inheritance  = $inheritance;
    }
    
    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form      = $observer->getForm();
        $fieldSet  = $form->getElement('base_fieldset');
        $attribute = $this->registry->registry('entity_attribute');
        $yesNo     = $this->yesNoFactory->create()->toOptionArray();
        
        $fieldSet->addField(
            'import_to_dfw',
            'select',
            [
                'name'     => 'import_to_dfw',
                'label'    => __('Import To DataFeedWatch'),
                'title'    => __('Import To DataFeedWatch'),
                'values'   => $yesNo,
                'disabled' => $attribute->hasCanConfigureImport() && !$attribute->getCanConfigureImport(),
            ]
        );
        
        $inheritance = $this->inheritance->toOptionArray();
        $fieldSet->addField(
            'inheritance',
            'select',
            [
                'name'     => 'inheritance',
                'label'    => __('DataFeedWatch Inheritance'),
                'title'    => __('DataFeedWatch Inheritance'),
                'values'   => $inheritance,
                'disabled' => $attribute->hasCanConfigureImport() && !$attribute->getCanConfigureInheritance(),
            ]
        );
    }
}
