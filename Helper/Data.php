<?php
/**
 *  {m2.internal}
 *  Created by prometheus
 *  User: hestajoe
 *  Date:  11/1/2019
 *  Time:  3:20 PM
 */

namespace Hestaworks\ScopeHelper\Helper;

use Magento\Config\Block\System\Config\Form;
use Magento\Config\Model\Config\SourceFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Data
 * @package Hestaworks\ScopeHelper\Helper
 */
class Data extends AbstractHelper
{
    const WARNING_CLASS = "hesta-warning";

    /**
     * @var SourceFactory
     */
    private $sourceFactory;
    /**
     * @var Config
     */
    private $config;

    /**
     * Data constructor.
     * @param SourceFactory $sourceFactory
     * @param Config $config
     * @param Context $context
     */
    public function __construct(
        SourceFactory $sourceFactory,
        Config $config,
        Context $context
    ) {
        parent::__construct($context);
        $this->sourceFactory = $sourceFactory;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getWarningMessage(): string
    {
        return "Warning! This attribute has different value on lower scopes. %1";
    }

    /**
     * @return string
     */
    public function getInfoMessage(): string
    {
        return "Caution! Setting this attribute on this scope will have no effect. Check on lower scope levels.";
    }

    /**
     * @param AbstractElement $element
     * @param string $scope
     * @return bool
     */
    public function canHaveValueInScope(AbstractElement $element, $scope = 'showInStore'): bool
    {
        return (isset($element->getFieldConfig()[$scope])) ? $element->getFieldConfig()[$scope] : false;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function getFullElementPath(AbstractElement $element): string
    {
        return implode('/', [$element->getFieldConfig()['path'], $element->getFieldConfig()['id']]);
    }

    /**
     * @param AbstractElement $element
     * @return bool
     */
    public function canShowTooltipDetails(AbstractElement $element): bool
    {
        return true;
    }

    /**
     * @param AbstractElement $element
     * @param string $originalValue
     * @return string
     */
    public function getValue(AbstractElement $element, string $originalValue): string
    {

        $sourceModel = $element->getFieldConfig()['source_model'];
        if ($sourceModel) {
            $method = false;
            if (preg_match('/^([^:]+?)::([^:]+?)$/', $sourceModel, $matches)) {
                array_shift($matches);
                list($sourceModel, $method) = array_values($matches);
            }

            $sourceModel = $this->sourceFactory->create($sourceModel);
            if ($sourceModel instanceof DataObject) {
                $sourceModel->setPath($this->getPath());
            }
            if ($method) {
                if ($this->getType() == 'multiselect') {
                    $optionArray = $sourceModel->{$method}();
                } else {
                    $optionArray = [];
                    foreach ($sourceModel->{$method}() as $key => $value) {
                        if (is_array($value)) {
                            $optionArray[] = $value;
                        } else {
                            $optionArray[] = ['label' => $value, 'value' => $key];
                        }
                    }
                }
            } else {
                $optionArray = $sourceModel->toOptionArray($element->getFieldConfig()['type'] == 'multiselect');
            }

            foreach ($optionArray as $option) {
                if ($option['value'] == $originalValue) {
                    return $option['label']->getText();
                }
            }
        }
        return $originalValue;
    }

    /**
     * @param AbstractElement $element
     * @param Config $this ->config
     * @param string $type default|websites
     * @param array $storeData
     * @param array $websitesData
     * @return array
     * @throws LocalizedException
     */
    public function getTooltips(
        AbstractElement $element,
        string $type,
        array $storeData,
        array $websitesData = []
    ): array {
        $result = ['tooltip' => '', 'class' => ''];
        $lowerLevels = [];
        $values = [];
        $connection = $this->config->getConnection();
        if (Form::SCOPE_DEFAULT == $type) {
            $select = $connection->select()->from(
                $this->config->getMainTable()
            )->where(
                'path = ?',
                $this->getFullElementPath($element)
            )->where(
                'scope != ?',
                'default'
            )->order(
                'scope'
            );
        } elseif (Form::SCOPE_WEBSITES == $type) {
            $select = $connection->select()->from(
                $this->config->getMainTable()
            )->join(
                $this->config->getTable('store'),
                $this->config->getMainTable() . '.scope_id = ' . $this->config->getTable('store') . '.store_id'
            )->where(
                'path = ?',
                $this->getFullElementPath($element)
            )->where(
                'scope = ?',
                'stores'
            )->where(
                'website_id = ?',
                $element->getScopeId()
            )->order(
                'scope'
            );
        } else {
            return $result;
        }
        $storeValues = $connection->fetchAll($select);

        foreach ($storeValues as $storeValue) {
            $value = $this->getValue($element, $storeValue['value']);
            $values[] = $value;
            if (!$this->canShowTooltipDetails($element)) {
                continue;
            }
            if ($storeValue['scope'] == "websites") {
                $label = $websitesData[$storeValue['scope_id']]['label'];
                $lowerLevels[] = '[' . $label . '] => ' . $value;
            }
            if ($storeValue['scope'] == "stores") {
                $label = $storeData[$storeValue['scope_id']]['label'];
                $lowerLevels[] = '[' . $label . '] => ' . $value;
            }
        }
        $lowerLevels = array_reverse($lowerLevels);
        if (count($storeValues)) {
            $values[] = $this->getValue($element, $element->getValue());
            $values = array_unique($values);
            if (count($values) == 1) {
                $result['tooltip'] = __($this->getWarningMessage());
            } else {
                $result['tooltip'] = __(
                    $this->getInfoMessage(),
                    '<br/>' . implode('<br/>', $lowerLevels)
                );
                $result['class'] = self::WARNING_CLASS;
            }
        }
        return $result;
    }
}
