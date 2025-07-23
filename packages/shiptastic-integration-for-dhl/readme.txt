=== Shiptastic Integration for DHL ===
Contributors: vendidero, vdwoocommercesupport
Tags: shipping, UPS, shiptastic, woocommerce
Requires at least: 5.4
Tested up to: 6.8
Stable tag: 4.1.0
Requires PHP: 7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Connect Shiptastic to the DHL API and create DHL labels to shipments and returns.

== Description ==

This plugin registers DHL as a shipping service provider for [Shiptastic](http://wordpress.org/plugins/shiptastic-for-woocommerce). Navigate to
WooCommerce > Settings > Shiptastic > Shipping Service Providers > DHL and connect your store to the DHL API. After that, you may conveniently create
DHL labels to your shipments right from within your admin panel and allow your customers to choose pickup locations from within your checkout.

Please note: This plugin does currently only work for shop owners in Germany (DHL Paket). We are working on a version which supports DHL Express too.

== Installation ==

= Minimal Requirements =

* WordPress 4.9 or newer
* WooCommerce 3.9 (newest version recommended)
* Shiptastic for WooCommerce
* PHP Version 7.0 or newer

== External services ==

This plugin connects to an API to create labels and/or provide your customer with pickup locations to choose from.
This service is provided by DHL: [terms of use](https://developer.dhl.com/terms-use?language_content_entity=en), [privacy policy](https://developer.dhl.com/privacy-notice?language_content_entity=en).

== Changelog ==
= 4.1.0 =
* New: Support remote shipment tracking

= 4.0.1 =
* New: Introduce GoGreen Plus
* New: Allow bulky goods service for DHL Europaket

= 4.0.0 =
* Initial version release