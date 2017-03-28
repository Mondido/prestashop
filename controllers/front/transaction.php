<?php

class mondidopayTransactionModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
	    // Init Module
        $this->module = Module::getInstanceByName('mondidopay');

        $log = new FileLogger();
        $log->setFilename(_PS_ROOT_DIR_ . '/app/logs/mondidopay.log');

        $transaction_id = Tools::getValue('id');
        if (empty($transaction_id)) {
            header(sprintf('%s %s %s', 'HTTP/1.1', '400', 'FAILURE'), true, '400');
            $log->logDebug('Error: Invalid transaction ID');
            exit('Error: Invalid transaction ID');
        }

        // Lookup transaction
        $transaction_data = $this->module->lookupTransaction($transaction_id);
        if (!$transaction_data) {
            header(sprintf('%s %s %s', 'HTTP/1.1', '400', 'FAILURE'), true, '400');
            $log->logDebug('Error: Failed to verify transaction');
            exit('Failed to verify transaction');
        }

        $payment_ref = Tools::getValue('payment_ref');
        $status = Tools::getValue('status');

        $cart_id = str_replace(['dev', 'a'], '', $payment_ref);
        $cart = new Cart($cart_id);
        $currency =  new Currency((int)$cart->id_currency);

        // Verify hash
        $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $hash = md5(sprintf('%s%s%s%s%s%s%s',
            $this->module->merchantID,
            $payment_ref,
            $cart->id_customer,
            $total,
            strtolower($currency->iso_code),
            $status,
            $this->module->secretCode
        ));
        if ($hash !== Tools::getValue('response_hash')) {
            header(sprintf('%s %s %s', 'HTTP/1.1', '400', 'FAILURE'), true, '400');
            $log->logDebug('Error: Hash verification failed');
            exit('Hash verification failed');
        }

        // Wait for order placement by customer
        set_time_limit(0);
        $times = 0;

        // Lookup Order
        $order_id = mondidopay::getOrderByCartId($cart_id);
        while (!$order_id) {
            $times++;
            if ($times > 6) {
                break;
            }
            sleep(10);

            // Lookup Order
            $order_id = mondidopay::getOrderByCartId($cart_id);
        }

        // Order was placed
        if ($order_id) {
            header(sprintf('%s %s %s', 'HTTP/1.1', '200', 'OK'), true, '200');
            $log->logDebug("Order {$order_id} already placed. Cart ID: {$cart->id}");
            exit("Order {$order_id} already placed. Cart ID: {$cart->id}");
        }

        // Place order
        $statuses = [
            'pending' => Configuration::get('PS_OS_MONDIDOPAY_PENDING'),
            'approved' => Configuration::get('PS_OS_MONDIDOPAY_APPROVED'),
            'authorized' => Configuration::get('PS_OS_MONDIDOPAY_AUTHORIZED'),
            'declined' => Configuration::get('PS_OS_MONDIDOPAY_DECLINED'),
            'failed' => Configuration::get('PS_OS_ERROR')
        ];

        $this->module->validateOrder(
            $cart->id,
            $statuses[$status],
            $total,
            $this->module->displayName,
            null,
            ['transaction_id' => $transaction_id],
            $currency->id,
            false,
            $cart->secure_key
        );

        $order_id = $this->module->currentOrder;
        if (in_array($status, ['pending', 'approved', 'authorized'])) {
            $this->module->confirmOrder($order_id, $transaction_data);
        }

        header(sprintf('%s %s %s', 'HTTP/1.1', '200', 'OK'), true, '200');
        $log->logDebug("Order was placed by WebHook. Order ID: {$order_id}. Transaction status: {$transaction_data['status']}");
        exit("Order was placed by WebHook. Order ID: {$order_id}. Transaction status: {$transaction_data['status']}");
	}
}
