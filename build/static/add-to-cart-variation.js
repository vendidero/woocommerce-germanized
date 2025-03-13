/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*global wc_gzd_add_to_cart_variation_params */
;
(function ($, window, document, undefined) {
  /**
   * VariationForm class which handles variation forms and attributes.
   */
  var GermanizedVariationForm = function ($form) {
    var self = this;
    self.params = wc_gzd_add_to_cart_variation_params;
    self.$form = $form;
    self.$wrapper = $form.closest(self.params.wrapper);
    self.$product = $form.closest('.product');
    self.variationData = $form.data('product_variations');
    self.$singleVariation = $form.find('.single_variation');
    self.$singleVariationWrap = $form.find('.single_variation_wrap');
    self.$resetVariations = $form.find('.reset_variations');
    self.$button = $form.find('.single_add_to_cart_button');
    self.$form.addClass('has-gzd-variation-form');
    self.$form.off('.wc-gzd-variation-form');
    if (self.$wrapper.length <= 0) {
      self.$wrapper = self.$product;
    }
    self.showOrHideTabs(self);
    self.isBlockLayout = self.$wrapper.find('.wp-block-woocommerce-product-price').length > 0;
    self.replacePrice = self.$wrapper.hasClass('bundled_product') ? false : self.params.replace_price;
    $form.on('click.wc-gzd-variation-form', '.reset_variations', {
      GermanizedvariationForm: self
    }, self.onReset);
    $form.on('reset_data.wc-gzd-variation-form', {
      GermanizedvariationForm: self
    }, self.onReset);
    $form.on('show_variation.wc-gzd-variation-form', {
      GermanizedvariationForm: self
    }, self.onShowVariation);
    self.$wrapper.find('' + '.woocommerce-product-attributes-item--food_description, ' + '.woocommerce-product-attributes-item--alcohol_content, ' + '.woocommerce-product-attributes-item--net_filling_quantity, ' + '.woocommerce-product-attributes-item--drained_weight, ' + '.woocommerce-product-attributes-item--food_place_of_origin, ' + '.woocommerce-product-attributes-item--nutri_score, ' + '.woocommerce-product-attributes-item--food_distributor').each(function () {
      var $tr = $(this);
      if ($tr.find('.woocommerce-product-attributes-item__value').is(':empty') || $tr.find('.woocommerce-product-attributes-item__value .wc-gzd-additional-info-placeholder').is(':empty')) {
        $tr.attr('aria-hidden', 'true').addClass('wc-gzd-additional-info-placeholder');
      }
    });
  };
  GermanizedVariationForm.prototype.showOrHideTabs = function (self, has_product_safety_information = undefined) {
    has_product_safety_information = undefined === has_product_safety_information ? self.$product.hasClass('has-product-safety-information') : has_product_safety_information;
    if (has_product_safety_information) {
      self.$product.find('.product_safety_tab').show().css('display', 'inline-block');
    } else {
      self.$product.find('.product_safety_tab').hide();
    }
  };
  GermanizedVariationForm.prototype.getPriceElement = function (self) {
    var $wrapper = self.$wrapper;

    /**
     * Ignore the price wrapper inside the variation form to make sure the right
     * price is being replaced even if the price element is located beneath the form.
     */
    return $wrapper.find(self.params.price_selector + ':not(.price-unit):visible').not('.variations_form .single_variation .price').first();
  };

  /**
   * Reset all fields.
   */
  GermanizedVariationForm.prototype.onReset = function (event) {
    var form = event.data.GermanizedvariationForm,
      $wrapper = form.$wrapper;
    $wrapper.find('.variation_gzd_modified').each(function () {
      $(this).wc_gzd_reset_content();
    });
    $wrapper.find('.variation_gzd_modified').remove();
    form.showOrHideTabs(form);
    event.data.GermanizedvariationForm.$form.trigger('germanized_reset_data');
  };
  GermanizedVariationForm.prototype.getElementOrBlock = function (self, element, innerElement) {
    var $wrapper = self.$wrapper;
    var blockSearch = '.wp-block-woocommerce-gzd-product-' + element + '[data-is-descendent-of-single-product-template]';
    if ($wrapper.find(blockSearch).length > 0) {
      return $wrapper.find(blockSearch + ' ' + innerElement);
    } else {
      return $wrapper.find(innerElement);
    }
  };
  GermanizedVariationForm.prototype.onUpdate = function (event) {
    setTimeout(function () {
      if (typeof event.data === 'undefined' || !event.data.hasOwnProperty('GermanizedvariationForm')) {
        return;
      } else if (typeof event.data.GermanizedvariationForm === 'undefined') {
        return;
      }

      // If the button is diabled (or has disabled class) no variation can be added to the cart - reset has been triggered
      if (event.data.GermanizedvariationForm.$button.is('[disabled]') || event.data.GermanizedvariationForm.$button.hasClass('disabled')) {
        event.data.GermanizedvariationForm.onReset(event);
      }
    }, 250);
  };
  GermanizedVariationForm.prototype.onShowVariation = function (event, variation, purchasable) {
    var form = event.data.GermanizedvariationForm,
      $wrapper = form.$wrapper,
      hasCustomPrice = variation.hasOwnProperty('price_html') && variation.price_html !== '',
      hasDisplayPrice = variation.hasOwnProperty('display_price') && variation.display_price !== '';
    if (hasCustomPrice && form.replacePrice) {
      var $priceElement = form.getPriceElement(form);
      form.$singleVariation.find('.price').hide();
      $priceElement.wc_gzd_set_content(variation.price_html);
      $priceElement.find('.price').contents().unwrap();
    }
    form.getElementOrBlock(form, 'delivery-time', '.delivery-time-info').wc_gzd_set_content(variation.delivery_time);
    form.getElementOrBlock(form, 'defect-description', '.defect-description').wc_gzd_set_content(variation.defect_description);
    form.getElementOrBlock(form, 'tax-info', '.tax-info').wc_gzd_set_content(hasDisplayPrice ? variation.tax_info : '');
    form.getElementOrBlock(form, 'manufacturer', '.manufacturer').wc_gzd_set_content(variation.manufacturer);
    form.getElementOrBlock(form, 'manufacturer-heading', '.wc-gzd-product-manufacturer-heading').wc_gzd_set_content(variation.manufacturer_heading);
    form.getElementOrBlock(form, 'product_safety_attachments', '.product-safety-attachments').wc_gzd_set_content(variation.product_safety_attachments);
    form.getElementOrBlock(form, 'product-safety-attachments-heading', '.wc-gzd-product-safety-attachments-heading').wc_gzd_set_content(variation.product_safety_attachments_heading);
    form.getElementOrBlock(form, 'safety_instructions', '.safety-instructions').wc_gzd_set_content(variation.safety_instructions);
    form.getElementOrBlock(form, 'safety-instructions-heading', '.wc-gzd-product-safety-instructions-heading').wc_gzd_set_content(variation.safety_instructions_heading);
    form.getElementOrBlock(form, 'power_supply', '.wc-gzd-power-supply').wc_gzd_set_content(variation.power_supply);
    form.getElementOrBlock(form, 'deposit', '.deposit-amount').wc_gzd_set_content(hasDisplayPrice ? variation.deposit_amount : '');
    form.getElementOrBlock(form, 'deposit-packaging-type', '.deposit-packaging-type').wc_gzd_set_content(hasDisplayPrice ? variation.deposit_packaging_type : '');
    form.getElementOrBlock(form, 'food-description', '.wc-gzd-food-description').wc_gzd_set_content(variation.food_description);
    form.getElementOrBlock(form, 'nutri-score', '.wc-gzd-nutri-score').wc_gzd_set_content(variation.nutri_score);
    form.getElementOrBlock(form, 'food-distributor', '.wc-gzd-food-distributor').wc_gzd_set_content(variation.food_distributor);
    form.getElementOrBlock(form, 'food-place-of-origin', '.wc-gzd-food-place-of-origin').wc_gzd_set_content(variation.food_place_of_origin);
    form.getElementOrBlock(form, 'net-filling-quantity', '.wc-gzd-net-filling-quantity').wc_gzd_set_content(variation.net_filling_quantity);
    form.getElementOrBlock(form, 'drained-weight', '.wc-gzd-drained-weight').wc_gzd_set_content(variation.drained_weight);
    form.getElementOrBlock(form, 'alcohol-content', '.wc-gzd-alcohol-content').wc_gzd_set_content('no' === variation.includes_alcohol ? '' : variation.alcohol_content);
    form.getElementOrBlock(form, 'nutrients', '.wc-gzd-nutrients').wc_gzd_set_content(variation.nutrients);
    form.getElementOrBlock(form, 'nutrients-heading', '.wc-gzd-nutrients-heading').wc_gzd_set_content(variation.nutrients_heading);
    form.getElementOrBlock(form, 'ingredients', '.wc-gzd-ingredients').wc_gzd_set_content(variation.ingredients);
    form.getElementOrBlock(form, 'ingredients-heading', '.wc-gzd-ingredients-heading').wc_gzd_set_content(variation.ingredients_heading);
    form.getElementOrBlock(form, 'allergenic', '.wc-gzd-allergenic').wc_gzd_set_content(variation.allergenic);
    form.getElementOrBlock(form, 'allergenic-heading', '.wc-gzd-allergenic-heading').wc_gzd_set_content(variation.allergenic_heading);
    form.getElementOrBlock(form, 'shipping-costs-info', '.shipping-costs-info').wc_gzd_set_content(hasDisplayPrice ? variation.shipping_costs_info : '');
    form.getElementOrBlock(form, 'unit-price', '.price-unit').wc_gzd_set_content(hasDisplayPrice ? variation.unit_price : '');
    form.getElementOrBlock(form, 'unit-product', '.product-units').wc_gzd_set_content(hasDisplayPrice ? variation.product_units : '');
    form.showOrHideTabs(form, variation.has_product_safety_information);
    form.$form.trigger('germanized_variation_data', variation, $wrapper);
  };

  /**
   * Function to call wc_gzd_variation_form on jquery selector.
   */
  $.fn.wc_germanized_variation_form = function () {
    new GermanizedVariationForm(this);
    return this;
  };

  /**
   * Stores the default text for an element so it can be reset later
   */
  $.fn.wc_gzd_set_content = function (content) {
    /**
     * Explicitly exclude loop wrappers to prevent information
     * to be replaced within the main product wrapper (e.g. cross-sells).
     */
    var $this = this.not('.wc-gzd-additional-info-loop');
    content = undefined === content ? '' : content;
    if (undefined === $this.attr('data-o_content')) {
      $this.attr('data-o_content', $this.html());
    }
    $this.html(content);
    $this.addClass('variation_modified variation_gzd_modified').attr('aria-hidden', 'false').removeClass('wc-gzd-additional-info-placeholder').show();
    if ($this.is(':empty')) {
      $this.attr('aria-hidden', 'true').hide();
      if ($this.parents('.wp-block-woocommerce-gzd-product-price-label').length > 0) {
        $this.parents('.wp-block-woocommerce-gzd-product-price-label').attr('aria-hidden', 'true').addClass('wp-block-woocommerce-gzd-product-is-empty');
      }
      if ($this.parents('.woocommerce-product-attributes-item').length > 0) {
        $this.parents('.woocommerce-product-attributes-item').attr('aria-hidden', 'true').hide();
      }
    } else {
      if ($this.parents('.wp-block-woocommerce-gzd-product-price-label').length > 0) {
        $this.parents('.wp-block-woocommerce-gzd-product-price-label').attr('aria-hidden', 'false').removeClass('wp-block-woocommerce-gzd-product-is-empty');
      }
      if ($this.parents('.woocommerce-product-attributes-item').length > 0) {
        $this.parents('.woocommerce-product-attributes-item').attr('aria-hidden', 'false').show();
      }
    }
  };

  /**
   * Stores the default text for an element so it can be reset later
   */
  $.fn.wc_gzd_reset_content = function () {
    var $this = this.not('.wc-gzd-additional-info-loop');
    if (undefined !== $this.attr('data-o_content')) {
      $this.html($this.attr('data-o_content'));
      $this.removeClass('variation_modified variation_gzd_modified').show();
    }
    if ($this.is(':empty')) {
      $this.addClass('wc-gzd-additional-info-placeholder').attr('aria-hidden', 'true').hide();
      if ($this.parents('.wp-block-woocommerce-gzd-product-price-label').length > 0) {
        $this.parents('.wp-block-woocommerce-gzd-product-price-label').addClass('wp-block-woocommerce-gzd-product-is-empty').attr('aria-hidden', 'true');
      }
      if ($this.parents('.woocommerce-product-attributes-item').length > 0) {
        $this.parents('.woocommerce-product-attributes-item').hide();
      }
    } else {
      if ($this.parents('.wp-block-woocommerce-gzd-product-price-label').length > 0) {
        $this.parents('.wp-block-woocommerce-gzd-product-price-label').removeClass('wp-block-woocommerce-gzd-product-is-empty').attr('aria-hidden', 'false');
      }
      if ($this.parents('.woocommerce-product-attributes-item').length > 0) {
        $this.parents('.woocommerce-product-attributes-item').show();
      }
    }
  };
  $(function () {
    if (typeof wc_gzd_add_to_cart_variation_params !== 'undefined') {
      $('.variations_form').each(function () {
        $(this).wc_germanized_variation_form();
      });

      /**
       * Improve compatibility with custom implementations which might
       * manually construct wc_variation_form() (e.g. quick view).
       */
      $(document.body).on('wc_variation_form', function (e, variationForm) {
        var $form;
        if (typeof variationForm === 'undefined') {
          $form = $(e.target);
        } else {
          $form = $(variationForm.$form);
        }
        if ($form.length > 0) {
          if (!$form.hasClass('has-gzd-variation-form')) {
            $form.wc_germanized_variation_form();
            // Make sure to reload variation to apply our logic
            $form.trigger('check_variations');
          }
        }
      });
    }
  });
})(jQuery, window, document);
((window.germanized = window.germanized || {})["static"] = window.germanized["static"] || {})["add-to-cart-variation"] = __webpack_exports__;
/******/ })()
;