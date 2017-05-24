=== WooCommerce Germanized ===
Contributors: vendidero
Tags: woocommerce, german market, german, germany, deutsch, deutschland, de, de_DE, shop, commerce, e-commerce, ecommerce, woothemes, sepa, invoice
Requires at least: 3.8
Tested up to: 4.7
Stable tag: 1.8.11
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Extends WooCommerce to become a legally compliant Shop for German Market. Must Have for every German Shop Owner. Certified by Trusted Shops.

== Description ==

*This description is available in [German](https://de.wordpress.org/plugins/woocommerce-germanized/ "WooCommerce Germanized")*

WooCommerce Germanized extends WooCommerce to technically match german legal conditions. The objective of this plugin is to perfectly adapt WooCommerce to meet the special requirements of german market. WC Germanized is being continually developed by an experienced german team - on updates of WooCommerce we will respond quickly by offering compatibility. 

Users of [WooCommerce Germanized Pro](https://vendidero.de/woocommerce-germanized "WooCommerce optimized for German Market") benefit from additional features such as PDF Invoices, Model Text Generators for terms and revocation, Premium Support and more!

To fit german requirements even better you may have a look at our [WooCommerce Theme](https://vendidero.de/vendipro "WooCommerce Theme for German Market") specifically developed for the german market: VendiPro.
While developing VendiPro we have specifically considered german design principles. WooCommerce Germanized + VendiPro are a perfect combination for your german WooCommerce Shop.

= Legal Certainty for German Market =
WooCommerce Germanized supports typical german shop functionality: Delivery Times, Base Prices, Shipping Costs and Tax Notices, legally relevant Pages (e.g. Terms, Revocation Page etc.) - even in Emails, Small Business Regulation, Fees for Payment Methods and much more.
Furthermore we have customized the checkout to make your WooCommerce Shop meet the german law requirments.

* Delivery Times
* Small Business Regulation
* Notices regarding Shipping Costs and Taxes
* Base Prices
* Short Cart Product Descriptions 
* Notices regarding Terms and Revocation
* Compatibility with Button Solution
* Double Opt In for Customers
* Tax Calculation for Shipping Costs and Fees
* Terms, Revocation etc. in certain Email Templates
* Payment Gateway: SEPA direct debit
* Payment Gateway: Pay by Invoice
* Payment Gateway Fees
* Online Revocation Form
* Sale Price Labels
* Delivery to DHL Parcel Shops or Pick-Up Stations

= Certified by Trusted Shops =
WooCommerce Germanized has been approved by Trusted Shops and therefor offers best technical conditions to operate a legally certain Online Shop in Germany. 
Trusted Shops certifies Shops after selected and weighted criteria and has carefully examined this WooCommerce Plugin. 
Of course Trusted Shops customers may embed their quality seals or further Trusted Shops Products as genuine Seller and Product Reviews by adapting just a few options within WooCommerce Germanized.

= Properly Implemented =
While developing WC Germanized we have specifically considered clean integration within WooCommerce and WordPress by adapting core functionality.
Most of the changes are made by using Hooks & Filters so that Germanized is compatible to almost every Theme.

= SEPA Direct Debit & Pay By Invoice for WooCommerce = 
With WooCommerce Germanized 1.4 you may offer Pay by Direct Debit and Pay By Invoice to your customers.
Using SEPA Direct Debit checkout fields for IBAN, BIC and Account Holder will be automatically added and verified during checkout.
Furthermore the customer may preview his SEPA Mandate before submitting the order. Starting with 1.6 shop managers may easily export
SEPA Mandates in XML format to import mandates to their house bank our banking software. 
Pay By Invoice may be optionally restricted to regular customers only.

= Pro: WooCommerce PDF Invoices & Packing Slips = 
As a Pro User of WC Germanized you may automatically or manually create PDF Invoices and Packing Slips for your orders. Doing so you may customize the PDF layout to meet your Corporate Design.
You may upload your head of a letter which will be used as background for your PDF's. With only a few clicks you may export (CSV, zip) invoices for your tax consultant or accountant.
Learn more about [PDF Invoices for WooCommerce](https://vendidero.de/woocommerce-germanized#accounting "WooCommerce PDF Invoices")

New: Attach legally relevant Pages (e.g. Terms & Conditions) as PDF documents to specific WooCommerce Emails.

= Tax Calculation for Shipping Costs and Fees =
WooCommerce Germanized supports complicated calculation of shipping/fee taxes for "mixed" shopping carts (that is: cart contains multiple tax rates e.g. 7% and 19%).
In that case tax has to be calculated proportional (based on tax rate share in comparison to total amount).

= Pro: Theme Support =
Professional Users benefit from specific Support of popular Themes. For those Themes we make sure that WooCommerce Germanized Options are visible and visually appealing.
At the moment professional version supports the following Themes: 

* Enfold
* Flatsome
* Storefront
* Virtue

= Pro: Premium Support =
Customers of WC Germanized Pro enjoy our qualified Germanized Support via Tickets. Of course we also seek to offer a good Plugin Support via WordPress Support Forums.

= Double Opt In for WooCommerce =
A new judgement of a German Court makes Shop Managers nervous about whether Double Opt In is required for Online Shops. Therefor WC Germanized offers Double Opt In Support for WooCommerce.
After creating an account the customer receives an activation link via Email. Inactive customers may be automatically deleted after a certain amount of time.
Starting with version 1.6 one may deactivate the checkout for inactive customers. To make this possible, registration has to be separated from the checkout - of course customers may still checkout as guests (if enabled).

= Pro: Conclusion of Contract = 
By default WooCommerce does not offer a distinction between receiving and confirming the order. WooCommerce Germanized Pro offers a feature to manually examine orders before confirming them to the customer.
Shop managers may check order details and then manually confirm the order through a Button in the WooCommerce Backend.

= Delivery to DHL Parcel Shops =
You may optionally choose to offer delivery to DHL parcel shops or pick-up stations. Customers may find a parcel shop nearby by embedding an overlay of the DHL search API.
On choosing a parcel shop within the overlay the corresponding data is automatically added to the checkout fields.

= eKomi Integration =
Shop managers who are using eKomi as a Review Management Service may easily integrate it's features within WooCommerce. 
Easily set up eKomi by adapting a few Settings within WooCommerce Germanized.

== Installation ==

= Minimal Requirments =

* WordPress 3.8 or newer
* WooCommerce 2.4 (newest version recommended)
* PHP Version 5.3 or newer

= Automatic Installation =

We recommend installing WooCommerce Germanized through the WordPress Backend. Please install WooCommerce before installing WC Germanized.
After the installation you may easily adapt the settings to your own needs. 

= Shortcodes =

`[revocation_form]`
Inserts online revocation form. Customer and shop manager receive a confirmation by Email.

`[payment_methods_info]`
Very useful for Payment Gateway Information Page - Inserts an overview of all enabled payment gateways plus their descriptions.

`[gzd_complaints]`
Inserts a text that informs the customer about the EU Online Dispute Platform - this Shortcode should be inserted within your imprint. Find more information [here](http://shop.trustedshops.com/de/rechtstipps/jetzt-handeln-link-auf-eu-online-schlichtungs-plattform-ab-9.1.2016).

`[trusted_shops_badge]`
If you are a Trusted Shops Customer, you may insert this Shortcode to insert the Trusted Shops Badge at your favourite place.
With the parameter width=55 (55 means 55px width/height) you may optionally set badge's width.

`[trusted_shops_rich_snippets]`
If you are using Trusted Shop's review functionality, you may insert a Google Rich Snippet about your current shop rating.

`[trusted_shops_reviews]`
Inserts your current Trusted Shops rating as an image (provided by Trusted Shops).

`[ekomi_badge]`
Inserts your eKomi badge as image.
With the parameter width=55 (55 means 55px width/height) you may optionally set badge's width.

= Updating =

Before updating WooCommerce you should definitely check whether WooCommerce Germanized does already support the newest version. 
You may of course Update WooCommerce Germanized automatically through the WordPess Backend. Please make sure to always backup (at least your database) before updating any theme or plugin.

== Frequently Asked Questions ==

= Where do I receive support for WooCommerce Germanized? =

You may ask your questions regarding Germanized within our free [WordPress Support Forum](https://wordpress.org/support/plugin/woocommerce-germanized).
Professional Support via Ticket is being offered to [Professional Users](https://vendidero.de/woocommerce-germanized "Support for WooCommerce Germanized") only.

= Not every option fits my Theme =

Unfortunately not every Theme does implement WooCommerce in the way it's meant to be or differs from the original structure which leads to layout and/or compatibility 
issues. Out of that reason we have developed [VendiPro](https://vendidero.de/vendipro) which perfectly fits all Germanized Options and is optimized for German Market.

= Email Attachments not showing in Order Confirmation Email =

In most times you have a wrong configuration within your Germanized Email settings. The order confirmation email sent to the customer after an order has been made is called "Processing Order". The email called "New Order" is the email
sent to the Administrator which serves as a notification only - so make sure you have "Processing Order" selected within the Germanized settings.

= Want to file a Bug or improve Germanized? =

Bug reports may be filed via our [GitHub repository](https://github.com/vendidero/woocommerce-germanized).

== Screenshots ==

1. WooCommerce Germanized Settings

== Changelog ==

= 1.8.11 =
* Improvement: Better Woo 3.0 compliant product data saving
* Improvement: Filters for double opt in and unit prices
* Improvement: Fragment refresh for parcel delivery checkbox (e.g. when changing shipping method)
* Fix: Woo 3.0 variable meta box warnings
* Fix: Replaced legacy Woo 3.0 checkout posted data warning
* Fix: Product Variation fields for API v2
* Fix: Mini Cart REST API fix
* Fix: WPML revocation form and better email translation support
* Fix: Direct Debit field adding check for order existence

= 1.8.10 =
* Improvement - Better E-Mail-Template naming (renamed processing order to order confirmation)
* Fix - WC 3.0 unit prices (from-to) for variations
* Fix - Unit price bulk saving fallback
* Fix - rate_id within order tax totals (using get_shop_base_rate)
* Fix - tour email explanations, WC 3.0 preparations

= 1.8.9 =
* Feature - Allow DHL parcel shop delivery for certain shipping rates only
* Improvement - woocommerce_gzd_add_to_cart_variation_params filter to adjust wrapper name
* Improvement - better shipping rate choosing options (choose instances instead of methods) for parcel delivery checkbox
* Fix - Double Opt In WooCommerce 3.0
* Fix - Use get_rate_code for order item totals
* Fix - WooCommerce 3.0 stock reducing
* Fix - Order Item Meta CRUD - better product instance check
* Fix - Disable Paid for Order Email for direct debit and invoice gateway

= 1.8.8 =
* Feature - Better product data saving
* Feature - Allow setting a custom Small Business notice text
* Feature - REST API WC 3.0 v2 support

= 1.8.7 =
* Fix - Small Business VAT total tax removal (as Trusted Shops advised)
* Fix - FontAwesome Update to latest version
* Fix - Use frontend options filter for admin billing_title field
* Fix - By default remove username from password reset, new account mail if password or reset link is included (Trusted Shops advised)

= 1.8.6 =
* Fix - plugin_locale filter in WC_GZD_Install
* Fix - Support Parcel Shop Delivery in My Account - Edit Address
* Fix - Parcel Shop Delivery JS better event check

= 1.8.5 =
* Feature - DHL Parcel Shop or Pick-Up Station delivery
* Fix - Parcel Shop empty address placeholder
* Fix - Sale Price Labels Price Suffixes HTML
* Fix - Partial refunded order email legal item information
* Fix - Hook priority for product units in loop
* Fix - Parcel delivery checkbox validation

= 1.8.4 =
* Feature - DHL Parcel Shop or Pick-Up Station delivery
* Fix - Sale Price Labels Price Suffixes HTML
* Fix - Partial refunded order email legal item information
* Fix - Hook priority for product units in loop
* Fix - Parcel delivery checkbox validation

= 1.8.3 =
* Fix - Is empty check in 1.8.2
* Fix - API free shipping
* Fix - Clean SEPA XML whitespaces
* Fix - WC 2.7 RC 1 compatibility
* Fix - WPML Compatibility improvements
* Fix - Tax Rate adjustments for LU and RO
* Fix - Labels Price HTML
* Fix - Add to cart variation js preserving markup
* Fix - Removed direct debit gateway subscriptions support (not yet prepared)
* Feature - Added plugin version cache deletion tool to system status

= 1.8.2 =
* Fix - API free shipping
* Fix - Clean SEPA XML whitespaces
* Fix - WC 2.7 RC 1 compatibility
* Fix - WPML Compatibility improvements
* Fix - Tax Rate adjustments for LU and RO
* Fix - Labels Price HTML
* Fix - Add to cart variation js preserving markup
* Fix - Removed direct debit gateway subscriptions support (not yet prepared)
* Feature - Added plugin version cache deletion tool to system status

= 1.8.1 =
* Fix - Parent product data inherit
* Fix - Only manipulate price display if sale_price_labels are applicable
* Fix - REST API saving free_shipping parameter

= 1.8.0 =
* Feature - WooCommerce 2.7 beta compatibility (CRUD)
* Feature - gzd_complaints Shortcode extended to meet newest law requirements
* Feature - Option that hides specific tax rate from product pages
* Feature - Trusted Shops MPN and Brand support
* Improvement - Direct Debit default status to on-hold
* Improvement - Direct Debit data sanitization
* Improvement - Filter for customer account activation
* Improvement - eKomi v3 API
* Improvement - Double Opt In Social Login Plugin compatibility
* Improvememt - Added template for gzd_complaints Shortcode
* Fix - REST API to fit latest WC version
* Fix - Removed some unnecessary required fields from revocation form
* Fix - Direct Debit WPML compatiblity (string translation)

= 1.7.4 =
* Fix - WP 4.7 compatibility
* Fix - add_to_cart Shortcode output buffering
* Fix - WooCommerce GZD Status Page
* Fix - Trusted Shops Rich Snippet Meta 
* Fix - Country Codes for Virtual Rates
* Fix - On-hold to Processing Order Confirmation unhook

= 1.7.3 =
* Fix - Variations Unit Price Saving
* Fix - Better Performance by using get_the_terms
* Fix - Cancel Order Button Removal if option has been activated
* Fix - Digital Extended Type Check
* Fix - PHP < 5.3 check for SEPA Gateway (not supported)

= 1.7.2 =
* Feature - Optionally do not allow customers to switch payment gateway after ordering
* Feature - Better Woo Subscriptions Compatibility
* Feature - Parcel Delivery Data Transfer Checkbox for certain shipping methods (optional)
* Feature - Better SEPA XML Library (https://github.com/php-sepa-xml/php-sepa-xml) with Pain Format Support
* Fix - Customer Double Opt In Notice
* Fix - Hide Shipping Time if Product is not in stock
* Fix - Free Shipping REST API
* Fix - Unit Price Saving
* Fix - Order REST API
* Fix - JS Variation Reset
* Fix - Maybe Reduce Order Stock (for gateways like PayPal)

= 1.7.1 =
* Fix - WooCommerce Multilingual 3.6.8 compatibility
* Fix - Better Inline Plugin Update Compatibility
* Fix - Disable WC Default Term Display instead of hiding them via CSS

= 1.7.0 =
* Feature - Email Template Paid for Order
* Feature - Better WC Subscriptions Compatibility
* Feature - Service Products
* Fix - Customer Activation URL Escaping
* Fix - Free Shipping Auto Select
* Fix - REST Endpoint Order ID
* Fix - Trusted Shops Prefix Options
* Fix - Removed random_compat lib

= 1.6.8 =
* Fix - Direct Debit Checkbox Validation

= 1.6.7 =
* Fix - Free Shipping Auto Select WC 2.6
* Fix - Payment Gateways Shortcode
* Fix - Fallback Library random_compat for Direct Debit Gateway Encryption
* Fix - Removed Unused Hook from Direct Debit Gateway
* Fix - Better Order Email Filter Removal
* Fix - Direct Debit Checkbox Validation
* Fix - Better Dependency Management

= 1.6.6 =
* Feature - WooCommerce 2.6 Support
* Feature - WooCommerce REST API Support
* Feature - Direct Debit Pay Order Checkbox Support
* Fix - Saving Product Variation Sale Price Labels

= 1.6.5 =
* Fix - Direct Debit Encryption Class Loading (case-sensitive)
* Fix - Customer Helper Fixes for Double Opt-In
* Feature - Data Importer Update

= 1.6.4 =
* Fix - Encryption Library Classload fix
* Feature - Encryption for Direct Debit Gateway data
* Feature - Install German Formal Language (see Systemstatus > Germanized > Tools)
* Feature - Tool to delete Germanized Text Options (will we replaced by defaults)
* Fix - Double-Opt-In even if checkout shouldn't be disabled
* Fix - Trusted Shops Template SKU Parameter
* Fix - Trusted Shops Product Sticker Star Size

= 1.6.3 =
* Feature - Encryption for Direct Debit Gateway data
* Feature - Install German Formal Language (see Systemstatus > Germanized > Tools)
* Feature - Tool to delete Germanized Text Options (will we replaced by defaults)
* Fix - Double-Opt-In even if checkout shouldn't be disabled
* Fix - Trusted Shops Template SKU Parameter
* Fix - Trusted Shops Product Sticker Star Size

= 1.6.2 =
* Fix - Trusted Shops PHP < 5.4 backwards compatibility
* Fix - Prevent showing terms twice before AJAX call
* Fix - Trusted Shops implementation improvements

= 1.6.1 =
* Fix - PHP < 5.4 backwards compatibility
* Fix - Check taxonomy for WP_Error

= 1.6 =
* Notice - removed compatibility to WooCommerce < 2.4
* Feature - Sale Price Labels
* Feature - Better unit price management for variable products
* Feature - Double Opt In optional checkout deactivation for inactive users
* Feature - Officially supporting german formal language (Sie)
* Feature - SEPA XML Export
* Feature - Optionally hide shipping time/shipping cost notice for certain product types options
* Feature - Better Trusted Shops integration
* Fix - Matching WooCommerce checkbox HTML layout
* Fix - Role based prices compatibility
* Fix - WPML Helper improvement to stop double sending confirmation emails
* Fix - Better "Pay for Order" link

= 1.5.1 =
* Fix - Correctly autoloading WC_GZD_Admin
* Fix - Correctly hide unit base if option equals current value (not greater than) 

= 1.5.0 =
* Feature - Import data from previously installed German Market Plugins
* Feature - New Shortcode [gzd_complaints] within imprint to inform customers about new EU online dispute resolution platform
* Feature - Performance Improvements
* Feature - Set digital revocation notice error message
* Fix - Variation HTML syntax
* Fix - Title address field (+ better english translation)
* Fix - Coupons tax share calculation (for total = 0)

= 1.4.6 =
* Feature - set free shipping option for products do disable "plus shipping costs" notice
* Feature - forwarding fee for cash on delivery
* Feature - better WPML compatibility
* Feature - better virtual vat calculation (if enabled)
* Fix - payment methods shortcode to include payment fees
* Fix - remove order total html for better compatibility
* Fix - show notices (theme, pro) in backend only after major releases
* Fix - clear cart after order (if customer cancellations of orders has been disabled)
* Fix - variable products delivery time removal (admin screen)
* Fix - tour hide referer improvement

= 1.4.5 = 
* Feature - WC_GZD_Product_Variable object containing get_variation_unit_price with min max option
* Fix - virtual vat calculation (vat exempt compatibility)
* Fix - email object $type check

= 1.4.4 =
* Fix - in_array default option checkout

= 1.4.3 =
* Feature - intro settings tour
* Feature - better settings overview (new section: emails)
* Feature - set variation delivery time based on parent
* Feature - select product types (e.g. virtual) for digital loss of revocation notice
* Fix - variable delivery time saving
* Fix - stop unregistering prettyPhoto for direct debit
* Fix - virtual price filter only on checkout/cart
* Fix - adjusted class-wc-gzd-cart.php to newest version

= 1.4.2 =
* Feature - product units
* Feature - digital product notice (loss of right of withdrawal) in order confirmation
* Feature - turn off direct order confirmation by hidden option or filter
* Feature - WooCommerce style updates
* Fix - better base price
* Fix - send mails by id not by classname
* Fix - Trusted Shops review reminder days option
* Fix - order item name filter fix

= 1.4.1 =
* Fix - Direct Debit SEPA checkbox validation

= 1.4.0 =
* Feature - SEPA Direct Debit Gateway
* Feature - Pay by Invoice Gateway
* Fix - Trusted Shops Widget Fix
* Fix - Checkbox CSS Styling

= 1.3.7 =
* Feature - is_ajax double-check for nasty themes
* Fix - better checkout fallback mode
* Fix - digital checkbox markup

= 1.3.6 =
* Feature - WC 2.4 compatibility
* Fix - better checkbox html syntax
* Fix - tax notice vat exempt check
* Fix - FontAwesome Admin update
* Fix - better theme compatibility
* Fix - unforce shipping calculation
* Fix - better script loading

= 1.3.5 = 
* Feature - force free shipping method if available (optional)
* Feature - unit price sale scheduling (based on price schedules)
* Feature - unit price auto calculation (pro)
* Feature - system status for better error reporting
* Fix - product description removal
* Fix - better product data saving
* Fix - better email confirmation hooks
* Fix - wpml email attachment translation fix
* Fix - performance improvements

= 1.3.4 = 
* Feature - optionally stop customers from manually cancelling orders (paypal etc.)
* Feature - optionally show pay now button in confirmation email and success page
* Feature - optionally customize default customer account creation checkbox text
* Feature - optionally set checkbox for customer registration form
* Fix - wpml string compatibility
* Fix - unit price saving (sale price = 0 fix)

= 1.3.3 = 
* Feature - email attachment drag & drop ordering
* Feature - email plain text support
* Fix - saving variation data
* Fix - email template override notice (copy to woocommerce-germanized-pro instead of woocommerce)
* Fix - unit base display fix (hide number if base = 1)
* Fix - typo fixes

= 1.3.2 = 
* Feature - Better hook priority management
* Fix - saving variation cart description
* Fix - add to cart bug
* Fix - better date diff calculation

= 1.3.1 = 
* Fix - Variation shipping time

= 1.3.0 =
* Feature - Better theme compatibility (no template overriding required any longer)
* Feature - Unit editor (taxonomy)
* Feature - WPML compatibility
* Feature - Better shipping time assignment
* Feature - Trusted Shops Review Collector
* Feature - Fallback-Mode for users facing problems within checkout
* Feature - "Review your order" notice within checkout
* Fix - Different tax rates for variations supported
* Fix - WP-Cron incompatibility
* Fix - Language problems while overriding cart total strings

= 1.2.3 =
* Fix - Buy now button visibility WC < 2.3

= 1.2.2 =
* Feature - Double Opt-In for customer accounts
* Fix - No need to override payment.php any longer (WC 2.3)

= 1.2.1 =
* Fix - Variations admin edit custom attributes (unit price etc.)
* Fix - Fallback for non-wc-compliant Plugins to inject gzd_product

= 1.2.0 =
* Feature - Better shipping and fee taxation
* Feature - Now supporting WooCommerce 2.3
* Fix - Template location improvement

= 1.1.1 =
* Feature - Optionally choose to hide the edit order button on checkout
* Fix - Crucial bug which led to blank screens after upgrading to 1.1.0
* Fix - Even better third party compatibility
* Fix - Small business notification legal text update

= 1.1 =
* Feature - Hide shipping method select from checkout
* Feature - Hide "taxes and shipping estimated" from cart
* Feature - Show thumbnails within checkout table
* Feature - Implemented Trusted Shops API v2
* Fix - Sending wrong email text attachments
* Fix - Much better third party compatibility

= 1.0.5 =
* Feature - Optionally remove address field from checkout
* Fix - Shipping time bug within checkout
* Fix - Better theme compatibility for variations

= 1.0.4 =
* Feature - Applying new VAT rules for selling virtual products to EU countries
* Feature - Added option to set a label for displaying unit price
* Fix - Stop updating default order status to send order confirmation. Using (in)appropriate hooks instead
* Fix - Filter priority bug fix

= 1.0.3 =
* Fix - PHP 5.3 date diff backward compatibility
* Fix - Trusted Shops review email days ignore fix
* Feature - By default hide shipping costs notice for virtual products
* Feature - Added product cart description for variations

= 1.0.2 =
* Feature - Added WP multisite support
* Feature - Added optional payment charge for PayPal, COD, Prepayment
* Feature - Added optional review reminder mail for Trusted Shops
* Fix - Filter templates with priority PHP_INT_MAX to disallow template override
* Fix - Fixed vat calculation for payment method charges

= 1.0.1 =
* Fix - Better theme compatibility
* Fix - By default don't let themes override legally relevant templates (e.g. checkout/review-order.php, checkout/form-pay.php)
* Fix - Legal notice within checkout is now located before product cart table (Button-Loesung)

= 1.0.0 =
* Feature - shipping costs notice
* Feature - vat notice
* Feature - unit price
* Feature - shipping time editor & notice
* Feature - german checkout modification
* Feature - Trusted Shops integration
* Feature - eKomi integration
* Feature - e-Mail footers containing legal page contents
* Feature - buy now button text
* Feature - small-enterprise regulation
* Feature - optional fee for cod payment method

== Upgrade Notice ==

= 1.0.0 =
no upgrade - just install :)