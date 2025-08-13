<?php

namespace Sm\Form\Model\ResourceModel\Form;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Sm\Form\Model\Form', 'Sm\Form\Model\ResourceModel\Form');
    }

}