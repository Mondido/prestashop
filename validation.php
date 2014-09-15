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

require dirname(__FILE__).'/../../config/config.inc.php';
include(dirname(__FILE__).'/../../header.php');
require dirname(__FILE__).'/mondidopay.php';




$currency = new Currency(intval(isset($_POST['currency_payement']) ? $_POST['currency_payement'] : $cookie->id_currency));
$total = floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));
$transaction_id = $_GET['transaction_id'];
$hash = $_GET['hash'];
$payment_ref=$_GET['payment_ref'];

$mondidopay = new mondidopay();
$mondidopay->validateOrder($cart->id,  _PS_OS_PAYMENT_, $total, $mondidopay->displayName, NULL, NULL, $currency->id);
/*
function httpAuth2(){
		$merchantID = $this->merchantID;
		$password = $this->password;
		$remoteurl = 'https://api.mondido.com/v1/transactions/'. $transaction_id;
		
		$opts = array('http' => array('method' => "GET",
			'header' => "Authorization: Basic " . base64_encode("$merchantID:$password")
		));
		
		$context = stream_context_create($opts);
		
		$file = file_get_contents($remoteurl, false, $context);
		 $data = (array) json_decode($file, true);
		 //$data = implode(',', array_values($data));
		 return $data;
		 
	}
*/

if (isset($_GET['transaction_id'])){
    $merchantID = Configuration::get('MONDIDO_MERCHANTID');
    $password =Configuration::get('MONDIDO_PASSWORD');
    $remoteurl = 'https://api.mondido.com/v1/transactions/'. $transaction_id;

    $opts = array('http' => array('method' => "GET",
        'header' => "Authorization: Basic " . base64_encode("$merchantID:$password")
    ));

    $context = stream_context_create($opts);

    $file = file_get_contents($remoteurl, false, $context);
    $data = (array) json_decode($file, true);
    $order = new Order($mondidopay->currentOrder);
    $payments = $order->getOrderPaymentCollection();
    $payments[0]->transaction_id = $transaction_id;
    $payments[0]-> card_number = $data['card_number'];
    $payments[0]-> card_holder = $data['card_holder'];
    $payments[0]-> card_brand = $data['card_type'];
    $payments[0]->update();
}
/*'card_holder' =>  $file['card_holder'],
				'card_number' =>  $file['card_number'],
				'card_type' =>  $file['card_type'],
*/

Tools::redirectLink(_PS_BASE_URL_ . __PS_BASE_URI__.'index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&payment_ref='.$payment_ref.'&id_module='.$mondidopay->id.'&id_order='.$mondidopay->currentOrder.'&key='.$order->secure_key.'&transaction_id='.$transaction_id.'&hash='.$hash);

?>