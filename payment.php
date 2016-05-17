<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/mondidopay.php');

//if (!$cookie->isLogged())
//    Tools::redirect('authentication.php?back=order.php');



$mondidopay = new mondidopay();
echo $mondidopay->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');
?>