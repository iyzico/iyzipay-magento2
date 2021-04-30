# Pay With iyzico - Magento 2 Payment Gateway
------------
* Create live account at https://merchant.iyzipay.com
* Create test account at https://sandbox-merchant.iyzipay.com

# Requirements
------------
* PHP 7.0.x and greater.
* cURL
* [Magento2 version 2.2.3+](https://devdocs.magento.com/guides/v2.4/install-gde/system-requirements.html)

# Collaboration
------------
* We commit all our new features directly into our GitHub repository. But you can also request or suggest new features or code changes yourself!

# Installation
---------------
```php
* Copy or download the repository
* Create the iyzico folder in the app folder. In the Iyzico folder, create the PayWithIyzico folder. (app -> Iyzico -> PayWithIyzico)
* Send the repository files into the PayWithIyzico folder
* bin/magento module:enable Iyzico_PayWithIyzico --clear-static-content
* bin/magento setup:upgrade
* bin/magento setup:di:compile
* bin/magento module:status
* Enjoy :)
```

<a href="https://dev.iyzipay.com/tr/acik-kaynak/magento">Detailed Info</a>

# Support
---------------
* You can create issues on our Magento Repository. In case of specific problems with your account, please contact support@iyzico.com.

# Additional Features
---------------------
* Installment Management Support
* Guest Checkout Payment Support
* iyzico Protected Script Support
* Live / Sandbox Support

# Notes
---------------
* Developed and Tested on vanilla Magento2 2.2.3+ Installation

# License
---------------
* MIT license. For more information, see the LICENSE file.
