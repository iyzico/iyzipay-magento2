# iyzico - Magento 2 Payment Gateway
------------
* Create live account at https://merchant.iyzipay.com
* Create test account at https://sandbox-iyzipay.com

# Requirements
------------
* PHP 7.0.x and greater.
* cURL
* [Magento2 version 2.2+](https://devdocs.magento.com/guides/v2.2/install-gde/system-requirements-tech.html)

# Collaboration
------------
* We commit all our new features directly into our GitHub repository. But you can also request or suggest new features or code changes yourself!

# Installation
---------------
```php
* Copy or download the repository
* Create the iyzico folder in the app folder. In the Iyzico folder, create the Iyzipay folder. (app -> Iyzico -> Iyzipay)
* Send the repository files into the Iyzipay folder
* bin/magento module:enable Iyzico_Iyzipay --clear-static-content
* bin/magento setup:upgrade
* bin/magento setup:di:compile
* bin/magento module:status
* Enjoy :)
```

<a href="https://dev-beta.iyzipay.com/tr/3-secenek/teknik-bilgi-gerekli">Detailed Info</a>

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
* Developed and Tested on vanilla Magento2 2.2+ Installation

# License
---------------
* MIT license. For more information, see the LICENSE file.