"use strict";(self.webpackWcBlocksJsonp=self.webpackWcBlocksJsonp||[]).push([[6],{606:function(e,t,r){r.d(t,{Z:function(){return h}});var n=r(196),o=r(184),a=r.n(o),c=r(293),l=r(864),s=r(736);const m=e=>!(e=>null===e)(e)&&e instanceof Object&&e.constructor===Object,i=e=>"string"==typeof e;var u=r(857),p=r(83);function d(e={}){const t={};return(0,p.R)(e,{selector:""}).forEach((e=>{t[e.key]=e.value})),t}function g(e,t){return e&&t?`has-${(0,u.o)(t)}-${e}`:""}const _=e=>{const t=(e=>{const t=m(e)?e:{style:{}};let r=t.style;return i(r)&&(r=JSON.parse(r)||{}),m(r)||(r={}),{...t,style:r}})(e),r=function(e){const{backgroundColor:t,textColor:r,gradient:n,style:o}=e,c=g("background-color",t),l=g("color",r),s=function(e){if(e)return`has-${e}-gradient-background`}(n),i=s||o?.color?.gradient;return{className:a()(l,s,{[c]:!i&&!!c,"has-text-color":r||o?.color?.text,"has-background":t||o?.color?.background||n||o?.color?.gradient,"has-link-color":m(o?.elements?.link)?o?.elements?.link?.color:void 0}),style:d({color:o?.color||{}})}}(t),n=function(e){const t=e.style?.border||{};return{className:function(e){const{borderColor:t,style:r}=e,n=t?g("border-color",t):"";return a()({"has-border-color":!!t||!!r?.border?.color,[n]:!!n})}(e),style:d({border:t})}}(t),o=function(e){return{className:void 0,style:d({spacing:e.style?.spacing||{}})}}(t),c=(e=>{const t=m(e.style.typography)?e.style.typography:{},r=i(t.fontFamily)?t.fontFamily:"";return{className:e.fontFamily?`has-${e.fontFamily}-font-family`:r,style:{fontSize:e.fontSize?`var(--wp--preset--font-size--${e.fontSize})`:t.fontSize,fontStyle:t.fontStyle,fontWeight:t.fontWeight,letterSpacing:t.letterSpacing,lineHeight:t.lineHeight,textDecoration:t.textDecoration,textTransform:t.textTransform}}})(t);return{className:a()(c.className,r.className,n.className,o.className),style:{...c.style,...r.style,...n.style,...o.style}}};var y=r(333);const f=e=>({thousandSeparator:e?.thousandSeparator,decimalSeparator:e?.decimalSeparator,fixedDecimalScale:!0,prefix:e?.prefix,suffix:e?.suffix,isNumericString:!0});var w=({className:e,value:t,currency:r,onValueChange:o,displayType:c="text",...l})=>{var s;const m="string"==typeof t?parseInt(t,10):t;if(!Number.isFinite(m))return null;const i=m/10**r.minorUnit;if(!Number.isFinite(i))return null;const u=a()("wc-block-formatted-money-amount","wc-block-components-formatted-money-amount",e),p=null!==(s=l.decimalScale)&&void 0!==s?s:r?.minorUnit,d={...l,...f(r),decimalScale:p,value:void 0,currency:void 0,onValueChange:void 0},g=o?e=>{const t=+e.value*10**r.minorUnit;o(t)}:()=>{};return(0,n.createElement)(y.Z,{className:u,displayType:c,...d,value:i,onValueChange:g})},b=r(307),v=({align:e,className:t,labelType:r,formattedLabel:o,labelClassName:c,labelStyle:l,style:s})=>{const m=a()(t,"wc-gzd-block-components-product-"+r,"wc-gzd-block-components-product-price-label",{[`wc-gzd-block-components-product-price-label--align-${e}`]:e});let i=(0,n.createElement)("span",{className:a()("wc-gzd-block-components-product-"+r+"__value",c)});return o&&(i=(0,b.isValidElement)(o)?(0,n.createElement)("span",{className:a()("wc-gzd-block-components-product-"+r+"__value",c),style:l},o):(0,n.createElement)("span",{className:a()("wc-gzd-block-components-product-"+r+"__value",c),style:l,dangerouslySetInnerHTML:{__html:o}})),(0,n.createElement)("span",{className:m,style:s},i)},h=e=>{const{className:t,textAlign:r,isDescendentOfSingleProductTemplate:o,labelType:m}=e,{parentName:i,parentClassName:u}=(0,l.useInnerBlockLayoutContext)(),{product:p}=(0,l.useProductDataContext)(),d=_(e),g="woocommerce/all-products"===i,y=a()("wc-gzd-block-components-product-"+m,t,d.className,{[`${u}__product-${m}`]:u});if(!p.id&&!o){const e=(0,n.createElement)(v,{align:r,className:y,labelType:m});if(g){const t=`wp-block-woocommerce-gzd-product-${m}`;return(0,n.createElement)("div",{className:t},e)}return e}const f=((e,t,r)=>{const o=t.hasOwnProperty("extensions")?t.extensions["woocommerce-germanized"]:{unit_price_html:"",unit_prices:{price:0,regular_price:0,sale_price:0},unit_product:0,unit_product_html:"",delivery_time_html:"",tax_info_html:"",shipping_costs_info_html:"",defect_description_html:"",nutri_score:"",nutri_score_html:"",deposit_html:"",deposit_prices:{price:0,quantity:0,amount:0},deposit_packaging_type_html:"",manufacturer_html:"",product_safety_attachments_html:"",safety_instructions_html:""},a=t.prices,l=r?(0,c.getCurrencyFromPriceResponse)():(0,c.getCurrencyFromPriceResponse)(a),m=e.replace(/-/g,"_"),i=o.hasOwnProperty(m+"_html")?o[m+"_html"]:"";let u="";return"unit_price"===m?u=(0,n.createElement)(n.Fragment,null,(0,n.createElement)(w,{currency:l,value:1e3})," / ",(0,n.createElement)("span",{className:"unit"},(0,s._x)("kg","unit","woocommerce-germanized"))):"delivery_time"===m?u=(0,s._x)("Delivery time: 2-3 days","preview","woocommerce-germanized"):"tax_info"===m?u=(0,s._x)("incl. 19 % VAT","preview","woocommerce-germanized"):"shipping_costs_info"===m?u=(0,s._x)("plus shipping costs","preview","woocommerce-germanized"):"unit_product"===m?u=(0,s.sprintf)((0,s._x)("Product includes: %1$s kg","preview","woocommerce-germanized"),10):"defect_description"===m?u=(0,s._x)("This product has a serious defect.","preview","woocommerce-germanized"):"deposit"===m?u=(0,n.createElement)(n.Fragment,null,(0,n.createElement)("span",{className:"additional"},(0,s._x)("Plus","preview","woocommerce-germanized"))," ",(0,n.createElement)(w,{currency:l,value:40})," ",(0,n.createElement)("span",{className:"deposit-notice"},(0,s._x)("deposit","preview","woocommerce-germanized"))):"deposit_packaging_type"===m?u=(0,s._x)("Disposable","preview","woocommerce-germanized"):"nutri_score"===m?u=(0,n.createElement)(n.Fragment,null,(0,n.createElement)("span",{className:"wc-gzd-nutri-score-value wc-gzd-nutri-score-value-a"},"A")):"manufacturer"===m?u=(0,n.createElement)(n.Fragment,null,(0,n.createElement)("p",null,(0,n.createElement)("stong",null,(0,s._x)("Sample company name","preview","woocommerce-germanized")),(0,n.createElement)("br",null),(0,s._x)("Sample address","preview","woocommerce-germanized"),(0,n.createElement)("br",null),(0,s._x)("12345 Berlin","preview","woocommerce-germanized"),(0,n.createElement)("br",null),(0,s._x)("sample@sample.com","preview","woocommerce-germanized")),(0,n.createElement)("h3",null,(0,s.__)("Person responsible for the EU","woocommerce-germanized")),(0,n.createElement)("p",null,(0,n.createElement)("stong",null,(0,s._x)("Sample company name","preview","woocommerce-germanized")),(0,n.createElement)("br",null),(0,s._x)("Sample address","preview","woocommerce-germanized"),(0,n.createElement)("br",null),(0,s._x)("12345 Berlin","preview","woocommerce-germanized"),(0,n.createElement)("br",null),(0,s._x)("sample@sample.com","preview","woocommerce-germanized"))):"product_safety_attachments"===m?u=(0,n.createElement)(n.Fragment,null,(0,n.createElement)("ul",null,(0,n.createElement)("li",null,(0,n.createElement)("a",{href:"#"},(0,s._x)("sample-filename.pdf","sample","woocommerce-germanized"))))):"safety_instructions"===m&&(u=(0,n.createElement)(n.Fragment,null,(0,n.createElement)("p",null,(0,s._x)("Sample safety instructions for a certain product.","preview","woocommerce-germanized"),(0,n.createElement)("br",null)))),{preview:u,data:i}})(m,p,o),b=(0,n.createElement)(v,{align:r,className:y,labelType:m,style:d.style,labelStyle:d.style,formattedLabel:o?f.preview:f.data});if(g){const e=`wp-block-woocommerce-gzd-product-${m}`;return(0,n.createElement)("div",{className:e},b)}return b}},0:function(e,t,r){r.r(t),r.d(t,{default:function(){return c}});var n=r(196),o=r(721),a=r(606),c=e=>(e={...e,labelType:"unit-product"}).isDescendentOfSingleProductTemplate?(0,n.createElement)(a.Z,{...e}):(0,o.withProductDataContext)(a.Z)(e)}}]);