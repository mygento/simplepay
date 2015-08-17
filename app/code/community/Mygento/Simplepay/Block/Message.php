<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Block_Message extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        $this->setTemplate('mygento/simplepay/message.phtml');
        parent::_construct();
    }
}
