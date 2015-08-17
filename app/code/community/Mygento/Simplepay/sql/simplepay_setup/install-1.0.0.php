<?php
/**
 *
 *
 * @category Mygento
 * @package Mygento_Simplepay
 * @copyright Copyright © 2015 NKS LLC. (http://www.mygento.ru)
 * @license GPLv2
 */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->dropTable('simplepay/keys');

$simplepay_table = $installer->getConnection()
    ->newTable($installer->getTable('simplepay/keys'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        'auto_increment' => true,
        ), 'ID')
    ->addColumn('hkey', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
        ), 'ID')
    ->addColumn('orderid', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'ID')
    ->addIndex($installer->getIdxName('simplepay/keys', array(
        'id'
    )), array(
    'id'
    ), array(
    'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ));

$installer->getConnection()->createTable($simplepay_table);

$installer->endSetup();
