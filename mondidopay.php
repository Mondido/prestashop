<?php
/**
*  $Id$
*  mondidopayment Module
* 
* Copyright @copyright 2014 3o-BPO
* 
* @category Payment
* @version 1.0
* @copyright 01.06.2014, 3o-BPO
* @author Jeeky Vincent Mojica, <www.3obpo.com> 
* @link
* @license
* 
* Description:
* 
* Payment module mondidopay
* 
* --
* 
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@touchdesign.de so we can send you a copy immediately.
* 
*/
if(!defined('_PS_VERSION_'))
	exit;
	
class mondidopay extends PaymentModule {
	
	protected $_errors = array();
	
	public function __construct() {
		$this->name = 'mondidopay';
		
		parent::__construct();
			$this->displayName = $this->l('MONDIDO PAYMENTS');
			$this->description = $this->l('Online payment by Mondido');
			
			$this->author = 'Mondido';
			$this->version = '1.0';
			$this->tab = 'payments_gateways';
			
			
			
			$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
			
			$this->setModuleSettings();
			
			
		}	
		
	
	
	public function install(){
		if(!parent::install() OR  !$this->registerHook('invoice') OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn'))
			return false;
			
		return true;
			
	}
	
	
	
	public function uninstall(){
		Configuration::deleteByName('MONDIDO_MERCHANTID');
		Configuration::deleteByName('MONDIDO_SECRET');
		Configuration::deleteByName('MONDIDO_PASSWORD');
		Configuration::deleteByName('MONDIDO_TEST');
		Configuration::deleteByName('MONDIDO_SUCCESS_URL');
		Configuration::deleteByName('MONDIDO_ERROR_URL');
		
		return parent::uninstall();
	}
	
	public function hookPayment($params){
		$cart = $this->context->cart;
		$cart_details = $cart->getSummaryDetails(null, true);
		$billing_address = new Address($this->context->cart->id_address_invoice);
		$file = $this->httpAuth();
		global $smarty;
		$data = Tools::jsonEncode($cart->getProducts());
		
		
		$this->context->smarty->assign(array(
				
				//'order' => $order = $this->order_id() + 5,
				'merchantID' => $this->merchantID,
				'secretCode' => $this->secretCode,
				'password'	=> $this->password,
				'test'	=> $this->test,
				'total' => $total = number_format($cart->getOrderTotal(true, 3), 2, '.', ''),
				'subtotal' => number_format($cart_details['total_price_without_tax'], 2, '.', ''),
				'currency' => new Currency((int)$cart->id_currency),
				'custom' => Tools::jsonEncode(array('id_cart' => $cart->id, 'hash' => $cart->nbProducts())),
				'customer' => $this->context->customer,
				'metadata'=> $data,
				'cart' => $cart,
				'address'	=> $billing_address,
				'hash'	=> md5($this->merchantID . 'a'.$cart->id . $this->context->customer->id . $total . $this->secretCode),
				'this_path' => $this->_path,
				'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));
				return $this->display(__FILE__, 'views/templates/hooks/payment.tpl');
	}	
	
	
	public function httpAuth(){
		$merchantID = $this->merchantID;
		$password = $this->password;
		$remoteurl = 'https://api.mondido.com/' ;
		
		$opts = array('http' => array('method' => "GET",
			'header' => "Authorization: Basic " . base64_encode("$merchantID:$password")
		));
		
		$context = stream_context_create($opts);
		
		$file = file_get_contents($remoteurl, false, $context);
		 $data = (array) json_decode($file, true);
		 //$data = implode(',', array_values($data));
		 return $data;
		 
	}
	
	public function getContent(){
		
		
		if (isset($_POST['mondido_updateSettings'])){
			Configuration::updateValue('MONDIDO_MERCHANTID', Tools::getValue('merchantID'));
			Configuration::updateValue('MONDIDO_SECRET', Tools::getValue('secretCode'));
			Configuration::updateValue('MONDIDO_PASSWORD', Tools::getValue('password'));
			Configuration::updateValue('MONDIDO_TEST', Tools::getValue('test'));
			Configuration::updateValue('MONDIDO_SUCCESS_URL', Tools::getValue('success_url'));
			Configuration::updateValue('MONDIDO_ERROR_URL', Tools::getValue('error_url'));
			
			$this->setModuleSettings();
			$this->httpAuth();
		}
		
		global $smarty;
		
		$smarty->assign(array(
				'merchantID' => $this->merchantID,
				'secretCode' => $this->secretCode,
				'password'	=> $this->password,
				'test'	=> $this->test, 
				'this_path' => $this->_path,
				'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));
				return $this->display(__FILE__, 'views/templates/admin/config.tpl');
		
		}
	
	public function setModuleSettings(){
		$this->merchantID = Configuration::get('MONDIDO_MERCHANTID');
		$this->secretCode = Configuration::get('MONDIDO_SECRET');
		$this->password	  = Configuration::get('MONDIDO_PASSWORD');
		$this->test		  = Configuration::get('MONDIDO_TEST');
		
	}
	
		
	public function execPayment($cart){
		
		
		
		if(!$this->active)
		return;
		$data = Tools::jsonEncode($cart->getProducts());
		$error_name=$_GET['error_name'];
		$cart = $this->context->cart;
		$cart_details = $cart->getSummaryDetails(null, true);
		$billing_address = new Address($this->context->cart->id_address_invoice);
		global $cookie, $smarty;
		
		$smarty->assign(array(
			'error_name' =>  $error_name,	
			'merchantID' => $this->merchantID,
			'secretCode' => $this->secretCode,
			'password'	=> $this->password,
			'test'	=> $this->test,
			'total' => $total = number_format($cart->getOrderTotal(true, 3), 2, '.', ''),
			'subtotal' => number_format($cart_details['total_price_without_tax'], 2, '.', ''),
			'currency' => new Currency((int)$cart->id_currency),
			'custom' => Tools::jsonEncode(array('id_cart' => $cart->id, 'hash' => $cart->nbProducts())),
			'customer' => $this->context->customer,
			'metadata'=> $data,
			'cart' => $cart,
			'address'	=> $billing_address,
			'hash'	=> md5($this->merchantID . 'a'.$cart->id . $this->context->customer->id . $total . $this->secretCode),
			'this_path' => $this->_path,
			'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/'
));
 
		return $this->display(__FILE__, 'views/templates/hooks/payment_execution.tpl');

	}
	
}
	

?>