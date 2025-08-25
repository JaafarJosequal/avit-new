<?php
namespace Josequal\DataImportExport\Model\Export;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class CategoryExport
{
    protected $categoryCollectionFactory;
    protected $resourceConnection;
    protected $storeManager;

    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    public function exportCategories($filters = [])
    {
        $collection = $this->categoryCollectionFactory->create();

        // Apply filters
        if (!empty($filters['is_active'])) {
            $collection->addFieldToFilter('is_active', $filters['is_active']);
        }

        if (!empty($filters['parent_id'])) {
            $collection->addFieldToFilter('parent_id', $filters['parent_id']);
        }

        $collection->addAttributeToSelect('*');
        $collection->setOrder('position', 'ASC');

        $exportData = [];
        $headers = [
            'category_id',
            'code',
            'parent_id',
            'name',
            'description',
            'is_active',
            'position',
            'url_key'
        ];

        $exportData[] = $headers;

        foreach ($collection as $category) {
            $row = [];
            foreach ($headers as $header) {
                switch ($header) {
                    case 'category_id':
                        $row[] = $category->getId();
                        break;
                    case 'code':
                        $row[] = $category->getCode();
                        break;
                    case 'parent_id':
                        $row[] = $category->getParentId();
                        break;
                    case 'name':
                        $row[] = $category->getName();
                        break;
                    case 'description':
                        $row[] = $category->getDescription();
                        break;
                    case 'is_active':
                        $row[] = $category->getIsActive();
                        break;
                    case 'position':
                        $row[] = $category->getPosition();
                        break;
                    case 'url_key':
                        $row[] = $category->getUrlKey();
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
