<?php declare(strict_types = 1);
namespace MondidoPayments;

use OrderHistory;
use ModuleFrontController;
use PrestaShopLogger;
use Cart;
use Tools;

class WebhookModuleController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    protected function getTransaction()
    {
        $transaction = $this->module->getApiClient()->getTransaction(Tools::getValue('id'));
        if (!$transaction) {
            PrestaShopLogger::addLog('Could not load transaction: ' . Tools::getValue('id'), 4, 404, Cart::class, (int) Tools::getValue('payment_ref'), true);
            $this->exit(['status' => 'error', 'type' => 'internal', 'message' => 'Could not load transaction', 'details' => []]);
        }

        return $transaction;
    }

    protected function verifyRequest($transaction)
    {
        $config = $this->module->getConfig();
        $result = Transaction::verifyHash(
            Tools::getValue('response_hash'),
            $config->merchantId(),
            $config->secret(),
            Tools::getValue('payment_ref'),
            Tools::getValue('status'),
            $transaction
        );

        if (!$result) {
            PrestaShopLogger::addLog('Invalid hash: ' . $transaction->id, 4, 404, Cart::class, (int) Tools::getValue('payment_ref'), true);
            $this->exit(['status' => 'error', 'type' => 'hash', 'message' => 'Hash verification failed', 'details' => []]);
        }
    }

    protected function exit($result)
    {
        switch ($result['status']) {
            case 'error':
                if ($result['type'] === 'internal') {
                    $status_code = 500;
                } else {
                    $status_code = 400;
                }
                break;
            case 'locked':
                $status_code = 429;
                break;
            default:
                $status_code = 200;
        }

        http_response_code($status_code);
        exit(json_encode($result));
    }
}
