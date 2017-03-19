{if $status == 'ok'}
    <p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='mondidopay'}
        <br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='mondidopay'}</span>
        <br /><br />{l s='For any questions or for further information, please contact our' mod='mondidopay'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer support' mod='mondidopay'}</a>.
    </p>
{else}
    {if $status == 'pending'}
        <p>{l s='Your order on %s is pending.' sprintf=$shop_name mod='mondidopay'}
            <br /><br /><span class="bold">{l s='Your order will be shipped as soon as we receive your payment.' mod='mondidopay'}</span>
            <br /><br />{l s='For any questions or for further information, please contact our' mod='mondidopay'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer support' mod='mondidopay'}</a>.
        </p>
    {else}
        <p class="warning">
            {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='mondidopay'}
            <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer support' mod='mondidopay'}</a>.
            <br />
            {l s='Details: %s.' sprintf=$message mod='mondidopay'}
        </p>
    {/if}
{/if}
