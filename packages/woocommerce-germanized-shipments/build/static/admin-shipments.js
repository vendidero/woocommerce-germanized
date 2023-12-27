!function(){var e;window.germanized=window.germanized||{},window.germanized.admin=window.germanized.admin||{},e=jQuery,window.germanized.admin.shipments={params:{},shipments:{},$wrapper:!1,needsSaving:!1,needsShipments:!0,needsReturns:!1,init:function(){var n=germanized.admin.shipments;n.params=wc_gzd_admin_shipments_params,n.$wrapper=e("#panel-order-shipments"),n.needsShipments=n.$wrapper.find("#order-shipment-add").is(":visible"),n.needsReturns=n.$wrapper.find("#order-return-shipment-add").is(":visible"),n.initShipments(),e(document).ajaxComplete(n.onAjaxComplete),e(document).on("click","#order-shipments-list .shipment-header",n.onToggleShipment).on("change","#order-shipments-list :input:visible",n.setNeedsSaving).on("click","#panel-order-shipments #order-shipment-add",n.onAddShipment).on("click","#panel-order-shipments .remove-shipment",n.onRemoveShipment).on("click","#panel-order-shipments button#order-shipments-save",n.onSave).on("click","#panel-order-shipments .notice-dismiss",n.onRemoveNotice).on("change",".germanized-create-label #product_id",n.onChangeLabelProductId),e(document).on("wc_gzd_admin_shipment_modal_after_submit_success","a#order-return-shipment-add",n.onAddReturnSuccess),e("#panel-order-shipments #order-return-shipment-add").wc_gzd_admin_shipment_modal(),e(document.body).on("init_tooltips",n.initTiptip),n.initTiptip()},onAjaxComplete:function(e,n,i){var t=germanized.admin.shipments;if(null!=n&&i.hasOwnProperty("data")){var r=i.data,s=!1;try{s=JSON.parse('{"'+r.replace(/&/g,'","').replace(/=/g,'":"')+'"}',(function(e,n){return""===e?n:decodeURIComponent(n)}))}catch(e){s=!1}if(s&&s.hasOwnProperty("action")){var a=s.action;"woocommerce_save_order_items"!==a&&"woocommerce_remove_order_item"!==a&&"woocommerce_add_order_item"!==a&&"woocommerce_delete_refund"!==a||t.syncItemQuantities()}}},syncItemQuantities:function(){var e=germanized.admin.shipments;e.block();var n={action:"woocommerce_gzd_validate_shipment_item_quantities",active:e.getActiveShipmentId()};e.doAjax(n,e.onSyncSuccess)},onSyncSuccess:function(e){var n=germanized.admin.shipments;n.unblock(),n.initShipments(),n.initTiptip()},onSave:function(e){var n=germanized.admin.shipments;return e.preventDefault(),n.save(),!1},save:function(){var e=germanized.admin.shipments;e.block();var n={action:"woocommerce_gzd_save_shipments",active:e.getActiveShipmentId()};e.doAjax(n,e.onSaveSuccess)},initShipment:function(n){var i=germanized.admin.shipments;i.shipments.hasOwnProperty(n)?i.shipments[n].refreshDom():i.shipments[n]=new e.GermanizedShipment(n)},onSaveSuccess:function(e){var n=germanized.admin.shipments;n.initShipments(),n.setNeedsSaving(!1),n.unblock(),n.initTiptip()},getActiveShipmentId:function(){var e=germanized.admin.shipments.$wrapper.find(".order-shipment.active");return e.length>0&&e.data("shipment")},block:function(){germanized.admin.shipments.$wrapper.block({message:null,overlayCSS:{background:"#fff",opacity:.6}})},unblock:function(){germanized.admin.shipments.$wrapper.unblock()},getData:function(n){var i=germanized.admin.shipments,t={};return n=n||{},e.each(i.$wrapper.find(":input[name]").serializeArray(),(function(n,i){-1!==i.name.indexOf("[]")?(i.name=i.name.replace("[]",""),t[i.name]=e.makeArray(t[i.name]),t[i.name].push(i.value)):t[i.name]=i.value})),e.extend(t,n),t},refresh:function(n){var i=germanized.admin.shipments,t=i.getShipment(i.getActiveShipmentId());current_packaging_id=!1,t&&(current_packaging_id=t.getShipment().find(".shipment-packaging-select").val()),n.hasOwnProperty("order_needs_new_shipments")&&i.setNeedsShipments(n.order_needs_new_shipments),n.hasOwnProperty("order_needs_new_returns")&&i.setNeedsReturns(n.order_needs_new_returns);var r=n.hasOwnProperty("shipments")?n.shipments:{};e.each(i.getShipments(),(function(e,n){r.hasOwnProperty(e)&&(n.setIsEditable(r[e].is_editable),n.setNeedsItems(r[e].needs_items),n.setWeight(r[e].weight),n.setLength(r[e].length),n.setWidth(r[e].width),n.setHeight(r[e].height),n.setTotalWeight(r[e].total_weight),i.initShipment(e))})),(n.hasOwnProperty("needs_refresh")||n.hasOwnProperty("needs_packaging_refresh"))&&n.hasOwnProperty("shipment_id")&&(i.initShipment(n.shipment_id),n.hasOwnProperty("needs_packaging_refresh")&&(t=i.getShipment(i.getActiveShipmentId()))&&(new_packaging_id=t.getShipment().find(".shipment-packaging-select").val(),new_packaging_id!==current_packaging_id&&i.getShipment(n.shipment_id).refreshDimensions()))},doAjax:function(n,i,t){var r=germanized.admin.shipments,s=r.params.ajax_url,a=r.$wrapper,d=!0;a.find(".notice-wrapper").empty(),i=i||r.onAjaxSuccess,t=t||r.onAjaxError,n.hasOwnProperty("refresh_fragments")&&(d=n.refresh_fragments),n.hasOwnProperty("security")||(n.security=r.params.edit_shipments_nonce),n.hasOwnProperty("order_id")||(n.order_id=r.params.order_id),n=r.getData(n),e.ajax({type:"POST",url:s,data:n,success:function(n){n.success?(d&&n.fragments&&e.each(n.fragments,(function(n,i){e(n).replaceWith(i),e(n).unblock()})),i.apply(a,[n]),r.refresh(n),r.initTiptip()):(t.apply(a,[n]),r.unblock(),n.hasOwnProperty("message")?r.addNotice(n.message,"error"):n.hasOwnProperty("messages")&&e.each(n.messages,(function(e,n){r.addNotice(n,"error")})),r.initTiptip())},error:function(e){t.apply(a,[e]),r.unblock(),r.initTiptip()},dataType:"json"})},onAjaxError:function(e){},onAjaxSuccess:function(e){},onRemoveNotice:function(){e(this).parents(".notice").slideUp(150,(function(){e(this).remove()}))},addNotice:function(e,n){germanized.admin.shipments.$wrapper.find(".notice-wrapper").append('<div class="notice is-dismissible notice-'+n+'"><p>'+e+'</p><button type="button" class="notice-dismiss"></button></div>')},onChangeLabelProductId:function(){germanized.admin.shipments.showOrHideByLabelProduct(e(this).val())},showOrHideByLabelProduct:function(n){e(".germanized-create-label").find("p.form-field :input[data-products-supported]").each((function(){var i=e(this),t=i.data("products-supported");if(t.length>0)if(t.indexOf("&")>-1&&i.is("select")){var r=t.split("&").filter(Boolean),s=!1;e.each(r,(function(t,r){var a=r.split("=").filter(Boolean);if(a.length>1){var d=a[0],p=a[1].split(","),o=!0,m=i.find('option[value="'+d+'"]');m.length>0&&(-1!==e.inArray(n,p)&&(o=!1),o?(m.is(":selected")&&(s=!0),m.hide()):m.show())}}));var a=!0;i.find("option").each((function(){if("none"!==e(this).css("display"))return s&&e(this).prop("selected",!0),a=!1,!1})),a?(i.parents(".form-field").hide(),i.trigger("change")):(i.parents(".form-field").show(),i.trigger("change")),i.trigger("change")}else{var d=t.split(",").filter(Boolean),p=d.length>0;-1!==e.inArray(n,d)&&(p=!1),p?(i.parents(".form-field").hide(),i.trigger("change")):(i.parents(".form-field").show(),i.trigger("change"))}}))},getParams:function(){return germanized.admin.shipments.params},onRemoveShipment:function(){var n=germanized.admin.shipments,i=e(this).parents(".order-shipment").data("shipment");return window.confirm(n.getParams().i18n_remove_shipment_notice)&&n.removeShipment(i),!1},removeShipment:function(e){var n=germanized.admin.shipments,i={action:"woocommerce_gzd_remove_shipment",shipment_id:e};n.block(),n.doAjax(i,n.onRemoveShipmentSuccess,n.onRemoveShipmentError)},onRemoveShipmentSuccess:function(n){var i=germanized.admin.shipments,t=Array.isArray(n.shipment_id)?n.shipment_id:[n.shipment_id];e.each(t,(function(e,n){var t=i.$wrapper.find("#shipment-"+n);t.length>0&&(t.hasClass("active")?t.find(".shipment-content-wrapper").slideUp(300,(function(){t.removeClass("active"),t.remove(),i.initShipments()})):t.remove())})),i.initShipments(),i.unblock()},onRemoveShipmentError:function(e){germanized.admin.shipments.unblock()},onAddShipment:function(){return germanized.admin.shipments.addShipment(),!1},addShipment:function(){var e=germanized.admin.shipments;e.block(),e.doAjax({action:"woocommerce_gzd_add_shipment"},e.onAddShipmentSuccess,e.onAddShipmentError)},onAddShipmentSuccess:function(e){var n=germanized.admin.shipments;n.$wrapper.find(".order-shipment.active").length>0?n.$wrapper.find(".order-shipment.active").find(".shipment-content-wrapper").slideUp(300,(function(){n.$wrapper.find(".order-shipment.active").removeClass("active"),n.appendNewShipment(e),n.initShipments(),n.initTiptip(),n.unblock()})):(n.appendNewShipment(e),n.initShipments(),n.initTiptip(),n.unblock())},appendNewShipment:function(e){var n=germanized.admin.shipments;"simple"===e.new_shipment_type&&n.$wrapper.find(".panel-order-return-title").length>0?n.$wrapper.find(".panel-order-return-title").before(e.new_shipment):n.$wrapper.find("#order-shipments-list").append(e.new_shipment)},onAddShipmentError:function(e){},onAddReturnSuccess:function(e,n){germanized.admin.shipments.onAddShipmentSuccess(n)},setNeedsSaving:function(n){var i=germanized.admin.shipments,t=i.getActiveShipmentId(),r=!!t&&i.getShipment(t).getShipment();"boolean"!=typeof n&&(n=!0),i.needsSaving=!0===n,i.needsSaving?i.$wrapper.find("#order-shipments-save").show():i.$wrapper.find("#order-shipments-save").hide(),r&&(i.needsSaving?i.disableCreateButtons(r):i.enableCreateButtons(r)),i.hideOrShowFooter(),e(document.body).trigger("woocommerce_gzd_shipments_needs_saving",[i.needsSaving,i.getActiveShipmentId()]),i.initTiptip()},disableCreateButtons:function(n){var i=germanized.admin.shipments,t=n.find(".column-shipment-documents a.wc-gzd-shipment-action-button.create, .column-shipment-documents a.wc-gzd-shipment-action-button.refresh");t.length>0&&(t.addClass("disabled button-disabled"),t.each((function(){e(this).data("org-title",e(this).prop("title")),e(this).prop("title",i.params.i18n_save_before_create)})))},enableCreateButtons:function(n){germanized.admin.shipments;var i=n.find(".column-shipment-documents a.wc-gzd-shipment-action-button.create, .column-shipment-documents a.wc-gzd-shipment-action-button.refresh");i.length>0&&(i.removeClass("disabled button-disabled"),i.each((function(){e(this).data("org-title")&&e(this).prop("title",e(this).data("org-title"))})))},setNeedsShipments:function(e){var n=germanized.admin.shipments;"boolean"!=typeof e&&(e=!0),n.needsShipments=!0===e,n.needsShipments?(n.$wrapper.addClass("needs-shipments"),n.$wrapper.find("#order-shipment-add").show()):(n.$wrapper.removeClass("needs-shipments"),n.$wrapper.find("#order-shipment-add").hide()),n.hideOrShowFooter()},hideOrShowReturnTitle:function(){var e=germanized.admin.shipments;0===e.$wrapper.find(".order-shipment.shipment-return").length?e.$wrapper.find(".panel-order-return-title").addClass("hide-default"):e.$wrapper.find(".panel-order-return-title").removeClass("hide-default")},setNeedsReturns:function(e){var n=germanized.admin.shipments;"boolean"!=typeof e&&(e=!0),n.needsReturns=!0===e,n.needsReturns?(n.$wrapper.addClass("needs-returns"),n.$wrapper.find("#order-return-shipment-add").show()):(n.$wrapper.removeClass("needs-returns"),n.$wrapper.find("#order-return-shipment-add").hide()),n.hideOrShowFooter()},hideOrShowFooter:function(){var e=germanized.admin.shipments;e.needsSaving||e.needsShipments||e.needsReturns?e.$wrapper.find(".panel-footer").slideDown(300):e.$wrapper.find(".panel-footer").slideUp(300)},onToggleShipment:function(){var n=germanized.admin.shipments,i=e(this).parents(".order-shipment:first"),t=i.hasClass("active");n.closeShipments(),t||i.find("> .shipment-content-wrapper").slideDown(300,(function(){i.addClass("active")}))},closeShipments:function(){var e=germanized.admin.shipments;e.$wrapper.find(".order-shipment.active .shipment-content-wrapper").slideUp(300,(function(){e.$wrapper.find(".order-shipment.active").removeClass("active")}))},initShipments:function(){var n=germanized.admin.shipments;n.$wrapper=e("#panel-order-shipments"),n.$wrapper.find(".order-shipment").each((function(){var i=e(this).data("shipment");n.initShipment(i)})),n.hideOrShowReturnTitle()},getShipments:function(){return germanized.admin.shipments.shipments},getShipment:function(e){var n=germanized.admin.shipments.getShipments();return!!n.hasOwnProperty(e)&&n[e]},initTiptip:function(){var n=germanized.admin.shipments;n.$wrapper.find(".woocommerce-help-tip").tipTip({attribute:"data-tip",fadeIn:50,fadeOut:50,delay:200}),n.$wrapper.find(".tip").tipTip({fadeIn:50,fadeOut:50,delay:200}),e(document.body).trigger("shipments_init_tooltips")}},e(document).ready((function(){germanized.admin.shipments.init()})),((window.germanizedShipments=window.germanizedShipments||{}).static=window.germanizedShipments.static||{})["admin-shipments"]={}}();