"use strict";(self.webpackWcGzdBlocksJsonp=self.webpackWcGzdBlocksJsonp||[]).push([[131],{953:function(e,t,c){c.r(t),c.d(t,{default:function(){return _}});var o=c(196),n=c(307),a=c(554),s=c(819),d=c.n(s),r=c(984),l=c(231),i=({show:e,url:t,onClose:c,content:a})=>{const[s,d]=(0,n.useState)(!0),[i,u]=(0,n.useState)("");let h="";if(a)u(a),d(!1);else if(t)try{const e=new URL(t);h=t.toString().substring(e.origin.length)}catch{}return(0,n.useEffect)((()=>{e?document.body.classList.add("checkout-modal-open"):document.body.classList.remove("checkout-modal-open"),h&&(d(!0),u(""),fetch(h,{method:"get"}).then((e=>e.text())).then((e=>{u(e),d(!1)})).catch((function(e){d(!1)})))}),[h,u,e]),e?(0,o.createElement)(o.Fragment,null,(0,o.createElement)("div",{className:"wc-gzd-checkout-modal-bg"}),(0,o.createElement)("div",{className:"wc-gzd-checkout-modal-wrapper"},(0,o.createElement)("div",{className:"wc-gzd-checkout-modal"},(0,o.createElement)("div",{className:"actions"},(0,o.createElement)("a",{className:"wc-gzd-checkout-modal-close",onClick:e=>{document.body.classList.remove("checkout-modal-open"),c(e)}},(0,o.createElement)(r.Z,{className:"wc-gzd-checkout-modal-close-icon",icon:l.Z,size:24}))),s?(0,o.createElement)("div",{className:"content is-loading"},(0,o.createElement)("span",{className:"wc-block-components-spinner","aria-hidden":"true"})):(0,o.createElement)(o.Fragment,null,(0,o.createElement)("div",{className:"content",dangerouslySetInnerHTML:{__html:i}}))))):null},u=c(342),h=c(818),m=c(801),b=c(617),k=e=>{const{onChangeCheckbox:t,checkbox:c}=e,{shouldCreateAccount:a,customerId:s}=(0,h.useSelect)((e=>{const t=e(m.CHECKOUT_STORE_KEY);return{customerId:t.getCustomerId(),shouldCreateAccount:t.getShouldCreateAccount()}})),d=!1===(0,b.getSetting)("checkoutAllowsGuest",!1)&&!s;return(0,n.useEffect)((()=>{t(a||d?{...c,hidden:!1}:{...c,hidden:!0})}),[a]),(0,o.createElement)(u.Z,{...e})},g=e=>{const[t,c]=(0,n.useState)({}),{onChangeCheckbox:a,checkbox:s}=e,{billingAddress:d,paymentData:r,currentPaymentMethod:l}=(0,h.useSelect)((e=>{const t=e(m.CART_STORE_KEY),c=e(m.PAYMENT_STORE_KEY);return{billingAddress:t.getCartData().billingAddress,paymentData:c.getPaymentMethodData(),currentPaymentMethod:c.getActivePaymentMethod()}}));return(0,n.useEffect)((()=>{const e={country:d.country,postcode:d.postcode,city:d.city,street:d.address_1,address_2:d.address_2,account_holder:r.hasOwnProperty("direct_debit_account_holder")?r.direct_debit_account_holder:"",account_iban:r.hasOwnProperty("direct_debit_account_iban")?r.direct_debit_account_iban:"",account_swift:r.hasOwnProperty("direct_debit_account_bic")?r.direct_debit_account_bic:""};c(e),a("direct-debit"===l&&e.account_holder&&e.account_iban&&e.account_swift?{...s,hidden:!1}:{...s,hidden:!0})}),[d,r,l]),(0,o.createElement)(u.Z,{...e,setModalUrl:c=>{c+="&"+new URLSearchParams(t).toString(),e.setModalUrl(c)}})},_=({children:e,checkoutExtensionData:t,extensions:c,cart:s})=>{const[r,l]=(0,n.useState)(!1),{setExtensionData:h}=t,m=c.hasOwnProperty("woocommerce-germanized")?c["woocommerce-germanized"]:{},b=(m.hasOwnProperty("checkboxes")?m.checkboxes:[]).reduce(((e,t)=>({...e,[t.id]:t})),{}),[_,E]=(0,n.useState)(b),[p,w]=(0,n.useState)(""),y=e=>Object.values(e).filter((e=>e.checked||!e.has_checkbox&&!e.hidden?e:null));(0,n.useEffect)((()=>{let e={};Object.keys(b).map((t=>{const c=_.hasOwnProperty(t)?{checked:_[t].checked,hidden:_[t].hidden}:{};e[t]={...b[t],...c}})),d().isEqual(e,_)||E(e)}),[b,E]),(0,n.useEffect)((()=>{h("woocommerce-germanized","checkboxes",y(_))}),[_]);const C=(0,n.useCallback)((e=>{E((t=>{const c=t[e.id].checked!==e.checked,o={...t,[e.id]:{...e}};return c&&(0,a.extensionCartUpdate)({namespace:"woocommerce-germanized-checkboxes",data:{checkboxes:y(o)}}),o}))}),[h,_,E,a.extensionCartUpdate]);return(0,o.createElement)("div",{className:"wc-gzd-checkboxes"},(0,o.createElement)(i,{show:r,url:p,onClose:()=>{l(!1)}}),Object.keys(_).map((e=>{const t={..._[e]};return"sepa"===t.id?(0,o.createElement)(g,{checkbox:t,setShowModal:l,setModalUrl:w,key:t.id,onChangeCheckbox:C}):"privacy"===t.id?(0,o.createElement)(k,{checkbox:t,setShowModal:l,setModalUrl:w,key:t.id,onChangeCheckbox:C}):(0,o.createElement)(u.Z,{checkbox:t,setShowModal:l,setModalUrl:w,key:t.id,onChangeCheckbox:C})})))}}}]);