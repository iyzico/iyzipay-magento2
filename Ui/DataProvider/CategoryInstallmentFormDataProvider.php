<?php

namespace Iyzico\Iyzipay\Ui\DataProvider;

use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class CategoryInstallmentFormDataProvider extends AbstractDataProvider
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
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
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
                $installmentOptions = json_decode($modelData['settings'], true);

                // JSON formatı düzgün mü kontrol et
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Eğer doğrudan taksit sayıları dizisi ise
                    if (is_array($installmentOptions)) {
                        $modelData['data']['installment_options'] = $installmentOptions;
                    }
                }
            }

            $this->loadedData[$model->getCategoryId()] = $modelData;
        }

        $data = $this->dataPersistor->get('iyzico_installment');
        if (!empty($data)) {
            $id = isset($data['category_id']) ? $data['category_id'] : null;
            $this->loadedData[$id] = $data;
            $this->dataPersistor->clear('iyzico_installment');
        }

        return $this->loadedData;
    }
}
