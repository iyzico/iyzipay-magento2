<?php

namespace Iyzico\Iyzipay\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class InstallmentSettings extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
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
                if (isset($item['settings'])) {
                    $settings = json_decode($item['settings'], true);
                    $item[$this->getData('name')] = $this->formatSettings($settings);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Format settings for display
     *
     * @param array $settings
     * @return string
     */
    private function formatSettings($settings)
    {
        if (empty($settings)) {
            return 'Taksit seçeneği yoktur';
        }

        if (is_array($settings)) {
            // Eğer düz bir dizi ise (direkt taksit sayıları)
            if (isset($settings[0]) && !is_array($settings[0])) {
                $taksitler = [];

                foreach ($settings as $installment) {
                    $taksitler[] = $installment . ' Taksit';
                }

                return implode(', ', $taksitler);
            }
        }

        return '';
    }
}