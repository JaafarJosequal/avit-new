<?php
/**
 * @category Magentech
 * @package Sm_PopupLogin
 * @version 1.0.0
 * @copyright Copyright (c) 2022 Magentech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * @author Magentech Company <contact@sm.com>
 * @link https://sm.com
 */

namespace Sm\PopupLogin\Model\Config\Source;

class Type implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'modal', 'label' => __('Modal')],
            ['value' => 'sidebar', 'label' => __('Sidebar')],
        ];
    }
}