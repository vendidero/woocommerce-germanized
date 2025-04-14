/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};
(function ($, germanized) {
  germanized.unit_price_observer_queue = {
    queue: {},
    timeout: null,
    params: {},
    request: null,
    init: function () {
      this.params = wc_gzd_unit_price_observer_queue_params;
      this.queue = {};
      this.timeout = null;
      this.request = null;
    },
    execute: function () {
      var self = germanized.unit_price_observer_queue,
        data = [],
        currentQueue = {
          ...self.queue
        };
      self.queue = {};
      self.timeout = null;

      /**
       * Reverse queue
       */
      Object.keys(currentQueue).forEach(function (queueKey) {
        data = data.concat([{
          'product_id': currentQueue[queueKey].productId,
          'price': currentQueue[queueKey].priceData.price,
          'price_sale': currentQueue[queueKey].priceData.sale_price,
          'quantity': currentQueue[queueKey].priceData.quantity,
          'key': queueKey
        }]);
      });
      self.request = $.ajax({
        type: "POST",
        url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'gzd_refresh_unit_price'),
        data: {
          'security': self.params.refresh_unit_price_nonce,
          'products': data
        },
        success: function (data) {
          Object.keys(currentQueue).forEach(function (queueId) {
            var current = currentQueue[queueId],
              observer = current.observer,
              priceData = current.priceData,
              priceSelector = current.priceSelector,
              isPrimary = current.isPrimary,
              unitPrices = self.getUnitPricesFromMap(priceData.unit_price);
            if (observer) {
              if (data.products.hasOwnProperty(queueId)) {
                var response = data.products[queueId];
                observer.stopObserver(observer, priceSelector);

                /**
                 * Do only adjust unit price in case current product id has not changed
                 * in the meantime (e.g. variation change).
                 */
                if (parseInt(response.product_id) === observer.getCurrentProductId(observer)) {
                  if (response.hasOwnProperty('unit_price_html')) {
                    observer.unsetUnitPriceLoading(observer, unitPrices, response.unit_price_html);
                  } else {
                    observer.unsetUnitPriceLoading(observer, unitPrices);
                  }
                } else {
                  observer.unsetUnitPriceLoading(observer, unitPrices);
                }
                observer.startObserver(observer, priceSelector, isPrimary);
              } else {
                observer.stopObserver(observer, priceSelector);
                observer.unsetUnitPriceLoading(observer, unitPrices);
                observer.startObserver(observer, priceSelector, isPrimary);
              }
            }
          });
        },
        error: function () {
          Object.keys(currentQueue).forEach(function (queueId) {
            var current = currentQueue[queueId],
              observer = current.observer,
              priceData = current.priceData,
              priceSelector = current.priceSelector,
              isPrimary = current.isPrimary,
              unitPrices = self.getUnitPricesFromMap(priceData.unit_price);
            if (observer) {
              observer.stopObserver(observer, priceSelector);
              observer.unsetUnitPriceLoading(observer, unitPrices);
              observer.startObserver(observer, priceSelector, isPrimary);
            }
          });
        },
        dataType: 'json'
      });
    },
    getUnitPricesFromMap: function (unitPriceMap) {
      let unitPrices = [];
      unitPriceMap.forEach(function (unitPrice) {
        unitPrices = $.merge(unitPrices, $(unitPrice));
      });
      return $(unitPrices);
    },
    getQueueKey: function (productId) {
      return (productId + '').replace(/[^a-zA-Z0-9]/g, '');
    },
    add: function (observer, productId, priceData, priceSelector, isPrimary) {
      var self = germanized.unit_price_observer_queue,
        queueKey = self.getQueueKey(productId);
      if (self.queue.hasOwnProperty(queueKey)) {
        priceData['unit_price'].each(function (i, obj) {
          if (!self.queue[queueKey]['priceData']['unit_price'].has(obj)) {
            self.queue[queueKey]['priceData']['unit_price'].set(obj, obj);
          }
        });
      } else {
        var unitPrices = new Map();
        priceData['unit_price'].each(function (i, obj) {
          unitPrices.set(obj, obj);
        });
        priceData['unit_price'] = unitPrices;
        self.queue[queueKey] = {
          'productId': productId,
          'observer': observer,
          'priceData': priceData,
          'priceSelector': priceSelector,
          'isPrimary': isPrimary
        };
      }
      clearTimeout(self.timeout);
      self.timeout = setTimeout(self.execute, 500);
    }
  };
  $(document).ready(function () {
    germanized.unit_price_observer_queue.init();
  });
})(jQuery, window.germanized);
((window.germanized = window.germanized || {})["static"] = window.germanized["static"] || {})["unit-price-observer-queue"] = __webpack_exports__;
/******/ })()
;