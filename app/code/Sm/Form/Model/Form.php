<?php

namespace Sm\Form\Model;

class Form extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */

    protected function _construct()
    {
        $this->_init('Sm\Form\Model\ResourceModel\Form');
    }

}
