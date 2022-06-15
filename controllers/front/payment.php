<?php declare(strict_types = 1);

use MondidoPayments\Transaction;
use MondidoPayments\Configuration;
use MondidoPayments\Exception\InvalidPaymentView;
use MondidoPayments\Exception\InvalidPaymentMethod;

class MondidoPaymentsPaymentModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    public function initContent()
    {
        if (!$this->context->cart->id) {
            return Tools::redirect($this->context->link->getPageLink('Index', [], true));
        }


        try {
            $method = $this->getPaymentMethod();
            $view = $this->getView();

            $transaction = $this->getTransaction($this->context->cart->id);
            if ($transaction) {
                $transaction = $this->updateTransaction($transaction, $method);
            } else {
                $transaction = $this->createTransaction($method);
            }

            if ($view === 'redirect') {
                return Tools::redirect($transaction->href);
            }

            $this->context->smarty->assign(['payment_link' => $transaction->href]);
            $this->setTemplate('module:mondidopayments/views/templates/payment/iframe.tpl');
        } catch (\Throwable $error) {
            PrestaShopLogger::addLog(
                $error->getMessage() . " : " . basename($error->getFile()) . ":" . $error->getLine(),
                4,
                $error->getCode(),
                Cart::class,
                (int) $this->context->cart->id,
                true
            );
            $this->context->smarty->assign(['message' => $this->module->l('Something went wrong')]);
            $this->setTemplate('module:mondidopayments/views/templates/payment/error.tpl');
        }

        parent::initContent();
    }

    private function getTransaction($cart_id)
    {
        return $this->module->getApiClient()->getTransactionFromReference($cart_id);
    }

    private function createTransaction($method)
    {
        $success_url = $this->context->link->getModuleLink('mondidopayments', 'paymentsuccess', [], true);
        $error_url = $this->context->link->getModuleLink('mondidopayments', 'paymenterror', [], true);
        $payment_callback_url = $this->context->link->getModuleLink('mondidopayments', 'paymentwebhook', [], true);
        $refund_callback_url = $this->context->link->getModuleLink('mondidopayments', 'refundwebhook', [], true);

        return $this->module->getApiClient()->createTransaction(Transaction::createTransaction(
            $method,
            $this->module,
            $this->context->cart,
            $this->context->customer,
            $this->context->currency,
            $success_url,
            $error_url,
            $payment_callback_url,
            $refund_callback_url
        ));
    }

    private function updateTransaction($transaction, $method)
    {
        return $this->module->getApiClient()->updateTransaction($transaction->id, Transaction::createTransactionUpdate(
            $method,
            $this->module,
            $this->context->cart,
            $this->context->customer,
            $this->context->currency
        ));
    }

    private function getView()
    {
        $view = Tools::getValue('view');

        $options = array_map(function($option) {
            return $option['value'];
        }, $this->module->getConfig()->paymentViewOptions());

        if (!in_array($view, $options)) {
            throw new InvalidPaymentView($view);
        }

        return $view;
    }

    private function getPaymentMethod()
    {
        $method = Tools::getValue('payment_method');
        if ($method === false) {
            return '';
        }

        $options = array_map(function($option) {
            return $option['value'];
        }, $this->module->getConfig()->paymentOptionsOptions());

        if (!in_array($method, $options)) {
            throw new InvalidPaymentMethod($method);
        }

        return $method;
    }
}
