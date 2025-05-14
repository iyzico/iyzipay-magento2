<?php

namespace Iyzico\Iyzipay\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryInstallmentActions extends Column
{
    /** Url path */
    const URL_PATH_EDIT = 'iyzico/categoryinstallmentsettings/edit';
    const URL_PATH_DELETE = 'iyzico/categoryinstallmentsettings/delete';

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'id' => $item['category_id']
                                ]
                            ),
                            'label' => __('Düzenle')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [
                                    'id' => $item['category_id']
                                ]
                            ),
                            'label' => __('Sil'),
                            'confirm' => [
                                'title' => __('Sil %1', $item['category_id']),
                                'message' => __('Bu taksit ayarını silmek istediğinize emin misiniz?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
