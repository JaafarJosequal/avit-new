<?php
/*------------------------------------------------------------------------
# SM Ecomus - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Ecomus\Controller\Export;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Exportaction extends \Magento\Framework\App\Action\Action {

	/** @var  \Magento\Framework\View\Result\Page */
	protected $resultPageFactory;
	protected $_storeManager;
    protected $_formKeyValidator;
	protected $_storeId ;
	protected $pageFactory ; 
	protected $blockFactory ;
	protected $Generator ; 
	/**      * @param \Magento\Framework\App\Action\Context $context      */
	public function __construct(
	Context $context, 
	PageFactory $resultPageFactory,
	StoreManagerInterface $storeManagerInterface, 
	\Magento\Cms\Model\PageFactory $pageFactory, 
	\Magento\Cms\Model\BlockFactory $blockFactory,
	\Magento\Eav\Model\Config $eavConfig,
	\Magento\Framework\Xml\Generator $Generator,
    \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
	)     
	{
		$this->resultPageFactory = $resultPageFactory;
		$this->_storeManager = $storeManagerInterface;
		$this->_storeId = $storeManagerInterface->getStore()->getId();
		$this->pageFactory = $pageFactory;
		$this->blockFactory = $blockFactory;
		$this->Generator = $Generator;
        $this->_formKeyValidator = $formKeyValidator;
		parent::__construct($context, $eavConfig);
	}

	/**
	 * Blog Index, shows a list of recent blog posts.
	 *
	 * @return \Magento\Framework\View\Result\PageFactory
	 */
	public function execute()
	{
		return $this->exportAction();
	}
	
    public function exportAction(){
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/export');
        }
        $type = $this->getRequest()->getParam('type');		
        $ids  = $this->getRequest()->getParam('ids'); 

        if ($type == 'pages' || $type == 'blocks'){
            if (count($ids) > 0){
                $filename = $type . '.xml';
                // create xml document
                $xmldoc = new \DOMDocument();
                $xmldoc->formatOutput = true;
                 
                // create root nodes
                $root = $xmldoc->createElement("root");
                $xmldoc->appendChild($root);
    
                $type_root = $xmldoc->createElement($type);
                $root->appendChild($type_root);
                 
                try {
					
                    $model = $type == "pages" ? "pageFactory" : "blockFactory";
                    foreach ($ids as $id){
						$cms_obj = $this->$model->create()->load($id);
				
                        if ($cms_obj instanceof Magento\Cms\Model\Page || $type == "pages"){
                            $cms_obj_xml = $xmldoc->createElement("cms_item");
                            $tmp = $xmldoc->createElement("title");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getTitle()));
                            $cms_obj_xml->appendChild($tmp);
    
							/*
                            $tmp = $xmldoc->createElement("root_template");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getCustom_root_template()));
                            $cms_obj_xml->appendChild($tmp);
							*/
                             
                            $tmp = $xmldoc->createElement("identifier");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getIdentifier()));
                            $cms_obj_xml->appendChild($tmp);
                             
                            $tmp = $xmldoc->createElement("content_heading");
							
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getContent_heading() ?? ''));
                            $cms_obj_xml->appendChild($tmp);
                             
                            $content_html = $xmldoc->createDocumentFragment();
                            $content_html->appendXML('<![CDATA['.$cms_obj->getContent().']]>');
                            $tmp = $xmldoc->createElement("content");
                            $tmp->appendChild($content_html);
                            $cms_obj_xml->appendChild($tmp);
                             
                            $tmp = $xmldoc->createElement("is_active");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getIs_active()));
                            $cms_obj_xml->appendChild($tmp);
							
                            $tmp = $xmldoc->createElement("page_layout");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getPage_layout()));
                            $cms_obj_xml->appendChild($tmp);							
                             
                            $layout_update_xml = $xmldoc->createDocumentFragment();
                            $layout_update_xml->appendXML('<![CDATA['.$cms_obj->getLayout_update_xml().']]>');
                            $tmp = $xmldoc->createElement("layout_update_xml");
                            $tmp->appendChild($layout_update_xml);
                            $cms_obj_xml->appendChild($tmp);
                             
                            $tmp = $xmldoc->createElement("store_id");
                            $tmp->appendChild($xmldoc->createTextNode(implode(",", $cms_obj->getStore_id())));
                            $cms_obj_xml->appendChild($tmp);
                             
                            $type_root->appendChild($cms_obj_xml);
                        } else if ($cms_obj instanceof Magento\Cms\Model\Block || $type == "blocks"){
                            $cms_obj_xml = $xmldoc->createElement("cms_item");
    
                            $tmp = $xmldoc->createElement("title");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getTitle()));
                            $cms_obj_xml->appendChild($tmp);
    
                            $tmp = $xmldoc->createElement("identifier");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getIdentifier()));
                            $cms_obj_xml->appendChild($tmp);
                             
                            $content_html = $xmldoc->createDocumentFragment();
                            $content_html->appendXML('<![CDATA['.$cms_obj->getContent().']]>');
                            $tmp = $xmldoc->createElement("content");
                            $tmp->appendChild($content_html);
                            $cms_obj_xml->appendChild($tmp);
    
                            $tmp = $xmldoc->createElement("is_active");
                            $tmp->appendChild($xmldoc->createTextNode($cms_obj->getIs_active()));
                            $cms_obj_xml->appendChild($tmp);
							
                            $tmp = $xmldoc->createElement("store_id");
                            $tmp->appendChild($xmldoc->createTextNode(implode(",", $cms_obj->getStore_id())));
                            $cms_obj_xml->appendChild($tmp);							
                             
                            $type_root->appendChild($cms_obj_xml);
                        }
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
                ob_end_clean();
				

				header('Content-type: "text/xml"; charset="utf8"');
				header('Content-disposition: attachment; filename="'.$filename.'"');

				// Output content
				echo $xmldoc->saveXML();
                //$this->_prepareDownloadResponse($filename, $xmldoc->saveXML());
            } else {
                die("No items were selected");
            }
        } else if ($type == 'configuration'){
            
        } else {
            die("Invalid Type!");
        }
    }	
		
}
