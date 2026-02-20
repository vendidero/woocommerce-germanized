/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
jQuery(function ($) {
  var wc_gzd_product = {
    warranty_upload_file_frame: false,
    upload_file_frame: false,
    params: {},
    init: function () {
      var self = wc_gzd_product;
      self.params = wc_gzd_admin_product_params;
      $(document).on('click', 'a.wc-gzd-add-new-country-specific-delivery-time', self.onAddNewDeliveryTime).on('click', 'a.wc-gzd-remove-country-specific-delivery-time', self.onRemoveDeliveryTime).on('click', '.wc-gzd-product-upload', self.onUpload).on('click', '.wc-gzd-product-upload-remove', self.onRemoveUpload).on('woocommerce-product-type-change wc-gzd-product-type-change', self.onProductTypeChange).on('wc-gzd-refresh-unit-placeholder', self.onRefreshProductUnitPlaceholder).on('change', ':input#_unit', self.onChangeUnit).on('wc-gzd-show-hide-panels', self.showHidePanels);
      try {
        $(document.body).on('wc-enhanced-select-init wc-gzd-enhanced-select-init', this.onEnhancedSelectInit).trigger('wc-gzd-enhanced-select-init');
      } catch (err) {
        // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
        window.console.log(err);
      }
      $('input#_is_food, input#_defective_copy, input#_wireless_electronic_device').on('change', function () {
        self.showHidePanels();
      });
      $('input#_defective_copy').trigger('change');
      $('#the-list').on('click', '.editinline', self.onQuickEdit);
    },
    onRefreshProductUnitPlaceholder: function () {
      var $selected = $(':input#_unit').find(":selected");
      if ($selected.length > 0) {
        $('.wc-gzd-unit-placeholder').text($selected.text());
      } else {
        $('.wc-gzd-unit-placeholder').text('');
      }
    },
    onChangeUnit: function () {
      $(document.body).trigger('wc-gzd-refresh-unit-placeholder');
    },
    onEnhancedSelectInit: function () {
      var self = wc_gzd_product;

      // Tag select
      $(':input.wc-gzd-enhanced-nutri-score').filter(':not(.enhanced)').each(function () {
        var select2_args = {
          minimumResultsForSearch: 10,
          allowClear: $(this).data('allow_clear') ? true : false,
          placeholder: $(this).data('placeholder'),
          // There seems to be a bug in WooSelect: https://github.com/woocommerce/selectWoo/issues/39
          // templateSelection: self.formatNutriScore,
          templateResult: self.formatNutriScore
        };
        $(this).selectWoo(select2_args).addClass('enhanced');
      });
    },
    formatNutriScore: function (nutriScore) {
      if (!nutriScore.id) {
        return nutriScore.text;
      }
      var $nutri = $('<span><i class="nutri-score-select-value nutri-score-select-value-' + nutriScore.element.value + '">&#9679;</i> ' + nutriScore.text + '</span>');
      return $nutri;
    },
    onProductTypeChange: function () {
      wc_gzd_product.showHidePanels();
    },
    showHidePanels: function () {
      var is_food = $('input#_is_food:checked').length,
        is_defective_copy = $('input#_defective_copy:checked').length,
        is_wireless_electronic_device = $('input#_wireless_electronic_device:checked').length;
      var hide_classes = '.hide_if_is_food, .hide_if_defective_copy, .hide_if_wireless_electronic_device';
      var show_classes = '.show_if_is_food, .show_if_defective_copy, .show_if_wireless_electronic_device';
      $(hide_classes).show();
      $(show_classes).hide();
      if (is_food) {
        $('.show_if_is_food').show();
      } else {
        if ($('.food_options.food_tab').hasClass('active')) {
          $('.general_options.general_tab > a').trigger('click');
        }
      }
      if (is_defective_copy) {
        $('.show_if_defective_copy').show();
        $('#wc-gzd-product-defect-description').show();
      } else {
        $('#wc-gzd-product-defect-description').hide();
      }
      if (is_wireless_electronic_device) {
        $('.show_if_wireless_electronic_device').show();
      }
    },
    onQuickEdit: function () {
      var post_id = $(this).closest('tr').attr('id');
      post_id = post_id.replace('post-', '');
      var $inline_data = $('#inline_' + post_id);
      if ($inline_data.find('.gzd_delivery_time_slug').length > 0) {
        var delivery_time = $inline_data.find('.gzd_delivery_time_slug').text(),
          delivery_time_name = $inline_data.find('.gzd_delivery_time_name').text(),
          manufacturer = $inline_data.find('.gzd_manufacturer_slug').text(),
          manufacturer_name = $inline_data.find('.gzd_manufacturer_name').text(),
          unit = $inline_data.find('.gzd_unit_slug').text();
        $('select[name="_unit"] option:selected', '.inline-edit-row').attr('selected', false).trigger('change');
        $('select[name="_unit"] option[value="' + unit + '"]').attr('selected', 'selected').trigger('change');
        $('select[name="_delivery_time"] option').remove().trigger('change');
        $('select[name="_manufacturer"] option').remove().trigger('change');
        if (delivery_time) {
          $('select[name="_delivery_time"]').append('<option value="' + delivery_time + '" selected="selected">' + delivery_time_name + '</option>');
        }
        if (manufacturer) {
          $('select[name="_manufacturer"]').append('<option value="' + manufacturer + '" selected="selected">' + manufacturer_name + '</option>');
        }

        /**
         * Ugly hack to make sure select2 initialization happens after WP cloned the data to the new div
         */
        setTimeout(function () {
          var $select2 = $('tr#edit-' + post_id + ' .wc-gzd-term-search-quick-edit.enhanced');

          /**
           * Destroy the select2 element from template in case it still exists and has been initialized
           */
          if ($select2.length > 0) {
            $select2.selectWoo('destroy');
            $select2.removeClass('enhanced');
          }
          $('tr#edit-' + post_id + ' .wc-gzd-delivery-time-select-placeholder').addClass('wc-product-search', 'wc-gzd-delivery-time-search').removeClass('wc-gzd-delivery-time-select-placeholder');
          $('tr#edit-' + post_id + ' .wc-gzd-manufacturer-select-placeholder').addClass('wc-product-search', 'wc-gzd-manufacturer-search').removeClass('wc-gzd-manufacturer-select-placeholder');
          $(document.body).trigger('wc-enhanced-select-init');
        }, 100);
      }
    },
    onUpload: function (e) {
      var self = wc_gzd_product,
        $el = $(this),
        $wrapper = $el.parents('.wc-gzd-product-upload-wrapper'),
        multiple = $el.data('multiple'),
        $attachmentHolder = $wrapper.find('.wc-gzd-product-upload-attachments'),
        attachmentInputName = $el.data('input_name'),
        attachments = [];
      $wrapper.find('input[name="' + attachmentInputName + '"]').each(function () {
        attachments.push(wp.media.attachment(parseInt($(this).val())));
      });
      e.preventDefault();

      // Create the media frame.
      self.upload_file_frame = wp.media.frames.customHeader = wp.media({
        // Set the title of the modal.
        title: $el.data('choose'),
        library: {
          type: $el.data('types').split(',')
        },
        button: {
          text: $el.data('update')
        },
        multiple: $el.data('multiple')
      });

      // When an image is selected, run a callback.
      self.upload_file_frame.on('select', function () {
        var selection = self.upload_file_frame.state().get('selection');
        selection.map(function (attachment) {
          attachment = attachment.toJSON();
          if (!multiple) {
            $attachmentHolder.find('.wc-gzd-product-single-attachment').remove();
            $el.text(attachment.filename);
          } else {
            $el.text($el.data('update'));
          }
          if ($.inArray(parseInt(attachment.id), attachments) === -1) {
            attachments.push(attachment.id);
            if (attachment.filename) {
              $attachmentHolder.append('<span class="wc-gzd-product-single-attachment" data-attachment_id="' + attachment.id + '">' + (multiple ? attachment.filename : '') + ' <a href="#" class="wc-gzd-product-upload-remove dashicons dashicons-no-alt">' + self.params.i18n_remove_attachment + '</a><input class="wc-gzd-product-attachments" type="hidden" name="' + attachmentInputName + '" value="' + attachment.id + '" /></span>');
            }
          }
        });
      });
      self.upload_file_frame.on('open', function () {
        var selection = self.upload_file_frame.state().get('selection');
        if (!multiple && attachments.length > 0) {
          selection.add(attachments);
          self.upload_file_frame.content.mode('browse');
        } else {
          selection.remove();
          self.upload_file_frame.content.mode('upload');
        }
      });

      // Finally, open the modal.
      self.upload_file_frame.open();
    },
    onRemoveUpload: function () {
      var $field = $(this).closest('.wc-gzd-product-single-attachment'),
        $wrapper = $(this).parents('.wc-gzd-product-upload-wrapper'),
        $button = $wrapper.find('.wc-gzd-product-upload'),
        multiple = $button.data('multiple');
      $field.remove();
      var hasAttachments = $wrapper.find('.wc-gzd-product-single-attachment').length > 0;
      console.log($button);
      console.log(hasAttachments);
      if (!hasAttachments) {
        $button.text($button.data('default_label'));
      }
      return false;
    },
    onAddNewDeliveryTime: function () {
      var $parent = $(this).parents('#shipping_product_data');
      if ($parent.length === 0) {
        $parent = $(this).parents('.woocommerce_variable_attributes');
      }
      var $select2 = $parent.find('.wc-gzd-add-country-specific-delivery-time-template .wc-gzd-delivery-time-search.enhanced');

      /**
       * Destroy the select2 element from template in case it still exists and has been initialized
       */
      if ($select2.length > 0) {
        $select2.selectWoo('destroy');
        $select2.removeClass('enhanced');
      }
      var $template = $parent.find('.wc-gzd-add-country-specific-delivery-time-template:first').clone();
      $template.removeClass('wc-gzd-add-country-specific-delivery-time-template').addClass('wc-gzd-country-specific-delivery-time-new');
      $parent.find('.wc-gzd-new-country-specific-delivery-time-placeholder').append($template).show();
      $(document.body).trigger('wc-enhanced-select-init');
      return false;
    },
    onRemoveDeliveryTime: function () {
      var $parent = $(this).parents('.form-row, .form-field');

      // Trigger change to notify Woo about an update (variations).
      $parent.find('select').trigger('change');
      $parent.remove();
      return false;
    }
  };
  wc_gzd_product.init();
});
((window.germanized = window.germanized || {})["static"] = window.germanized["static"] || {})["admin-product"] = __webpack_exports__;
/******/ })()
;