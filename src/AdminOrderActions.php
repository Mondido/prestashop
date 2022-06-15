<?php declare(strict_types = 1);
namespace MondidoPayments;

use Tools;
use Link;
use RuntimeException;

class AdminOrderActions
{
    private $api;
    private $config;
    private $link;

    public function __construct(Api $api, Configuration $config, Link $link)
    {
        $this->api = $api;
        $this->config = $config;
        $this->link = $link;
    }

    public function capture($order, $transaction)
    {
        if ($order->current_state === $this->config->transactionStatusApproved()) {
            return;
        }

        try {
            $amount_to_remove = (float) $transaction->org_auth_amount;
            if ($amount_to_remove !== (float) $transaction->amount) {
                $amount_to_remove -= (float) $transaction->amount;
            }

            $old_state = $order->current_state;
            $order->current_state = $this->config->transactionStatePending();
            $order->save();

            $this->api->captureTransaction($transaction->id, $amount_to_remove);

            sleep(5);
        } catch (\Throwable $error) {
            $order->current_state = $old_state;
            $order->save();
            throw $error;
        }
    }

    public function refund($order, $transaction)
    {
        if ($order->current_state === (int) $this->config->transactionStatusRefunded()) {
            return;
        }

        if ($transaction->amount < $transaction->org_auth_amount) {
            throw new RuntimeException('Refund a partial capture is not supported');
        }

        $old_state = $order->current_state;
        try {
            $amount = (float) $transaction->amount;
            foreach ($transaction->refunds as $refund) {
                $amount -= (float) $refund->amount;
            }

            if ($amount !== 0) {
                $order->current_state = $this->config->transactionStatePending();
                $order->save();

                $this->api->createRefund($transaction->id, $amount);
                sleep(5);
            }
        } catch (\Throwable $error) {
            $order->current_state = $old_state;
            $order->save();
            throw $error;
        }
    }
}

