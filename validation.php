<?php
/**
*    $Id$ mondidopayment Module
*
*    Copyright @copyright 2016 Mondido
*
*    @category  Payment
*    @version   1.5.3
*    @author    Mondido
*    @copyright 2016 Mondido
*    @link      https://www.mondido.com
*    @license   MIT
*    @package   none
*
*   Description:
*   Payment module mondidopay
*/
define('_PS_MODE_DEV_', true);
require dirname(__FILE__).'/../../config/config.inc.php';
include dirname(__FILE__).'/../../header.php' ;
include_once dirname(__FILE__).'/mondidopay.php' ;

$isWebhookPost = false;
$transaction_id = Tools::getValue('transaction_id') ? Tools::getValue('transaction_id') :Tools::getValue('id');

if (isset($transaction_id)) {
    $merchantID = Configuration::get('MONDIDO_MERCHANTID');
    $password = Configuration::get('MONDIDO_PASSWORD');
    $secret = Configuration::get('MONDIDO_SECRET');
    
         //incoming webhook? Needs a better way to identify a webhook.
    if(Tools::getValue('id'))
    {
        $isWebhookPost = true; 
        ob_clean();
        echo "Order has already been settled by client redirect. ";

        $cart_id = str_replace("dev","",str_replace("a","",Tools::getValue('payment_ref')));
       
        $cart = new cart($cart_id);
        $currency =  new Currency((int)$cart->id_currency);
        $currencyiso =  strtolower($currency->iso_code);

        $thisCustomer = (array) Tools::jsonDecode(Tools::getValue('customer'), true);
        $hash = Tools::getValue('response_hash');
        $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
       
        $thisHash = ((string)$merchantID.(string)Tools::getValue('payment_ref').(string)$thisCustomer["ref"].$total.$currencyiso.(string)Tools::getValue('status').(string)$secret);
        $thisHash = MD5($thisHash);
        if(!($thisHash == $hash) )
        {
            ob_clean();
            echo "Wrong hash";
            die();
        }
    }
    else
    {   
        $context = Context::getContext();
        $cart = $context->cart;
        $currency = new Currency((int)Tools::getIsset((Tools::getValue('currency_payement')) ? Tools::getValue('currency_payement') : $context->cookie->id_currency));
        $total = (float) number_format($cart->getOrderTotal(true, 3), 2, '.', '');

        $hash = Tools::getValue('hash');
        $payment_ref=Tools::getValue('payment_ref');
    }
    $mondidopay = new mondidopay();
    $mondidopay->validateOrder($cart->id, _PS_OS_PAYMENT_, $total, $mondidopay->displayName, null, null, $currency->id);
    
    $remoteurl = 'https://api.mondido.com/v1/transactions/'. $transaction_id;
    $opts = array('http' => array('method' => "GET",
        'header' => "Authorization: Basic " .base64_encode("$merchantID:$password")
    ));
    $streamcontext = stream_context_create($opts);
    $file = Tools::file_get_contents($remoteurl, false, $streamcontext);
    $data = (array) Tools::jsonDecode($file, true);
    

    if(isset($data)) {
        $order = new Order($mondidopay->currentOrder);
        //TODO: update delivery address if it is the case 
        if($data['transaction_type'] == 'invoice') {
            $pd = $data['payment_details'];
            
            $shipping_address = new Address((int) $order->id_address_invoice);
            if(!empty($pd['phone'])) {
                $shipping_address->phone = $pd['phone'];
            }
            if(!empty($pd['last_name'])) {
                $shipping_address->lastname = $pd['last_name'];
            }
            if(!empty($pd['first_name'])) {
                $shipping_address->firstname = $pd['first_name'];
            }
            if(!empty($pd['address_1'])) {
                $shipping_address->address1 = $pd['address_1'];
            }
            if(!empty($pd['address_2'])) {
                $shipping_address->address2 = $pd['address_2'];
            }
            if(!empty($pd['city'])) {
                $shipping_address->city = $pd['city'];
            }
            if(!empty($pd['zip'])) {
                $shipping_address->postcode = $pd['zip'];
            }
            if(!empty($pd['country_code'])) {
                $shipping_address->country = $pd['country_code'];
            }
            
            $shipping_address->update();
        }
        $payments = $order->getOrderPaymentCollection();
        $payments[0]->transaction_id = $transaction_id;
        $payments[0]->card_number = $data['card_number'];
        $payments[0]->card_holder = $data['card_holder'];
        $payments[0]->card_brand = $data['card_type'];
        $payments[0]->payment_method = $data['transaction_type'];
        $payments[0]->update();
    }
    else
    {
        //redirect error
    }
}
if($isWebhookPost)
{
    ob_clean();
    echo "OK";
    die();
}
Tools::redirectLink(_PS_BASE_URL_ . __PS_BASE_URI__.'index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&payment_ref='.$payment_ref.'&id_module='.$mondidopay->id.'&id_order='.$mondidopay->currentOrder.'&key='.$order->secure_key.'&transaction_id='.$transaction_id.'&hash='.$hash);
