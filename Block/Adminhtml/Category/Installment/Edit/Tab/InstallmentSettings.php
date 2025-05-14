<?php

namespace Iyzico\Iyzipay\Block\Adminhtml\Category\Installment\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class InstallmentSettings extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var string
     */
    protected $_template = 'Iyzico_Iyzipay::category/installment/edit/tab/installment-settings.phtml';

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * Get current installment model
     *
     * @return \Iyzico\Iyzipay\Model\IyziInstallment
     */
    public function getInstallment()
    {
        return $this->coreRegistry->registry('iyzico_installment');
    }

    /**
     * Get installment settings
     *
     * @return array
     */
    public function getInstallmentSettings()
    {
        $installment = $this->getInstallment();
        $settings = [];

        if ($installment && $installment->getSettings()) {
            try {
                $settingsData = $installment->getSettings();
                $settings = json_decode($settingsData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return [];
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * Get available installment options
     *
     * @return array
     */
    public function getAvailableInstallments()
    {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    }

    /**
     * Get selected installment numbers from settings
     *
     * @return array
     */
    public function getSelectedInstallmentNumbers()
    {
        $settings = $this->getInstallmentSettings();

        // Eğer ayarlar direkt dizi olarak geldiyse
        if (is_array($settings) && isset($settings[0]) && !is_array($settings[0])) {
            return $settings;
        }

        return [];
    }

    /**
     * Get percentage value for a specific installment option
     *
     * @param int $installmentNumber
     * @return string
     */
    public function getPercentageValue($installmentNumber)
    {
        $settings = $this->getInstallmentSettings();

        if (is_array($settings)) {
            foreach ($settings as $setting) {
                if (isset($setting['installment']) && isset($setting['percentage']) &&
                    (int)$setting['installment'] === (int)$installmentNumber) {
                    return $setting['percentage'];
                }
            }
        }

        return '0.00';
    }
}
