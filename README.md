# Cardknox-Opencart-3.x
Cardknox Gateway plugin for OpenCart 3.x

> **End of life: 30 April 2027.** Version 1.1.0 is the final release of this plugin.
> Until that date it receives security fixes only, and only for credible reports.
> After it, the repository will be archived read-only and no further changes will be made.
> If you run this plugin, upgrade to 1.1.0 now and plan your move to another integration.

## Supported versions
- OpenCart 3.0.x

OpenCart 4 is not supported.

## Install
1. Copy the contents of `upload/` into the root of your OpenCart installation.
2. In the OpenCart admin, go to **Extensions > Extensions**, choose **Payments**, and install **Cardknox**.
3. Edit the extension and enter your Cardknox **Transaction Key** and **Token Key** (iFields key).
4. Set the **Transaction Method** (Authorization or Capture), the **Order Status** for approved payments, and **Status** to Enabled.

**Transaction URI Endpoint** is optional. Leave it blank to use `https://x1.cardknox.com/gateway`. Any value that is not an `https://` URL is ignored and the default is used.

## Upgrading from an earlier version
Copy `upload/` over your existing install. No database or settings changes are needed. See [CHANGELOG.md](CHANGELOG.md) for what changed.

## Migration
Before end of life, move to one of:
- A Cardknox-hosted payment page, which takes tokenization and compliance off your cart entirely
- A supported Cardknox integration for your platform
- Direct integration with the Cardknox API, if you have development capacity

Contact Cardknox support for help choosing.

## Logs
Logs are located at `storage/logs/cardknox.log`. API key, card data, and customer personal data are masked before logging.

## License
See [LICENSE](LICENSE).
