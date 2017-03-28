{extends file=$layout}
{block name='content'}
<div style="position:fixed;z-index:1000;top:0px;left:0px;height:100%;width:100%;background-color:#e8e8e8;">
    <img src="https://mondido.s3.amazonaws.com/www/img/ring-alt.gif" style="position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);">
</div>

<form action="https://pay.mondido.com/v1/form" method="post" id="mondido_form" style="display: none;">
    <input type="hidden" name="customer_ref" value="{$customer_ref|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="amount" value="{$total|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="currency" value="{$currency|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="hash" value="{$hash|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="merchant_id" value="{$merchantID|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="success_url" value="{$success_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="error_url" value="{$error_url|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="test" value="{$test|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="authorize" value="{$authorize|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="items" value='{$items|escape:'htmlall':'UTF-8'}'>
    <input type="hidden" name="payment_ref" value="{$payment_ref|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="vat_amount" value="{$vat_amount|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="webhook" value='{$webhook|escape:'htmlall':'UTF-8'}'>
    <input type="hidden" name="metadata" value='{$metadata|escape:'htmlall':'UTF-8'}' />
</form>

<script>
    window.onload = function() {
        document.getElementById('mondido_form').submit();
    };
</script>
{/block}