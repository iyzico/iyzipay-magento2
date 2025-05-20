<?php

namespace Iyzico\Iyzipay\Ui\DataProvider;

use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;

class ProductInstallmentFormDataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create()->addFieldToFilter('product_sku', ['notnull' => true]);
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $model) {
            $modelData = $model->getData();

            // Taksit ayarlarını hazırla
            if (isset($modelData['settings'])) {
                try {
                    $installmentOptions = json_decode($modelData['settings'], true);

                    // JSON formatı düzgün mü kontrol et
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Eğer doğrudan taksit sayıları dizisi ise
                        if (is_array($installmentOptions)) {
                            $modelData['data']['installment_options'] = $installmentOptions;
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error('ProductInstallmentFormDataProvider: ' . $e->getMessage());
                }
            }

            $this->loadedData[$model->getId()] = $modelData;
        }

        $data = $this->dataPersistor->get('iyzico_installment');
        if (!empty($data)) {
            $id = isset($data['installment_id']) ? $data['installment_id'] : null;
            $this->loadedData[$id] = $data;
            $this->dataPersistor->clear('iyzico_installment');
        }

        return $this->loadedData;
    }
}
