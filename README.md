
# iyzico for Adobe Commerce (Magento 2)

iyzico Payment Gateway integration for Magento 2, offering a seamless payment experience for your e-commerce store.

## 🚀 Features

- Payment by Debit or Credit Card
- Ease of Payment in Installments
- Payment with Shopping Credit
- Payment with Internal & Interbank Transfer
- Guest User Payment
- iyzico Protected

## 📋 Requirements

- PHP 8.2 or higher
- cURL extension
- GuzzleHttp library
## 🛠️ Installation 

* Create the directory structure:

```bash
# Navigate to the app directory
cd app

# Create a new directory named code
mkdir code

# Navigate to the code directory
cd code

# Create a new directory named Iyzico
mkdir Iyzico

# Navigate to the Iyzico directory
cd Iyzico
```

* Clone or download this repository into the `app/code/Iyzico` directory.

```bash
git clone <repo-url> "Iyzipay"
```

* Run the following Magento commands:

```bash
# Enable the Iyzico module and clear static content
bin/magento module:enable Iyzico_Iyzipay --clear-static-content

# Upgrade the setup
bin/magento setup:upgrade

# Deploy static content
bin/magento setup:static-content:deploy -f

# Compile dependency injection
bin/magento setup:di:compile

# Clean the cache
bin/magento cache:clean

# Flush the cache
bin/magento cache:flush

# Cron install
bin/magento cron:install

# Cron run
bin/magento cron:run
```

## 🔧 Configuration

* Create a live account at [https://merchant.iyzipay.com](https://merchant.iyzipay.com)
* Create a test account at [https://sandbox-iyzipay.com](https://sandbox-iyzipay.com)
* Configure the module in Magento admin panel under Stores > Configuration > Sales > Payment Methods
## 🤝 Contributing

We welcome contributions! Please feel free to submit a Pull Request.
  
## 📘 Documentation

For detailed integration information, please visit our [Technical Documentation](https://docs.iyzico.com/).

## 🆘 Support

If you encounter any issues, please:

* Check our [issue tracker](https://github.com/your-repo/issues) for known issues
* Create a new issue if your problem is not yet reported
* For account-specific problems, contact support@iyzico.com

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgements

- [@tarikkamat](https://www.github.com/tarikkamat)
- Magento community
- All our contributors and users

---

Made with ❤️ by iyzico Team
  