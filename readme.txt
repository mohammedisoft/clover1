=== Clover Payments for WooCommerce===
Contributors: cloverecommerce
Tags: clover, credit card, payment request
Plugin Name: Clover Payments for WooCommerce
Plugin URI: https://wordpress.org/plugins/clover-payments-for-woocommerce/
Description: Accepting payments in WooCommerce using Clover eCommerce.
Version: 2.2.0
Requires at least: 6.1
Tested up to: 6.7
Requires PHP: 7.4
Author: Clover eCommerce
Author URI: https://www.clover.com
License: BSD-3-Clause-Clear
License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause-Clear
Text Domain: woo-clv-payments
Domain Path: /languages
Stable tag: 2.2.0

The Clover Payments plugin enables merchants that use WooCommerce to process online card payments using Clover.

== Description ==

The WordPress plugin from Clover allows a merchant using a WordPress based app like WooCommerce
to securely collect card information from buyers and process the payment using their Clover merchant account.

The payment extension uses iframes to collect card information. Card details are the tokenized by directly
communicating with a Clover’s servers. The payment is processed using this token and the card details are never saved on WordPress or the merchant’s servers. Therefore, this plug-in does not contribute to the PCI scope of a merchant’s eCommerce site. 

The plug-in is free for merchants to use and transactions processed using this plugin will be charged under the merchant’s Clover account.

== Privacy Statement ==

Clover's privacy statement can be found [here](https://www.clover.com/privacy-policy).

== Feature list ==

* Authorize only
* Capture
* Charge (Authorize and Capture)
* Refund
* Void
* PCI Compliance through iFrames
* Multi-Lingual Support for Canadian French
* Payment option with the ‘Pay Now’ link sent via email

=== Prerequisites ===

* Clover Merchant or Sandbox account
* If you are currently not a Clover merchant or do not have a Clover Sandbox account, you will need to sign up for one at Clover.com prior to using the Payment extension. You can then use this extension to send transactions to your Sandbox or Production accounts.
* We recommend that you test your plugin integration with your Clover sandbox account prior to sending transactions to your production/live
account(s).

Direct all questions to wordpress@clover.com

[Clover Documentation](https://docs.clover.com/docs/woocommerce)

== Frequently Asked Questions ==

= Do I need a Clover POS device to use this plug in? =

No, all you need is a Clover Sandbox or Clover Production Account.

== Screenshots ==

1. Clover Networks Logo Screen
2. WordPress Plugin Page with Clover Payments for WooCommerce
3. Enable Clover Payments in WooCommerce Settings
4. Plugin Settings Screen - US English
5. Plugin Settings Screen - Canadian French
6. Clover Account Dashboard
7. Clover eCommerce API Credentials
8. Clover Payment Shopping Check with Cart Screen - US English
9. Clover Payment Shopping Cart Screen - Canadian French
10. Clover Payment Admin Order details - card details
11. Clover Payment My account Order details - card details
12. Clover Payment card details on invoice page

== Changelog ==

2025-06-23 - Version 2.2.0

* Added - "Cardholder Name" field to payment form
* Added - Accepted card networks icons
* Added - Admin settings "Merchant ID" validation

== Installation ==

**MINIMUM REQUIREMENTS**
PHP version 7.4 or greater
WordPress 6.1 or greater
WooCommerce 8.0 or greater
Clover Sandbox or Clover Production Account

Refer to the standard [WordPress plugin installation procedure](https://wordpress.org/support/article/managing-plugins/) for details.

**QUICK START**
Steps to Install the Clover Payments for WooCommerce plugin.
1. Install the Plugin from the WordPress Plugins page search **Clover Payments** or **Clover Payments for WooCommerce**.
2. **Activate** Clover Payments for WooCommerce from **Plugins** > **Installed Plugins**.
3. Go to **WooCommerce** > **Settings** > **Payments** > **Clover Payments** > **Manage** > **Enable**.
4. Set Clover Payments plugin environment: we provide the option for Merchants and Developers to test their integrations against their sandbox accounts prior to going live. Select **Production** when you want to send transactions to your production environment.
5. Set Clover Payments plugin keys: please visit Clover Merchant Portal to obtain a public and private key.
6. Set Clover Payments plugin **Payment Action** and **Save Changes**
7. You’re done! The active payment methods should be visible in the checkout of your website.
