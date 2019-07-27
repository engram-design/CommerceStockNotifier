# Stock Notifier plugin for Craft Commerce

This plugin allows you notify users by email if an item’s stock has dropped below a defined threshold.

## Requirements

This plugin requires Craft CMS 3.0.23 or later, and Commerce 2.0.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Commerce Stock Notifier”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require pixelandtonic/commerce-stock-notifier

# tell Craft to install the plugin
./craft install/plugin commerce-stock-notifier
```

## Settings

- ​*Low Stock Threshold*​ – ​_The stock amount that should trigger a notification._​
- ​*Notify Email*​ — ​_The email address(es) that should be notified when the stock gets below the threshold. Separate multiple emails with commas._​
