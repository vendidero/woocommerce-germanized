!function(){var i,e;window.germanized=window.germanized||{},window.germanized.shipments_pickup_locations=window.germanized.shipments_pickup_locations||{},i=jQuery,(e=window.germanized).shipments_pickup_locations={params:{},pickupLocations:{},available:!1,init:function(){var o=e.shipments_pickup_locations;o.params=wc_gzd_shipments_pickup_locations_params;var n=o.getPickupLocationSelect();n.length>0&&(o.pickupLocations=n.data("locations")),i("#current_pickup_location").length>0&&(o.available=i(".choose-pickup-location:visible").length>0||i(".currently-shipping-to:visible").length>0,i(document.body).on("updated_checkout",o.afterRefreshCheckout),i(document).on("change","#ship-to-different-address-checkbox",o.onSelectDifferentShipping),i(document).on("submit","#wc-gzd-shipments-pickup-location-search-form",o.onSearch),i(document).on("click",".submit-pickup-location",o.onSelectPickupLocation),i(document).on("change","#current_pickup_location",o.onChangeCurrentPickupLocation),i(document).on("click",".pickup-location-remove",o.onRemovePickupLocation),i(document).on("change","#pickup_location",o.onChangePickupLocation),i(document).on("change","#billing_postcode, #shipping_postcode",o.onChangeAddress),o.onChangeCurrentPickupLocation(),o.onChangePickupLocation(),o.maybeInitSelect2())},onChangeAddress:function(){e.shipments_pickup_locations;var o=i("#shipping_postcode:visible").val()?i("#shipping_postcode:visible").val():i("#billing_postcode").val();i("#pickup-location-postcode").val(o)},onChangePickupLocation:function(){e.shipments_pickup_locations.getPickupLocationSelect().val()?i(".pickup-location-search-actions").find(".submit-pickup-location").show():i(".pickup-location-search-actions").find(".submit-pickup-location").hide()},hasPickupLocationDelivery:function(){return e.shipments_pickup_locations,!!i("#current_pickup_location").val()},disablePickupLocationDelivery:function(){e.shipments_pickup_locations;var o=i('.wc-gzd-modal-content[data-id="pickup-location"].active');i(".wc-gzd-shipments-managed-by-pickup-location").val(""),i("#current_pickup_location").val("").trigger("change"),o.length>0&&o.find(".wc-gzd-modal-close").trigger("click")},onRemovePickupLocation:function(){return e.shipments_pickup_locations.disablePickupLocationDelivery(),!1},getCustomerNumberField:function(){return i("#pickup_location_customer_number_field")},onChangeCurrentPickupLocation:function(){var o=e.shipments_pickup_locations,n=i("#current_pickup_location"),c=n.val(),t=!!c&&o.getPickupLocation(c),a=i(".pickup_location_notice");c&&t?(n.attr("data-current-location",t),o.replaceShippingAddress(t.address_replacements),o.updateCustomerNumberField(t),a.find(".pickup-location-manage-link").text(t.label),a.find(".currently-shipping-to").show(),a.find(".choose-pickup-location").hide(),i("#wc-gzd-shipments-pickup-location-search-form .pickup-location-remove ").show()):(n.attr("data-current-location",""),n.val(""),o.getCustomerNumberField().addClass("hidden"),o.getCustomerNumberField().hide(),i(".wc-gzd-shipments-managed-by-pickup-location").find("input[type=text]").val(""),i(".wc-gzd-shipments-managed-by-pickup-location").find(":input").prop("readonly",!1),i("#wc-gzd-shipments-pickup-location-search-form .pickup-location-remove ").hide(),i(".wc-gzd-shipments-managed-by-pickup-location").removeClass("wc-gzd-shipments-managed-by-pickup-location"),i(".wc-gzd-shipments-managed-by-pickup-location-notice").remove(),a.find(".currently-shipping-to").hide(),a.find(".choose-pickup-location").show())},onSearch:function(){var o=e.shipments_pickup_locations,n=i(this).serialize();return o.getPickupLocationSelect().val(),i("#wc-gzd-shipments-pickup-location-search-form").block({message:null,overlayCSS:{background:"#fff",opacity:.6}}),n+="&action=woocommerce_gzd_shipments_search_pickup_locations&context="+o.params.context,i.ajax({type:"POST",url:o.params.wc_ajax_url.toString().replace("%%endpoint%%","woocommerce_gzd_shipments_search_pickup_locations"),data:n,success:function(e){e.success&&(o.pickupLocations=e.locations,o.updatePickupLocationSelect(),i("#wc-gzd-shipments-pickup-location-search-form").unblock())},error:function(i){},dataType:"json"}),!1},updatePickupLocationSelect:function(){var o=e.shipments_pickup_locations,n=o.getPickupLocationSelect(),c=n.val();n.attr("data-locations",o.pickupLocations),n.find('option:not([value=""])').remove(),i.each(o.pickupLocations,(function(e,o){var c=i("<textarea />").html(o.formatted_address).text();n.append(i("<option></option>").attr("value",e).text(c))}));var t=o.getPickupLocation(c);t&&(n.find('option[value="'+t.code+'"')[0].selected=!0),n.trigger("change")},onSelectDifferentShipping:function(){var o=e.shipments_pickup_locations;i(this).is(":checked")?i("#billing_pickup_location_notice").addClass("hidden").hide():(o.disablePickupLocationDelivery(),o.isAvailable()&&i("#billing_pickup_location_notice").removeClass("hidden").show())},maybeInitSelect2:function(){i().selectWoo&&i("select#pickup_location").each((function(){var e=i(this),o={placeholder:e.attr("data-placeholder")||e.attr("placeholder")||"",label:e.attr("data-label")||null,width:"100%",dropdownCssClass:"wc-gzd-pickup-location-select-dropdown"};i(this).on("select2:select",(function(){i(this).trigger("focus")})).selectWoo(o)}))},onSelectPickupLocation:function(){var o=e.shipments_pickup_locations.getPickupLocationSelect().val();i("#current_pickup_location").val(o).trigger("change"),i(this).parents(".wc-gzd-modal-content").find(".wc-gzd-modal-close").trigger("click");var n=i("#shipping_address_1_field");return i.scroll_to_notices(n),!1},updateCustomerNumberField:function(i){var o=e.shipments_pickup_locations.getCustomerNumberField();i.supports_customer_number?(o.find("label")[0].firstChild.nodeValue=i.customer_number_field_label+" ",i.customer_number_is_mandatory?(o.find("label .required").length||o.find("label").append(' <abbr class="required">*</abbr>'),o.find("label .optional").hide(),o.addClass("validate-required")):(o.find("label .required").remove(),o.find("label .optional").show(),o.removeClass("validate-required woocommerce-invalid woocommerce-invalid-required-field")),o.removeClass("hidden"),o.show()):(o.addClass("hidden"),o.hide())},getPickupLocationSelect:function(){return i("#pickup_location")},getPickupLocation:function(o){var n=e.shipments_pickup_locations;if(n.pickupLocations.hasOwnProperty(o))return n.pickupLocations[o];var c=i("#current_pickup_location");if(c.data("current-location")){var t=c.data("current-location");if(t.code===o)return t}return!1},afterRefreshCheckout:function(i,o){var n=e.shipments_pickup_locations,c=!1;(o=void 0===o?{fragments:{".gzd-shipments-pickup-location-supported":!1,".gzd-shipments-pickup-locations":JSON.stringify(n.pickupLocations)}}:o).hasOwnProperty("fragments")&&(o.fragments.hasOwnProperty(".gzd-shipments-pickup-location-supported")&&(c=o.fragments[".gzd-shipments-pickup-location-supported"]),o.fragments.hasOwnProperty(".gzd-shipments-pickup-locations")&&Object.keys(n.pickupLocations).length<=0&&(n.pickupLocations=JSON.parse(o.fragments[".gzd-shipments-pickup-locations"]),n.updatePickupLocationSelect())),c?n.enable():n.disable()},disable:function(){var o=e.shipments_pickup_locations;if(o.available=!1,o.hasPickupLocationDelivery()){o.disablePickupLocationDelivery();var n=i("form.checkout");n.find(".woocommerce-NoticeGroup-updateOrderReview").length<=0&&n.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"></div>'),n.find(".woocommerce-NoticeGroup-updateOrderReview").prepend('<div class="woocommerce-info">'+o.params.i18n_pickup_location_delivery_unavailable+"</div>");var c=i(".woocommerce-NoticeGroup-updateOrderReview");i.scroll_to_notices(c)}i(".pickup_location_notice").addClass("hidden").hide()},enable:function(){var o=e.shipments_pickup_locations;o.available=!0,i(".pickup_location_notice").removeClass("hidden").show(),i("#ship-to-different-address-checkbox").is(":checked")||o.hasPickupLocationDelivery()?i("#billing_pickup_location_notice").addClass("hidden").hide():i("#billing_pickup_location_notice").removeClass("hidden").show()},isAvailable:function(){return e.shipments_pickup_locations.available},replaceShippingAddress:function(o){var n=e.shipments_pickup_locations,c=i("#ship-to-different-address input"),t=[];Object.keys(o).forEach((e=>{var c=o[e];if(c&&i("#shipping_"+e).length>0){i("#shipping_"+e).val()!==c&&t.push(e),i("#shipping_"+e).val(c),i("#shipping_"+e).prop("readonly",!0),"country"===e&&i("#shipping_"+e).trigger("change");var a=i("#shipping_"+e+"_field");a.length>0?(a.addClass("wc-gzd-shipments-managed-by-pickup-location"),a.find(".wc-gzd-shipments-managed-by-pickup-location-notice").length<=0&&a.find("label").after('<span class="wc-gzd-shipments-managed-by-pickup-location-notice">'+n.params.i18n_managed_by_pickup_location+"</span>")):i("#shipping_"+e).addClass("wc-gzd-shipments-managed-by-pickup-location")}})),c.is(":checked")||(c.prop("checked",!0),c.trigger("change")),t.length>0&&-1!==i.inArray("postcode",t)&&i("#shipping_postcode").trigger("change")}},i(document).ready((function(){e.shipments_pickup_locations.init()})),((window.wcGzdShipments=window.wcGzdShipments||{}).static=window.wcGzdShipments.static||{})["pickup-locations"]={}}();