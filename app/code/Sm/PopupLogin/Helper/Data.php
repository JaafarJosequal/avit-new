<?php
/**
 * @category Magentech
 * @package Sm_PopupLogin
 * @version 1.0.0
 * @copyright Copyright (c) 2022 Magentech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * @author Magentech Company <contact@sm.com>
 * @link https://sm.com
 */

namespace Sm\PopupLogin\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;
    protected $_storeManager;
    protected $_urlInterface;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        parent::__construct($context);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @param $name
     * @param null $storeCode
     * @return mixed
     */

    public function getPopupLoginConfig($name, $storeCode = null)
    {
        return $this->scopeConfig->getValue(
            'popuplogin/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRedirectUrl()
    {
        $paramRedirect = $this->getPopupLoginConfig('general/redirect');
        $customPageUrl = $this->getPopupLoginConfig('general/custom_page');
        switch ($paramRedirect) {
            case "home":
                return $this->getUrl();
                break;
            case "current":
                return $this->_urlInterface->getCurrentUrl();
                break;
            case "custom":
                return $customPageUrl;
                break;
            default:
                return $this->getUrl() . $paramRedirect;
        }
    }

    /**
     * @return string
     */
    public function getUrlPage()
    {
        return $this->_urlInterface->getCurrentUrl();
    }
}