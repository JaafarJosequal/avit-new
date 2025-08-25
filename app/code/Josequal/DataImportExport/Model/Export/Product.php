<?php
namespace Josequal\DataImportExport\Model\Export;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class Product
{
    protected $productCollectionFactory;
    protected $resourceConnection;
    protected $storeManager;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    public function exportProducts($filters = [])
    {
        $collection = $this->productCollectionFactory->create();

        // Apply filters
        if (!empty($filters['category_id'])) {
            $collection->joinField(
                'category_id',
                'catalog_category_product',
                'category_id',
                'product_id=entity_id',
                null,
                'left'
            );
            $collection->addFieldToFilter('category_id', $filters['category_id']);
        }

        if (!empty($filters['status'])) {
            $collection->addFieldToFilter('status', $filters['status']);
        }

        if (!empty($filters['type_id'])) {
            $collection->addFieldToFilter('type_id', $filters['type_id']);
        }

        $collection->addAttributeToSelect('*');
        $collection->setPageSize(1000);

        $exportData = [];
        $headers = [
            'sku',
            'name',
            'description',
            'short_description',
            'price',
            'special_price',
            'quantity',
            'status',
            'visibility',
            'category_ids',
            'product_type',
            'image',
            'small_image',
            'thumbnail'
        ];

        $exportData[] = $headers;

        foreach ($collection as $product) {
            $row = [];
            foreach ($headers as $header) {
                switch ($header) {
                    case 'sku':
                        $row[] = $product->getSku();
                        break;
                    case 'name':
                        $row[] = $product->getName();
                        break;
                    case 'description':
                        $row[] = $product->getDescription();
                        break;
                    case 'short_description':
                        $row[] = $product->getShortDescription();
                        break;
                    case 'price':
                        $row[] = $product->getPrice();
                        break;
                    case 'special_price':
                        $row[] = $product->getSpecialPrice();
                        break;
                    case 'quantity':
                        $row[] = $product->getQty();
                        break;
                    case 'status':
                        $row[] = $product->getStatus();
                        break;
                    case 'visibility':
                        $row[] = $product->getVisibility();
                        break;
                    case 'category_ids':
                        $row[] = implode(',', $product->getCategoryIds());
                        break;
                    case 'product_type':
                        $row[] = $product->getTypeId();
                        break;
                    case 'image':
                        $row[] = $product->getImage();
                        break;
                    case 'small_image':
                        $row[] = $product->getSmallImage();
                        break;
                    case 'thumbnail':
                        $row[] = $product->getThumbnail();
                        break;
                    default:
                        $row[] = '';
                }
            }
            $exportData[] = $row;
        }

        return $this->arrayToCsv($exportData);
    }

    protected function arrayToCsv($data)
    {
        $output = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
