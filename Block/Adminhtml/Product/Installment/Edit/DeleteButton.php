<?php

namespace Iyzico\Iyzipay\Block\Adminhtml\Product\Installment\Edit;

use Iyzico\Iyzipay\Model\IyziInstallment;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var Registry
     */
    protected Registry $coreRegistry;

    /**
     * @param  Context  $context
     * @param  Registry  $registry
     */
    public function __construct(
        Context $context,
        Registry $registry
    ) {
        $this->context = $context;
        $this->coreRegistry = $registry;
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getModelId()) {
            $data = [
                'label' => __('Sil'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''.__(
                        'Silmek istediğinize emin misiniz?'
                    ).'\', \''.$this->getDeleteUrl().'\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Return model ID
     *
     * @return int|null
     */
    public function getModelId(): ?int
    {
        /** @var IyziInstallment $installment */
        $installment = $this->coreRegistry->registry('iyzico_installment');
        return $installment ? $installment->getId() : null;
    }

    /**
     * Get URL for delete button
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['id' => $this->getModelId()]);
    }
}
