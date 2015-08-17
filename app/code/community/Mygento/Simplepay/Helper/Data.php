<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
class Mygento_Simplepay_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_UPDATE_EMAIL_IDENTITY = 'sales_email/order_comment/identity';
    const XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE = 'sales_email/order_comment/guest_template';
    const XML_PATH_UPDATE_EMAIL_TEMPLATE = 'sales_email/order_comment/template';

    public function addLog($text)
    {
        if (Mage::getStoreConfig('payment/simplepay/debug')) {
            Mage::log($text, null, 'simplepay.log', true);
        }
    }

    public function decodeId($link)
    {
        $collection = Mage::getModel('simplepay/keys')->getCollection();
        $collection->addFieldToFilter('hkey', $link);
        if (count($collection) == 0) {
            return false;
        } else {
            $item = $collection->getFirstItem();
            return $item->getOrderid();
        }
    }

    public function sendEmailByOrder($order)
    {
        try {
            $order->sendNewOrderEmail();
        } catch (Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    public function getLink($order_id)
    {
        $collection = Mage::getModel('simplepay/keys')->getCollection();
        $collection->addFieldToFilter('orderid', $order_id);
        if (count($collection) == 0) {
            $model = Mage::getModel('simplepay/keys');
            $key = strtr(base64_encode(microtime() . $order_id . rand(1, 1048576)), '+/=', '-_,');
            $model->setHkey($key);
            $model->setOrderid($order_id);
            $model->save();
            return Mage::getUrl('simplepay/payment/paynow/', array('_secure' => true, 'order' => $key));
        } else {
            $item = $collection->getFirstItem();
            return Mage::getUrl('simplepay/payment/paynow/', array('_secure' => true, 'order' => $item->getHkey()));
        }
    }

    public function addTransaction($order_id)
    {
        $order = Mage::getModel('sales/order')->load($order_id);
        $this->addLog('TA: order #' . $order->getIncrementId());
        $orders = Mage::getModel('sales/order_invoice')->getCollection()
            ->addAttributeToFilter('order_id', array('eq' => $order->getId()));
        $orders->getSelect()->limit(1);
        if ((int) $orders->count() !== 0) {
            $this->addLog('TA: order #' . $order->getIncrementId() . ' already has invoice!!!');
            return $this;
        }
        if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
            try {
                if (!$order->canInvoice()) {
                    $order->addStatusHistoryComment('Simplepay_Invoicer: Order cannot be invoiced.', false);
                    $order->save();
                }
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
                $order->addStatusHistoryComment('Automatically INVOICED by Simplepay_Invoicer.', false);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
                if (Mage::getStoreConfig('payment/simplepay/send')) {
                    $order->sendOrderUpdateEmail($order->getStatus(), Mage::getStoreConfig('payment/simplepay/text'));
                }
                if (Mage::getStoreConfig('payment/simplepay/sendinvoice')) {
                    $invoice->sendEmail();
                }
                if (Mage::getStoreConfig('payment/simplepay/sendadmin') != '') {
                    $this->sendCustomComment($order, Mage::getStoreConfig('payment/simplepay/sendadmin'), Mage::getStoreConfig('payment/simplepay/text'));
                }
            } catch (Exception $e) {
                $order->addStatusHistoryComment('Simplepay_Invoicer: Exception occurred during automaticall transaction action. Exception message: ' . $e->getMessage(), false);
                $order->save();
            }
        } else {
            $this->addLog('TA: order #' . $order->getIncrementId() . ' is not in Mage_Sales_Model_Order::STATE_NEW');
        }
    }

    private function sendCustomComment($order, $toemail, $comment)
    {
        $storeId = $order->getStore()->getId();

        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($toemail, Mage::getStoreConfig('trans_email/ident_sales/name'));

        if ($order->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $storeId);
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $storeId);
        }
        $mailer->addEmailInfo($emailInfo);

        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
            'order' => $order,
            'comment' => $comment,
            'billing' => $order->getBillingAddress()
        ));
        $mailer->send();
    }
}
