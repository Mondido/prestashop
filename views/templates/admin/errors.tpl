{if isset($mondidopayments_error_messages)}
{foreach from=$mondidopayments_error_messages item=$mondidopayments_error_message}
    <div class="alert alert-danger">{$mondidopayments_error_message}</div>
{/foreach}
{/if}

{foreach from=$mondidopayments_errors item=error_key}
    <div class="alert alert-danger">
        {if $error_key == 'settings'} {l s='%s could not connect to backend, please check the credentials in settings' sprintf='Mondido Payments' mod='mondidopayments'} {/if}
        {if $error_key == 'status'} {l s='Status could not be updated' mod='mondidopayments'} {/if}
        {if $error_key == 'unknown'} {l s='Something went wrong' mod='mondidopayments'} {/if}
    </div>
{/foreach}
