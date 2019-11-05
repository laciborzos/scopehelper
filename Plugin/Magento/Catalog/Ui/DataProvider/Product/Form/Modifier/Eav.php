<?php
/**
 *  {m2.internal}
 *  Created by prometheus
 *  User: hestajoe
 *  Date:  11/1/2019
 *  Time:  3:20 PM
 */

namespace Hestaworks\ScopeHelper\Plugin\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Hestaworks\ScopeHelper\Helper\Data;
use Hestaworks\ScopeHelper\Helper\Eav as EavHelper;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as CoreEav;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Eav. Plugin for \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav
 * @package Hestaworks\ScopeHelper\Plugin\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier
 */
class Eav
{
    /**
     * @var LocatorInterface
     */
    private $locator;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var ArrayManager
     */
    private $arrayManager;
    /**
     * @var EavHelper
     */
    private $eavHelper;
    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Eav constructor.
     * @param LocatorInterface $locator
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param ArrayManager $arrayManager
     * @param EavHelper $eavHelper
     * @param Data $dataHelper
     */
    public function __construct(
        LocatorInterface $locator,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        ArrayManager $arrayManager,
        EavHelper $eavHelper,
        Data $dataHelper
    ) {
        $this->locator = $locator;
        $this->storeManager = $storeManager;
        $this->arrayManager = $arrayManager;
        $this->eavHelper = $eavHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param CoreEav $subject
     * @param $result
     * @param $attribute
     * @param $groupCode
     * @param $sortOrder
     * @return array
     */
    public function afterSetupAttributeMeta(
        CoreEav $subject,
        $result,
        $attribute,
        $groupCode,
        $sortOrder
    ) {
        if ($this->isScopeGlobal($attribute) || $this->storeManager->isSingleStoreMode()) {
            return $result;
        }
        if (!$values = $this->eavHelper->compareValuesByStore($this->locator->getProduct(), $attribute)) {
            return $result;
        }
        $valueHtml = $this->eavHelper->getValueHtml($attribute, $values,$this->locator->getStore()->getId());
        if ($this->eavHelper->getIfDistinctByValue($values)) {
            $text = $this->dataHelper->getWarningMessage();
            $type = Data::WARNING_CLASS;
        } else {
            $text = $this->dataHelper->getInfoMessage();
            $type = "default";
        }
        $tooltip = [
            'link' => '#' . $type,
            'description' => __($text, $valueHtml)
        ];

        $configPath = ltrim(AbstractModifier::META_CONFIG_PATH, ArrayManager::DEFAULT_PATH_DELIMITER);
        $result = $this->arrayManager->merge($configPath, $result, [
            'tooltip' => $tooltip
        ]);
        return $result;
    }

    /**
     * @param $attribute
     * @return bool
     */
    private function isScopeGlobal($attribute)
    {
        return $attribute->getScope() === ProductAttributeInterface::SCOPE_GLOBAL_TEXT;
    }
}
