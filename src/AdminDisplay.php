<?php declare(strict_types = 1);
namespace MondidoPayments;

use Order as PrestashopOrder;
use Cart as PrestashopCart;
use PrestaShopLogger;

class AdminDisplay
{
    private $api;
    private $smarty;

    public function __construct(Api $api, $smarty)
    {
        $this->api = $api;
        $this->smarty = $smarty;
    }

    public function addBlockToOrderView($order_id)
    {
        try {
            $cart_id = PrestashopCart::getCartIdByOrderId($order_id);
            $transaction = $this->api->getTransactionFromReference($cart_id);
            if ($transaction) {
                $this->smarty->assign([
                    'transactionLink' => $this->api->getTransactionAdminLink($transaction->id),
                ]);
                return $this->smarty->fetch(__DIR__ . '/../views/templates/admin/order_block.tpl');
            }
        } catch (\Throwable $error) {
            PrestaShopLogger::addLog(
                $error->getMessage() . " : " . basename($error->getFile()) . ":" . $error->getLine(),
                4,
                0,
                'Order',
                $order_id,
                true
            );
            $this->smarty->assign(['mondidopayments_errors' => ['unknown']]);
            return $this->smarty->fetch(__DIR__ . '/../views/templates/admin/errors.tpl');
        }
    }

    public function showErrors($errors)
    {
        if (count($errors)) {
            $this->smarty->assign(['mondidopayments_errors' => $errors]);
            return $this->smarty->display(__DIR__ . '/../views/templates/admin/errors.tpl');
        }
    }
}

