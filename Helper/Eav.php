<?php
/**
 *  {m2.internal}
 *  Created by prometheus
 *  User: hestajoe
 *  Date:  11/1/2019
 *  Time:  3:20 PM
 */

namespace Hestaworks\ScopeHelper\Helper;

use Magento\Catalog\Model\AbstractModel;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Eav
 * @package Hestaworks\ScopeHelper\Helper
 */
class Eav extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var Magento\Catalog\Model\Locator\LocatorInterface
     */
    private $locator;

    /**
     * Eav constructor.
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param LocatorInterface $locator
     * @param Context $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        LocatorInterface $locator,
        Context $context
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->locator = $locator;
    }

    /**
     * @param AbstractModel $entity
     * @param AttributeInterface $attribute
     * @return array|bool
     */
    public function compareValuesByStore(
        AbstractModel $entity,
        AttributeInterface $attribute
    ) {
        $hasStoreValue = false;
        if ($attribute->getBackendType() != 'static') {
            $connection = $this->resource->getConnection();
            $tableName = $attribute->getBackendTable();
            $storeData = $this->getStoreData();
            $select = $connection->select()->from(
                $tableName
            )->where(
                'entity_id = ?',
                $entity->getId()
            )->where(
                'attribute_id = ?',
                $attribute->getId()
            );
            $currentStoreId = $entity->getStore()->getId();
            $storeValues = $connection->fetchAll($select);
            $values = [];
            foreach ($storeValues as $storeValue) {
                if ($currentStoreId == 0 && $storeValue['store_id'] > 0) {
                    $hasStoreValue = true;
                }
                $values[$storeData[$storeValue['store_id']]['label']]['value'] = $storeValue['value'];
                $values[$storeData[$storeValue['store_id']]['label']]['store_id'] = $storeValue['store_id'];
            }

            if ($hasStoreValue) {
                return $values;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getStoreData(): array
    {
        $storeManagerDataList = $this->storeManager->getStores();
        $options = [];

        foreach ($storeManagerDataList as $key => $value) {
            $options[$key] = ['label' => $value['name'], 'code' => $value['code']];
        }
        $options[0] = ['label' => 'All store views', 'code' => 'default'];

        return $options;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array $values
     * @return string
     */
    public function getValueHtml(AttributeInterface $attribute, array $values,int $storeId): string
    {
        $valueHtml = '';
        foreach ($values as $key => $value) {
            if ($value['store_id'] != $storeId) {
                $valueHtml .= '[' . $key . '] => ' . $this->getValue($attribute, $value['value']) . "\r\n" . PHP_EOL;
            }
        }
        return $valueHtml;
    }

    /**
     * @param AttributeInterface $attribute
     * @param string $value
     * @return null|string
     */
    public function getValue(AttributeInterface $attribute, $value)
    {
        if ($attribute->usesSource()) {
            return $attribute->getSource()->getOptionText($value);
        }
        return $value;
    }

    /**
     * @param $values
     * @return bool
     */
    public function getIfDistinctByValue($values): bool
    {
        $usedValues = [];
        foreach ($values as $value) {
            if (!in_array($value['value'], $usedValues)) {
                $usedValues[] = $value['value'];
            } else {
                return false;
            }
        }
        return true;
    }
}
