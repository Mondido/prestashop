{extends file=$layout}
{block name='content'}
<div id="mondidopayments_hide_content"></div>
 <style>
    #mondidopayments_hide_content {
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        z-index: 100000;
        height: 100%;
        display: block;
        position: fixed;
        background: #fff;
    }
</style>
<script>
(function() {
    if (window.location != window.parent.location) {
        window.top.location.href = window.location.href;
    } else {
        document.querySelector('#mondidopayments_hide_content').style.display = 'none';
    }
})();
</script>
<section class="card">
    <div class="card-block"><h1>{l s='Something went wrong' mod='mondidopayments'}</h1></div>
    <div class="card-block">
        <p class="warning">{$message|escape:'htmlall':'UTF-8'}</p>
        <div class="button-container">
            <a href="{$link->getPageLink('order', true, NULL, 'step=1')|escape:'html':'UTF-8'}" class="button btn btn-default standard-checkout button-medium" title="{l s='Proceed to checkout' mod='mondidopayments'}">
                <span>{l s='Try Again' mod='mondidopayments'}<i class="icon-chevron-right right"></i></span>
            </a>
        </div>
    </div>
</section>
{/block}
