!function(){var e,o;window.germanized=window.germanized||{},e=jQuery,(o=window.germanized).cart_voucher={params:{},vouchers:{},init:function(){this.params=wc_gzd_cart_voucher_params,this.vouchers=wc_gzd_cart_voucher_params.vouchers,e(".woocommerce-checkout").length&&this.manipulate_checkout_totals(),e(".woocommerce-cart-form").length&&this.manipulate_cart_totals(),e(document.body).on("updated_cart_totals",this.manipulate_cart_totals),e(document.body).on("updated_checkout",this.manipulate_checkout_totals),e(document.body).on("applied_coupon",this.refresh_cart_vouchers),e(document.body).on("removed_coupon",this.refresh_cart_vouchers)},refresh_cart_vouchers:function(){var t=o.cart_voucher;setTimeout((function(){e.ajax({type:"POST",url:t.params.wc_ajax_url.toString().replace("%%endpoint%%","gzd_refresh_cart_vouchers"),data:{security:t.params.refresh_cart_vouchers_nonce},success:function(e){t.vouchers=e.vouchers,t.manipulate_cart_totals()},dataType:"json"})}),75)},manipulate_checkout_totals:function(t,r){var c=o.cart_voucher,a=e(".woocommerce-checkout #order_review table");(r=void 0===r?{}:r).hasOwnProperty("fragments")&&r.fragments.hasOwnProperty(".gzd-vouchers")&&(c.vouchers=r.fragments[".gzd-vouchers"]),c.params.display_prices_including_tax||c.move_vouchers_before_total_checkout(),c.manipulate_coupons(a)},manipulate_cart_totals:function(){var t=e(".cart_totals table"),r=t.find("tr.order-total"),c=o.cart_voucher;c.params.display_prices_including_tax||(c.move_vouchers_before_total(t,r),e(".woocommerce-checkout").length&&c.move_vouchers_before_total_checkout()),c.manipulate_coupons(t)},manipulate_coupons:function(t){var r=o.cart_voucher;e.each(r.vouchers,(function(e,o){var c=r.get_voucher_coupon(o,t),a=r.get_voucher_fee(o,t);if(c.hide(),a.length>0&&c.length>0){var n=c.find("a.woocommerce-remove-coupon");n.length>0&&(a.find("td:last").append(" "),a.find("td:last").append(n))}}))},move_vouchers_before_total_checkout:function(){var t=e(".woocommerce-checkout #order_review table"),r=t.find("tr.order-total");o.cart_voucher.move_vouchers_before_total(t,r)},get_voucher_fee:function(e,o){return o.find('tr.fee th:contains("'+e.name+'")').parents("tr")},get_voucher_coupon:function(e,o){return o.find("tr."+e.coupon_class)},move_vouchers_before_total:function(t,r){var c=o.cart_voucher;e.each(c.vouchers,(function(e,o){var a=c.get_voucher_fee(o,t);a.length>0&&a.insertBefore(r)}))}},e(document).ready((function(){o.cart_voucher.init()})),((window.germanized=window.germanized||{}).static=window.germanized.static||{})["cart-voucher"]={}}();