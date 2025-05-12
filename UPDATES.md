# IyziPay Magento 2 Module Updates

## Security Improvements

### Secure Storage of API Keys
- Updated configuration for secure encryption of API Key and Secret Key fields
- Changed field types to `obscure`
- Added `Magento\Config\Model\Config\Backend\Encrypted` backend model

These changes ensure API keys and secret keys are stored encrypted in the database, enhancing security. 