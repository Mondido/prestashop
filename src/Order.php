<?php declare(strict_types = 1);
namespace MondidoPayments;

use MondidoPayments;
use Tools;
use Country;
use Cart;
use Currency;
use Customer;
use Address;
use PrestaShopLogger;
use Order as PrestashopOrder;
use OrderHistory;
use League\ISO3166\ISO3166;

class Order
{
    public static function createOrUpdate(
        MondidoPayments $module,
        Configuration $config,
        \stdClass $transaction
    ) {
        $cart_id = (int) $transaction->payment_ref;

        $cart = new Cart($cart_id);

        if ($cart->id === null) {
            return ['status' => 'error', 'type' => 'not-found', 'message' => 'Cart not found', 'details' => ['cart_id' => $cart_id]];
        }

        if (!in_array($transaction->status, ['approved', 'authorized'])) {
            return ['status' => 'noop', 'message' => 'Status not actionable', 'details' => []];
        }

        if (!$cart->id_customer) {
            self::addGuestCustomer($cart, $transaction);
        }

        self::setAddress($cart, $transaction, $module);

        return self::setOrder($cart, $transaction, $transaction->currency, $module, $config);
    }

    private static function addGuestCustomer($cart, $transaction)
    {
        $customer = new Customer();
        $customer->firstname = $transaction->payment_details->first_name;
        $customer->lastname = $transaction->payment_details->last_name;
        $customer->email = $transaction->payment_details->email;
        $customer->id_guest = $cart->id_guest;
        $customer->is_guest = true;
        $customer->passwd = Tools::encrypt(Tools::passwdGen(8));
        $customer->add();

        $cart->id_customer = $customer->id;
        $cart->secure_key = $customer->secure_key;
        $cart->save();
    }

    private static function setAddress($cart, $transaction, $module)
    {
        $invoice = new Address((int) $cart->id_address_invoice);
        $delivery = new Address((int) $cart->id_address_delivery);

        $invoice->firstname = $transaction->payment_details->first_name;
        $invoice->lastname = $transaction->payment_details->last_name;
        $invoice->address1 = $transaction->payment_details->address_1;
        $invoice->address2 = $transaction->payment_details->address_2;
        $invoice->postcode = $transaction->payment_details->zip;
        $invoice->phone_mobile = $transaction->payment_details->phone;
        $invoice->city = $transaction->payment_details->city;
        $invoice->id_country = Country::getByIso((new ISO3166())->alpha3($transaction->payment_details->country_code)['alpha2']);

        if (!$cart->id_address_invoice) {
            $invoice->id_customer = $cart->id_customer;
            $invoice->alias = $module->l('My billing address');
            $invoice->add();
            $cart->id_address_invoice = $invoice->id;
        } else {
            $invoice->update();
        }

        if (!$cart->id_address_delivery) {
            $delivery->firstname = $transaction->payment_details->first_name;
            $delivery->lastname = $transaction->payment_details->last_name;
            $delivery->address1 = $transaction->payment_details->address_1;
            $delivery->address2 = $transaction->payment_details->address_2;
            $delivery->postcode = $transaction->payment_details->zip;
            $delivery->phone_mobile = $transaction->payment_details->phone;
            $delivery->city = $transaction->payment_details->city;
            $delivery->id_country = Country::getByIso((new ISO3166())->alpha3($transaction->payment_details->country_code)['alpha2']);
            $delivery->id_customer = $cart->id_customer;
            $delivery->alias = $module->l('My shipping address');
            $delivery->add();
            $cart->id_address_delivery = $delivery->id;
        } else if ($transaction->transaction_type === 'invoice') {
            $cart->id_address_delivery = $cart->id_address_invoice;
        }

        $cart->save();
    }

    private static function setOrder($cart, $transaction, $currency, $module, $config)
    {
        $created = false;
        $order_id = PrestashopOrder::getOrderByCartId($cart->id);
        $order = null;
        if (!$order_id) {
            $created = true;
            $module->validateOrder(
                $cart->id,
                self::mapTransactionStatus($transaction->status, $config->transactionStatusPending(), $config),
                $cart->getOrderTotal(true, 3),
                $module->getPaymentMethodName($transaction),
                null,
                ['transaction_id' => $transaction->id],
                Currency::getIdByIsoCode($currency),
                false,
                $cart->secure_key
            );

            $order = new PrestashopOrder($module->currentOrder);
        } else {
            $order = new PrestashopOrder($order_id);

            $new_state = self::mapTransactionStatus($transaction->status, (int) $order->current_state, $config);
            if ((int)$order->current_state !== $new_state) {
                $new_history = new OrderHistory();
                $new_history->id_order = (int)$order->id;
                $new_history->changeIdOrderState((int)$new_state, $order, true);
                $new_history->addWithEmail(true);
            }
        }

        switch ($transaction->transaction_type) {
            case 'credit_card':
                foreach ($order->getOrderPaymentCollection() as $payment) {
                    if ((string)$payment->transaction_id === (string)$transaction->id) {
                        $payment->card_number = $transaction->card_number;
                        $payment->card_holder = $transaction->card_holder;
                        $payment->card_brand = $transaction->card_type;
                        $payment->update();
                    }
                }
                break;
        }

        if ($created) {
            PrestaShopLogger::addLog("Order created. Cart: {$cart->id} Transaction: {$transaction->id}", 1, 0, 'Order', (int) $module->currentOrder, true);
            return ['status' => 'success', 'type' => 'order-created', 'message' => 'Order created', 'details' => ['order_id' => $module->currentOrder]];
        }

        PrestaShopLogger::addLog("Order updated. Transaction: {$transaction->id}", 1, 0, 'Order', (int) $order->id, true);
        return ['status' => 'success', 'type' => 'order-updated', 'message' => 'Order updated', 'details' => ['order_id' => $order->id]];
    }

    private static function mapTransactionStatus($status, $current_status, $config)
    {
        switch ($status) {
            case 'pending': return (int) $config->transactionStatusPending();
            case 'authorized': return (int) $config->transactionStatusAuthorized();
            case 'declined': return (int) $config->transactionStatusDeclined();
            case 'approved': return (int) $config->transactionStatusApproved();
            case 'failed': return (int) $config->transactionStatusFailed();
            default: return $current_status;
        }
    }
}
