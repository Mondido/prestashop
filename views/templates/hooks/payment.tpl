{*
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
*
*   Description:
*   Payment module mondidopay
*}
<form action="https://pay.mondido.com/v1/form" method="post" id="mondido_form" class="display:none;">
    <input type="hidden" name="customer_ref" value="{$customer->id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="amount" value="{$total|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="currency" value="{$currency->iso_code|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="hash" value="{$hash|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="merchant_id" value="{$merchantID|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="success_url" value="{$this_path_ssl|escape:'htmlall':'UTF-8'}validation.php">
    <input type="hidden" name="error_url" value="{$this_path_ssl|escape:'htmlall':'UTF-8'}payment.php">
    <input type="hidden" name="test" value="{$test|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="items" value='{$items|escape:'htmlall':'UTF-8'}'>
    <input type="hidden" name="payment_ref" value="{$payment_ref|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="vat_amount" value="{$vat_amount|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="webhook" value="{$webhook|escape:'htmlall':'UTF-8'}">

    <input type="hidden" name="metadata[products]" value='{$metadata|escape:'htmlall':'UTF-8'}' />
    <input type="hidden" name="metadata[customer][firstname]" value="{$address->firstname|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][lastname]" value="{$address->lastname|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][address1]" value="{$address->address1|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][address2]" value="{$address->address2|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][postcode]" value="{$address->postcode|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][phone]" value="{$address->phone|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][phone_mobile]" value="{$address->phone_mobile|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][city]" value="{$address->city|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][country]" value="{$address->country|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[customer][email]" value="{$customer->email|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="metadata[analytics]" value='{$analytics|escape:'htmlall':'UTF-8'}' />
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