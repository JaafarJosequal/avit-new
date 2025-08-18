<?php
/*------------------------------------------------------------------------
# SM Themecore
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Themecore\Helper;

use Magento\Store\Model\StoreManagerInterface;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $imageHelper ;
	protected $productRepository ; 
    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    )
    {
        $this->imageHelper       = $imageHelper;
        $this->productRepository = $productRepository;
    }

    public function getItemImage($productId, $imageSize)
    {
        try {
            // Use the same image for all products
            $storeManager = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $imagePath = '/w/h/white-shirt.jpg';
            $image_url = $baseUrl . 'catalog/product' . $imagePath;
            return $image_url;
        } catch (\Exception $e) {
            return 'product not found';
        }
    }
}