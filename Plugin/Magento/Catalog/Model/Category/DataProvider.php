<?php
/**
 *  {m2.internal}
 *  Created by prometheus
 *  User: hestajoe
 *  Date:  11/1/2019
 *  Time:  3:20 PM
 */

namespace Hestaworks\ScopeHelper\Plugin\Magento\Catalog\Model\Category;

use Hestaworks\ScopeHelper\Helper\Data;
use Hestaworks\ScopeHelper\Helper\Eav;
use Magento\Catalog\Model\Category\DataProvider as CoreDataProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DataProvider. Plugin class for \Magento\Catalog\Model\Category\DataProvider
 * @package Hestaworks\ScopeHelper\Plugin\Magento\Catalog\Model\Category
 */
class DataProvider
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
     * @var Eav
     */
    private $eavHelper;
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * DataProvider constructor.
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param Eav $eavHelper
     * @param Data $dataHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ResourceConnection  $resource,
        Eav $eavHelper,
        Data $dataHelper
    ) {
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->eavHelper = $eavHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param CoreDataProvider $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetDefaultMetaData(
        CoreDataProvider $subject,
        $result
    ) {
        if($this->storeManager->isSingleStoreMode()){
            return $result;
        }
        $currentCategory = $subject->getCurrentCategory();
        $categoryAttributes = $currentCategory->getAttributes();

        foreach ($result as $metaAttrCode => &$attrMeta) {
            if (!isset($categoryAttributes[$metaAttrCode])) {
                continue;
            }
            $attribute = $categoryAttributes[$metaAttrCode];
            if ($attribute->isScopeGlobal()) {
                continue;
            }
            if (!$values = $this->eavHelper->compareValuesByStore($currentCategory, $attribute)) {
                continue;
            }
            $valueHtml = $this->eavHelper->getValueHtml($attribute, $values,$currentCategory->getStoreId());
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
            $attrMeta['tooltip'] = $tooltip;
        }
        return $result;
    }
}
