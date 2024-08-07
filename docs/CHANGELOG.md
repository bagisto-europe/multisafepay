# Changelog

## Version 1.1.0

### Added
- Compatibility with Bagisto v2.2.*
- Admins can now choose MultiSafepay as a payment option during the order creation.
- Customers can easily pay their orders from the customer panel.

## Version 1.0.6

### Bugfixes
- By canceling the payment, the invoice is created automatically.

## Version 1.0.5

### Added
- Added a event listener for shipments.
- Added shopping cart data in the transaction request

## Version 1.0.4

### Bugfixes

- Fixed webhook returns error 500.

## Version 1.0.3

### Bugfixes

- Removed irrelevant changes

## Version 1.0.2

### Bug Fixes

- Override file instead of overriding routes.

## Version 1.0.1

### Bug Fixes

- Added the missing license information to the composer file.
- Fixed sdk version in composer.
- Fixed issues caused by order id prefix.

## Version 1.0.0 (Initial Release)

### Features

- Added support for all payment methods from MultiSafePay on the checkout page.
- Implemented a webhook to accept transaction notifications from MultiSafePay.
- Implemented a user-friendly refund processing system, empowering administrators to effortlessly manage customer refunds through MultiSafePay.
