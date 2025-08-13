<?php
/*------------------------------------------------------------------------
# SM Filter Products - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\FilterProducts\Controller\Cart;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

class Slider extends \Magento\Framework\App\Action\Action {
	/** @var  \Magento\Framework\View\Result\Page */
	protected $resultPageFactory;
	protected $jsonEncoder;
	protected $_layout;
	protected $response;

	private $_filterProducts;
	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 */
	public function __construct(
		Context $context, 
		PageFactory $resultPageFactory,
		\Magento\Framework\Json\EncoderInterface $jsonEncoder,
		\Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
		\Magento\Framework\App\Response\Http $response,
        \Sm\FilterProducts\Block\FilterProducts $filterProducts
	)
	{
		$this->resultPageFactory = $resultPageFactory;
		$this->jsonEncoder = $jsonEncoder;
		$this->_layout = $layout;
		$this->response = $response;
        $this->_filterProducts = $filterProducts;
		parent::__construct($context);
	}

	/**
	 * Blog Index, shows a list of recent blog posts.
	 *
	 * @return \Magento\Framework\View\Result\PageFactory
	 */
	public function execute()
	{
		$isAjax = $this->getRequest()->isAjax();

		if ($isAjax){
			$layout =  $this->_layout;
            $filterProducts = $this->_filterProducts;
            $template_file = "Sm_FilterProducts::list-slider-cart.phtml";
            $filterProducts->setTemplate($template_file);

            $result = [];
                

            $layout->getUpdate()->load(['filterproducts_cart_ajax']);
            $layout->generateXml();
            $output = $layout->getOutput();
            $result['items_markup'] =  $output;
            
            return $this->getResponse()->representJson(
                $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
            );
        }

		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend(__('Sm Filter Products'));
		return $resultPage;
	}
}
