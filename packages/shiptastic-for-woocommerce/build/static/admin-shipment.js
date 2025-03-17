(()=>{var t,i;window.shiptastic=window.shiptastic||{},window.shiptastic.admin=window.shiptastic.admin||{},t=jQuery,i=window.shiptastic,t.shipmentsShipment=function(e){this.vars={$shipment:!1,params:{},id:"",isEditable:!0,needsItems:!0,addItemModal:!1,modals:[]},this.root=this,this.construct=function(t){this.vars.id=t,this.vars.params=i.admin.shipments.getParams(),this.refreshDom()},this.refreshDom=function(){var i=this;i.vars.$shipment=t("#order-shipments-list").find("#shipment-"+i.getId()),i.setNeedsItems(i.vars.$shipment.hasClass("needs-items")),i.setIsEditable(i.vars.$shipment.hasClass("is-editable")),i.onChangeProvider(),t.each(i.vars.modals,(function(t,i){i.destroy()})),i.vars.modals=[],$modals=t("#shipment-"+i.vars.id+" a.has-shipment-modal").wc_shiptastic_admin_shipment_modal(),$modals.each((function(){i.vars.modals.push(t(this).data("self"))})),t("#shipment-"+i.vars.id+" #shipment-items-"+i.vars.id).off(".wc-stc-shipment"),t("#shipment-"+i.vars.id+" #shipment-footer-"+i.vars.id).off(".wc-stc-shipment"),t("#shipment-"+i.vars.id+" #shipment-shipping-provider-"+i.vars.id).off(".wc-stc-shipment"),t("#shipment-"+i.vars.id+" #shipment-packaging-"+i.vars.id).off(".wc-stc-shipment"),t("#shipment-"+i.vars.id+" .wc-stc-shipment-label").off(".wc-stc-shipment"),t("#shipment-"+i.vars.id+" #shipment-shipping-provider-"+i.vars.id).on("change",i.onChangeProvider.bind(i)),t("#shipment-"+i.vars.id+" #shipment-packaging-"+i.vars.id).on("change",i.refreshDimensions.bind(i)),t("#shipment-"+i.vars.id+" #shipment-items-"+i.vars.id).on("change.wc-stc-shipment",".item-quantity",i.onChangeQuantity.bind(i)).on("click.wc-stc-shipment","a.remove-shipment-item",i.onRemoveItem.bind(i)).on("wc_shiptastic_admin_shipment_modal_after_load_success.wc-stc-shipment","a.add-shipment-item",i.onLoadedItemsSuccess.bind(i)).on("wc_shiptastic_admin_shipment_modal_after_submit_success.wc-stc-shipment","a.add-shipment-item",i.onAddedItem.bind(i)).on("click.wc-stc-shipment","a.sync-shipment-items",i.onSyncItems.bind(i)),t("#shipment-"+i.vars.id+" #shipment-footer-"+i.vars.id).on("click.wc-stc-shipment",".send-return-shipment-notification",i.onSendReturnNotification.bind(i)).on("click.wc-stc-shipment",".confirm-return-shipment",i.onConfirmReturnRequest.bind(i)),t("#shipment-"+i.vars.id+" .wc-stc-shipment-label").on("click.wc-stc-shipment",".remove-shipment-label",i.onRemoveLabel.bind(i))},this.refreshDimensions=function(){var t=this.getShipment(),i=t.find("#shipment-packaging-"+this.getId()).find("option:selected");""===i.val()?(t.find("#shipment-length-"+this.getId()).removeClass("disabled").prop("disabled",!1),t.find("#shipment-length-"+this.getId()).val(""),t.find("#shipment-width-"+this.getId()).removeClass("disabled").prop("disabled",!1),t.find("#shipment-width-"+this.getId()).val(""),t.find("#shipment-height-"+this.getId()).removeClass("disabled").prop("disabled",!1),t.find("#shipment-height-"+this.getId()).val("")):(t.find("#shipment-length-"+this.getId()).addClass("disabled").prop("disabled",!0),t.find("#shipment-length-"+this.getId()).val(i.data("length")),t.find("#shipment-width-"+this.getId()).addClass("disabled").prop("disabled",!0),t.find("#shipment-width-"+this.getId()).val(i.data("width")),t.find("#shipment-height-"+this.getId()).addClass("disabled").prop("disabled",!0),t.find("#shipment-height-"+this.getId()).val(i.data("height")))},this.blockPackaging=function(){this.getShipmentContent().find(".wc-stc-shipment-packaging-wrapper").block({message:null,overlayCSS:{background:"#fff",opacity:.6}})},this.unblockPackaging=function(){this.getShipmentContent().find(".wc-stc-shipment-packaging-wrapper").unblock()},this.refreshPackaging=function(){var t={action:"woocommerce_stc_refresh_shipment_packaging",shipment_id:this.getId(),security:i.admin.shipments.getParams().refresh_packaging_nonce};this.blockPackaging(),i.admin.shipments.doAjax(t,this.unblockPackaging.bind(this),this.unblockPackaging.bind(this))},this.onSendReturnNotification=function(){var t={action:"woocommerce_stc_send_return_shipment_notification_email",shipment_id:this.getId(),security:i.admin.shipments.getParams().send_return_notification_nonce};return this.block(),i.admin.shipments.doAjax(t,this.unblock.bind(this),this.unblock.bind(this)),!1},this.onConfirmReturnRequest=function(){var t={action:"woocommerce_stc_confirm_return_request",shipment_id:this.getId(),security:i.admin.shipments.getParams().confirm_return_request_nonce};return this.block(),i.admin.shipments.doAjax(t,this.unblock.bind(this),this.unblock.bind(this)),!1},this.onRemoveLabel=function(){return window.confirm(i.admin.shipments.getParams().i18n_remove_label_notice)&&this.removeLabel(),!1},this.removeLabel=function(){var t={action:"woocommerce_stc_remove_shipment_label",shipment_id:this.getId(),security:i.admin.shipments.getParams().remove_label_nonce};this.block(),i.admin.shipments.doAjax(t,this.unblock.bind(this),this.unblock.bind(this))},this.onChangeProvider=function(){var t=this.getShipment(),i=t.find("#shipment-shipping-provider-"+this.getId()),e=i.find("option:selected");t.find(".show-if-provider").hide(),e.length>0&&e.data("is-manual")&&"yes"===e.data("is-manual")&&t.find(".show-if-provider-is-manual").show(),t.find(".show-if-provider-"+i.val()).show()},this.getShipment=function(){return this.vars.$shipment},this.getShipmentContent=function(){return this.vars.$shipment.find("> .shipment-content-wrapper > .shipment-content > .columns > div:not(.shipment-returns-data)")},this.onChangeQuantity=function(e){var n=t(e.target),s=n.parents(".shipment-item").data("id"),h=n.val();this.blockItems();var d={action:"woocommerce_stc_limit_shipment_item_quantity",shipment_id:this.getId(),item_id:s,quantity:h};i.admin.shipments.doAjax(d,this.onChangeQuantitySuccess.bind(this))},this.onChangeQuantitySuccess=function(t){var i=this.getShipment().find('.shipment-item[data-id="'+t.item_id+'"]'),e=i.find(".item-quantity").val(),n=t.max_quantity;e>n?i.find(".item-quantity").val(n):e<=0&&i.find(".item-quantity").val(1),this.refreshDom(),this.unblockItems()},this.setWeight=function(t){this.getShipment().find("#shipment-weight-"+this.getId()).attr("placeholder",t)},this.setLength=function(t){this.getShipment().find("#shipment-length-"+this.getId()).attr("placeholder",t)},this.setWidth=function(t){this.getShipment().find("#shipment-width-"+this.getId()).attr("placeholder",t)},this.setHeight=function(t){this.getShipment().find("#shipment-height-"+this.getId()).attr("placeholder",t)},this.setTotalWeight=function(t){},this.setIsEditable=function(i){var e=this;"boolean"!=typeof i&&(i=!0),this.vars.isEditable=!0===i,this.vars.isEditable?(this.getShipment().addClass("is-editable"),this.getShipment().removeClass("is-locked"),this.getShipmentContent().find(".remove-shipment-item ").show(),this.getShipmentContent().find(".shipment-item-actions").show(),this.getShipmentContent().find(":input:not(.disabled):not([type=hidden])").prop("disabled",!1)):(this.getShipment().removeClass("is-editable"),this.getShipment().addClass("is-locked"),this.getShipmentContent().find(".remove-shipment-item ").hide(),this.getShipmentContent().find(".shipment-item-actions").hide(),this.getShipmentContent().find(":input:not([type=hidden])").prop("disabled",!0),t.each(this.vars.params.shipment_locked_excluded_fields,(function(t,i){e.getShipmentContent().find(":input[name^=shipment_"+i+"]").prop("disabled",!1)})))},this.setNeedsItems=function(t){"boolean"!=typeof t&&(t=!0),this.vars.needsItems=!0===t,this.vars.needsItems?this.getShipment().addClass("needs-items"):this.getShipment().removeClass("needs-items")},this.onSyncItems=function(){return this.syncItems(),!1},this.syncItems=function(){this.blockItems();var t={action:"woocommerce_stc_sync_shipment_items",shipment_id:this.getId()};i.admin.shipments.doAjax(t,this.onSyncItemsSuccess.bind(this),this.onSyncItemsError.bind(this))},this.onSyncItemsSuccess=function(t){this.unblockItems()},this.onSyncItemsError=function(t){this.unblockItems()},this.onAddItem=function(t,e){return e.doAjax({action:"woocommerce_stc_get_available_shipment_items",reference_id:e.reference_id,security:i.admin.shipments.getParams().edit_shipments_nonce},this.onLoadedItemsSuccess.bind(this)),!1},this.onAddedItem=function(t,i){return this.getShipmentContent().find(".shipment-item-list").append(i.new_item),this.refreshDom(),!1},this.onLoadedItemsSuccess=function(i,e,n){$select=n.$modal.find("select#wc-stc-shipment-add-items-select"),$quantity=n.$modal.find("input#wc-stc-shipment-add-items-quantity"),t(document.body).on("change","select#wc-stc-shipment-add-items-select",(function(){var i=t(this).find("option:selected");$quantity.val(i.data("max-quantity")),$quantity.prop("max",i.data("max-quantity"))})),$select.trigger("change")},this.addItem=function(t,e){e=e||1,this.blockItems();var n={action:"woocommerce_stc_add_shipment_item",shipment_id:this.getId(),original_item_id:t,quantity:e};i.admin.shipments.doAjax(n,this.onAddItemSuccess.bind(this),this.onAddItemError.bind(this))},this.addReturn=function(e){this.block();var n={action:"woocommerce_stc_add_shipment_return",shipment_id:this.getId()};t.extend(n,e),i.admin.shipments.doAjax(n,this.onAddReturnSuccess.bind(this),this.onAddReturnError.bind(this))},this.onAddReturnSuccess=function(t){this.getShipment().find(".shipment-return-list").append(t.new_shipment),this.refreshDom(),i.admin.shipments.initShipments(),this.unblock()},this.onAddReturnError=function(t){this.unblock()},this.onAddItemError=function(t){this.unblockItems()},this.onAddItemSuccess=function(t){this.refreshDom(),this.unblockItems()},this.onRemoveItem=function(i){var e=t(i.target).parents(".shipment-item"),n=e.data("id");return e.length>0&&this.removeItem(n),!1},this.blockItems=function(){this.getShipmentContent().find(".shipment-items").block({message:null,overlayCSS:{background:"#fff",opacity:.6}})},this.block=function(){this.getShipment().block({message:null,overlayCSS:{background:"#fff",opacity:.6}})},this.unblockItems=function(){this.getShipmentContent().find(".shipment-items").unblock()},this.unblock=function(){this.getShipment().unblock()},this.removeItem=function(t){this.getShipment().find('.shipment-item[data-id="'+t+'"]');var e={action:"woocommerce_stc_remove_shipment_item",shipment_id:this.getId(),item_id:t};this.blockItems(),i.admin.shipments.doAjax(e,this.onRemoveItemSuccess.bind(this))},this.onRemoveItemSuccess=function(i){var e=this.getShipment().find('.shipment-item[data-id="'+i.item_id+'"]');e.length>0&&e.slideUp(150,(function(){e.hasClass("shipment-item-is-parent")&&($children=e.parents(".shipment-item-list").find(".shipment-item-parent-"+i.item_id),$children.each((function(i){t(this).remove()}))),e.remove()})),this.unblockItems()},this.getId=function(){return this.vars.id},this.construct(e)},((window.wcShiptastic=window.wcShiptastic||{}).static=window.wcShiptastic.static||{})["admin-shipment"]={}})();