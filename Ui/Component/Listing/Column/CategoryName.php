<?php

namespace Iyzico\Iyzipay\Ui\Component\Listing\Column;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryName extends Column
{
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CategoryFactory $categoryFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CategoryFactory $categoryFactory,
        array $components = [],
        array $data = []
    ) {
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['category_id'])) {
                    $category = $this->categoryFactory->create()->load($item['category_id']);
                    $item['category_name'] = $category->getName();
                }
            }
        }

        return $dataSource;
    }
}
