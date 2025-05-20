<?php

namespace Iyzico\Iyzipay\Model;

use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class IyziInstallment extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_installment_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get product SKU
     *
     * @return string|null
     */
    public function getProductSku()
    {
        return $this->getData('product_sku');
    }

    /**
     * Set product SKU
     *
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku)
    {
        return $this->setData('product_sku', $productSku);
    }

    /**
     * Get category ID
     *
     * @return int|null
     */
    public function getCategoryId()
    {
        return $this->getData('category_id');
    }

    /**
     * Set category ID
     *
     * @param int $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData('category_id', $categoryId);
    }

    /**
     * Get settings
     *
     * @return string|null
     */
    public function getSettings()
    {
        return $this->getData('settings');
    }

    /**
     * Set settings
     *
     * @param string $settings
     * @return $this
     */
    public function setSettings($settings)
    {
        return $this->setData('settings', $settings);
    }
}
