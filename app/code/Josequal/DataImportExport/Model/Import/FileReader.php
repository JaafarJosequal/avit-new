<?php
namespace Josequal\DataImportExport\Model\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Magento\Framework\Exception\LocalizedException;

class FileReader
{
    /**
     * Read data from file (CSV or Excel)
     *
     * @param string $filePath
     * @return array
     * @throws LocalizedException
     */
    public function readFile($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'csv':
                return $this->readCsvFile($filePath);
            case 'xlsx':
            case 'xls':
                return $this->readExcelFile($filePath);
            default:
                throw new LocalizedException(__('Unsupported file format. Please use CSV, XLSX, or XLS files.'));
        }
    }

    /**
     * Read CSV file
     *
     * @param string $filePath
     * @return array
     */
    protected function readCsvFile($filePath)
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Read Excel file
     *
     * @param string $filePath
     * @return array
     * @throws LocalizedException
     */
    protected function readExcelFile($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];

            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $data[] = $rowData;
            }

            return $data;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error reading Excel file: %1', $e->getMessage()));
        }
    }

    /**
     * Get supported file extensions
     *
     * @return array
     */
    public function getSupportedExtensions()
    {
        return ['csv', 'xlsx', 'xls'];
    }

    /**
     * Validate file extension
     *
     * @param string $filePath
     * @return bool
     */
    public function isValidFile($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, $this->getSupportedExtensions());
    }
}
