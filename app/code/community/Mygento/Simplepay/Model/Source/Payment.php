<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Model_Source_Payment
{

    public function toOptionArray()
    {
        return array(
            array('value' => 'CARDPSB', 'label' => Mage::helper('simplepay')->__('Банковские карты ')),
            array('value' => 'EUROSETPT', 'label' => Mage::helper('simplepay')->__('Евросеть')),
            array('value' => 'ALFACLICK', 'label' => Mage::helper('simplepay')->__('Альфа-Клик')),
            array('value' => 'WEBMONEY', 'label' => Mage::helper('simplepay')->__('Webmoney')),
            array('value' => 'CONTACT', 'label' => Mage::helper('simplepay')->__('CONTACT')),
            array('value' => 'ELEKSNETPT', 'label' => Mage::helper('simplepay')->__('Элекснет')),
            array('value' => 'FAKTURARUPT', 'label' => Mage::helper('simplepay')->__('Faktura.ru')),
            array('value' => 'PROMSVYAZBANKPT', 'label' => Mage::helper('simplepay')->__('Промсвязьбанк')),
            array('value' => 'RUSSTANDARTPT', 'label' => Mage::helper('simplepay')->__('Русский стандарт')),
            array('value' => 'NSYMBOLPT', 'label' => Mage::helper('simplepay')->__('HandyBank')),
            array('value' => 'PETROKOMMERCPT', 'label' => Mage::helper('simplepay')->__('Петрокоммерц')),
            array('value' => 'QIWIMAIN', 'label' => Mage::helper('simplepay')->__('QIWI Wallet')),
            array('value' => 'SP', 'label' => Mage::helper('simplepay')->__('Choose on merchant interface')),
        );
    }
}
