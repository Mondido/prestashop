<?php
/**
*    $Id$ mondidopayment Module
*
*    Copyright @copyright 2016 Mondido
*
*    @category  Payment
*    @version   1.5.0
*    @author    Mondido
*    @copyright 2016 Mondido
*    @link      https://www.mondido.com
*    @license   MIT
*
*   Description:
*   Payment module mondidopay
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require dirname(__FILE__).'/../../config/config.inc.php';
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/mondidopay.php');

$context = Context::getContext();
$cart = $context->cart;
$currency = new Currency((int)Tools::getIsset((Tools::getValue('currency_payement')) ? Tools::getValue('currency_payement') : $context->cookie->id_currency));
$total = (float) number_format($cart->getOrderTotal(true, 3), 2, '.', '');
$transaction_id = Tools::getValue('transaction_id');
$hash = Tools::getValue('hash');
$payment_ref=Tools::getValue('payment_ref');

$mondidopay = new mondidopay();
$mondidopay->validateOrder($cart->id, _PS_OS_PAYMENT_, $total, $mondidopay->displayName, null, null, $currency->id);

$theval = Tools::getValue('transaction_id');
if (isset($transaction_id)) {
    $merchantID = Configuration::get('MONDIDO_MERCHANTID');
    $password =Configuration::get('MONDIDO_PASSWORD');
    $remoteurl = 'https://api.mondido.com/v1/transactions/'. $transaction_id;
    $opts = array('http' => array('method' => "GET",
        'header' => "Authorization: Basic " .base64_encode("$merchantID:$password")
    ));

    $context = stream_context_create($opts);

    $file = Tools::file_get_contents($remoteurl, false, $context);
    $data = (array) Tools::jsonDecode($file, true);
    if(isset($data)){
        $order = new Order($mondidopay->currentOrder);
        $payments = $order->getOrderPaymentCollection();
        $payments[0]->transaction_id = $transaction_id;
        $payments[0]->card_number = $data['card_number'];
        $payments[0]->card_holder = $data['card_holder'];
        $payments[0]->card_brand = $data['card_type'];
        $payments[0]->payment_method = $data['transaction_type'];
        $payments[0]->update();
    }
}

Tools::redirectLink(_PS_BASE_URL_ . __PS_BASE_URI__.'index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&payment_ref='.$payment_ref.'&id_module='.$mondidopay->id.'&id_order='.$mondidopay->currentOrder.'&key='.$order->secure_key.'&transaction_id='.$transaction_id.'&hash='.$hash);
