<?php

namespace Iyzico\Iyzipay\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ProductInstallmentActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        array              $components = [],
        array              $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['installment_id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                'iyzico/productinstallmentsettings/edit',
                                [
                                    'id' => $item['installment_id']
                                ]
                            ),
                            'label' => __('Düzenle')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                'iyzico/productinstallmentsettings/delete',
                                [
                                    'id' => $item['installment_id']
                                ]
                            ),
                            'label' => __('Sil'),
                            'confirm' => [
                                'title' => __('Silmek istediğinize emin misiniz?'),
                                'message' => __('Bu kaydı silmek istediğinize emin misiniz?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
