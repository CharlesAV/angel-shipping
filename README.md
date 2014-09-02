Angel Shipping
==============
This is a Laravel package that allows you to calculate shipping rates for USPS, FedEx, and UPS.  It was built for use in the [Angel CMS](https://github.com/JVMartin/angel), but works independently of that as well.

Installation
------------
Add the following requirements to your `composer.json` file:
```javascript
"require": {
	"angel/shipping": "dev-master"
},
```

Issue a `composer update` to install the package.

Add the following service provider to your `providers` array in `app/config/app.php`:
```php
'Angel\Shipping\ShippingServiceProvider'
```

Issue the following command:
```bash
php artisan config:publish angel/shipping       # Publish the config
```

Configuration
------------
Open up your `app/config/packages/angel/shipping/config.php` and input the credentials for the shipping APIs you're using:
```php
array(
	'logins' => array(
		'usps' => array(
			'username' => '' // The username for your USPS account.
		),
		'ups' => array(
			'access' => '', // The access license key of your UPS account.
			'username' => '', // The username of your UPS account.
			'password' => '', // The password of your UPS account.
			'account' => '' // The account number of your UPS account.
		),
		'fedex' => array(
			'key' => '', // The developer key of your FedEx account.
			'password' => '', // The password of your FedEx account.
			'account' => '', // The account number of your FedEx account.
			'meter' => '' // The meter number of your FedEx account.
		)
	)
);
```

Use
------------

```php
// Class
$shipping = App::make('Shipping');

// Shipping from - currently this only supports shipping from the US
$shipping->from_zip = 97211;
$shipping->from_state = 'OR';
$shipping->from_country = 'US';

// Shipping to
$shipping->to_zip = 55355; // The zip code you'll be shipping the package to (international zip codes allowed)
$shipping->to_state = 'MN'; // The state code you'll be shipping the package to (international states allowed)
$shipping->to_country = 'US'; // The country code you'll be shipping the package to (international zip codes allowed)

// Debug - this will 'print' some info about the requests we're making, use it for debugging purposes
$shipping->debug = 1;

// Company
$shipping_company = App::make('ShippingFedEx');
$shipping->company($shipping_company);
	
// Package
$package = array(
	'dimensions_length' => 10,
	'dimensions_width' => 6,
	'dimensions_height' => 2,
	'dimensions_unit' => 'in', // cm, in [default], ft
	'weight' => 1.2,
	'weight_unit' => 'lb', // gram, oz, lb [default], kg
	'key' => 'abc123' // Optional, but will be returned with rates and may be useful for you to differentiate between multiple packages
);
$shipping->package($package);

// Calculate
$rates = $shipping->calculate();
```

The resulting array that's returned by the calculate() method will contain the total 'rates', information about each package (dimensions, weight, rate, etc.), and the name that corresponds to each method code. An example (returned by FedEx):

```php
Array
(
    [rates] => Array
        (
            [PRIORITY_OVERNIGHT] => 72.76
            [STANDARD_OVERNIGHT] => 67.74
            [FEDEX_2_DAY_AM] => 37.28
            [FEDEX_2_DAY] => 32.7
            [FEDEX_EXPRESS_SAVER] => 25.29
            [FEDEX_GROUND] => 12.03
        )

    [packages] => Array
        (
            [0] => Array
                (
                    [package] => Array
                        (
                            [dimensions_length] => 10
                            [dimensions_width] => 6
                            [dimensions_height] => 2
                            [dimensions_unit] => in
                            [weight] => 1.2
                            [weight_unit] => lb
                            [key] => abc123
                        )

                    [rates] => Array
                        (
                            [PRIORITY_OVERNIGHT] => 72.76
                            [STANDARD_OVERNIGHT] => 67.74
                            [FEDEX_2_DAY_AM] => 37.28
                            [FEDEX_2_DAY] => 32.7
                            [FEDEX_EXPRESS_SAVER] => 25.29
                            [FEDEX_GROUND] => 12.03
                        )

                )

        )

    [names] => Array
        (
            [PRIORITY_OVERNIGHT] => Priority Overnight
            [STANDARD_OVERNIGHT] => Standard Overnight
            [FEDEX_2_DAY_AM] => 2 Day AM
            [FEDEX_2_DAY] => 2 Day
            [FEDEX_EXPRESS_SAVER] => Express Saver
            [FEDEX_GROUND] => Ground
        )

)
```

Methods
------------

Each shipping company has several shipping 'methods' available.  By default, we'll return the rates for all methods that can be used to ship your specified package. If you want to limit what 'methods' we calculate the shipping rates for, however, you can pass an array of methods in the first parameter when setting up the company's shipping class.

For example (UPS):

```php
...

// Methods
$methods = array(
	// United States Domestic Shipments
	'01', // UPS Next Day Air
	'02', // UPS Second Day Air
	'03' // UPS Ground
);

// Company
$shipping_company = App::make('ShippingUPS',array('methods' => $methods));
$shipping->company($shipping_company);

...
```

The methods are defined by the 'code' the shipping company uses for that method in their API (example: UPS's '01' code is for the method 'UPS Next Day Air'). As we mentioned before, the array returned by the calculate() method contains an array for the method 'name' that matches each method 'code'.  For example, in our above request, the 'names' array in the array returned by calculate() would be:

```php
...
[names] => Array(
	'01' => 'UPS Next Day Air',
	'02' => 'UPS Second Day Air',
	'03' => 'UPS Ground'
),
...
```