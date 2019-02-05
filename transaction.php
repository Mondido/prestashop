<?php
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/mondidopay.php' ;

$log = new FileLogger();
$log->setFilename(_PS_ROOT_DIR_ . '/log/mondidopay.log');

$mondidopay = new mondidopay();

try {
    $raw_body = file_get_contents( 'php://input' );
    $data = @json_decode( $raw_body, TRUE );
    if (!$data ) {
        throw new Exception( 'Invalid data' );
    }

    if (empty($data['id'])) {
        throw new Exception( 'Invalid transaction ID' );
    }

    // Log transaction details
    $log->logDebug('Incoming Transaction: ' . var_export(json_encode($data, true), true));

    // Lookup transaction
    $transaction_data = $mondidopay->lookupTransaction($data['id']);
    if (!$transaction_data) {
        throw new Exception('Error: Failed to verify transaction');
    }

    $transaction_id = $data['id'];
    $payment_ref = $data['payment_ref'];
    $status = $data['status'];
    $cart_id = str_replace(array('dev', 'a'), '', $payment_ref);
    $cart = new Cart($cart_id);
    if (!Validate::isLoadedObject($cart)) {
        throw new Exception('Error: Failed to load cart with ID ' . $cart_id);
    }
    $currency =  new Currency((int)$cart->id_currency);

    // Verify hash
    $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
    $hash = md5(sprintf('%s%s%s%s%s%s%s',
        $mondidopay->merchantID,
        $payment_ref,
        $cart->id_customer,
        $total,
        strtolower($currency->iso_code),
        $status,
        $mondidopay->secretCode
    ));
    if ($hash !== $data['response_hash']) {
        throw new Exception('Hash verification failed');
    }

    // Lookup Order
    $order_id = mondidopay::getOrderByCartId((int)$cart->id);
    if ($order_id) {
        throw new Exception("Order {$order_id} already placed. Cart ID: {$cart->id}");
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
            $order = new Order($mondidopay->currentOrder);
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
            $order = new Order($mondidopay->currentOrder);
            break;
    }

    http_response_code(200);
    $log->logDebug("Order was placed by WebHook. Order ID: {$order->id}. Transaction status: {$transaction_data['status']}");
    exit('OK');
} catch (Exception $e) {
    http_response_code(400);
    $log->logDebug('Error: ' . $e->getMessage());
    exit('Error: ' . $e->getMessage());
}
