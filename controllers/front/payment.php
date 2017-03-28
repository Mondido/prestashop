<?php

class mondidopayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = TRUE;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        if (!$this->module->active) {
            Tools::redirect('index.php?controller=order');
        }

        $error_name = Tools::getValue('error_name');
        $payment_ref = ($this->module->test ? 'dev' : 'a') . $cart->id;
        $billing_address = new Address($this->context->cart->id_address_invoice);
        $products = $cart->getProducts();
        $currency = $this->context->currency;
        $cart_details = $cart->getSummaryDetails(null, true);
        $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $subtotal = number_format($cart_details['total_price_without_tax'], 2, '.', '');
        $vat_amount = $total - $subtotal;

        // Process Products
        $items = [];
        foreach ($products as $product) {
            $items[] = [
                'artno' => $product['reference'],
                'description' => $product['name'],
                'amount' => $product['total_wt'],
                'qty' => $product['quantity'],
                'vat' => number_format($product['rate'], 2, '.', ''),
                'discount' => 0
            ];
        }

        // Process Shipping
        $total_shipping_tax_incl = (float)$cart->getTotalShippingCost();
        if ($total_shipping_tax_incl > 0) {
            $carrier = new Carrier((int)$cart->id_carrier);
            $carrier_tax_rate = Tax::getCarrierTaxRate((int)$carrier->id, $cart->id_address_invoice);
            $total_shipping_tax_excl = $total_shipping_tax_incl / (($carrier_tax_rate / 100) + 1);

            $items[] = [
                'artno' => 'Shipping',
                'description' => $carrier->name,
                'amount' => $total_shipping_tax_incl,
                'qty' => 1,
                'vat' => number_format($carrier_tax_rate, 2, '.', ''),
                'discount' => 0
            ];
        }

        // Process Discounts
        $total_discounts_tax_incl = (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $cart->getProducts(), (int)$cart->id_carrier));
        if ($total_discounts_tax_incl > 0) {
            $total_discounts_tax_excl = (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $cart->getProducts(), (int)$cart->id_carrier));
            $total_discounts_tax_rate = (($total_discounts_tax_incl / $total_discounts_tax_excl) - 1) * 100;

            $items[] = [
                'artno' => 'Discount',
                'description' => $this->module->getTranslator()->trans('Discount', [], 'Modules.MondidoPay.Shop'),
                'amount' => $total_discounts_tax_incl,
                'qty' => 1,
                'vat' => number_format($total_discounts_tax_rate, 2, '.', ''),
                'discount' => 0
            ];
        }

        // Prepare Metadata
        $metadata = [
            'products' => (array) $products,
            'customer' => [
                'firstname' => $billing_address->firstname,
                'lastname' => $billing_address->lastname,
                'address1' => $billing_address->address1,
                'address2' => $billing_address->address2,
                'postcode' => $billing_address->postcode,
                'phone' => $billing_address->phone,
                'phone_mobile' => $billing_address->phone_mobile,
                'city' => $billing_address->city,
                'country' => $billing_address->country,
                'email' => $this->context->customer->email
            ],
            'analytics' => [],
            'platform' => [
                'type' => 'prestashop',
                'version' => _PS_VERSION_,
                'language_version' => phpversion(),
                'plugin_version' => $this->module->version
            ]
        ];

        // Prepare Analytics
        if (isset($_COOKIE['m_ref_str'])) {
            $metadata['analytics']['referrer'] = $_COOKIE['m_ref_str'];
        }
        if (isset($_COOKIE['m_ad_code'])) {
            $metadata['analytics']['google'] = [];
            $metadata['analytics']['google']['ad_code'] = $_COOKIE['m_ad_code'];
        }

        // Prepare WebHook
        $webhook = [
            'url' => $this->context->link->getModuleLink($this->module->name, 'transaction', [], true),
            'trigger' => 'payment',
            'http_method' => 'post',
            'data_format' => 'form_data',
            'type' => 'CustomHttp'
        ];

        $this->context->smarty->assign([
            'customer_ref' => $this->context->customer->id,
            'total' => $total,
            'currency' => strtolower($currency->iso_code),
            'hash' => md5(sprintf(
                '%s%s%s%s%s%s%s',
                $this->module->merchantID,
                $payment_ref,
                $this->context->customer->id,
                $total,
                strtolower($currency->iso_code),
                $this->module->test ? 'test' : '',
                $this->module->secretCode
            )),
            'merchantID' => $this->module->merchantID,
            'success_url' => $this->context->link->getModuleLink($this->module->name, 'validation', [], true),
            'error_url' => $this->context->link->getModuleLink($this->module->name, 'error', [], true),
            'test' => $this->module->test ? 'true' : 'false',
            'authorize' => $this->module->authorize ? 'true' : '',
            'items' => json_encode($items),
            'payment_ref' => $payment_ref,
            'vat_amount' => $vat_amount,
            'webhook' => json_encode($webhook),
            'metadata' => json_encode($metadata),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(TRUE, TRUE) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',

        ]);
        $this->setTemplate('module:mondidopay/views/templates/front/payment.tpl');
    }
}
