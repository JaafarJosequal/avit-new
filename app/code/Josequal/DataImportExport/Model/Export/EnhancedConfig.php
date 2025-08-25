<?php
namespace Josequal\DataImportExport\Model\Export;

use Magento\ImportExport\Model\Export\Config as BaseConfig;
use Magento\ImportExport\Model\Export\Config\Reader;

class EnhancedConfig extends BaseConfig
{
    /**
     * Enhanced export configuration with additional features
     */
    public function __construct(
        Reader $dataStorage,
        \Magento\Framework\Serialize\SerializerInterface $serializer = null
    ) {
        parent::__construct($dataStorage, $serializer);
    }
}
