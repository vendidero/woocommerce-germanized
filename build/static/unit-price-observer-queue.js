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
      this.queuesInExecution = {};
      this.latestQueueInExection = '';
      this.aborted = {};
      this.timeout = null;
      this.request = null;
    },
    execute: function () {
      var self = germanized.unit_price_observer_queue,
        data = [],
        currentQueueId = Date.now() + '';
      self.queuesInExecution[currentQueueId] = {
        ...self.queue
      };
      self.latestQueueInExection = currentQueueId;
      self.queue = {};

      /**
       * Reverse queue
       */
      Object.keys(self.queuesInExecution[currentQueueId]).forEach(function (queueKey) {
        data = data.concat([{
          'product_id': self.queuesInExecution[currentQueueId][queueKey].productId,
          'price': self.queuesInExecution[currentQueueId][queueKey].priceData.price,
          'price_sale': self.queuesInExecution[currentQueueId][queueKey].priceData.sale_price,
          'quantity': self.queuesInExecution[currentQueueId][queueKey].priceData.quantity,
          'key': queueKey
        }]);
      });
      self.request = $.ajax({
        type: "POST",
        url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'gzd_refresh_unit_price'),
        data: {
          'security': self.params.refresh_unit_price_nonce,
          'products': data,
          'queue_id': currentQueueId
        },
        queueId: currentQueueId,
        success: function (data) {
          var xhrQueueId = this.queueId,
            currentQueue = self.queuesInExecution.hasOwnProperty(xhrQueueId) ? self.queuesInExecution[xhrQueueId] : {},
            aborted = self.aborted.hasOwnProperty(xhrQueueId) ? self.aborted[xhrQueueId] : {};
          Object.keys(currentQueue).forEach(function (queueId) {
            if (!aborted.hasOwnProperty(queueId)) {
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
            } else {
              delete self.aborted[xhrQueueId][queueId];
            }
          });
          delete self.queuesInExecution[xhrQueueId];
        },
        error: function () {
          var xhrQueueId = this.queueId,
            currentQueue = self.queuesInExecution.hasOwnProperty(xhrQueueId) ? self.queuesInExecution[xhrQueueId] : {},
            aborted = self.aborted.hasOwnProperty(xhrQueueId) ? self.aborted[xhrQueueId] : {};
          Object.keys(currentQueue).forEach(function (queueId) {
            if (!aborted.hasOwnProperty(queueId)) {
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
            } else {
              delete self.aborted[xhrQueueId][queueId];
            }
          });
          delete self.queuesInExecution[xhrQueueId];
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
    getLatestQueueInExecution() {
      var self = germanized.unit_price_observer_queue;
      return self.queuesInExecution.hasOwnProperty(self.latestQueueInExection) ? self.queuesInExecution[self.latestQueueInExection] : {};
    },
    exists: function (productId) {
      var self = germanized.unit_price_observer_queue,
        queueKey = self.getQueueKey(productId);
      return self.queue.hasOwnProperty(queueKey) || self.getLatestQueueInExecution().hasOwnProperty(queueKey);
    },
    get: function (productId) {
      var self = germanized.unit_price_observer_queue,
        queueKey = self.getQueueKey(productId),
        queueInExecution = self.getLatestQueueInExecution().hasOwnProperty(queueKey);
      if (queueInExecution.hasOwnProperty(queueKey)) {
        return queueInExecution[queueKey];
      } else if (self.queue.hasOwnProperty(queueKey)) {
        return self.queue[queueKey];
      }
      return false;
    },
    abort: function (productId) {
      var self = germanized.unit_price_observer_queue,
        queueKey = self.getQueueKey(productId),
        latestQueueInExecutionKey = self.latestQueueInExection,
        latestQueueInExecution = self.queuesInExecution.hasOwnProperty(latestQueueInExecutionKey) ? self.queuesInExecution[latestQueueInExecutionKey] : {};
      if (latestQueueInExecution.hasOwnProperty(queueKey)) {
        var current = latestQueueInExecution[queueKey],
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
        if (!self.aborted.hasOwnProperty(latestQueueInExecutionKey)) {
          self.aborted[latestQueueInExecutionKey] = {};
        }
        self.aborted[latestQueueInExecutionKey][queueKey] = current;
        return true;
      }
      return false;
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