(()=>{var i,a;window.shiptastic=window.shiptastic||{},window.shiptastic.admin=window.shiptastic.admin||{},i=jQuery,(a=window.shiptastic).admin.packaging={params:{},init:function(){var n=a.admin.packaging;i(document).on("change","input.wc-stc-override-toggle",n.onChangeOverride)},onChangeOverride:function(){var a=i(this),n=a.parents(".wc-stc-shipping-provider-override-title-wrapper").next(".wc-stc-packaging-zone-wrapper");n.removeClass("zone-wrapper-has-override"),a.is(":checked")&&n.addClass("zone-wrapper-has-override")}},i(document).ready((function(){a.admin.packaging.init()})),((window.wcShiptastic=window.wcShiptastic||{}).static=window.wcShiptastic.static||{})["admin-packaging"]={}})();