<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_PaymentController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        $this->getResponse()->setBody('Nope. Visit <a href="http://www.mygento.ru/">Magento development</a>');
    }

    public function processAction()
    {

        if (Mage::getStoreConfig('payment/simplepay/active')) {
            if (Mage::getStoreConfig('payment/simplepay/redirect')) {
                Mage::helper('simplepay')->addLog('Redirecting to immidiate payment');
                $session = Mage::getSingleton('checkout/session');
                $session->setSimplepayQuoteId($session->getQuoteId());

                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

                $this->getResponse()->setBody($this->getLayout()->createBlock('simplepay/redirect')->setMnum(str_replace('simplepay_', '', $order->getPayment()->getMethodInstance()->getCode()))->toHtml());

                $session->unsQuoteId();
                $session->unsRedirectUrl();
            } else {
                Mage::helper('simplepay')->addLog('NO Redirect');
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
            }
        } else {
            $this->_forward('noRoute');
        }
    }

    public function paynowAction()
    {
        if (Mage::getStoreConfig('payment/simplepay/active')) {
            $session = Mage::getSingleton('checkout/session');
            //сессия
            $order_id = Mage::helper('simplepay')->decodeId($this->getRequest()->getParam('order'));
            Mage::helper('simplepay')->addLog('Paynow for order #' . $order_id);
            $order = Mage::getModel('sales/order')->load($order_id);
            if ($order->canInvoice()) {
                if (strpos($order->getPayment()->getMethodInstance()->getCode(), 'simplepay') !== false) {
                    $this->getResponse()->setBody($this->getLayout()->createBlock('simplepay/redirect')->setFast(true)->setMnum(1)->setOid($order->getIncrementId())->toHtml());
                } else {
                    return;
                }
            } else {
                Mage::helper('simplepay')->addLog('Order #' . $order_id . ' is already paid');
                $session->addError(Mage::helper('simplepay')->__('Payment failed. Please try again later.'));
                $this->_redirect('checkout/cart'); //отправка на корзину
            }
        } else {
            $this->_forward('noRoute');
        }
    }

    public function successAction()
    {
        if (Mage::getStoreConfig('payment/simplepay/active')) {
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            Mage::getSingleton('core/session')->addSuccess(Mage::helper('simplepay')->__('Оплата прошла успешно.'));
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            $this->_forward('noRoute');
        }
    }

    public function failAction()
    {
        if ($this->getRequest()->isPost()) {
            $session = Mage::getSingleton('checkout/session');
            $session->addError(Mage::helper('simplepay')->__('Payment failed. Please try again later.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $this->_redirect('/');
    }

    public function resultAction()
    {
        if ($this->getRequest()->isPost()) {
            $request = Mage::app()->getRequest()->getPost();
            Mage::helper('simplepay')->addLog('---START OF RESULT POST---');
            Mage::helper('simplepay')->addLog($request);
            Mage::helper('simplepay')->addLog('---END OF RESULT POST---');

            if (empty($request['sp_sig'])) {
                Mage::helper('simplepay')->addLog('No signature.');
                return;
            }

            $check_array = $request;
            unset($check_array['sp_sig']);

            $crc = $request['sp_sig'];
            $my_crc = Mage::getModel('simplepay/abstract')->sign('', $check_array, true);

            if ($crc != $my_crc) {
                Mage::helper('simplepay')->addLog('Bad result sign ' . $crc . ' vs. ' . $my_crc);
                return;
            }

            //ответ simplepay
            $answer = array();
            $answer['sp_salt'] = $request['sp_salt'];
            $answer['sp_status'] = 'ok';

            if ($request['sp_result'] == 1) {
                //успешно
                $answer['sp_description'] = "Оплата принята";
                Mage::helper('simplepay')->addLog("OK payment, order id #" . $request['sp_order_id']);
                Mage::helper('simplepay')->addTransaction($request['sp_order_id']);
            } else {
                $answer['sp_description'] = "Платеж отменен";
            }

            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
            foreach ($answer as $key => $value) {
                $xml->addChild($key, $value);
            }
            $xml->addChild('sp_sig', Mage::getModel('simplepay/abstract')->sign('', $answer, true));

            Mage::helper('simplepay')->addLog($xml->asXML());

            $this->getResponse()->setHeader('Content-type', 'text/xml');
            $this->getResponse()->setBody($xml->asXML());
        }
    }
}
