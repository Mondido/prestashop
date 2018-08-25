<?php
/**
 *    $Id$ mondidopayment Module
 *
 *    Copyright @copyright 2017 Mondido
 *
 * @category  Payment
 * @version   1.5.3
 * @author    Mondido
 * @copyright 2016 Mondido
 * @link      https://www.mondido.com
 * @license   MIT
 * @package   none
 *
 *   Description:
 *   Payment module mondidopay
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../header.php';
require_once dirname(__FILE__) . '/mondidopay.php';

$mondidopay = new mondidopay();
if (!$mondidopay->active) {
	Tools::redirect('index.php?controller=order&step=1');
}

$context = Context::getContext();
$cart = $context->cart;

$error_name = Tools::getValue('error_name');
$payment_ref = ($module->dev == 'true' ? 'dev' : 'a') . $cart->id;
$billing_address = new Address($cart->id_address_invoice);
$products = $cart->getProducts();
$currency = new Currency((int)$cart->id_currency);
$cart_details = $cart->getSummaryDetails(null, true);
$total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
$subtotal = number_format($cart_details['total_price_without_tax'], 2, '.', '');
$vat_amount = $total - $subtotal;

// Process Products
$items = array();
foreach ($products as $product) {
	$items[] = array(
		'artno' => $product['reference'],
		'description' => $product['name'],
		'amount' => $product['total_wt'],
		'qty' => $product['quantity'],
		'vat' => number_format($product['rate'], 2, '.', ''),
		'discount' => 0
	);
}

// Process Shipping
$total_shipping_tax_incl = _PS_VERSION_ < '1.5' ? (float)$cart->getOrderShippingCost() : (float)$cart->getTotalShippingCost();
if ($total_shipping_tax_incl > 0) {
	$carrier = new Carrier((int)$cart->id_carrier);
	$carrier_tax_rate = Tax::getCarrierTaxRate((int)$carrier->id, $cart->id_address_invoice);
	$total_shipping_tax_excl = $total_shipping_tax_incl / (($carrier_tax_rate / 100) + 1);

	$items[] = array(
		'artno' => 'Shipping',
		'description' => $carrier->name,
		'amount' => $total_shipping_tax_incl,
		'qty' => 1,
		'vat' => number_format($carrier_tax_rate, 2, '.', ''),
		'discount' => 0
	);
}

// Process Discounts
$total_discounts_tax_incl = (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $cart->getProducts(), (int)$cart->id_carrier));
if ($total_discounts_tax_incl > 0) {
	$total_discounts_tax_excl = (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $cart->getProducts(), (int)$cart->id_carrier));
	$total_discounts_tax_rate = (($total_discounts_tax_incl / $total_discounts_tax_excl) - 1) * 100;

	$items[] = array(
		'artno' => 'Discount',
		'description' => $mondidopay->l('Discount'),
		'amount' => -1 * $total_discounts_tax_incl,
		'qty' => 1,
		'vat' => number_format($total_discounts_tax_rate, 2, '.', ''),
		'discount' => 0
	);
}

// Prepare Metadata
$metadata = array(
	'products' => (array) $products,
	'customer' => array(
		'firstname' => $billing_address->firstname,
		'lastname' => $billing_address->lastname,
		'address1' => $billing_address->address1,
		'address2' => $billing_address->address2,
		'postcode' => $billing_address->postcode,
		'phone' => $billing_address->phone,
		'phone_mobile' => $billing_address->phone_mobile,
		'city' => $billing_address->city,
		'country' => $billing_address->country,
		'email' => $context->customer->email
	),
	'analytics' => array(),
	'platform' => array(
		'type' => 'prestashop',
		'version' => _PS_VERSION_,
		'language_version' => phpversion(),
		'plugin_version' => $mondidopay->version
	)
);

// Prepare Analytics
if (isset($_COOKIE['m_ref_str'])) {
	$metadata['analytics']['referrer'] = $_COOKIE['m_ref_str'];
}
if (isset($_COOKIE['m_ad_code'])) {
	$metadata['analytics']['google'] = [];
	$metadata['analytics']['google']['ad_code'] = $_COOKIE['m_ad_code'];
}

// Prepare WebHook
$webhook = array(
	'url' => mondidopay::getShopDomain() . __PS_BASE_URI__ . 'modules/' . $mondidopay->name . '/transaction.php',
	'trigger' => 'payment',
	'http_method' => 'post',
	'data_format' => 'json',
	'type' => 'CustomHttp'
);

$context->smarty->assign(array(
	'customer_ref' => $context->customer->id,
	'total' => $total,
	'currency' => strtolower($currency->iso_code),
	'hash' => md5(sprintf(
		'%s%s%s%s%s%s%s',
		$mondidopay->merchantID,
		$payment_ref,
		$context->customer->id,
		$total,
		strtolower($currency->iso_code),
		$mondidopay->test === 'true' ? 'test' : '',
		$mondidopay->secretCode
	)),
	'merchantID' => $mondidopay->merchantID,
	'success_url' => mondidopay::getShopDomain() . __PS_BASE_URI__ . 'modules/' . $mondidopay->name . '/validation.php',
	'error_url' => mondidopay::getShopDomain() . __PS_BASE_URI__ . 'modules/' . $mondidopay->name . '/payment.php',
	'test' => $mondidopay->test === 'true' ? 'true' : 'false',
	//'authorize' => $mondidopay->module->authorize ? 'true' : '',
	'items' => json_encode($items),
	'payment_ref' => $payment_ref,
	'vat_amount' => $vat_amount,
	'webhook' => json_encode($webhook),
	'metadata' => json_encode($metadata),
	'this_path' => $mondidopay->getPath(),
	'this_path_ssl' => $mondidopay->getPath(true),
));
echo $mondidopay->display($mondidopay->name, 'views/templates/hooks/payment_execution.tpl');

require_once dirname(__FILE__) . '/../../footer.php';
