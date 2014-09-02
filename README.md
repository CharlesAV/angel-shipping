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
$shipping_company = App::make('ShippingUSPS');
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

The resulting array that's returned by the calculate() method will contain both the total 'rates' and information about the 'packages' (dimensions, weight, price per package, etc.). An example (returned by USPS):

```php
Array
(
    [rates] => Array
        (
            [Priority Mail Express] => 38.8
            [Priority Mail Express Hold For Pickup] => 38.8
            [Priority Mail Express Flat Rate Boxes] => 44.95
            [Priority Mail Express Flat Rate Boxes Hold For Pickup] => 44.95
            [Priority Mail Express Flat Rate Envelope] => 19.99
            [Priority Mail Express Flat Rate Envelope Hold For Pickup] => 19.99
            [Priority Mail Express Legal Flat Rate Envelope] => 19.99
            [Priority Mail Express Legal Flat Rate Envelope Hold For Pickup] => 19.99
            [Priority Mail Express Padded Flat Rate Envelope] => 19.99
            [Priority Mail Express Padded Flat Rate Envelope Hold For Pickup] => 19.99
            [Priority Mail] => 10.25
            [Priority Mail Large Flat Rate Box] => 17.45
            [Priority Mail Medium Flat Rate Box] => 12.35
            [Priority Mail Flat Rate Envelope] => 5.6
            [Priority Mail Legal Flat Rate Envelope] => 5.75
            [Priority Mail Padded Flat Rate Envelope] => 5.95
            [Priority Mail Gift Card Flat Rate Envelope] => 5.6
            [Priority Mail Small Flat Rate Envelope] => 5.6
            [Priority Mail Window Flat Rate Envelope] => 5.6
            [Standard Post] => 8.76
            [Media Mail] => 3.17
            [Library Mail] => 3.02
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
                            [Priority Mail Express] => 38.8
                            [Priority Mail Express Hold For Pickup] => 38.8
                            [Priority Mail Express Flat Rate Boxes] => 44.95
                            [Priority Mail Express Flat Rate Boxes Hold For Pickup] => 44.95
                            [Priority Mail Express Flat Rate Envelope] => 19.99
                            [Priority Mail Express Flat Rate Envelope Hold For Pickup] => 19.99
                            [Priority Mail Express Legal Flat Rate Envelope] => 19.99
                            [Priority Mail Express Legal Flat Rate Envelope Hold For Pickup] => 19.99
                            [Priority Mail Express Padded Flat Rate Envelope] => 19.99
                            [Priority Mail Express Padded Flat Rate Envelope Hold For Pickup] => 19.99
                            [Priority Mail] => 10.25
                            [Priority Mail Large Flat Rate Box] => 17.45
                            [Priority Mail Medium Flat Rate Box] => 12.35
                            [Priority Mail Flat Rate Envelope] => 5.6
                            [Priority Mail Legal Flat Rate Envelope] => 5.75
                            [Priority Mail Padded Flat Rate Envelope] => 5.95
                            [Priority Mail Gift Card Flat Rate Envelope] => 5.6
                            [Priority Mail Small Flat Rate Envelope] => 5.6
                            [Priority Mail Window Flat Rate Envelope] => 5.6
                            [Standard Post] => 8.76
                            [Media Mail] => 3.17
                            [Library Mail] => 3.02
                        )

                )

        )

)
```