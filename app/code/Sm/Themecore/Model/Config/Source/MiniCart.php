<?php

namespace Sm\Themecore\Model\Config\Source;

class MiniCart implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'click', 'label' => __('Click')],
            ['value' => 'hover', 'label' => __('Hover')]
        ];
    }
}