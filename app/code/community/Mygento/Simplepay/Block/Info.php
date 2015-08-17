<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Block_Info extends Mage_Payment_Block_Info
{

    public function getOid()
    {
        $info = $this->getInfo();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $order = $info->getOrder();
            return $order->getId();
        }
        return false;
    }

    public function getPaylink()
    {
        return Mage::helper('simplepay')->getLink($this->getOid());
    }

    public function isPaid()
    {
        $order = Mage::getModel('sales/order')->load($this->getOid());
        if (!$order->hasInvoices()) {
            return false;
        } else {
            return true;
        }
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mygento/simplepay/info.phtml');
    }

    public function getOrder()
    {
        $info = $this->getInfo();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            return $info->getOrder();
        }
    }

    public function getTotalSum()
    {
        $info = $this->getInfo();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $order = $info->getOrder();
            return round($order->getGrandTotal(), 2);
        }
    }

    public function getSimplepayName()
    {
        return $this->escapeHtml($this->getMethod()->getTitle());
    }

    public function isSendLink()
    {
        return Mage::getStoreConfig('payment/simplepay/sendlink');
    }

    public function isPayable()
    {
        $order = Mage::getModel('sales/order')->load($this->getOid());
        if (Mage::getStoreConfig('payment/simplepay/order_status_confirm') == $order->getStatus()) {
            return true;
        } else {
            return false;
        }
    }

    public function isEmailContext()
    {
        $info = $this->getInfo();
        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            return false;
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
            if (Mage::app()->getStore()->isAdmin()) {
                $action = Mage::app()->getRequest()->getActionName();
                if ($action == 'email' || $action == 'save') {
                    return true; // Admin
                } else {
                    return false; // Admin View
                }
            } else {
                return true; // Frontend
            }
        }
    }
}
