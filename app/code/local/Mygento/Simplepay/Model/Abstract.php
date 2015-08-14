<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    protected $_num;

    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_formBlockType = 'simplepay/message';
    protected $_infoBlockType = 'simplepay/info';
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param Varien_Object $stateObject
     * @SuppressWarnings("unused")
     */
    public function initialize($action, $stateObject)
    {
        if ($status = $this->getConfigData('order_status')) {
            $stateObject->setStatus($status);
            $state = $this->_getAssignedState($status);
            $stateObject->setState($state);
            $stateObject->setIsNotified(true);
        }
        return $this;
    }

    protected function _getAssignedState($status)
    {
        $item = Mage::getResourceModel('sales/order_status_collection')
            ->joinStates()
            ->addFieldToFilter('main_table.status', $status)
            ->getFirstItem();
        return $item->getState();
    }

    public function getPlaceUrl()
    {
        return 'https://api.simplepay.pro/sp/payment';
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('simplepay/payment/process/', array('_secure' => true));
    }

    public function getMethodTitle()
    {
        return Mage::getStoreConfig('payment/simplepay' . $this->_num . '/title');
    }

    public function getNum()
    {
        return $this->_num;
    }

    public function getFields($order)
    {
        $result = array();

        if (strpos($order->getPayment()->getMethodInstance()->getCode(), 'simplepay') !== false) {
            $result['sp_outlet_id'] = Mage::getStoreConfig('payment/simplepay/outlet_id');
            $result['sp_order_id'] = $order->getId();

            if ($order->getOrderCurrency()->getCurrencyCode() == 'RUB') {
                $total = $order->getGrandTotal();
            } else {
                $total = Mage::helper('directory')->currencyConvert($order->getBaseGrandTotal(), $order->getBaseCurrency()->getCurrencyCode(), 'RUB');
            }

            $result['sp_amount'] = round($total, 2);
            if (Mage::getStoreConfig('payment/simplepay/test')) {
                $result['sp_payment_system'] = 'TESTCARD';
            } else {
                $result['sp_payment_system'] = Mage::getStoreConfig('payment/simplepay_' . $this->getNum() . '/paytype');
            }

            $lifetime = Mage::getStoreConfig('payment/simplepay/lifetime');
            if ($lifetime < 300) {
                $lifetime = 300;
            } elseif ($lifetime > 604800) {
                $lifetime = 604800;
            } else {
                $result['sp_lifetime'] = $lifetime;
            }

            $result['sp_description'] = Mage::helper('simplepay')->__('Shopping in ') . htmlspecialchars(Mage::getStoreConfig('general/store_information/name'), ENT_QUOTES);

            $result['sp_user_contact_email'] = $order->getCustomerEmail();
            $result['sp_user_phone'] = $order->getBillingAddress()->getTelephone();
            $result['sp_user_name'] = $order->getCustomerName();

            $result['sp_user_ip'] = $order->getRemoteIp();
            $result['sp_recurring_start'] = 0;




            $result['sp_success_url'] = Mage::getUrl('simplepay/payment/success', array('_secure' => true));
            $result['sp_failure_url'] = Mage::getUrl('simplepay/payment/fail', array('_secure' => true));
            $result['sp_result_url'] = Mage::getUrl('simplepay/payment/result', array('_secure' => true));

            $salt = rtrim(base64_encode(md5(microtime())), "=");
            $result['sp_salt'] = $salt;

            $result['sp_sig'] = $this->sign('payment', $result);
        }
        return $result;
    }

    public function getRedirectFormFields()
    {
        $result = array();
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        if (!$order->getId()) {
            return $result;
        }
        $result = $this->getFields($order);
        return $result;
    }

    public function getRedirectFastFormFields($order_id)
    {
        $result = array();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if (!$order->getId()) {
            return $result;
        }
        $result = $this->getFields($order);
        return $result;
    }

    public function sign($method, $request, $result_key = false)
    {
        ksort($request);
        Mage::helper('simplepay')->addLog($request);
        $string = $method . ';' . implode(';', $request);
        if ($result_key) {
            $string .= ';' . Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/simplepay/result_password'));
        } else {
            $string.= ';' . Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/simplepay/password'));
        }
        Mage::helper('simplepay')->addLog($string);
        Mage::helper('simplepay')->addLog(md5($string));
        return md5($string);
    }
}
