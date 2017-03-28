<?php

class mondidopayValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === 'mondidopay') {
                $authorized = true;
                break;
            }
        }

		if (!$authorized) {
            die($this->module->getTranslator()->trans('This payment method is not available.', [], 'Modules.MondidoPay.Shop'));
        }

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;

        $transaction_id = Tools::getValue('transaction_id');
		if (empty($transaction_id)) {
            die($this->module->getTranslator()->trans('Invalid transaction ID', [], 'Modules.MondidoPay.Shop'));
        }

        $payment_ref = Tools::getValue('payment_ref');
        $error_name = Tools::getValue('error_name');
        $status = Tools::getValue('status');

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
        if ($hash !== Tools::getValue('hash')) {
            die($this->module->getTranslator()->trans('Hash verification failed', [], 'Modules.MondidoPay.Shop'));
        }

        // Lookup transaction
        $transaction_data = $this->module->lookupTransaction($transaction_id);
        if (!$transaction_data) {
            die($this->module->getTranslator()->trans('Failed to verify transaction', [], 'Modules.MondidoPay.Shop'));
        }

        // Lookup Order
        $order_id = mondidopay::getOrderByCartId((int)$cart->id);
        if ($order_id === false) {
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
        }

        if (in_array($status, ['pending', 'approved', 'authorized'])) {
            $this->module->confirmOrder($order_id, $transaction_data);
        }

		Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
	}
}
