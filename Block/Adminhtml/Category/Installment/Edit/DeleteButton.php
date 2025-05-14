<?php

namespace Iyzico\Iyzipay\Block\Adminhtml\Category\Installment\Edit;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
    }

    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $installment = $this->registry->registry('iyzico_installment');

        if ($installment && $installment->getCategoryId()) {
            $data = [
                'label' => __('Sil'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Bu taksit ayarını silmek istediğinize emin misiniz?'
                ) . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    /**
     * Get URL for delete button
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $installment = $this->registry->registry('iyzico_installment');
        return $this->urlBuilder->getUrl('*/*/delete', ['id' => $installment->getCategoryId()]);
    }
}