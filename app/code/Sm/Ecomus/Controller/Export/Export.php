<?php
/*------------------------------------------------------------------------
# SM Autostore - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Ecomus\Controller\Export;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;


class Export extends \Magento\Framework\App\Action\Action {

	/** @var  \Magento\Framework\View\Result\Page */
	protected $resultPageFactory;
	protected $_storeManager;
	protected $_storeId ;
	protected $pageFactory ;
	protected $blockFactory ;
	/**      * @param \Magento\Framework\App\Action\Context $context      */
	public function __construct(Context $context, PageFactory $resultPageFactory, StoreManagerInterface $storeManagerInterface, \Magento\Cms\Model\PageFactory $pageFactory, \Magento\Cms\Model\BlockFactory $blockFactory)     {
		$this->resultPageFactory = $resultPageFactory;
		
		$this->_storeManager = $storeManagerInterface;
		$this->_storeId = $storeManagerInterface->getStore()->getId();
		$this->pageFactory = $pageFactory;
		$this->blockFactory = $blockFactory;
		
		parent::__construct($context);
	}

	/**
	 * Blog Index, shows a list of recent blog posts.
	 *
	 * @return \Magento\Framework\View\Result\PageFactory
	 */
	public function execute()
	{
		return $this->indexAction();
	}	
	
    public function indexAction(){
        ob_start();
        $FormKey = $this->_objectManager->get('Magento\Framework\Data\Form\FormKey');
        echo '<html><head>
            <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet" />
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
            <script type="text/javascript">
	            jQuery(function(){
	    
                    $("#all_pages").click(function(){
                        var allchecked = this.checked;
                        if (allchecked){
                            $("#table_pages input.ids").each(function(){
                                if (!this.checked){
                                    $(this).parents("tr").trigger("click");
                                }
                            });
                        } else {
                            $("#table_pages input.ids").each(function(){
                                if (this.checked){
                                    $(this).parents("tr").trigger("click");
                                }
                            });
                        }
                    });
                    $("#all_blocks").click(function(){
                        var allchecked = this.checked;
                        if (allchecked){
                            $("#table_blocks input.ids").each(function(){
                                if (!this.checked){
                                    $(this).parents("tr").trigger("click");
                                }
                            });
                        } else {
                            $("#table_blocks input.ids").each(function(){
                                if (this.checked){
                                    $(this).parents("tr").trigger("click");
                                }
                            });
                        }
                    });
                        
                    $("#table_pages tbody tr").click(function(e){
                        if ($(e.target).attr("type") == "checkbox") return;
                        $("input.ids", this).each(function(){
                            $(this).trigger("click");
                        });
                    });
                    $("#table_pages input.ids").change(function(e){
                        var active = this.checked;
                        $(this).parent().parent()[active?"addClass":"removeClass"]("success");
                        $(this).blur();
                    });
                    
                    $("#table_blocks tbody tr").click(function(e){
                        if ($(e.target).attr("type") == "checkbox") return;
                        $("input.ids", this).each(function(){
                            $(this).trigger("click");
                        });
                    });
                    $("#table_blocks input.ids").change(function(e){
                        var active = this.checked;
                        $(this).parent().parent()[active?"addClass":"removeClass"]("success");
                        $(this).blur();
                    });
                });
            </script>
	        ';
        echo '</head><body>';
        echo '<div class="container">';
        echo '<h2 class="page-header">Export CMS Page/Static Block</h2>';
        echo '<h3>CMS Pages</h3>';
        echo '<form method="POST" action="'.$this->_storeManager->getStore()->getBaseUrl().'ecomus/export/Exportaction">
            <table id="table_pages" class="table table-bordered table-condensed">
	        <thead>
	            <tr>
	                <th style="text-align:center;">#</th>
	                <th>Title</th>
	                <th>URL Key</th>
	                <th><input type="checkbox" id="all_pages" title="Select All"></th>
	            </tr>
	        </thead>
	        <tfoot>
	            <tr>
	                <td colspan="6" class="text-right"><button class="btn btn-primary" type="submit">Export CMS Pages</button></td>
	            </tr>
	        </tfoot>
	        <tbody>
	        ';
		$pages = $this->pageFactory->create();
		$pages = $pages->getCollection();
        $i = 0;
        foreach ($pages as $key => $page){
            $i++;
            $frm = '<tr style="cursor: pointer;">
	            <td style="text-align:center;">%s</td>
	            <td>%s</td>
	            <td>%s</td>
	            <td>%s</td>
	            </tr>';
            $chk = '<input type="checkbox" class="ids" name="ids[]" value="'.$page->getId().'" title="'.$page->getId().'">';
            echo sprintf($frm, $i, $page->getTitle(), $page->getIdentifier(), $chk);
        }
         
        echo '</tbody>
	        </table>
	        <input name="form_key" type="hidden" value="'.$FormKey->getFormKey().'">
	        <input type="hidden" name="type" value="pages">
	        </form>';
        
        echo '<br/><h3>Static Blocks</h3>';
        echo '<form method="POST" action="'.$this->_storeManager->getStore()->getBaseUrl().'ecomus/export/Exportaction/">
            <table id="table_blocks" class="table table-bordered table-condensed">
	        <thead>
	            <tr>
	                <th style="text-align:center;">#</th>
	                <th>Title</th>
	                <th>Identifier</th>
	                <th><input type="checkbox" id="all_blocks" title="Select All"></th>
	            </tr>
	        </thead>
	        <tfoot>
	            <tr>
	                <td colspan="6" class="text-right"><button class="btn btn-primary" type="submit">Export Static Blocks</button></td>
	            </tr>
	        </tfoot>
	        <tbody>
	        ';
		$blocks = $this->blockFactory->create();
		$blocks = $blocks->getCollection();		
        $i = 0;
        foreach ($blocks as $key => $block){
            $i++;
            $frm = '<tr style="cursor: pointer;">
	            <td style="text-align:center;">%s</td>
	            <td>%s</td>
	            <td>%s</td>
	            <td>%s</td>
	            </tr>';
            $chk = '<input type="checkbox" class="ids" name="ids[]" value="'.$block->getId().'">';
    
            echo sprintf($frm, $i, $block->getTitle(), $block->getIdentifier(), $chk);
        }
    
        echo '</tbody>
	        </table>
	        <input name="form_key" type="hidden" value="'.$FormKey->getFormKey().'">
	        <input type="hidden" name="type" value="blocks">
	        </form>';
         
        echo '</div>';
        echo '</body>';
        $this->getResponse()->setBody(ob_get_clean());
    }	
		
}
