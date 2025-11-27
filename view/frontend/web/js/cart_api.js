define([
    'jquery',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote'
], function(_, totalsProcessor, cartCache, customerData, quote) {
    console.log("[BH] [DEBUG] {cart_api} initializing");

    window.Bubblehouse ??= {};

    Object.assign(window.Bubblehouse, {
        MagentoCheckout: {
            cart: {
                reload: () => {
                    var sections = ['cart'];
                    customerData.invalidate(sections);
                    customerData.reload(sections, true);

                    cartCache.set('totals', null);
                    totalsProcessor.estimateTotals();

                    return quote;
                }
            }
        }
    })
});
