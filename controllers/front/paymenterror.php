<?php declare(strict_types = 1);

use MondidoPayments\Transaction;

class MondidoPaymentsPaymentErrorModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    public function initContent()
    {
        $cart_id = (int) Tools::getValue('payment_ref');
        try {
            $this->setTemplate('module:mondidopayments/views/templates/payment/error.tpl');
            $message = $this->mapError(Tools::getValue('error_name'));
            $severity = 1;

            if ($message === null) {
                $message = $this->module->l('Something went wrong');
                $severity = 4;
            }

            $this->context->smarty->assign(['message' => $message]);

            $transaction = $this->getTransaction();

            if ($this->context->cookie->id_cart === false) {
                if ($transaction !== false && $this->verifyHash($transaction) === true) {
                    if (Tools::getValue('error_name')) {
                        PrestaShopLogger::addLog(Tools::getValue('error_name'), $severity, 500, 'Cart', $cart_id, true);
                    }
                    $this->context->cookie->id_cart = $cart_id;
                }
            }
        } catch (\Throwable $error) {
            PrestaShopLogger::addLog(
                $error->getMessage() . " : " . basename($error->getFile()) . ":" . $error->getLine(),
                4,
                $error->getCode(),
                Cart::class,
                $cart_id,
                true
            );
        }

        parent::initContent();
    }

    protected function getTransaction()
    {
        $transaction = $this->module->getApiClient()->getTransaction(Tools::getValue('transaction_id'));
        if ($transaction) {
            return $transaction;
        }
        PrestaShopLogger::addLog('Could not load transaction', 4, 404, Cart::class, (int) Tools::getValue('payment_ref'), true);

        return false;
    }

    protected function verifyHash($transaction)
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

        if ($result === false) {
            PrestaShopLogger::addLog('Hash verification failed', 4, 404, Cart::class, (int) Tools::getValue('payment_ref'), true);
        }

        return $result;
    }

    private function mapError($message) {
        switch ($message) {
            case 'errors.payment.declined':
                return $this->module->l('Payment declined');
            case 'errors.payment.failed':
            case 'errors.mpi.not_approved':
                return $this->module->l('Payment failed');
            case 'errors.payment.canceled':
                return $this->module->l('Payment canceled');
            case 'errors.card_cvv.invalid':
                return $this->module->l('Card CVV is invalid');
            case 'errors.card_cvv.missing':
                return $this->module->l('Card CVV is missing');
            case 'errors.card_expiry.invalid':
                return $this->module->l('Card expiry is invalid');
            case 'errors.card_expiry.missing':
                return $this->module->l('Card expiry is missing');
            case 'errors.card.expired':
                return $this->module->l('Card expired');
        }

        return null;
    }
}
