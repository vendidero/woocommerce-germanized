"use strict";(self.webpackWcGzdBlocksJsonp=self.webpackWcGzdBlocksJsonp||[]).push([[131],{953:function(e,t,c){c.r(t),c.d(t,{default:function(){return g}});var n=c(196),o=c(307),a=c(818),s=c(554),d=c(819),r=c.n(d),l=c(801),i=c(984),h=c(231),u=({show:e,url:t,onClose:c,content:a})=>{const[s,d]=(0,o.useState)(!0),[r,l]=(0,o.useState)("");let u="";if(a)l(a),d(!1);else if(t)try{const e=new URL(t);u=t.toString().substring(e.origin.length)}catch{}return(0,o.useEffect)((()=>{e?document.body.classList.add("checkout-modal-open"):document.body.classList.remove("checkout-modal-open"),u&&(d(!0),l(""),fetch(u,{method:"get"}).then((e=>e.text())).then((e=>{l(e),d(!1)})).catch((function(e){d(!1)})))}),[u,l,e]),e?(0,n.createElement)(n.Fragment,null,(0,n.createElement)("div",{className:"wc-gzd-checkout-modal-bg"}),(0,n.createElement)("div",{className:"wc-gzd-checkout-modal-wrapper"},(0,n.createElement)("div",{className:"wc-gzd-checkout-modal"},(0,n.createElement)("div",{className:"actions"},(0,n.createElement)("a",{className:"wc-gzd-checkout-modal-close",onClick:e=>{document.body.classList.remove("checkout-modal-open"),c(e)}},(0,n.createElement)(i.Z,{className:"wc-gzd-checkout-modal-close-icon",icon:h.Z,size:24}))),s?(0,n.createElement)("div",{className:"content is-loading"},(0,n.createElement)("span",{className:"wc-block-components-spinner","aria-hidden":"true"})):(0,n.createElement)(n.Fragment,null,(0,n.createElement)("div",{className:"content",dangerouslySetInnerHTML:{__html:r}}))))):null},m=c(342),_=c(617),k=e=>{const{onChangeCheckbox:t,checkbox:c}=e,{shouldCreateAccount:s,customerId:d}=(0,a.useSelect)((e=>{const t=e(l.CHECKOUT_STORE_KEY);return{customerId:t.getCustomerId(),shouldCreateAccount:t.getShouldCreateAccount()}})),r=!1===(0,_.getSetting)("checkoutAllowsGuest",!1)&&!d;return(0,o.useEffect)((()=>{t(s||r?{...c,hidden:!1}:{...c,hidden:!0})}),[s]),(0,n.createElement)(m.Z,{...e})},b=e=>{const[t,c]=(0,o.useState)({}),{onChangeCheckbox:s,checkbox:d}=e,{billingAddress:r,paymentData:i,currentPaymentMethod:h}=(0,a.useSelect)((e=>{const t=e(l.CART_STORE_KEY),c=e(l.PAYMENT_STORE_KEY);return{billingAddress:t.getCartData().billingAddress,paymentData:c.getPaymentMethodData(),currentPaymentMethod:c.getActivePaymentMethod()}}));return(0,o.useEffect)((()=>{const e={country:r.country,postcode:r.postcode,city:r.city,street:r.address_1,address_2:r.address_2,account_holder:i.hasOwnProperty("direct_debit_account_holder")?i.direct_debit_account_holder:"",account_iban:i.hasOwnProperty("direct_debit_account_iban")?i.direct_debit_account_iban:"",account_swift:i.hasOwnProperty("direct_debit_account_bic")?i.direct_debit_account_bic:""};c(e),s("direct-debit"===h&&e.account_holder&&e.account_iban&&e.account_swift?{...d,hidden:!1}:{...d,hidden:!0})}),[r,i,h]),(0,n.createElement)(m.Z,{...e,setModalUrl:c=>{c+="&"+new URLSearchParams(t).toString(),e.setModalUrl(c)}})},g=({children:e,checkoutExtensionData:t,extensions:c,cart:d})=>{const[i,h]=(0,o.useState)(!1),{setExtensionData:_}=t,g=c.hasOwnProperty("woocommerce-germanized")?c["woocommerce-germanized"]:{},E=g.hasOwnProperty("checkboxes")?g.checkboxes:[],p=E.reduce(((e,t)=>({...e,[t.id]:{...t,hidden:t.default_hidden,checked:t.default_checked}})),{}),[y,w]=(0,o.useState)(p),[f,C]=(0,o.useState)(""),x=(0,o.useRef)(!1),{currentPaymentMethod:S}=(0,a.useSelect)((e=>({currentPaymentMethod:e(l.PAYMENT_STORE_KEY).getActivePaymentMethod()}))),M=e=>Object.values(e).filter((e=>e.checked||!e.has_checkbox&&!e.hidden?e:null));(0,o.useEffect)((()=>{_("woocommerce-germanized","checkboxes",M(y))}),[y]),(0,o.useEffect)((()=>{Object.keys(y).map((e=>{y[e].show_for_payment_methods.length>0&&P(y[e])}))}),[S]);const P=(0,o.useCallback)((e=>{w((t=>{const c=t[e.id].checked!==e.checked;if(e.show_for_payment_methods.length>0){let t=e.default_hidden;e.hidden=t||!r().includes(e.show_for_payment_methods,S)}const n={...t,[e.id]:{...e}};return c&&(0,s.extensionCartUpdate)({namespace:"woocommerce-germanized-checkboxes",data:{checkboxes:M(n)}}),n}))}),[_,y,w,s.extensionCartUpdate,S]);return(0,o.useEffect)((()=>{if(x.current){let e={};Object.keys(p).map((t=>{const c=y.hasOwnProperty(t)?y[t]:{},n=y.hasOwnProperty(t)?{checked:y[t].checked,hidden:y[t].hidden}:{};e[t]={...p[t],...n},e[t]!==c&&P(e[t])}))}x.current=!0}),[E]),(0,n.createElement)("div",{className:"wc-gzd-checkboxes"},(0,n.createElement)(u,{show:i,url:f,onClose:()=>{h(!1)}}),Object.keys(y).map((e=>{const t={...y[e]};return"sepa"===t.id?(0,n.createElement)(b,{checkbox:t,setShowModal:h,setModalUrl:C,key:t.id,onChangeCheckbox:P}):"privacy"===t.id?(0,n.createElement)(k,{checkbox:t,setShowModal:h,setModalUrl:C,key:t.id,onChangeCheckbox:P}):(0,n.createElement)(m.Z,{checkbox:t,setShowModal:h,setModalUrl:C,key:t.id,onChangeCheckbox:P})})))}}}]);