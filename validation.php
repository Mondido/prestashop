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

// Lookup Order
$order_id = Order::getOrderByCartId((int)$cart->id);
if ($order_id === false) {
    // Place order
    switch ($transaction_data['status']) {
        case 'pending':
            $mondidopay->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_MONDIDOPAY_PENDING'),
                $total,
                $mondidopay->displayName,
                null,
                array('transaction_id' => $transaction_id),
                $currency->id,
                false,
                $cart->secure_key
            );

            $order = new Order($mondidopay->currentOrder);
            $mondidopay->confirmOrder($order->id, $transaction_data);
            break;
        case 'approved':
            $mondidopay->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_MONDIDOPAY_APPROVED'),
                $total,
                $mondidopay->displayName,
                null,
                array('transaction_id' => $transaction_id),
                $currency->id,
                false,
                $cart->secure_key
            );

            $order = new Order($mondidopay->currentOrder);
            $mondidopay->confirmOrder($order->id, $transaction_data);
            break;
        case 'authorized':
            $mondidopay->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_MONDIDOPAY_AUTHORIZED'),
                $total,
                $mondidopay->displayName,
                null,
                array('transaction_id' => $transaction_id),
                $currency->id,
                false,
                $cart->secure_key
            );

            $order = new Order($mondidopay->currentOrder);
            $mondidopay->confirmOrder($order->id, $transaction_data);
            break;
        case 'declined':
            $mondidopay->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_MONDIDOPAY_DECLINED'),
                $total,
                $mondidopay->displayName,
                null,
                array('transaction_id' => $transaction_id),
                $currency->id,
                false,
                $cart->secure_key
            );
            break;
        case 'failed';
        default:
            $mondidopay->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_ERROR'),
                $total,
                $mondidopay->displayName,
                null,
                array('transaction_id' => $transaction_id),
                $currency->id,
                false,
                $cart->secure_key
            );
            break;
    }
} else {
    $order = new Order($order_id);
}

$hash = Tools::getValue('hash');
$payment_ref = Tools::getValue('payment_ref');

Tools::redirectLink(_PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&payment_ref=' . $payment_ref . '&id_module=' . $mondidopay->id . '&id_order=' . $mondidopay->currentOrder . '&key=' . $order->secure_key . '&transaction_id=' . $transaction_id . '&hash=' . $hash);
