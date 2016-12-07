<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}
include_once(_PS_SWIFT_DIR_.'Swift/Message/Encoder.php');
class mondidopay extends PaymentModule 
{
    protected $_errors = array();
    public function __construct() 
    {
        $this->name = 'mondidopay';
        $this->displayName = $this->l('MONDIDO PAYMENTS');
        $this->description = $this->l('Online payment by Mondido');
        $this->author = 'Mondido';
        $this->version = '1.5.3';
        $this->tab = 'payments_gateways';
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->setModuleSettings();
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        parent::__construct();
    
        if (!Configuration::get('mondidopay')) {
            $this->warning = $this->l('No name provided');
        }
    }
    public function install() 
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('displayPayment');
    }
    public function uninstall() 
    {
        Configuration::deleteByName('MONDIDO_MERCHANTID');
        Configuration::deleteByName('MONDIDO_SECRET');
        Configuration::deleteByName('MONDIDO_PASSWORD');
        Configuration::deleteByName('MONDIDO_TEST');
        Configuration::deleteByName('MONDIDO_DEV');
        Configuration::deleteByName('MONDIDO_SUCCESS_URL');
        Configuration::deleteByName('MONDIDO_ERROR_URL');
        return parent::uninstall();
    }
    public function hookPayment($params) 
    {
        $cart = $this->context->cart;
        $cart_details = $cart->getSummaryDetails(null, true);
        $billing_address = new Address($this->context->cart->id_address_invoice);
        $prod_data = $cart->getProducts();
        
        $error_name = Tools::getValue('error_name');
        $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        
        $platform_version = _PS_VERSION_;
        $platform_type = 'prestashop';
        $lang_version = phpversion();
        $plugin_version = '1.5.2';
        
        $analytics = [];
        $google = [];
        $items = [];
        foreach ($prod_data as $i){
            $prod = array(
              'artno' => $i['reference'],
              'description' => $i['name'],
              'amount' => $i['total_wt'],
              'qty' => $i['quantity'],
              'vat' => $i['rate'],
              'discount' => $i['0.00']
            );
            array_push($items, $prod);
        }    
        //Shipping
        $ship_name = $cart_details['carrier']->name;
        if(!$ship_name){
            $ship_name = 'Shipping';
        }
        $prod = array(
            'artno' => 'Shipping',
            'description' => $ship_name,
            'amount' => $cart_details['total_shipping'],
            'qty' => '1',
            'vat' => number_format($cart_details['total_shipping']-$cart_details['total_shipping_tax_exc'], 2, '.', ''),
            'discount' => '0.00'
        );
        array_push($items, $prod);
        
        if(isset($_COOKIE['m_ad_code'])) {
            $google["ad_code"] = $_COOKIE['m_ad_code'];
        }
        if(isset($_COOKIE['m_ref_str'])) {
            $analytics["referrer"] = $_COOKIE['m_ref_str'];
        }
        $currency =  new Currency((int)$cart->id_currency);
        $analytics['google'] = $google;
        $data = Tools::jsonEncode($prod_data);
        if($this->dev == 'true')
        {
            $payment_ref =  'dev'.$cart->id;
        }
        else
        {
            $payment_ref =  'a'.$cart->id;
        }
        $subtotal = number_format($cart_details['total_price_without_tax'], 2, '.', '');
        $vat_amount = $total - $subtotal;
        
        $webhook = array( 
            'url' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/validation.php',
            'trigger' => 'payment', 
            'http_method' => 'post', 
            'data_format' => 'form_data', 
            'type' => 'CustomHttp' 
            );

        $form_data = array(
            'payment_ref' => $payment_ref,
            'items' => Tools::jsonEncode($items),
            'analytics' => Tools::jsonEncode($analytics),
            'error_name' =>  $error_name,
            'merchantID' => $this->merchantID,
            'secretCode' => $this->secretCode,
            'password'	=> $this->password,
            'test'	=> $this->test,
            'total' => $total,
            'subtotal' => $subtotal,
            'currency' => $currency,
            'custom' => Tools::jsonEncode(array('id_cart' => $cart->id, 'hash' => $cart->nbProducts())),
            'customer' => $this->context->customer,
            'metadata'=> $data,
            'cart' => $cart,
            'address'	=> $billing_address,
            'vat_amount' => $vat_amount,
            'hash'	=> md5(
                $this->merchantID .
                $payment_ref .
                $this->context->customer->id .
                $total .
                strtolower($currency->iso_code) .
                (  ($this->test == "true") ? "test"  : ""  ) .
                $this->secretCode
            ),
            'this_path' => $this->_path,
            'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/',
            'webhook'=> Tools::jsonEncode($webhook)
        );
        $this->context->smarty->assign($form_data);
        return $this->display(__FILE__, 'views/templates/hooks/payment.tpl');
    }
    public function httpAuth() 
    {
        $merchantID = $this->merchantID;
        $password = $this->password;
        $remoteurl = 'https://api.mondido.com/' ;
        $Swift_Message_Encoder = new Swift_Message_Encoder();
        $opts = array('http' => array('method' => "GET",
            'header' => "Authorization: Basic " . $Swift_Message_Encoder->base64Encode("$merchantID:$password")
        ));
        $context = stream_context_create($opts);
        $file = Tools::file_get_contents($remoteurl, false, $context);
        $data = (array) Tools::jsonDecode($file, true);
        return $data;
    }
    public function getContent() 
    {
        if (Tools::getValue('mondido_updateSettings')) 
        {
            Configuration::updateValue('MONDIDO_MERCHANTID', Tools::getValue('merchantID'));
            Configuration::updateValue('MONDIDO_SECRET', Tools::getValue('secretCode'));
            Configuration::updateValue('MONDIDO_PASSWORD', Tools::getValue('password'));
            Configuration::updateValue('MONDIDO_TEST', Tools::getValue('test'));
            Configuration::updateValue('MONDIDO_DEV', Tools::getValue('dev'));
            Configuration::updateValue('MONDIDO_SUCCESS_URL', Tools::getValue('success_url'));
            Configuration::updateValue('MONDIDO_ERROR_URL', Tools::getValue('error_url'));
            $this->setModuleSettings();
        }
        $this->context->smarty->assign(array(
            'merchantID' => $this->merchantID,
            'secretCode' => $this->secretCode,
            'password'	=> $this->password,
            'test'	=> $this->test,
            'dev'	=> $this->dev,
            'this_path' => $this->_path,
            'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));
        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }
    public function setModuleSettings() 
    {
        $this->merchantID = Configuration::get('MONDIDO_MERCHANTID');
        $this->secretCode = Configuration::get('MONDIDO_SECRET');
        $this->password	  = Configuration::get('MONDIDO_PASSWORD');
        $this->test		  = Configuration::get('MONDIDO_TEST');
        $this->dev        = Configuration::get('MONDIDO_DEV');
    }
    public function execPayment($cart) 
    {
        if (!$this->active) {
            return;
        }
        $data = Tools::jsonEncode($cart->getProducts());
        $error_name=Tools::getValue('error_name');
        $cart = $this->context->cart;
        $cart_details = $cart->getSummaryDetails(null, true);
        $billing_address = new Address($this->context->cart->id_address_invoice);
        $total = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
        $currency = new Currency((int)$cart->id_currency);
        if($this->dev == 'true')
        {
            $payment_ref =  'dev'.$cart->id;
        }
        else
        {
            $payment_ref =  'a'.$cart->id;
        }        
        $this->context->smarty->assign(array(
            'payment_ref' => $payment_ref,
            'error_name' =>  $error_name,
            'merchantID' => $this->merchantID,
            'secretCode' => $this->secretCode,
            'password'	=> $this->password,
            'test'	=> $this->test,
            'total' => $total,
            'subtotal' => number_format($cart_details['total_price_without_tax'], 2, '.', ''),
            'currency' => $currency,
            'custom' => Tools::jsonEncode(array('id_cart' => $cart->id, 'hash' => $cart->nbProducts())),
            'customer' => $this->context->customer,
            'metadata'=> $data,
            'cart' => $cart,
            'address'	=> $billing_address,
            'hash'	=> md5(
                $this->merchantID .
                $payment_ref .
                $this->context->customer->id .
                $total .
                strtolower($currency->iso_code) .
                (  ($this->test == "true") ? "test"  : ""  ) .
                $this->secretCode
            ),
            'this_path' => $this->_path,
            'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));
        return $this->display(__FILE__, 'views/templates/hooks/payment_execution.tpl');
    }
}