<?php
/*------------------------------------------------------------------------
# SM Ecomus - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Ecomus\Model\Config\Source;

class ListProductStyles implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'product-1', 'label' => __('Product Style 1')],
			['value' => 'product-2', 'label' => __('Product Style 2')],
			['value' => 'product-3', 'label' => __('Product Style 3')],
			['value' => 'product-4', 'label' => __('Product Style 4')],
			['value' => 'product-5', 'label' => __('Product Style 5')],
			['value' => 'product-6', 'label' => __('Product Style 6')] 
        ];
    }
}
