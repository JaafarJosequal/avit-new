<?php
namespace Josequal\DataImportExport\Model\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class FileWriter
{
    /**
     * Export data to Excel file
     *
     * @param array $data
     * @param string $format
     * @return string
     */
    public function exportToFile($data, $format = 'xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Add data to worksheet
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $worksheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        // Auto-size columns
        foreach (range(1, count($data[0])) as $column) {
            $worksheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        // Create writer based on format
        switch (strtolower($format)) {
            case 'xlsx':
                $writer = new Xlsx($spreadsheet);
                break;
            case 'csv':
                $writer = new Csv($spreadsheet);
                break;
            default:
                $writer = new Xlsx($spreadsheet);
        }

        // Output to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Get supported export formats
     *
     * @return array
     */
    public function getSupportedFormats()
    {
        return ['xlsx', 'csv'];
    }

    /**
     * Get file extension for format
     *
     * @param string $format
     * @return string
     */
    public function getFileExtension($format)
    {
        $format = strtolower($format);
        switch ($format) {
            case 'xlsx':
                return 'xlsx';
            case 'csv':
                return 'csv';
            default:
                return 'xlsx';
        }
    }
}
