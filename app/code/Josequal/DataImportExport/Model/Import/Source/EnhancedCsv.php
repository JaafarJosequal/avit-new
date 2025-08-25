<?php
namespace Josequal\DataImportExport\Model\Import\Source;

use Magento\ImportExport\Model\Import\Source\Csv as BaseCsv;

class EnhancedCsv extends BaseCsv
{
    /**
     * Enhanced CSV reader with better error handling
     */
    public function __construct($file, $directory, $options = [])
    {
        parent::__construct($file, $directory, $options);
    }
}
