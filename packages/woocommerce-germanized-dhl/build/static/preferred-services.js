!function(){var e,r;window.germanized=window.germanized||{},window.germanized.dhl_preferred_services=window.germanized.dhl_preferred_services||{},e=jQuery,(r=window.germanized).dhl_preferred_services={params:{},init:function(){var t=r.dhl_preferred_services;t.params=wc_gzd_dhl_preferred_services_params,e(document.body).on("updated_checkout",t.afterRefreshCheckout),e(document).on("change",".dhl-preferred-service-content .dhl-preferred-location-types input",t.onChangeLocationType).on("change",".woocommerce-checkout #billing_postcode",t.triggerCheckoutRefresh).on("change",".woocommerce-checkout #shipping_postcode",t.triggerCheckoutRefresh).on("change",".dhl-preferred-service-content .dhl-preferred-service-times input",t.triggerCheckoutRefresh).on("change",".dhl-preferred-service-content .dhl-preferred-delivery-types input",t.triggerCheckoutRefresh),t.params.payment_gateways_excluded&&e(document.body).on("payment_method_selected",t.triggerCheckoutRefresh),t.afterRefreshCheckout()},triggerCheckoutRefresh:function(){e(document.body).trigger("update_checkout")},afterRefreshCheckout:function(){var e=r.dhl_preferred_services;e.initTipTip(),e.onChangeLocationType()},onChangeLocationType:function(){r.dhl_preferred_services;var t=e(".dhl-preferred-service-content .dhl-preferred-location-types input:checked");e(".dhl-preferred-service-content .dhl-preferred-service-location-data").hide(),t.length>0&&("place"===t.val()?e(".dhl-preferred-service-content .dhl-preferred-service-location-place").show():"neighbor"===t.val()&&e(".dhl-preferred-service-content .dhl-preferred-service-location-neighbor").show())},initTipTip:function(){e("#tiptip_holder").removeAttr("style"),e("#tiptip_arrow").removeAttr("style"),e(".dhl-preferred-service-content .woocommerce-help-tip").tipTip({attribute:"data-tip",fadeIn:50,fadeOut:50,delay:200})}},e(document).ready((function(){r.dhl_preferred_services.init()})),((window.germanizedShipments=window.germanizedShipments||{}).static=window.germanizedShipments.static||{})["preferred-services"]={}}();