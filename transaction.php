<?php
/**
 *    $Id$ mondidopayment Module
 *
 *    Copyright @copyright 2017 Mondido
 *
 *    @category  Payment
 *    @version   1.5.3
 *    @author    Mondido
 *    @copyright 2016 Mondido
 *    @link      https://www.mondido.com
 *    @license   MIT
 *    @package   none
 *
 *   Description:
 *   Payment module mondidopay
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/mondidopay.php' ;

$log = new FileLogger();
$log->setFilename(_PS_ROOT_DIR_ . '/log/mondidopay.log');

$mondidopay = new mondidopay();
$transaction_id = Tools::getValue('transaction_id') ? Tools::getValue('transaction_id') : Tools::getValue('id');
if (empty($transaction_id)) {
    $log->logDebug('Error: Invalid transaction ID');
    exit('Error: Invalid transaction ID');
}

$merchantID = Configuration::get('MONDIDO_MERCHANTID');
$password = Configuration::get('MONDIDO_PASSWORD');
$secret = Configuration::get('MONDIDO_SECRET');

$cart_id = str_replace(array('dev', 'a'), '', Tools::getValue('payment_ref'));
$cart = new Cart($cart_id);
$currency =  new Currency((int)$cart->id_currency);

// Verify hash
$thisCustomer = (array) Tools::jsonDecode(Tools::getValue('customer'), true);
$total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
$hash = md5(sprintf('%s%s%s%s%s%s%s',
    (string)$merchantID,
    (string)Tools::getValue('payment_ref'),
    (string)$thisCustomer['ref'],
    $total,
    strtolower($currency->iso_code),
    (string)Tools::getValue('status'),
    (string)$secret
));
if($hash !== Tools::getValue('response_hash')) {
    $log->logDebug('Error: Wrong hash');
    exit('Error: Wrong hash');
}

// Lookup Order
$order_id = Order::getOrderByCartId((int)$cart->id);
if ($order_id !== false) {
    $log->logDebug("Order {$order_id} already placed. Cart ID: {$cart->id}");
    exit("Order {$order_id} already placed. Cart ID: {$cart->id}");
}
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

$log->logDebug('OK');
exit('OK');