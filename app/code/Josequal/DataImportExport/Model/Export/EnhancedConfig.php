<?php
namespace Josequal\DataImportExport\Model\Export;

use Magento\ImportExport\Model\Export\Config as BaseConfig;

class EnhancedConfig extends BaseConfig
{
    /**
     * Enhanced export configuration with additional features
     */
    public function __construct(
        \Magento\Framework\Config\DataInterface $dataStorage,
        \Magento\Framework\Serialize\SerializerInterface $serializer = null
    ) {
        parent::__construct($dataStorage, $serializer);
    }
}
