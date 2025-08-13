<?php
/*------------------------------------------------------------------------
# SM Form - Version 1.0.0
# Copyright (c) 2024 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\Form\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Store\Model\StoreManagerInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_inlineTranslation;
    protected $_transportBuilder;
    protected $_scopeConfig;
    protected $_logLoggerInterface;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $loggerInterface,
        StoreManagerInterface $storeManager,
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_logLoggerInterface = $loggerInterface;
        $this->messageManager = $context->getMessageManager();
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }   

    public function execute()
    {
        $isAjax = $this->getRequest()->isAjax();
        $post = $this->getRequest()->getPostValue();
        if(!empty($post)){

            //$post = $this->getRequest()->getPostValue();
            $post['create_at'] = date("Y-m-d H:i:s");

            //var_dump($post);
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();       
            $data = $objectManager->create('Sm\Form\Model\Form');
            $data->setData($post);
            $response = [];

            if($data->save()){
                $response['status'] = 'success';
            } else {
                $response['status'] = 'error';
            }

            /* send mail */
            try
            {
                // Send Mail
                $this->_inlineTranslation->suspend();

                $sender = [
                    'name' => $post['name'],
                    'email' => $post['email']
                ];

                $sentToEmail = $this->_scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

                $sentToName = $this->_scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);


                $transport = $this->_transportBuilder
                    ->setTemplateIdentifier('smform_email_template')
                    ->setTemplateOptions(
                        [
                            'area' => 'frontend',
                            'store' => $this->storeManager->getStore()->getId()
                        ]
                    )
                    ->setTemplateVars([
                        'name'      => $post['name'],
                        'email'     => $post['email'],
                        'product'   => $post['product'],
                        //'quantity'  => $post['quantity'],
                        'comment'   => $post['comment']
                    ])
                    ->setFrom($sender)
                    ->addTo($sentToEmail,$sentToName)
                    ->getTransport();

                $transport->sendMessage();

                $this->_inlineTranslation->resume();
                $this->messageManager->addSuccess('Email sent successfully');
                $response['sendmail'] = 'success';
            } catch(\Exception $e){
                $this->messageManager->addError($e->getMessage());
                $this->_logLoggerInterface->debug($e->getMessage());
                $response['sendmail'] = 'error';
            }

            return $this->getResponse()->representJson(
                $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($response)
            );
            //$this->messageManager->addSuccess(__('Form successfully submitted'));

        }

        return $this->_pageFactory->create();

    }
}