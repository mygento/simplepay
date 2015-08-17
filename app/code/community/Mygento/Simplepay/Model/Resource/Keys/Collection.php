<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Model_Resource_Keys_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('simplepay/keys');
    }
}
