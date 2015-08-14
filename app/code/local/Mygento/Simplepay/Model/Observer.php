<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Model_Observer extends Varien_Object
{

    public function sendEmail($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (strpos($order->getPayment()->getMethodInstance()->getCode(), 'simplepay') !== false) {
            Mage::helper('simplepay')->sendEmailByOrder($order);
        }
    }
}
