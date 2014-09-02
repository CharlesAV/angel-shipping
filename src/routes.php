<?php
Route::get('shipping',function() {
	// Class
	$shipping = App::make('Shipping');
	$shipping->from_zip = 97211;
	$shipping->from_state = 'OR';
	$shipping->from_country = 'US';
	$shipping->to_zip = 55355;
	$shipping->to_state = 'MN';
	$shipping->to_country = 'US';
	$shipping->debug = 1;
	
	$companies = array(
		'USPS',
		//'UPS',
		//'FedEx'
	);
	foreach($companies as $company) {
		print "<br />
<b>".$company."</b><br />";
		// Company
		$shipping_company = App::make('Shipping'.$company);
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
		print "Rates:<pre>";
		print_r($rates);
	}
});