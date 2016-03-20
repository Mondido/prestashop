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
<h2>{l s='Order summary' mod='mondidopay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}


{if isset($error_name) && $error_name >= 0}
    <p class="warning">{l s='An error occured during processing of your payment.' mod='mondidopay'}</p>

    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Go Back to payment methods' mod='mondidopay'}</a>
{else}
    <h3>{l s='Mondido Payment.' mod='mondidopay'}</h3>

    <p>
        {l s='Here is a short summary of your order:' mod='mondidopay'}
    </p>
    <p style="margin-top:20px;">
        - {l s='The total amount of your order is' mod='mondidopay'}
        <span id="amount" class="price">{displayPrice price=$total}</span>
        {if $use_taxes == 1}
            {l s='(tax incl.)' mod='mondidopay'}
        {/if}
    </p>


    <form action="https://pay.mondido.com/v1/form" method="post" id="mondido_form">
    <input type="hidden" name="payment_ref" value="a{$cart->id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="customer_ref" value="{$customer->id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="amount" value="{$total|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="currency" value="{$currency->iso_code|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="hash" value="{$hash}">
    <input type="hidden" name="merchant_id" value="{$merchantID}">
    <input type="hidden" name="success_url" value="{$this_path_ssl}validation.php">
    <input type="hidden" name="error_url" value="{$this_path_ssl}payment.php">
    <input type="hidden" name="test" value="{$test}">

    <input type="hidden" name="metadata[products]" value="{$metadata|escape:'htmlall':'UTF-8'}" />
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
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='mondidopay'}</a><input type="submit" value="{l s='I confirm my order' mod='mondidopay'}" class="exclusive_large" style="float: right;"/>
    </form>{/if}
