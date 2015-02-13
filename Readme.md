# Order Creation

Create order from admin of Thelia2 (2.1.1+)

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is OrderCreation.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/order-creation-module:~1.0
```

## Usage

Be sure that you have :
 - an active payment module
 - an active delivery module

Then, go to the customer edit page and click on the button "Create an order for this customer"