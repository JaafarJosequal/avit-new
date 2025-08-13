<?php
/*------------------------------------------------------------------------
# SM Form - Version 1.0.0
# Copyright (c) 2024 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Form\Block;

class Form extends \Magento\Framework\View\Element\Template
{
    protected $_storeManager;
    protected $moduleManager;
    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    )
    {
        $this->_storeManager = $context->getStoreManager();
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $data);
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->_storeManager->getStore()->getBaseUrl().'smform/index/index';
    }

    /**
     * Get current URL
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    protected function _prepareLayout()
	{
		 parent::_prepareLayout();
	}
    
    public function isModuleEnable(){
        if ($this->moduleManager->isOutputEnabled('Sm_Form')) {
            return true;
        } else {
            return false;
        }                   
    }
}