define([
    'jquery',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache'
], function(_, totalsProcessor, cartCache) {
    console.log("[BH] [DEBUG] {cart_api} initializing");

    window.Bubblehouse ??= {};

    Object.assign(window.Bubblehouse, {
        MagentoCheckout: {
            cart: {
                reload: () => {
                    cartCache.set('totals', null);
                    totalsProcessor.estimateTotals();
                }
            }
        }
    })
});
