<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Model\ResourceModel\Catalog\Category;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\MagentoCoreBugFixes\FakeSelectForMagentoIssue32292 as FakeSelect;
use Baldwin\UrlDataIntegrityChecker\Storage\StorageInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class UrlKeyCollection extends DataCollection implements SearchResultInterface
{
    private $storage;
    private $fakeSelect;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        StorageInterface $storage,
        FakeSelect $fakeSelect
    ) {
        parent::__construct($entityFactory);

        $this->storage = $storage;
        $this->fakeSelect = $fakeSelect;
    }

    /**
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return UrlKeyCollection<DataObject>
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!$this->isLoaded()) {
            $urlKeys = $this->storage->read(UrlKeyChecker::STORAGE_IDENTIFIER);
            foreach ($urlKeys as $urlKey) {
                $this->addItem($this->createDataObject($urlKey));
            }

            foreach ($this->_orders as $field => $direction) {
                usort($this->_items, function ($itemA, $itemB) use ($field, $direction) {
                    $comparisonFieldA = $itemA->getData($field);
                    $comparisonFieldB = $itemB->getData($field);

                    if ($direction === DataCollection::SORT_ORDER_ASC) {
                        return $comparisonFieldA <=> $comparisonFieldB;
                    } else {
                        return $comparisonFieldB <=> $comparisonFieldA;
                    }
                });

                break; // breaking after using one entry of $this->_orders
            }

            $this->_setIsLoaded();

            // fill $this->_totalRecords by calling getSize
            // if we don't do this and leave this up to Magento, it will do it too late in Magento 2.4.x
            $this->getSize();

            // page the data, need to do this after setting the data as loaded,
            // otherwise the getCurPage would create a recursive problem
            $startIndex = ($this->getCurPage() - 1) * $this->getPageSize();
            $this->_items = array_slice($this->_items, $startIndex, $this->getPageSize());
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function createDataObject(array $arguments = []): DataObject
    {
        $arguments['hash'] = sha1(json_encode($arguments) ?: '');

        $obj = $this->_entityFactory->create($this->_itemObjectClass, ['data' => $arguments]);

        $attributes = [];
        foreach ($arguments as $key => $value) {
            $attribute = new AttributeValue([
                AttributeInterface::ATTRIBUTE_CODE => $key,
                AttributeInterface::VALUE          => $value,
            ]);

            $attributes[] = $attribute;
        }
        $obj->setCustomAttributes($attributes);

        return $obj;
    }

    /**
     * @return UrlKeyCollection<DataObject>
     */
    public function setItems(array $items = null)
    {
        throw new LocalizedException(__('Not implemented: setItems!'));
    }

    public function getAggregations()
    {
        throw new LocalizedException(__('Not implemented: getAggregations!'));
    }

    /**
     * @return UrlKeyCollection<DataObject>
     */
    public function setAggregations($aggregations)
    {
        throw new LocalizedException(__('Not implemented: setAggregations!'));
    }

    public function getSearchCriteria()
    {
        throw new LocalizedException(__('Not implemented: getSearchCriteria!'));
    }

    /**
     * @return UrlKeyCollection<DataObject>
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {
        throw new LocalizedException(__('Not implemented: setSearchCriteria!'));
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @return UrlKeyCollection<DataObject>
     */
    public function setTotalCount($totalCount)
    {
        throw new LocalizedException(__('Not implemented: setTotalCount!'));
    }

    public function getSelect(): FakeSelect
    {
        return $this->fakeSelect;
    }
}
