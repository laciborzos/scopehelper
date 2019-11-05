<?php


namespace Hestaworks\ScopeHelper\Plugin\Magento\Config\Block\System\Config\Form;

use Magento\Config\Block\System\Config\Form;
use Magento\Config\Block\System\Config\Form\Field as FormField;
use Magento\Config\Model\Config\SourceFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Hestaworks\ScopeHelper\Helper\Data;

/**
 * Class Field
 *
 * @package Hestaworks\ScopeHelper\Plugin\Magento\Config\Block\System\Config\Form
 */
class Field
{
    /**
     * @var AbstractElement
     */
    protected $element;
    /**
     * @var Data
     */
    protected $configHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * Field constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Data                  $configHelper
     * @param Config                $config
     * @param SourceFactory         $sourceFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $configHelper,
        Config $config,
        SourceFactory $sourceFactory
    ) {
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->config = $config;
        $this->sourceFactory = $sourceFactory;
    }

    /**
     * @param  FormField       $subject
     * @param  AbstractElement $element
     * @return array
     * @throws LocalizedException
     */
    public function beforeRender(
        FormField $subject,
        AbstractElement $element
    ) {
        if ($this->storeManager->isSingleStoreMode()) {
            return [$element];
        }
        $this->element = $element;
        if ($element->getScope() == Form::SCOPE_STORES
            || ($element->getScope() == Form::SCOPE_WEBSITES && !$this->canHaveValueInStore())
            || ($element->getScope() == Form::SCOPE_DEFAULT && !$this->canHaveValueInStore()
            && !$this->canHaveValueInWebsite())
        ) {
            return [$element];
        }
        $storeData = $this->getStoreData();
        $websiteData = $this->getWebsiteData();
        $tooltipsAndClasses = $this->configHelper->getTooltips(
            $element,
            $element->getScope(),
            $storeData,
            $websiteData
        );
        $element->setTooltip($tooltipsAndClasses['tooltip']);
        $element->setClass($element->getClass().' '.$tooltipsAndClasses['class']);
        return [$element];
    }

    /**
     * @return bool
     */
    private function canHaveValueInWebsite()
    {
        return $this->configHelper->canHaveValueInScope($this->element, 'showInWebsite');
    }

    /**
     * @return bool
     */
    private function canHaveValueInStore()
    {
        return $this->configHelper->canHaveValueInScope($this->element, 'showInStore');
    }

    /**
     * @return array
     */
    private function getStoreData(): array
    {
        $storeManagerDataList = $this->storeManager->getStores();
        $options = [];

        foreach ($storeManagerDataList as $key => $value) {
            $options[$key] = ['label' => $value['name'], 'code' => $value['code']];
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getWebsiteData(): array
    {
        $storeManagerDataList = $this->storeManager->getWebsites();
        $options = [];

        foreach ($storeManagerDataList as $key => $value) {
            $options[$key] = ['label' => $value['name'], 'code' => $value['code']];
        }

        return $options;
    }
}
