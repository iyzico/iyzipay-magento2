<?php

namespace Iyzico\Iyzipay\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CategoryList implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(CollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addIsActiveFilter();

        $options = [];

        foreach ($collection as $category) {
            if ($category->getId() > 1) { // Exclude root categories
                $options[] = [
                    'value' => $category->getId(),
                    'label' => $category->getName()
                ];
            }
        }

        return $options;
    }
}
