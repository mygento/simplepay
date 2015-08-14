<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Block_Redirect extends Mage_Payment_Block_Form
{

    /**
     * Set template with message
     */
    protected function _construct()
    {
        $this->setTemplate('mygento/simplepay/redirect.phtml');
        parent::_construct();
    }

    public function getForm()
    {

        $paymentMethod = Mage::getModel('simplepay/m' . $this->getMnum());
        $form = new Varien_Data_Form();
        $form->setAction($paymentMethod->getPlaceUrl())
            ->setId('simplepay_redirect')
            ->setName('simplepay_redirect')
            ->setMethod('POST')
            ->setUseContainer(true);

        if ($this->getFast() === true) {
            $all_fields = $paymentMethod->getRedirectFastFormFields($this->getOid());
        } else {
            $all_fields = $paymentMethod->getRedirectFormFields();
        }

        foreach ($all_fields as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        return $form;
    }
}
