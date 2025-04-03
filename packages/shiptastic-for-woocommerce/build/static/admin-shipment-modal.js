window.shiptastic=window.shiptastic||{},window.shiptastic.admin=window.shiptastic.admin||{},function(t,a,o,i){var n=function(a){var e=this;e.params=wc_shiptastic_admin_shipment_modal_params,e.$modalTrigger=a,e.destroy(),e.setup(),e.$modalTrigger.on("click.wc-stc-modal-"+e.modalId,{adminShipmentModal:e},e.onClick),t(o.body).on("wc_backbone_modal_loaded.wc-stc-modal-"+e.modalId,{adminShipmentModal:e},e.onOpen).on("wc_backbone_modal_response.wc-stc-modal-"+e.modalId,{adminShipmentModal:e},e.response).on("wc_backbone_modal_before_remove.wc-stc-modal-"+e.modalId,{adminShipmentModal:e},e.onClose)};n.prototype.setup=function(){var t=this;t.referenceId=t.$modalTrigger.data("reference")?t.$modalTrigger.data("reference"):0,t.modalClass=t.$modalTrigger.data("id"),t.modalId=t.modalClass+"-"+t.referenceId,t.loadAsync=!!t.$modalTrigger.data("load-async")&&t.$modalTrigger.data("load-async"),t.nonceParams=t.$modalTrigger.data("nonce-params")?t.$modalTrigger.data("nonce-params"):"wc_shiptastic_admin_shipments_params",t.$modal=!1,t.$modalTrigger.data("self",this)},n.prototype.destroy=function(){var a=this;a.$modalTrigger.off(".wc-stc-modal-"+a.modalId),t(o).off(".wc-stc-modal-"+a.modalId),t(o.body).off(".wc-stc-modal-"+a.modalId)},n.prototype.getShipment=function(a){return t("#panel-order-shipments").find("#shipment-"+a)},n.prototype.onRemoveNotice=function(a){return a.data.adminShipmentModal,t(this).parents(".notice").slideUp(150,(function(){t(this).remove()})),!1},n.prototype.onClick=function(t){var a=t.data.adminShipmentModal;return a.$modalTrigger.WCBackboneModal({template:a.modalId}),!1},n.prototype.parseFieldId=function(t){return t.replace("[","_").replace("]","")},n.prototype.onExpandMore=function(a){var e=a.data.adminShipmentModal,o=e.$modal.find(".show-more-wrapper"),i=t(this).parents(".show-more-trigger");return o.show(),o.find(":input:visible").trigger("change",[e]),i.find(".show-more").hide(),i.find(".show-fewer").show(),!1},n.prototype.onHideMore=function(a){var e=a.data.adminShipmentModal.$modal.find(".show-more-wrapper"),o=t(this).parents(".show-more-trigger");return e.hide(),o.find(".show-further-services").show(),o.find(".show-fewer-services").hide(),!1},n.prototype.onChangeField=function(a){var e=a.data.adminShipmentModal,i=e.$modal,n=e.parseFieldId(t(this).attr("id")),d=t(this).val();if(t(this).attr("max")){var r=t(this).attr("max");d>r&&t(this).val(r)}if(t(this).attr("min")){var s=t(this).attr("min");d<s&&t(this).val(s)}if(t(this).hasClass("show-if-trigger")){var m=i.find(t(this).data("show-if"));m.length>0&&(t(this).is(":checked")?m.show():m.hide(),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_show_if",[e]),e.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_show_if",[e]))}else i.find(":input[data-show-if-"+n+"]").parents(".form-field").hide(),t(this).is(":visible")&&(t(this).is(":checkbox")?t(this).is(":checked")&&i.find(":input[data-show-if-"+n+"]").parents(".form-field").show():("0"!==d&&""!==d&&i.find(":input[data-show-if-"+n+'=""]').parents(".form-field").show(),i.find(":input[data-show-if-"+n+'*="'+d+'"]').parents(".form-field").show())),i.find(":input[data-show-if-"+n+"]").trigger("change"),i.find(".show-more-wrapper").each((function(){var a=t(this),e="none"!==a.find("p.form-field").css("display"),o=!!a.data("trigger")&&i.find(a.data("trigger"));o.length>0&&(e?o.show():o.hide())}))},n.prototype.onClose=function(t,a){var e=t.data.adminShipmentModal;-1!==a.indexOf(e.modalId)&&e.$modal&&e.$modal.length>0&&e.$modal.off("click.wc-stc-modal-"+e.modalId)},n.prototype.onOpen=function(a,e){var i=a.data.adminShipmentModal;-1!==e.indexOf(i.modalId)&&(i.setup(),i.$modal=t("."+i.modalClass),i.$modal.data("self",i),i.loadAsync?(params={action:i.getAction("load"),reference_id:i.referenceId,security:i.getNonce("load")},i.doAjax(params,i.onLoadSuccess)):i.initData(),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_open",[i]),i.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_open",[i]))},n.prototype.onLoadSuccess=function(a,e){e.initData(),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_after_load_success",[a,e]),e.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_after_load_success",[a,e])},n.prototype.onAjaxSuccess=function(t,a){},n.prototype.onAjaxError=function(t,a){},n.prototype.getModalMainContent=function(){return this.$modal.find("article")},n.prototype.doAjax=function(a,e,n){var d=this,r=d.getModalMainContent();e=e||d.onAjaxSuccess,n=n||d.onAjaxError,a.hasOwnProperty("reference_id")||(a.reference_id=d.referenceId),d.$modal.find(".wc-backbone-modal-content").block({message:null,overlayCSS:{background:"#fff",opacity:.6}}),d.$modal.find(".notice-wrapper").empty(),t.ajax({type:"POST",url:d.params.ajax_url,data:a,success:function(a){a.success?(a.fragments&&t.each(a.fragments,(function(a,e){t(a).replaceWith(e)})),d.$modal.find("#btn-ok").prop("disabled",!1),d.$modal.find(".wc-backbone-modal-content").unblock(),e.apply(d,[a,d]),i.admin.shipments&&i.admin.shipments.refresh(a),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_ajax_success",[a,d]),d.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_ajax_success",[a,d]),a.fragments&&d.afterRefresh()):(d.$modal.find("#btn-ok").prop("disabled",!1),d.$modal.find(".wc-backbone-modal-content").unblock(),n.apply(d,[a,d]),d.printNotices(r,a),r.animate({scrollTop:0},500),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_ajax_error",[a,d]),d.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_ajax_error",[a,d]))},error:function(t){},dataType:"json"})},n.prototype.afterRefresh=function(){0===this.$modal.find(".notice-wrapper").length&&this.getModalMainContent().prepend('<div class="notice-wrapper"></div>'),t(o.body).trigger("wc-enhanced-select-init"),t(o.body).trigger("wc-init-datepickers"),t(o.body).trigger("init_tooltips")},n.prototype.initData=function(){var a=this;a.$modal=t("."+a.modalClass),a.$modal.data("self",a),a.afterRefresh(),a.$modal.on("click.wc-stc-modal-"+a.modalId,"#btn-ok",{adminShipmentModal:a},a.onSubmit),a.$modal.on("touchstart.wc-stc-modal-"+a.modalId,"#btn-ok",{adminShipmentModal:a},a.onSubmit),a.$modal.on("keydown.wc-stc-modal-"+a.modalId,{adminShipmentModal:a},a.onKeyDown),a.$modal.on("click.wc-stc-modal-"+a.modalId,".notice .notice-dismiss",{adminShipmentModal:a},a.onRemoveNotice),a.$modal.on("change.wc-stc-modal-"+a.modalId,":input[id]",{adminShipmentModal:a},a.onChangeField),a.$modal.on("click.wc-stc-modal-"+a.modalId,".show-more",{adminShipmentModal:a},a.onExpandMore),a.$modal.on("click.wc-stc-modal-"+a.modalId,".show-fewer",{adminShipmentModal:a},a.onHideMore),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_after_init_data",[a]),a.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_after_init_data",[a]),a.$modal.find(":input:visible").trigger("change",[a])},n.prototype.printNotices=function(a,e){var o=this;e.hasOwnProperty("message")?o.addNotice(e.message,"error",a):e.hasOwnProperty("messages")&&t.each(e.messages,(function(e,i){"string"==typeof i||i instanceof String?o.addNotice(i,"error",a):t.each(i,(function(t,i){o.addNotice(i,"soft"===e?"warning":e,a)}))}))},n.prototype.onSubmitSuccess=function(a,e){var n=e.getModalMainContent();a.hasOwnProperty("messages")&&(a.messages.hasOwnProperty("error")||a.messages.hasOwnProperty("soft"))?(e.printNotices(n,a),e.$modal.find("footer").find("#btn-ok").addClass("modal-close").attr("id","btn-close").text(e.params.i18n_modal_close)):e.$modal.find(".modal-close").trigger("click"),a.hasOwnProperty("shipment_id")&&t("div#shipment-"+a.shipment_id).length>0&&i.admin.shipments.initShipment(a.shipment_id),t(o.body).trigger("wc_shiptastic_admin_shipment_modal_after_submit_success",[a,e]),e.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_after_submit_success",[a,e])},n.prototype.getCleanId=function(t=!1){var a=this.modalClass.split("-").join("_").replace("_modal_","_");return t&&(a=a.replace("wc_stc_","").replace("wc_gzdp_","")),a},n.prototype.getNonceParams=function(){return a.hasOwnProperty(this.nonceParams)?a[this.nonceParams]:{}},n.prototype.getNonce=function(t){var a=this.getCleanId(!0)+"_"+t+"_nonce",e=this.getNonceParams();return e.hasOwnProperty(a)?e[a]:this.params[t+"_nonce"]},n.prototype.getAction=function(t){return this.getCleanId().replace("wc_","woocommerce_")+"_"+t},n.prototype.onKeyDown=function(t){var a=t.data.adminShipmentModal;13!==(t.keyCode||t.which)||t.target.tagName&&("input"===t.target.tagName.toLowerCase()||"textarea"===t.target.tagName.toLowerCase())||a.onSubmit.apply(a.$modal.find("button#btn-ok"),[e])},n.prototype.getFormData=function(a){var e={};return a.find(".show-more-wrapper").each((function(){t(this).is(":visible")||t(this).addClass("show-more-wrapper-force-show").show()})),t.each(a.find(":input").serializeArray(),(function(o,i){var n=a.find(':input[name="'+i.name+'"]');if(n&&!n.is(":visible")&&"hidden"!==n.attr("type"))return!0;-1!==i.name.indexOf("[]")?(i.name=i.name.replace("[]",""),e[i.name]=t.makeArray(e[i.name]),e[i.name].push(i.value)):e[i.name]=i.value})),a.find(".show-more-wrapper-force-show").each((function(){t(this).removeClass("show-more-wrapper-force-show").hide()})),e},n.prototype.onSubmit=function(t){var a=t.data.adminShipmentModal,e=a.getModalMainContent().find("form"),o=a.getFormData(e),i=a.$modal.find("#btn-ok");i.length>0&&i.prop("disabled",!0),o.security=a.getNonce("submit"),o.reference_id=a.referenceId,o.action=a.getAction("submit"),a.doAjax(o,a.onSubmitSuccess),t.preventDefault(),t.stopPropagation()},n.prototype.addNotice=function(t,a,e){e.find(".notice-wrapper").append('<div class="notice is-dismissible notice-'+a+'"><p>'+t+'</p><button type="button" class="notice-dismiss"></button></div>')},n.prototype.response=function(a,e,i){var n=a.data.adminShipmentModal;-1!==e.indexOf(n.modalId)&&(t(o.body).trigger("wc_shiptastic_admin_shipment_modal_response",[n,i]),n.$modalTrigger.trigger("wc_shiptastic_admin_shipment_modal_response",[n,i]))},t.fn.wc_shiptastic_admin_shipment_modal=function(){return this.each((function(){return new n(t(this)),this}))}}(jQuery,window,document,window.shiptastic),((window.wcShiptastic=window.wcShiptastic||{}).static=window.wcShiptastic.static||{})["admin-shipment-modal"]={};