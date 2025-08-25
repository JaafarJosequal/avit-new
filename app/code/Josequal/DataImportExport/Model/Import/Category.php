<?php
namespace Josequal\DataImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManagerInterface;

class CategoryImport
{
    protected $categoryFactory;
    protected $resourceConnection;
    protected $csv;
    protected $storeManager;
    protected $connection;
    protected $fileReader;

    public function __construct(
        CategoryFactory $categoryFactory,
        ResourceConnection $resourceConnection,
        Csv $csv,
        StoreManagerInterface $storeManager,
        FileReader $fileReader
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->csv = $csv;
        $this->storeManager = $storeManager;
        $this->connection = $resourceConnection->getConnection();
        $this->fileReader = $fileReader;
    }

    public function importFromFile($filePath)
    {
        $importedCount = 0;
        $errors = [];

        try {
            // Validate file format
            if (!$this->fileReader->isValidFile($filePath)) {
                throw new LocalizedException(__('Unsupported file format. Please use CSV, XLSX, or XLS files.'));
            }

            // Read file data
            $data = $this->fileReader->readFile($filePath);
            $headers = array_shift($data);

            foreach ($data as $rowIndex => $row) {
                try {
                    $categoryData = array_combine($headers, $row);
                    $this->importCategory($categoryData);
                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error reading file: %1', $e->getMessage()));
        }

        return [
            'imported_count' => $importedCount,
            'errors' => $errors
        ];
    }

    protected function importCategory($data)
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new LocalizedException(__('Category name is required'));
        }

        $category = $this->categoryFactory->create();

        // Check if category exists by ID or code
        if (!empty($data['category_id'])) {
            $category->load($data['category_id']);
        } elseif (!empty($data['code'])) {
            $category->load($data['code'], 'code');
        }

        // Set basic category data
        $category->setName($data['name']);
        $category->setDescription($data['description'] ?? '');
        $category->setIsActive($data['is_active'] ?? 1);
        $category->setPosition($data['position'] ?? 0);
        $category->setUrlKey($data['url_key'] ?? '');

        // Set parent category
        if (!empty($data['parent_id'])) {
            $category->setParentId($data['parent_id']);
        } else {
            $category->setParentId(2); // Default parent (Root Catalog)
        }

        $category->save();

        return $category;
    }
}
