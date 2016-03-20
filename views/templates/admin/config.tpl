{*
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
*}
<img src="{$this_path|escape:'htmlall':'UTF-8'}/img/mondido-logo-plugin.png" alt="{l s='Pay with your Credit Card using Mondido Pay' mod='mondidopay'}" width="231" height="58"/>
<h2>{l s='Mondido Payment Checkout 1.4.0' mod='mondidopay'}</h2>
<fieldset>
    <legend>Help</legend>
    <h3>Welcome to Mondido Payments</h3>
    <p>
        <a href="https://mondido.com/en">SIMPLE PAYMENTS,SMART FUNCTIONS, Get your account here</a>
    </p>
    <ul>
        <li>CARD PAYMENTS FOR ALL PLATFORMS</li>
        <li>SIMPLE INTEGRATION</li>
        <li>SECURELY STORED CARDS AND RECURRING PAYMENTS</li>
        <li>SIMPLE PRICING, NO STARTUP OR MONTHLY FEES</li>
    </ul>
    <p>
        We love beautiful code and our goal is that the implementation should be as easy as possible. Get started quickly through our API’s and SDK’s for web and mobile platforms.
    </p>
    <p>
        Mondido is not only a simple way to start accepting payments online, our system helps you analyse and increase sales.
    </p>
    <p>
        Your money reaches your bank account two bank days after purchase. Our charge for the service is 2,5% and 2,5 SEK, no additional fees or startup costs.
    </p>
    <p>
        Read our documentation here: <a href="https://mondido.com/documentation" target="_blank">https://mondido.com/documentation</a>
    </p>

</fieldset>
<form action="#" method="post" style="clear: both; margin-top: 10px;">
<fieldset>
	<legend>{l s='Settings' mod='mondidopay'}</legend>
    <p>
        Find your merchant settings here: <a href="https://mondido.com/en/settings" target="_blank">https://mondido.com/en/settings</a>
    </p>
	<label for="merchantID">{l s='Merchant ID' mod='mondidopay'}</label>
        <div class="margin-form"><input type="text" size="33" id="merchantID" name="merchantID" value="{$merchantID|escape:'htmlall':'UTF-8'}" /></div>
	<label for="mondidoSecret">{l s='Secret' mod='mondidopay'}</label>
		<div class="margin-form"><input type="text" size="33" name="secretCode" id="secretCode" value="{$secretCode|escape:'htmlall':'UTF-8'}" /></div>
	<label for="mondidoPword">{l s='Password' mod='mondidopay'}</label>	
		<div class="margin-form"><input type="password" size="33" name="password" id="password" value="{$password|escape:'htmlall':'UTF-8'}" /></div>
	<label for="mondidotest">{l s='Test (true/false)' mod='mondidopay'}</label>
		<div class="margin-form" style="margin-bottom: 30px;"><input type="text" size="2" name="test" id="test" value="{$test|escape:'htmlall':'UTF-8'}" /></div>
	
	<br /><center><input type="submit" name="mondido_updateSettings" value="{l s='Save Settings' mod='mondidopay'}" class="button" style="cursor: pointer; display:" /></center>	
</fieldset>
</form>