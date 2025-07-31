<?php

namespace Iyzico\Iyzipay\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class InstallmentOptions implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $availableInstallments = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        foreach ($availableInstallments as $installment) {
            $options[] = [
                'value' => $installment,
                'label' => __('%1 Taksit', $installment)
            ];
        }

        return $options;
    }
}
