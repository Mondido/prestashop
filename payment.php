<?php
/**
*    $Id$ mondidopayment Module
*
*    Copyright @copyright 2016 Mondido
*
*    @category  Payment
*    @version   1.4.0
*    @author    Mondido
*    @copyright 2016 Mondido
*    @link      https://www.mondido.com
*    @license   MIT
*
*   Description:
*   Payment module mondidopay
*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/mondidopay.php');

$mondidopay = new mondidopay();
$context = Context::getContext();
$cart = $context->cart;
echo $mondidopay->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');
