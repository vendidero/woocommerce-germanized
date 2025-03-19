/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};
(function ($, germanized) {
  /**
   * Order Data Panel
   */
  germanized.admin = {
    params: {},
    init: function () {
      var self = this;
      this.params = wc_gzd_admin_params;
      $(document).on('click', 'a.woocommerce-gzd-input-toggle-trigger', this.onInputToogleClick);
    },
    onInputToogleClick: function () {
      var $toggle = $(this).find('span.woocommerce-gzd-input-toggle'),
        $row = $toggle.parents('fieldset'),
        $checkbox = $row.find('input[type=checkbox]').length > 0 ? $row.find('input[type=checkbox]') : $toggle.parent().nextAll('input[type=checkbox]:first'),
        $enabled = $toggle.hasClass('woocommerce-input-toggle--enabled');
      $toggle.removeClass('woocommerce-input-toggle--enabled');
      $toggle.removeClass('woocommerce-input-toggle--disabled');
      if ($enabled) {
        $checkbox.prop('checked', false);
        $toggle.addClass('woocommerce-input-toggle--disabled');
      } else {
        $checkbox.prop('checked', true);
        $toggle.addClass('woocommerce-input-toggle--enabled');
      }
      $checkbox.trigger('change');
      return false;
    }
  };
  $(document).ready(function () {
    germanized.admin.init();
  });
})(jQuery, window.germanized);
((window.germanized = window.germanized || {})["static"] = window.germanized["static"] || {}).admin = __webpack_exports__;
/******/ })()
;