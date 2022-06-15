<?php declare(strict_types = 1);

use MondidoPayments\Order as MondidoPaymentsOrder;
use MondidoPayments\Lock;

class MondidoPaymentsPaymentWebhookModuleFrontController extends MondidoPayments\WebhookModuleController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

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
            $result = MondidoPaymentsOrder::createOrUpdate(
                $this->module,
                $this->module->getConfig(),
                $transaction
            );
            Lock::drop('transaction', $transaction->id);
            return $this->exit($result);
        } catch (\Throwable $error) {
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
}
