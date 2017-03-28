{if $status == 'ok'}
    <p>{l s='Your order on %s is complete.' sprintf=[$shop_name] d='Modules.MondidoPay.Shop'}
        <br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' d='Modules.MondidoPay.Shop'}</span>
        <br /><br />{l s='For any questions or for further information, please contact our' d='Modules.MondidoPay.Shop'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer service department.' d='Modules.MondidoPay.Shop'}</a>.
    </p>
{else}
    {if $status == 'pending'}
        <p>{l s='Your order on %s is pending.' sprintf=[$shop_name] d='Modules.MondidoPay.Shop'}
            <br /><br /><span class="bold">{l s='Your order will be shipped as soon as we receive your payment.' d='Modules.MondidoPay.Shop'}</span>
            <br /><br />{l s='For any questions or for further information, please contact our' d='Modules.MondidoPay.Shop'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer service department.' d='Modules.MondidoPay.Shop'}</a>.
        </p>
    {else}
        <p class="warning">
            {l s='We have noticed that there is a problem with your order. If you think this is an error, you can contact our' d='Modules.MondidoPay.Shop'}
            <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer service department.' d='Modules.MondidoPay.Shop'}</a>.
            <br />
            {l s='Details: %s.' sprintf=[$message] d='Modules.MondidoPay.Shop'}
        </p>
    {/if}
{/if}

