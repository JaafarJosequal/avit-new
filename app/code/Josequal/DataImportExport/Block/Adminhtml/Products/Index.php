<?php
namespace Josequal\DataImportExport\Block\Adminhtml\Products;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Index extends Template
{
    protected $urlBuilder;

    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context, $data);
    }

    public function getImportUrl()
    {
        return $this->urlBuilder->getUrl('josequal_dataimportexport/products/import');
    }

    public function getExportUrl()
    {
        return $this->urlBuilder->getUrl('josequal_dataimportexport/products/export');
    }

    public function getSampleCsvUrl()
    {
        return $this->urlBuilder->getUrl('josequal_dataimportexport/products/downloadSample');
    }
}
