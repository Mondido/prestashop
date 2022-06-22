<?php declare(strict_types = 1);
namespace MondidoPayments;

use MondidoPayments;
use Cart;
use Customer;
use Currency;
use Country;
use Tax;
use League\ISO3166\ISO3166;

class Transaction
{
    public static function createTransaction(
        string $method,
        MondidoPayments $module,
        Cart $cart,
        Customer $customer,
        Currency $currency,
        string $success_url,
        string $error_url,
        string $payment_callback_url,
        string $refund_callback_url
    ) {
        return array_merge(
            self::createTransactionUpdate($method, $module, $cart, $customer, $currency),
            array_filter([
                'success_url' => $success_url,
                'error_url' => $error_url,
                //Without this encondig the api does not allow multiple webhooks
                'webhook' => json_encode([
                    [
                        'url' => $payment_callback_url,
                        'trigger' => 'payment',
                        'http_method' => 'post',
                        'data_format' => 'form_data',
                        'type' => 'CustomHttp',
                    ],
                    [
                        'url' => $refund_callback_url,
                        'trigger' => 'refund',
                        'http_method' => 'post',
                        'data_format' => 'form_data',
                        'type' => 'CustomHttp',
                    ],
                ]),
            ])
        );
    }

    public static function createTransactionUpdate(
        string $method,
        MondidoPayments $module,
        Cart $cart,
        Customer $customer,
        Currency $currency
    ) {
        $config = $module->getConfig();
        $merchant_id = $config->merchantId();
        $action = $config->paymentAction();
        $test_mode = $config->isTestMode();

        $summary = $cart->getSummaryDetails();
        $currency = $currency->iso_code;

        $payment_details = self::getPaymentDetails($summary, $customer);
        $customer_reference = self::getCustomerReference($customer);

        return array_filter([
            'amount' => self::formatNumber($summary['total_price']),
            'vat_amount' => self::formatNumber($summary['total_tax']),
            'merchant_id' => $merchant_id,
            'currency' => $currency,
            'customer_ref' => $customer_reference,
            'payment_ref' => $cart->id,
            'test' => self::formatBool($test_mode),
            'authorize' => self::formatBool($action === 'authorize'),
            'process' => self::formatBool(false),
            'items' => self::getItems($cart, $summary),
            'hash' => md5(implode([
                $merchant_id,
                $cart->id,
                $customer_reference,
                self::formatNumber($summary['total_price']),
                strtolower($currency),
                $test_mode === true ? 'test' : '',
                $config->secret(),
            ])),
            'payment_details' => $payment_details,
            'payment_method' => $method,
            'metadata' => self::getMetadata($payment_details, $config, $module->version),
        ]);
    }

    public static function verifyHash($hash, $merchant_id, $secret, $payment_ref, $status, $transaction)
    {
        $customer_reference = null;
        if (!empty($transaction->customer->id)) {
            $customer_reference = $transaction->customer->ref;
        }

        $calculated_hash = md5(implode([
            $merchant_id,
            $payment_ref,
            $customer_reference,
            self::formatNumber((float) $transaction->amount),
            $transaction->currency,
            $status,
            $secret,
        ]));

        if ($calculated_hash === $hash) {
            return true;
        }

        return false;
    }

    private static function getItems($cart, $summary)
    {
        $items = array_map(function($item) {
            return [
                'artno' => $item['id_product'],
                'qty' => $item['cart_quantity'],
                'description' => $item['name'],
                'amount' => self::formatNumber($item['total_wt']),
                'vat' => self::formatNumber($item['rate']),
                'discount' => 0,
            ];
        }, $cart->getProducts(true));

        if (!$summary['free_ship']) {
            $items[] = [
                'artno' => 'Shipping',
                'description' => $summary['carrier']->name,
                'amount' => self::formatNumber($summary['total_shipping']),
                'qty' => 1,
                'vat' => self::formatNumber(Tax::getCarrierTaxRate($cart->id_carrier, $cart->id_address_invoice)),
                'discount' => 0
            ];
        }

        if ($summary['total_discounts'] > 0) {
            $items[] = [
                'artno'       => 'discount',
                'description' => 'Discount',
                'amount'      => self::formatNumber(- 1 * $summary['total_discounts']),
                'qty'         => 1,
                'vat'         => 0,
                'discount'    => 0,
            ];
        }

        return $items;
    }

    private static function getMetadata($payment_details, $config, $plugin_version)
    {
        return [
            'order' => [
                'billing_address' => [
                    'first_name' => $payment_details['first_name'],
                    'last_name' => $payment_details['last_name'],
                    'address1' => $payment_details['address_1'],
                    'address2' => $payment_details['address_2'],
                    'phone' => $payment_details['phone'],
                    'city' => $payment_details['city'],
                    'zip' => $payment_details['zip'],
                    'company' => $payment_details['company_name'],
                    'email' => $payment_details['email'],
                    'country' => $payment_details['country_code'],
                ],
            ],
            'platform' => [
                'type' => 'prestashop',
                'version' => _PS_VERSION_,
                'language_version' => phpversion(),
                'plugin_version' => $plugin_version,
            ],
            'checkoutMode' => self::formatBool(false),
            'settings' => [
                'payment_mode' => 'hosted',
                'payment_view' => $config->paymentView(),
            ],
        ];
    }

    private static function getPaymentDetails($summary, $customer)
    {
        $invoice_address = $summary['formattedAddresses']['invoice']['object'];

        $details = [
            'email' => $customer->email,
            'phone' => null,
            'first_name' => null,
            'last_name' => null,
            'zip' => null,
            'address_1' => null,
            'address_2' => null,
            'city' => null,
            'company_name' => null,
            'country_code' => null,
        ];

        if (array_key_exists('phone', $invoice_address)) {
            $country = (new ISO3166())->alpha2((new Country($invoice_address['id_country']))->iso_code);
            $details = [
                'phone' => $invoice_address['phone'],
                'first_name' => $invoice_address['firstname'],
                'last_name' => $invoice_address['lastname'],
                'zip' => $invoice_address['postcode'],
                'address_1' => $invoice_address['address1'],
                'address_2' => $invoice_address['address2'],
                'city' => $invoice_address['city'],
                'company_name' => $invoice_address['company'],
                'country_code' => $country['alpha3'],
            ] + $details;
        }

        return $details;
    }

    private static function getCustomerReference($customer)
    {
        if ($customer->id && !$customer->is_guest) {
            return "customer_{$customer->id}";
        }
        return null;
    }

    private static function formatNumber($number)
    {
        return number_format($number, 2, '.', '' );
    }

    private static function formatBool($bool)
    {
        return $bool === true ? 'true' : 'false';
    }
}

