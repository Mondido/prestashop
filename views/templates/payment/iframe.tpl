{extends file=$layout}
{block name='content'}
<section class="card"><iframe id="mondidopayments-iframe" src="{$payment_link}" frameborder="0" scrolling="yes" style="height: 860px; width: 100%;"> </iframe></section>
<script>
(function() {
    var iframe = document.querySelector("#mondidopayments-iframe");

    window.addEventListener('message', function(msg) {
        if (typeof msg.data !== 'object') {
            return;
        }
        if (msg.data.msg === 'resize' && typeof msg.data.height === 'string') {
            if (window.requestAnimationFrame) {
                window.requestAnimationFrame(function() {
                    iframe.scrolling = 'no';
                    iframe.style.height = msg.data.height;
                });
            } else {
                iframe.scrolling = 'no';
                iframe.style.height = msg.data.height;
            }
        }
    }, false);
})();
</script>
{/block}
