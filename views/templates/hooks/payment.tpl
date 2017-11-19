{*
*    $Id$ mondidopayment Module
*
*    Copyright @copyright 2016 Mondido
*
*    @category  Payment
*    @version   1.5.4
*    @author    Mondido
*    @copyright 2016 Mondido
*    @link      https://www.mondido.com
*    @license   MIT
*
*   Description:
*   Payment module mondidopay
*}
<form action="https://pay.mondido.com/v1/form" method="post" id="mondido_form" style="display: none;">
    <input type="hidden" name="customer_ref" value="{$customer_ref|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="amount" value="{$total|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="currency" value="{$currency|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="hash" value="{$hash|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="merchant_id" value="{$merchantID|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="success_url" value="{$success_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="error_url" value="{$error_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="test" value="{$test|escape:'htmlall':'UTF-8'}">
    <!-- <input type="hidden" name="authorize" value="{$authorize|escape:'htmlall':'UTF-8'}"> -->
    <input type="hidden" name="items" value='{$items|escape:'htmlall':'UTF-8'}'>
    <input type="hidden" name="payment_ref" value="{$payment_ref|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="vat_amount" value="{$vat_amount|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="webhook" value='{$webhook|escape:'htmlall':'UTF-8'}'>
    <input type="hidden" name="metadata" value='{$metadata|escape:'htmlall':'UTF-8'}' />
</form>

<script type="application/javascript">
    jQuery(document).ready(function() {
        jQuery('#mondido').on('click', function(e) {
            jQuery('#mondido_form').submit();
            e.preventDefault();
            return false;
        });
    });
</script>

<p class="payment_module" style="width:570px;">
    <a id="mondido" href="#" title="{l s='Pay with your Credit Card' mod='mondidopay'}">
        <img src="https://cdn-02.mondido.com/www/img/mondido-2015.png" alt="{l s='Pay with your Credit Card' mod='mondidopay'}" width="106">
        {l s='Pay with a credit card' mod='mondidopay'}
    </a>
</p>