"use strict";(self.webpackWcBlocksJsonp=self.webpackWcBlocksJsonp||[]).push([[930],{473:function(e,t,i){i.r(t),i.d(t,{default:function(){return r}});var s=i(196),n=(i(307),i(813)),c=i(736),a=i(818),o=i(801),r=({className:e})=>{const t=(0,n.getSetting)("displayCartPricesIncludingTax",!1),i=(0,n.getSetting)("showMiniCartTaxNotice",!0),r=(0,n.getSetting)("showMiniCartShippingCostsNotice",!0),l=(0,n.getSetting)("isSmallBusiness",!1),m=(0,n.getSetting)("smallBusinessNotice",""),g=(0,a.useSelect)(((e,{dispatch:i})=>{const s=e(o.CART_STORE_KEY),n=s.getCartData(),a=s.getCartErrors(),r=s.getCartTotals(),l=!s.hasFinishedResolution("getCartData"),{receiveCart:m,receiveCartContents:g}=i(o.CART_STORE_KEY),p=n.extensions.hasOwnProperty("woocommerce-germanized")?n.extensions["woocommerce-germanized"]:{shipping_costs_notice:""},d=t?(0,c.__)("incl. VAT","woocommerce-germanized"):(0,c.__)("excl. VAT","woocommerce-germanized");return{cartItems:n.items,crossSellsProducts:n.crossSells,cartItemsCount:n.itemsCount,cartItemsWeight:n.itemsWeight,cartNeedsPayment:n.needsPayment,cartNeedsShipping:n.needsShipping,cartItemErrors:n.errors,cartTotals:r,cartIsLoading:l,cartErrors:a,extensions:n.extensions,shippingRates:n.shippingRates,cartHasCalculatedShipping:n.hasCalculatedShipping,paymentRequirements:n.paymentRequirements,shippingCostsNotice:n.needsShipping?p.shipping_costs_notice:"",taxNotice:r.total_tax>0?d:"",receiveCart:m,receiveCartContents:g}}),[t]);return(0,s.createElement)("div",{className:"wc-gzd-block-mini-cart-notices"},l&&m&&!i&&(0,s.createElement)("div",{className:"wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__small-business-notice",dangerouslySetInnerHTML:{__html:m}}),(0,s.createElement)("div",{className:"wc-gzd-block-mini-cart-notices__notice-wrap"},g.taxNotice&&i&&(0,s.createElement)("div",{className:"wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__tax-notice",dangerouslySetInnerHTML:{__html:g.taxNotice}}),g.shippingCostsNotice&&r&&(0,s.createElement)("div",{className:"wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__shipping-notice",dangerouslySetInnerHTML:{__html:g.shippingCostsNotice}})))}}}]);