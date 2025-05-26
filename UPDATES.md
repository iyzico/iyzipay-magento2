# IyziPay Magento 2 Module Updates

## Version 2.1.5 - Critical Security Fix

### Removed API Key Storage from Custom Tables
- **CRITICAL**: Removed `api_key` column from `iyzico_card` table
- **IMPROVEMENT**: Added `store_id` column for proper multi-store support
- Updated controllers to use store_id instead of api_key for card filtering
- Added upgrade scripts to safely remove existing API key data
- API keys now only stored encrypted in system configuration
- **BENEFIT**: Maintains multi-store functionality while ensuring security

### Previous Security Improvements (Version 2.1.4)

### Secure Storage of API Keys
- Updated configuration for secure encryption of API Key and Secret Key fields
- Changed field types to `obscure`
- Added `Magento\Config\Model\Config\Backend\Encrypted` backend model

These changes ensure API keys and secret keys are stored encrypted in the database, enhancing security. 