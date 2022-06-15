<?php declare(strict_types = 1);

use MondidoPayments\Order as MondidoPaymentsOrder;
use MondidoPayments\Transaction;
use MondidoPayments\Lock;

class MondidoPaymentsPaymentSuccessModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    const MAX_TRIES = 30;

    public function initContent()
    {
        $cart_id = (int) Tools::getValue('payment_ref');

        try {
            $transaction = $this->module->getApiClient()->getTransaction(Tools::getValue('transaction_id'));

            if (!$transaction) {
                PrestaShopLogger::addLog('Could not load transaction', 4, 404, Cart::class, $cart_id, true);
                return $this->handleFailure(null);
            }

            if (!$this->verifyRequest($transaction)) {
                PrestaShopLogger::addLog('Hash verification failed', 4, 400, Cart::class, $cart_id, true);
                return $this->handleFailure($transaction);
            }

            if (Order::getOrderByCartId($cart_id)) {
                return $this->handleSuccess($transaction);
            }

            if (!Lock::aquire('transaction', $transaction->id)) {
                for ($try = 1; $try <= self::MAX_TRIES; $try += 1) {
                    sleep(2);
                    if (Order::getOrderByCartId($cart_id)) {
                        return $this->handleSuccess($transaction);
                    }
                }
                PrestaShopLogger::addLog('Waiting too long for order to be created by webhook', 4, 0, Cart::cart, $cart_id, true);
                return $this->handleFailure($transaction);
            }

            $this->module->setIsInStatusChangeState();
            $result = MondidoPaymentsOrder::createOrUpdate(
                $this->module,
                $this->module->getConfig(),
                $transaction
            );

            if ($result['status'] === 'error') {
                Lock::drop('transaction', $transaction->id);
                PrestaShopLogger::addLog($result['message'], 4, 0, Cart::cart, $cart_id, true);
                return $this->handleFailure($transaction);
            }

            Lock::drop('transaction', $transaction->id);
            return $this->handleSuccess($transaction);
        } catch (\Throwable $error) {
            if ($transaction) {
                Lock::drop('transaction', $transaction->id);
            }
            PrestaShopLogger::addLog(
                $error->getMessage() . " : " . basename($error->getFile()) . ":" . $error->getLine(),
                4,
                $error->getCode(),
                Cart::class,
                $cart_id,
                true
            );
            return $this->handleFailure(null);
        }
    }

    private function handleSuccess($transaction)
    {
        $order = new Order(Order::getOrderByCartId((int) $transaction->payment_ref));

        $this->context->updateCustomer(new Customer($order->id_customer));

        $order_confirmation_link = $this->context->link->getPageLink('order-confirmation', null, null, [
            'id_cart' => $order->id_cart,
            'id_module' => $this->module->id,
            'id_order' => $order->id,
            'key' => $order->secure_key,
        ]);

        if ($transaction->metadata->settings->payment_view !== 'iframe') {
            return Tools::redirect($order_confirmation_link);
        }

        $this->setTemplate('module:mondidopayments/views/templates/payment/escape_iframe.tpl');
        $this->context->smarty->assign(['order_confirmation_link' => $order_confirmation_link]);
        parent::initContent();
    }

    private function handleFailure($transaction)
    {
        $this->context->cart->add();
        $this->context->cookie->id_cart = $this->context->cart->id;

        $error_callback_url = $this->context->link->getModuleLink('mondidopayments', 'paymenterror', [], true);

        if ($transaction && $transaction->metadata->settings->payment_view !== 'iframe') {
            return Tools::redirect($error_callback_url);
        }

        $this->setTemplate('module:mondidopayments/views/templates/payment/escape_iframe.tpl');
        $this->context->smarty->assign(['order_confirmation_link' => $error_callback_url]);
        parent::initContent();
    }

    private function verifyRequest($transaction)
    {
        $config = $this->module->getConfig();
        $result = Transaction::verifyHash(
            Tools::getValue('hash'),
            $config->merchantId(),
            $config->secret(),
            Tools::getValue('payment_ref'),
            Tools::getValue('status'),
            $transaction
        );

        return $result;
    }
}
