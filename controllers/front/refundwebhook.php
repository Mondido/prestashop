<?php declare(strict_types = 1);

use MondidoPayments\Lock;

class MondidoPaymentsRefundWebhookModuleFrontController extends MondidoPayments\WebhookModuleController
{
    public function postProcess()
    {
        try {
            $transaction = $this->getTransaction();
            $this->verifyRequest($transaction);

            if (!Lock::aquire('transaction', $transaction->id)) {
                $this->exit([
                    'status' => 'locked',
                    'message' => 'Request on the same cart already in progress',
                    'details' => ['cart_id' => $transaction->payment_ref],
                ]);
            }

            $this->module->setIsInStatusChangeState();

            $config = $this->module->getConfig();
            $cart = new Cart($transaction->payment_ref);
            $order = new Order(Order::getOrderByCartId($cart->id));

            $result = $this->updateState($order, $transaction, $config);
            Lock::drop('transaction', $transaction->id);
            $this->exit($result);
        } catch (Throwable $error) {
            if ($transaction) {
                Lock::drop('transaction', $transaction->id);
            }
            PrestaShopLogger::addLog(
                $error->getMessage() . " : " . basename($error->getFile()) . ":" . $error->getLine(),
                4,
                $error->getCode(),
                Cart::class,
                (int) Tools::getValue('payment_ref'),
                true
            );
            $this->exit(['status' => 'error', 'type' => 'internal', 'message' => $error->getMessage(), 'details' => []]);
        }
    }

    protected function updateState($order, $transaction, $config)
    {
        if ($order->current_state === (int) $config->transactionStatusRefunded()) {
            return ['status' => 'noop', 'message' => 'Order already refunded', 'details' => ['order_id' => $order->id]];
        }

        $refund_amount = 0;
        foreach ($transaction->refunds as $refund) {
            $refund_amount += (float) $refund->amount;
        }

        if ((string) $refund_amount !== (string) $transaction->org_auth_amount) {
            return ['status' => 'noop', 'message' => 'Transaction not fully refunded', 'details' => ['order_id' => $order->id]];
        }

        $new_history = new OrderHistory();
        $new_history->id_order = (int)$order->id;
        $new_history->changeIdOrderState((int) $config->transactionStatusRefunded(), $order, true);
        $new_history->addWithEmail(false);
        $new_history->save();
        return ['status' => 'success', 'message' => 'Order refunded', 'details' => ['order_id' => $order->id]];
    }
}
