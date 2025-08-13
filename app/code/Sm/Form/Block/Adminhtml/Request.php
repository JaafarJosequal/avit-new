<?php
/*------------------------------------------------------------------------
# SM Form - Version 1.0.0
# Copyright (c) 2024 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Form\Block\Adminhtml;

class Request extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_request';
		$this->_blockGroup = 'Sm_Form';
		$this->_headerText = __('Requests');
		
		parent::_construct();
		$this->removeButton('add');
	}
}

