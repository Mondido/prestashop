<?php
/**
 *    $Id$ mondidopayment Module
 *
 *    Copyright @copyright 2017 Mondido
 *
 * @category  Payment
 * @version   1.5.3
 * @author    Mondido
 * @copyright 2016 Mondido
 * @link      https://www.mondido.com
 * @license   MIT
 * @package   none
 *
 *   Description:
 *   Payment module mondidopay
 */
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../header.php';
require_once dirname(__FILE__) . '/mondidopay.php';

$mondidopay = new mondidopay();
$transaction_id = Tools::getValue('transaction_id') ? Tools::getValue('transaction_id') : Tools::getValue('id');
$hash = Tools::getValue('hash');
$payment_ref = Tools::getValue('payment_ref');

if (empty($transaction_id)) {
    //@todo Trigger error
    exit('Invalid transaction ID');
}

$context = Context::getContext();
$cart = $context->cart;
$currency = new Currency((int)Tools::getIsset((Tools::getValue('currency_payment')) ? Tools::getValue('currency_payment') : $context->cookie->id_currency));
$total = (float)number_format($cart->getOrderTotal(true, 3), 2, '.', '');

// Lookup transaction
$transaction_data = $mondidopay->lookupTransaction($transaction_id);
if (!$transaction_data) {
    //@todo Trigger error
    exit('Failed to verify transaction');
}

// @todo Process transaction cost with $transaction_data['cost']
// @todo Process error with $transaction_data['error']

$cart_id = str_replace(array('dev', 'a'), '', $payment_ref);

// Wait for order confirmation from IPN/WebHook
set_time_limit( 0 );
$times = 0;
do {
    $times ++;
    if ( $times > 6 ) {
        break;
    }
    sleep( 10 );

    $order_id = $mondidopay->getOrderByCartId($cart_id);
} while (empty($order_id));

// Order was not posted
if (empty($order_id)) {
    //@todo Trigger error
    exit('Order was not posted');
}

$order = new Order($order_id);

Tools::redirectLink(_PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&payment_ref=' . $payment_ref . '&id_module=' . $mondidopay->id . '&id_order=' . $order->id . '&key=' . $order->secure_key . '&transaction_id=' . $transaction_id . '&hash=' . $hash);
