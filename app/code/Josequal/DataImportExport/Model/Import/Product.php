<?php
namespace Josequal\DataImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManagerInterface;
use Josequal\DataImportExport\Model\Import\FileReader;

class Product
{
    protected $productFactory;
    protected $resourceConnection;
    protected $csv;
    protected $storeManager;
    protected $connection;
    protected $fileReader;

    public function __construct(
        ProductFactory $productFactory,
        ResourceConnection $resourceConnection,
        Csv $csv,
        StoreManagerInterface $storeManager,
        FileReader $fileReader
    ) {
        $this->productFactory = $productFactory;
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
                    $productData = array_combine($headers, $row);
                    $this->importProduct($productData);
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

    protected function importProduct($data)
    {
        // Validate required fields
        if (empty($data['sku'])) {
            throw new LocalizedException(__('SKU is required'));
        }

        $product = $this->productFactory->create();
        $product->loadByAttribute('sku', $data['sku']);

        if (!$product) {
            $product = $this->productFactory->create();
            $product->setSku($data['sku']);
        }

        // Set basic product data
        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? '');
        $product->setShortDescription($data['short_description'] ?? '');
        $product->setPrice($data['price'] ?? 0);

        if (!empty($data['special_price'])) {
            $product->setSpecialPrice($data['special_price']);
        }

        $product->setTypeId($data['product_type'] ?? Type::TYPE_SIMPLE);
        $product->setAttributeSetId(4); // Default attribute set
        $product->setStatus($data['status'] ?? Status::STATUS_ENABLED);
        $product->setVisibility($data['visibility'] ?? Visibility::VISIBILITY_BOTH);
        $product->setStockData([
            'qty' => $data['quantity'] ?? 0,
            'is_in_stock' => ($data['quantity'] ?? 0) > 0 ? 1 : 0
        ]);

        // Set categories
        if (!empty($data['category_ids'])) {
            $categoryIds = explode(',', $data['category_ids']);
            $product->setCategoryIds($categoryIds);
        }

        // Set images
        if (!empty($data['image'])) {
            $product->setImage($data['image']);
        }
        if (!empty($data['small_image'])) {
            $product->setSmallImage($data['small_image']);
        }
        if (!empty($data['thumbnail'])) {
            $product->setThumbnail($data['thumbnail']);
        }

        $product->save();

        return $product;
    }
}
