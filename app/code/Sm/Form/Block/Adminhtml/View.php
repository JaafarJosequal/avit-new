<?php
/*------------------------------------------------------------------------
# SM Form - Version 1.0.0
# Copyright (c) 2024 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Form\Block\Adminhtml;
use Magento\Backend\Block\Widget\Context;
use Sm\Form\Model\ResourceModel\Form\Collection;

class View extends \Magento\Framework\View\Element\Template
{
    protected $requestCollection;
	protected function _construct()
	{
		$this->_controller = 'adminhtml_request';
		$this->_blockGroup = 'Sm_Form';
		$this->_headerText = __('View');
		
		parent::_construct();
	}

    public function __construct(
		Context $context,
		Collection $collection,
		array $data = []
	)
	{
		$this->requestCollection = $collection;
		parent::__construct($context, $data);
	}

    public function getRequests(){
        $id = $this->getRequest()->getParam('id');
        $collection = $this->requestCollection
            ->addFieldToSelect(array('name','email','product','telephone','comment'))
            ->addFieldToFilter('contect_id',$id)
            ->setPageSize(1);
        $request = $collection->getFirstItem();
        $data = $request->getData();
        
        return $data;
    }
}