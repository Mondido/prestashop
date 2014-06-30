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

/**
* Backward function compatibility
* Need to be called for each module in 1.4
*/

//Get out if context is already defined
if (!in_array('Context', get_declared_classes()))
	require_once(dirname(__FILE__).'Context.php');
	
// Get out if the Display (BWDisplay to avoid any conflict)) is already defined
if (!in_array('BWDisplay', get_declared_classes()))
	require_once(dirname(__FILE__).'/Display.php');

// If not under an object we don't have to set the context
if (!isset($this))
	return;
else if (isset($this->context))
{
// If we are under an 1.5 version and backoffice, we have to set some backward variable
if (_PS_VERSION_ >= '1.5' && isset($this->context->employee->id) && $this->context->employee->id && isset(AdminController::$currentIndex) && !empty(AdminController::$currentIndex))
	{
		global $currentIndex;
		$currentIndex = AdminController::$currentIndex;
	}
	return;
}

$this->context = Context::getContext();
$this->smarty = $this->context->smarty;	

?>