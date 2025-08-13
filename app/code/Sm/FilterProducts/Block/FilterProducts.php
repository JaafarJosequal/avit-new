<?php
/*------------------------------------------------------------------------
# SM Filter Products - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\FilterProducts\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\UrlFactory;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class FilterProducts extends \Magento\Catalog\Block\Product\AbstractProduct
{
    const CACHE_TAGS = 'SM_FILTERPRODUCTS';
	protected $_config = null;
    protected $_collection;
    protected $_resource;
    protected $_helper;
	protected $_storeManager;
    protected $_scopeConfig;
	protected $_storeId;
	protected $_storeCode;
	protected $_catalogProductVisibility;
	protected $_review;
	protected $_productFactory;
	protected $_reviewFactory;
    protected $_cart;
    private $jsonSerializer;
	

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        \Magento\Framework\App\ResourceConnection $resource,
		\Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
		\Magento\Review\Model\Review $review,
		\Magento\Catalog\Block\Product\Context $context,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewFactory,
        \Magento\Checkout\Model\Cart $cart,
        SerializerJson $jsonSerializer,
        array $data = [],
		$attr = null
    ) {
        $this->_collection = $collection;
        $this->_resource = $resource;
		$this->_storeManager = $context->getStoreManager();
        $this->_scopeConfig = $context->getScopeConfig();
		$this->_catalogProductVisibility = $catalogProductVisibility;
		$this->_storeId=(int)$this->_storeManager->getStore()->getId();
		$this->_storeCode=$this->_storeManager->getStore()->getCode();
		$this->_review = $review;
		$this->_config = $this->_getCfg($attr, $data);
		$this->_productFactory = $productFactory;
		$this->_reviewFactory = $reviewFactory;
        $this->_cart = $cart;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $data);
    }
	
    /**
     * Resource initialization
     
    protected function _construct()
    {
        parent::_construct();
        $this->addData(
            [
                'cache_lifetime' => 86400,
                'cache_tags' => [self::CACHE_TAGS]]
        );
    }
    */
    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
    
    public function getCacheKeyInfo()
    {
        $params = $this->getRequest()->getParams();
        return [
            'BLOCK_TPL_SM_FilterProducts',
            $this->_storeManager->getStore()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            $this->_getNameLayout(),
            $this->getTemplateFile(),
            'base_url' => $this->getBaseUrl(),
            'template' => $this->getTemplate(),
            $this->jsonSerializer->serialize($params)
        ];
    }
    
    private function _getNameLayout() {
		$name_layout = $this->getNameInLayout();
		if ($this->_isAjax()) {
			$name_layout =  $this->getRequest()->getPost('moduleid');
		}
		return $name_layout;
	}
     */
     
    public function _isAjax()
	{
		$isAjax = $this->getRequest()->isAjax();
		$is_ajax_filter_product = $this->getRequest()->getPost('is_ajax_filter_product');
		if ($isAjax && $is_ajax_filter_product == 1) {
			return true;
		} else {
			return false;
		}
	}
    
	public function _getCfg($attr = null , $data = null)
	{
		$defaults = [];
		$_cfg_xml = $this->_scopeConfig->getValue('filterproducts',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeCode);
		if (empty($_cfg_xml)) return;
		$groups = [];
		foreach ($_cfg_xml as $def_key => $def_cfg) {
			$groups[] = $def_key;
			foreach ($def_cfg as $_def_key => $cfg) {
				$defaults[$_def_key] = $cfg;
			}
		}
		
		if (empty($groups)) return;
		$cfgs = [];
		foreach ($groups as $group) {
			$_cfgs = $this->_scopeConfig->getValue('filterproducts/'.$group.'',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeCode);
			foreach ($_cfgs as $_key => $_cfg) {
				$cfgs[$_key] = $_cfg;
			}
		}

		if (empty($defaults)) return;
		$configs = [];
		foreach ($defaults as $key => $def) {
			if (isset($defaults[$key])) {
				$configs[$key] = $cfgs[$key];
			} else {
				unset($cfgs[$key]);
			}
		}
		$cf = ($attr != null) ? array_merge($configs, $attr) : $configs;
		$this->_config = ($data != null) ? array_merge($cf, $data) : $cf;
		return $this->_config;
	}

	public function _getConfig($name = null, $value_def = null)
	{
		if (is_null($this->_config)) $this->_getCfg();
		if (!is_null($name)) {
			$value_def = isset($this->_config[$name]) ? $this->_config[$name] : $value_def;
			return $value_def;
		}
		return $this->_config;
	}

	public function _setConfig($name, $value = null)
	{

		if (is_null($this->_config)) $this->_getCfg();
		if (is_array($name)) {
			$this->_config = array_merge($this->_config, $name);

			return;
		}
		if (!empty($name) && isset($this->_config[$name])) {
			$this->_config[$name] = $value;
		}
		return true;
	}
	
	protected function _toHtml()
    {
		if (!(int)$this->_getConfig('enable', 1)) return ;
        $template_file = $this->getTemplate();
        $template_file = (!empty($template_file)) ? $template_file : "Sm_FilterProducts::default.phtml";
        $this->setTemplate($template_file);
        return parent::_toHtml();
    }
	
	public function getProductDetailsHtml(\Magento\Catalog\Model\Product $product)
    {
        $renderer = $this->getDetailsRenderer($product->getTypeId());
        if ($renderer) {
            $renderer->setProduct($product);
            return $renderer->toHtml();
        }
        return '';
    }

    public function getDetailsRenderer($type = null)
    {
        if ($type === null || $type !== 'configurable') {
            $type = 'default';
			return null;
        }
        $rendererList = $this->getDetailsRendererList();
        if ($rendererList) {
            return $rendererList->getRenderer($type, 'default');
        }
        return null;
    }
	
	private function isHomepage(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$request = $objectManager->get('Magento\Framework\App\Action\Context')->getRequest();
		if ($request->getFullActionName() == 'cms_index_index') {
			return true;
		}
		return false;
	}
	
	protected function getDetailsRendererList()
    {
		return $this->getDetailsRendererListName() ? $this->getLayout()->getBlock(
			$this->getDetailsRendererListName()
		) : $this->getChildBlock(
			$this->getNameInLayout().'.details.renderers'
		);
	}
	
	public function _tagId()
	{
		$tag_id = $this->getNameInLayout();
        if ($this->_isAjax()) {
			$tag_id =  $this->getRequest()->getPost('moduleid');
		}
		$tag_id = strpos($tag_id, '.') !== false ? str_replace('.', '_', $tag_id) : $tag_id;
		return $tag_id;
	}
	
	protected function _prepareLayout()
	{
		$this->getLayout()->addBlock(
			'Magento\Framework\View\Element\RendererList',
			$this->getNameInLayout().'.renderlist',
			$this->getNameInLayout(),
			$this->getNameInLayout().'.details.renderers'
		);
		$this->getLayout()->addBlock(
			'Sm\FilterProducts\Block\Product\Renderer\Listing\Configurable',
			$this->getNameInLayout().'.colorswatches',
			$this->getNameInLayout().'.renderlist',
			'configurable'
		)->setTemplate('Sm_FilterProducts::product/listing/renderer.phtml')->setData(['tagid' =>  $this->_tagId()]);
	}
	
	private function _getProducts(){
		$product_source = $this->_getConfig('product_source');
		//$product_source = 'best_sellers';
		switch($product_source){
			default:
			case 'lastest_products':
			    return $this->_newProducts();
			break;
			case 'best_sellers':
				return $this->_bestSellers();
			break;	
			case 'special_products':
				return $this->_specialProducts();
			break;
			case 'featured_products':
				return $this->_featuredProducts();
			break;	
			case 'other_products':
				return $this->_otherProducts();
			break;	
			case 'viewed_products':
				return $this->_viewedProducts();
			case 'countdown_products':
				return $this->_countDownProducts();
			break;	
		}
	}

	public function _ajaxLoad(){
		$catids = $this->getRequest()->getPost('category_id');
		$count = $this->getRequest()->getPost('count');
		$page = $this->getRequest()->getPost('page');
		
		return  $this->_newProducts(true, $catids, $count, $page);
	}
	
	private function _countDownProducts() {
		$count = $this->_getConfig('product_limitation');                       
        $category_id = $this->_getConfig('select_category');
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
		$collection = clone $this->_collection;	
		$collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP);
		
		$todayDate = date('m/d/y');	
		$dateTo =  $this->_getConfig('date_to', '');	
		$todayStartOfDayDate = $this->_localeDate->date($todayDate)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
		$todayEndOfDayDate = $this->_localeDate->date($dateTo)->setTime(23, 59, 59)->format('Y-m-d H:i:s');
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect('name')
			->addAttributeToSelect('image')
			->addAttributeToSelect('small_image')
			->addAttributeToSelect('thumbnail')
			->addAttributeToSelect('news_from_date')
			->addAttributeToSelect('news_to_date')
			->addAttributeToSelect('short_description')
			->addAttributeToSelect('special_from_date')
			->addAttributeToSelect('special_to_date')
			->addAttributeToFilter('special_price', ['neq' => ''])
			->addAttributeToFilter('special_from_date',
				['and' => [
					0 => ['date' => true, 'to' => $todayEndOfDayDate]
				]], 'left')
			->addAttributeToFilter('special_to_date',
				['and' => [
					0 => ['date' => true, 'from' => $todayStartOfDayDate]
				]], 'left')
			->addAttributeToFilter('is_saleable', 1, 'left')
			->setStoreId($this->_storeId);
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
		 $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		$collection->getSelect()->distinct(true)->limit($count);
		return $collection;
	}
	
	private function _featuredProducts(){
		$count = $this->_getConfig('product_limitation');                       
        $category_id = $this->_getConfig('select_category');
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
		$collection = clone $this->_collection;
        $collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP);
		
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect('name')
			->addAttributeToSelect('image')
			->addAttributeToSelect('small_image')
			->addAttributeToSelect('thumbnail')
			->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
			->addUrlRewrite()
			->setStoreId($this->_storeId)
			->addAttributeToFilter('is_saleable', 1, 'left')
			->addAttributeToFilter('sm_featured', 1, 'left');
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
		$collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		$collection->getSelect()
            ->order('rand()')
            ->limit($count);

        return $collection;	
	}
	
	private function _specialProducts() {
		$count = $this->_getConfig('product_limitation');                       
        $category_id = $this->_getConfig('select_category');
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
		$collection = clone $this->_collection;	
		$collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP);
		$now = date('Y-m-d');
		
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect('name')
			->addAttributeToSelect('image')
			->addAttributeToSelect('small_image')
			->addAttributeToSelect('thumbnail')
			->addAttributeToSelect('news_from_date')
			->addAttributeToSelect('news_to_date')
			->addAttributeToSelect('short_description')
			->addAttributeToSelect('special_from_date')
			->addAttributeToSelect('special_to_date')
			->addAttributeToFilter('special_price', ['neq' => ''])
			->addAttributeToFilter([
				[
					'attribute' => 'special_from_date',
					'lteq' => date('Y-m-d G:i:s', strtotime($now)),
					'date' => true,
				],
				[
					'attribute' => 'special_to_date',
					'gteq' => date('Y-m-d G:i:s', strtotime($now)),
					'date' => true,
				]
			])
			->addAttributeToFilter('is_saleable', 1, 'left')
			->setStoreId($this->_storeId);
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
		 $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		$collection->getSelect()->distinct(true)->limit($count);
		return $collection;
	}
	
	private function _bestSellers(){
		$count = $this->_getConfig('product_limitation');                       
        $category_id = $this->_getConfig('select_category');
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
		$collection = clone $this->_collection;
        $collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP)
			->reset(\Magento\Framework\DB\Select::COLUMNS)
			->reset('from');
		$connection  = $this->_resource->getConnection();
        $collection->getSelect()->join(['e' => $connection->getTableName('catalog_product_entity')],'');
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
			->addUrlRewrite()
			->setStoreId($this->_storeId)
			->addAttributeToFilter('is_saleable', 1, 'left');
			
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
		$collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		$collection->getSelect()->distinct(true);
		$collection->getSelect()
            ->joinLeft(['soi' => $connection->getTableName('sales_order_item')], 'soi.product_id = e.entity_id', ['SUM(soi.qty_ordered) AS ordered_qty'])
            ->join(['order' => $connection->getTableName('sales_order')], "order.entity_id = soi.order_id",['order.state'])
            ->where("order.state <> 'canceled' and soi.parent_item_id IS NULL AND soi.product_id IS NOT NULL")
            ->group('soi.product_id')
            ->order('ordered_qty DESC')
            ->limit($count);
			//echo $collection->getSelect()->__toString(); die;
		return $collection;	
	}
	
	private function _viewedProducts(){
		$count = $this->_getConfig('product_limitation');                       
        $category_id = $this->_getConfig('select_category');
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
		$collection = clone $this->_collection;
        $collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP)
			->reset(\Magento\Framework\DB\Select::COLUMNS)
			->reset('from');
		$connection  = $this->_resource->getConnection();
        $collection->getSelect()->join(['e' => $connection->getTableName('catalog_product_entity')],'');
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
			->addUrlRewrite()
			->setStoreId($this->_storeId)
			->addAttributeToFilter('is_saleable', 1, 'left');
			
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
		$collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		$collection->getSelect()->distinct(true);
		$collection->getSelect()
			->joinLeft(['mv' => $connection->getTableName('report_event')],'mv.object_id = e.entity_id', ['*', 'num_view_counts' => 'COUNT(`event_id`)'])
			->where('mv.event_type_id = 1 AND mv.store_id='.$this->_storeId.'' )
			->group('entity_id');
		$collection->getSelect()->distinct(true);
		$collection->getSelect()->order('num_view_counts DESC');
		$collection->clear();	
		$collection->getSelect()->limit($count);		
		$this->_review->appendSummary($collection);
		return $collection;
	}
	
	private function _newProducts($is_ajax=false, $category_id='', $count=10, $page=1){
		if(!$is_ajax){
			$count = $this->_getConfig('product_limitation');                       
        	$category_id = $this->_getConfig('select_category');
		}
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
        $collection = clone $this->_collection;
        $collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP);
        
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
			->addUrlRewrite()
			->setStoreId($this->_storeId)
			->addAttributeToFilter('is_saleable', 1, 'left')
			->addAttributeToSort('created_at','DESC');
			
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
        $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		$page--;
		$offset = $page*$count;
		$collection->getSelect()->distinct(true)->limit($count, $offset);
		$this->_review->appendSummary($collection);
        return $collection;
	}
	
	private function _otherProducts(){
		$count = $this->_getConfig('product_limitation');                       
        $category_id = $this->_getConfig('select_category');
		$product_order_by = $this->_getConfig('product_order_by');
		$product_order_dir = $this->_getConfig('product_order_dir');
		!is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
        $collection = clone $this->_collection;
        $collection->clear()->getSelect()
			->reset(\Magento\Framework\DB\Select::WHERE)
			->reset(\Magento\Framework\DB\Select::ORDER)
			->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
			->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
			->reset(\Magento\Framework\DB\Select::GROUP);
		
		$collection->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
			->addUrlRewrite()
			->setStoreId($this->_storeId)
			->addAttributeToFilter('is_saleable', 1, 'left');
			
		(!empty($category_id) && $category_id) ? $collection->addCategoriesFilter(['in' => $category_id]) : '';
        $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
		
		switch ($product_order_by) {
			case 'entity_id':
			case 'name':
			case 'created_at':
				$collection->setOrder($product_order_by, $product_order_dir);
				break;
			case 'price':
				$collection->getSelect()->order('final_price ' . $product_order_dir . '');
				break;
			case 'random':
				$collection->getSelect()->order(new \Zend_Db_Expr('RAND()'));
				break;
		}
		
		$collection->getSelect()->distinct(true)->limit($count);
		$this->_review->appendSummary($collection);
        return $collection;
	}
	
	public function getLoadedProductCollection() {
        return $this->_getProducts();
    }
	
	public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
    {	
		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED =>
                    $objectManager->get('\Magento\Framework\Url\Helper\Data')->getEncodedUrl($url),
            ]
        ];
    }

	public function getAjaxUrl(){
		return $this->_storeManager->getStore()->getBaseUrl().'filterproducts/index/index';
	}
   
	public function getProductById($productId=0){
		return $this->_productFactory->create()->load($productId);
	}

	public function getReviewCollection($productId=0){
        $collection = $this->_reviewFactory->create()
        ->addStatusFilter(
            \Magento\Review\Model\Review::STATUS_APPROVED
        )->addEntityFilter(
            'product',
            $productId
        )->setDateOrder();
		return $collection->getData();
    }
    
    public function getRelatedProductsCart()
    {
        $cartProductList = $this->_cart->getQuote()->getAllItems();
        $related_products = [];
        if(!empty($cartProductList)) {
            
            $cart_ids = [];
            foreach($cartProductList as $item) {
                $cart_ids[] = $item->getProductId();
            }
            
            foreach($cartProductList as $item) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                $products = $product->getRelatedProducts();
                if(!empty($products)){
                    foreach($products as $pro_relate){
                        $pro_id = $pro_relate->getId();
                        if (!in_array($pro_id, $cart_ids)) {
                            $related_products[$pro_id] = $pro_relate;
                        }
                    }
                }
            }
        }

        return $related_products;
    }
}
