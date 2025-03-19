"use strict";(self.webpackWcBlocksJsonp=self.webpackWcBlocksJsonp||[]).push([[284],{541:function(e,t,r){r.r(t),r.d(t,{default:function(){return c}});var l=r(196),n=r(721),a=r(606),c=e=>(e={...e,labelType:"manufacturer"}).isDescendentOfSingleProductTemplate?(0,l.createElement)(a.Z,{...e}):(0,n.withProductDataContext)(a.Z)(e)},606:function(e,t,r){r.d(t,{Z:function(){return x}});var l=r(196),n=r(184),a=r.n(n),c=r(293),o=r(864),s=r(736);const m=e=>!(e=>null===e)(e)&&e instanceof Object&&e.constructor===Object,i=e=>"string"==typeof e;var p=r(857),u=r(83);function d(e={}){const t={};return(0,u.R)(e,{selector:""}).forEach((e=>{t[e.key]=e.value})),t}function g(e,t){return e&&t?`has-${(0,p.o)(t)}-${e}`:""}const y=e=>{const t=(e=>{const t=m(e)?e:{style:{}};let r=t.style;return i(r)&&(r=JSON.parse(r)||{}),m(r)||(r={}),{...t,style:r}})(e),r=function(e){const{backgroundColor:t,textColor:r,gradient:l,style:n}=e,c=g("background-color",t),o=g("color",r),s=function(e){if(e)return`has-${e}-gradient-background`}(l),i=s||n?.color?.gradient;return{className:a()(o,s,{[c]:!i&&!!c,"has-text-color":r||n?.color?.text,"has-background":t||n?.color?.background||l||n?.color?.gradient,"has-link-color":m(n?.elements?.link)?n?.elements?.link?.color:void 0}),style:d({color:n?.color||{}})}}(t),l=function(e){const t=e.style?.border||{};return{className:function(e){const{borderColor:t,style:r}=e,l=t?g("border-color",t):"";return a()({"has-border-color":!!t||!!r?.border?.color,[l]:!!l})}(e),style:d({border:t})}}(t),n=function(e){return{className:void 0,style:d({spacing:e.style?.spacing||{}})}}(t),c=(e=>{const t=m(e.style.typography)?e.style.typography:{},r=i(t.fontFamily)?t.fontFamily:"";return{className:e.fontFamily?`has-${e.fontFamily}-font-family`:r,style:{fontSize:e.fontSize?`var(--wp--preset--font-size--${e.fontSize})`:t.fontSize,fontStyle:t.fontStyle,fontWeight:t.fontWeight,letterSpacing:t.letterSpacing,lineHeight:t.lineHeight,textDecoration:t.textDecoration,textTransform:t.textTransform}}})(t);return{className:a()(c.className,r.className,l.className,n.className),style:{...c.style,...r.style,...l.style,...n.style}}};var w=r(333);const _=e=>({thousandSeparator:e?.thousandSeparator,decimalSeparator:e?.decimalSeparator,fixedDecimalScale:!0,prefix:e?.prefix,suffix:e?.suffix,isNumericString:!0});var f=({className:e,value:t,currency:r,onValueChange:n,displayType:c="text",...o})=>{var s;const m="string"==typeof t?parseInt(t,10):t;if(!Number.isFinite(m))return null;const i=m/10**r.minorUnit;if(!Number.isFinite(i))return null;const p=a()("wc-block-formatted-money-amount","wc-block-components-formatted-money-amount",e),u=null!==(s=o.decimalScale)&&void 0!==s?s:r?.minorUnit,d={...o,..._(r),decimalScale:u,value:void 0,currency:void 0,onValueChange:void 0},g=n?e=>{const t=+e.value*10**r.minorUnit;n(t)}:()=>{};return(0,l.createElement)(w.Z,{className:p,displayType:c,...d,value:i,onValueChange:g})},h=r(307),v=({align:e,className:t,labelType:r,formattedLabel:n,labelClassName:c,labelStyle:o,style:s})=>{const m=a()(t,"wc-gzd-block-components-product-"+r,"wc-gzd-block-components-product-price-label",{[`wc-gzd-block-components-product-price-label--align-${e}`]:e});let i=(0,l.createElement)("span",{className:a()("wc-gzd-block-components-product-"+r+"__value",c)});return n&&(i=(0,h.isValidElement)(n)?(0,l.createElement)("span",{className:a()("wc-gzd-block-components-product-"+r+"__value",c),style:o},n):(0,l.createElement)("span",{className:a()("wc-gzd-block-components-product-"+r+"__value",c),style:o,dangerouslySetInnerHTML:{__html:n}})),(0,l.createElement)("span",{className:m,style:s},i)},x=e=>{const{className:t,textAlign:r,isDescendentOfSingleProductTemplate:n,labelType:m}=e,{parentName:i,parentClassName:p}=(0,o.useInnerBlockLayoutContext)(),{product:u}=(0,o.useProductDataContext)(),d=y(e),g="woocommerce/all-products"===i,w=a()("wc-gzd-block-components-product-"+m,t,d.className,{[`${p}__product-${m}`]:p});if(!u.id&&!n){const e=(0,l.createElement)(v,{align:r,className:w,labelType:m});if(g){const t=`wp-block-woocommerce-gzd-product-${m}`;return(0,l.createElement)("div",{className:t},e)}return e}const _=((e,t,r)=>{const n=t.hasOwnProperty("extensions")?t.extensions["woocommerce-germanized"]:{unit_price_html:"",unit_prices:{price:0,regular_price:0,sale_price:0},unit_product:0,unit_product_html:"",delivery_time_html:"",power_supply_html:"",tax_info_html:"",shipping_costs_info_html:"",defect_description_html:"",nutri_score:"",nutri_score_html:"",deposit_html:"",deposit_prices:{price:0,quantity:0,amount:0},deposit_packaging_type_html:"",manufacturer_html:"",product_safety_attachments_html:"",safety_instructions_html:""},a=t.prices,o=r?(0,c.getCurrencyFromPriceResponse)():(0,c.getCurrencyFromPriceResponse)(a),m=e.replace(/-/g,"_"),i=n.hasOwnProperty(m+"_html")?n[m+"_html"]:"";let p="";return"unit_price"===m?p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)(f,{currency:o,value:1e3})," / ",(0,l.createElement)("span",{className:"unit"},(0,s._x)("kg","unit","woocommerce-germanized"))):"delivery_time"===m?p=(0,s._x)("Delivery time: 2-3 days","preview","woocommerce-germanized"):"tax_info"===m?p=(0,s._x)("incl. 19 % VAT","preview","woocommerce-germanized"):"shipping_costs_info"===m?p=(0,s._x)("plus shipping costs","preview","woocommerce-germanized"):"unit_product"===m?p=(0,s.sprintf)((0,s._x)("Product includes: %1$s kg","preview","woocommerce-germanized"),10):"defect_description"===m?p=(0,s._x)("This product has a serious defect.","preview","woocommerce-germanized"):"deposit"===m?p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)("span",{className:"additional"},(0,s._x)("Plus","preview","woocommerce-germanized"))," ",(0,l.createElement)(f,{currency:o,value:40})," ",(0,l.createElement)("span",{className:"deposit-notice"},(0,s._x)("deposit","preview","woocommerce-germanized"))):"deposit_packaging_type"===m?p=(0,s._x)("Disposable","preview","woocommerce-germanized"):"nutri_score"===m?p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)("span",{className:"wc-gzd-nutri-score-value wc-gzd-nutri-score-value-a"},"A")):"manufacturer"===m?p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)("p",null,(0,l.createElement)("stong",null,(0,s._x)("Sample company name","preview","woocommerce-germanized")),(0,l.createElement)("br",null),(0,s._x)("Sample address","preview","woocommerce-germanized"),(0,l.createElement)("br",null),(0,s._x)("12345 Berlin","preview","woocommerce-germanized"),(0,l.createElement)("br",null),(0,s._x)("sample@sample.com","preview","woocommerce-germanized")),(0,l.createElement)("h3",null,(0,s.__)("Person responsible for the EU","woocommerce-germanized")),(0,l.createElement)("p",null,(0,l.createElement)("stong",null,(0,s._x)("Sample company name","preview","woocommerce-germanized")),(0,l.createElement)("br",null),(0,s._x)("Sample address","preview","woocommerce-germanized"),(0,l.createElement)("br",null),(0,s._x)("12345 Berlin","preview","woocommerce-germanized"),(0,l.createElement)("br",null),(0,s._x)("sample@sample.com","preview","woocommerce-germanized"))):"product_safety_attachments"===m?p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)("ul",null,(0,l.createElement)("li",null,(0,l.createElement)("a",{href:"#"},(0,s._x)("sample-filename.pdf","sample","woocommerce-germanized"))))):"safety_instructions"===m?p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)("p",null,(0,s._x)("Sample safety instructions for a certain product.","preview","woocommerce-germanized"),(0,l.createElement)("br",null))):"power_supply"===m&&(p=(0,l.createElement)(l.Fragment,null,(0,l.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 150 188"},(0,l.createElement)("line",{className:"power-supply-excluded-line",x1:"20",y1:"1",x2:"200",y2:"270",stroke:"black",strokeWidth:"3"}),(0,l.createElement)("path",{d:"m 49.026981,2.6593224 a 0.80511101,0.78626814 0 0 0 -0.533384,0.4874861 l -0.05535,0.1454597 V 32.754718 l -7.922292,0.004 -7.920279,0.0068 -0.153977,0.05798 a 0.87555821,0.85506659 0 0 0 -0.459919,0.452103 l -0.05535,0.142511 v 29.488987 l -8.129609,0.0078 c -8.849177,0.0118 -8.180933,0 -8.774703,0.146444 a 4.9715604,4.8552058 0 0 0 -3.70653,4.0768 c -0.05132,0.376426 -0.05132,113.336627 0,113.714027 0.274745,1.94994 1.686707,3.53526 3.612936,4.04928 0.372365,0.0964 0.600815,0.13466 1.026517,0.16118 0.266692,0.0158 19.121385,0.0236 58.361489,0.0158 53.90923,-0.008 57.98612,-0.008 58.20953,-0.0492 1.10501,-0.19657 1.96548,-0.61526 2.73234,-1.33274 a 4.9514326,4.835549 0 0 0 1.4784,-3.00256 c 0.0352,-0.37642 0.0352,-113.014245 -0.005,-113.406396 a 4.9514326,4.835549 0 0 0 -3.66326,-4.211449 c -0.64107,-0.169048 0.10063,-0.153322 -8.84615,-0.161185 l -8.12157,-0.0078 V 33.37291 l -0.0785,-0.154305 a 0.80511101,0.78626814 0 0 0 -0.62898,-0.441293 c -0.11473,-0.01572 -2.73234,-0.0226 -7.98469,-0.0226 h -7.81461 l -0.008,-14.781842 -0.009,-14.7710286 -0.0594,-0.1159746 a 0.94600542,0.92386506 0 0 0 -0.4086,-0.387237 L 98.9752,2.6367118 h -8.63683 l -0.144919,0.068799 a 0.78498322,0.76661143 0 0 0 -0.468739,0.595605 c -0.02043,0.1081118 -0.02748,4.3303718 -0.02748,14.8034622 V 32.755701 H 58.333064 l -0.008,-14.754322 -0.009,-14.7523555 -0.06642,-0.1415284 A 0.82523878,0.80592484 0 0 0 57.836909,2.7045328 l -0.150958,-0.068799 -4.277153,-0.00492 c -3.397568,-0.00294 -4.298286,0.00492 -4.381817,0.028502 M 56.677549,18.491811 V 32.753735 H 50.06155 V 4.2318587 h 6.615999 z m 41.289109,0 V 32.753735 H 91.350661 V 4.2318587 h 6.615997 z m 16.511832,30.14552 V 62.9081 H 33.554762 V 34.361653 h 80.923728 z m 18.0828,15.951414 c 0.67628,0.176911 1.17143,0.453088 1.63134,0.917969 0.41765,0.425568 0.67228,0.874723 0.84134,1.488995 l 0.0594,0.219173 0.008,56.682078 c 0.008,50.04793 0,56.70467 -0.0312,56.90123 -0.0594,0.31845 -0.16102,0.60937 -0.31902,0.917 -0.46295,0.8865 -1.28113,1.48996 -2.30362,1.70422 -0.20431,0.0422 -3.18421,0.0422 -58.420876,0.0422 -49.35733,0 -58.233678,-0.003 -58.38665,-0.0344 -1.435111,-0.27225 -2.519997,-1.3858 -2.689071,-2.76374 -0.02416,-0.18085 -0.02818,-14.48502 -0.02416,-56.83146 l 0.008,-56.59362 0.07045,-0.253571 c 0.08664,-0.318439 0.169173,-0.518938 0.322144,-0.791183 a 3.2908911,3.213871 0 0 1 1.734007,-1.478184 4.5287494,4.4227582 0 0 1 0.708498,-0.179859 c 0.04629,-0.004 26.286874,-0.0078 58.323246,-0.0078 l 58.244752,0.004 z m 0,0"})),(0,l.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 150 188",className:"power-supply-icon power-supply-charge-icon"},(0,l.createElement)("text",{x:"50%",y:"100",className:"power-supply-min-max-watt"},"50 - 90"),(0,l.createElement)("text",{x:"50%",y:"130",className:"power-supply-watt-abbr"},"W"),(0,l.createElement)("text",{x:"50%",y:"160",className:"power-supply-usb-pd"},"USB-PD"),(0,l.createElement)("path",{d:"m 49.026981,2.6593224 a 0.80511101,0.78626814 0 0 0 -0.533384,0.4874861 l -0.05535,0.1454597 V 32.754718 l -7.922292,0.004 -7.920279,0.0068 -0.153977,0.05798 a 0.87555821,0.85506659 0 0 0 -0.459919,0.452103 l -0.05535,0.142511 v 29.488987 l -8.129609,0.0078 c -8.849177,0.0118 -8.180933,0 -8.774703,0.146444 a 4.9715604,4.8552058 0 0 0 -3.70653,4.0768 c -0.05132,0.376426 -0.05132,113.336627 0,113.714027 0.274745,1.94994 1.686707,3.53526 3.612936,4.04928 0.372365,0.0964 0.600815,0.13466 1.026517,0.16118 0.266692,0.0158 19.121385,0.0236 58.361489,0.0158 53.90923,-0.008 57.98612,-0.008 58.20953,-0.0492 1.10501,-0.19657 1.96548,-0.61526 2.73234,-1.33274 a 4.9514326,4.835549 0 0 0 1.4784,-3.00256 c 0.0352,-0.37642 0.0352,-113.014245 -0.005,-113.406396 a 4.9514326,4.835549 0 0 0 -3.66326,-4.211449 c -0.64107,-0.169048 0.10063,-0.153322 -8.84615,-0.161185 l -8.12157,-0.0078 V 33.37291 l -0.0785,-0.154305 a 0.80511101,0.78626814 0 0 0 -0.62898,-0.441293 c -0.11473,-0.01572 -2.73234,-0.0226 -7.98469,-0.0226 h -7.81461 l -0.008,-14.781842 -0.009,-14.7710286 -0.0594,-0.1159746 a 0.94600542,0.92386506 0 0 0 -0.4086,-0.387237 L 98.9752,2.6367118 h -8.63683 l -0.144919,0.068799 a 0.78498322,0.76661143 0 0 0 -0.468739,0.595605 c -0.02043,0.1081118 -0.02748,4.3303718 -0.02748,14.8034622 V 32.755701 H 58.333064 l -0.008,-14.754322 -0.009,-14.7523555 -0.06642,-0.1415284 A 0.82523878,0.80592484 0 0 0 57.836909,2.7045328 l -0.150958,-0.068799 -4.277153,-0.00492 c -3.397568,-0.00294 -4.298286,0.00492 -4.381817,0.028502 M 56.677549,18.491811 V 32.753735 H 50.06155 V 4.2318587 h 6.615999 z m 41.289109,0 V 32.753735 H 91.350661 V 4.2318587 h 6.615997 z m 16.511832,30.14552 V 62.9081 H 33.554762 V 34.361653 h 80.923728 z m 18.0828,15.951414 c 0.67628,0.176911 1.17143,0.453088 1.63134,0.917969 0.41765,0.425568 0.67228,0.874723 0.84134,1.488995 l 0.0594,0.219173 0.008,56.682078 c 0.008,50.04793 0,56.70467 -0.0312,56.90123 -0.0594,0.31845 -0.16102,0.60937 -0.31902,0.917 -0.46295,0.8865 -1.28113,1.48996 -2.30362,1.70422 -0.20431,0.0422 -3.18421,0.0422 -58.420876,0.0422 -49.35733,0 -58.233678,-0.003 -58.38665,-0.0344 -1.435111,-0.27225 -2.519997,-1.3858 -2.689071,-2.76374 -0.02416,-0.18085 -0.02818,-14.48502 -0.02416,-56.83146 l 0.008,-56.59362 0.07045,-0.253571 c 0.08664,-0.318439 0.169173,-0.518938 0.322144,-0.791183 a 3.2908911,3.213871 0 0 1 1.734007,-1.478184 4.5287494,4.4227582 0 0 1 0.708498,-0.179859 c 0.04629,-0.004 26.286874,-0.0078 58.323246,-0.0078 l 58.244752,0.004 z m 0,0"})))),{preview:p,data:i}})(m,u,n),h=(0,l.createElement)(v,{align:r,className:w,labelType:m,style:d.style,labelStyle:d.style,formattedLabel:n?_.preview:_.data});if(g){const e=`wp-block-woocommerce-gzd-product-${m}`;return(0,l.createElement)("div",{className:e},h)}return h}}}]);