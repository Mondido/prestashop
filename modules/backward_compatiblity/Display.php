<?php
/**
*
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
 * Class allow to display tpl on the FO
 */
class BWDisplay extends FrontController
{
	// Assign template, on 1.4 create it else assign for 1.5
	public function setTemplate($template)
	{
		if (_PS_VERSION_ >= '1.5')
			parent::setTemplate($template);
		else
			$this->template = $template;
	}

	// Overload displayContent for 1.4
	public function displayContent()
	{
		parent::displayContent();

		echo Context::getContext()->smarty->fetch($this->template);
	}
}

?>