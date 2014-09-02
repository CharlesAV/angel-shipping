<?php
namespace Angel\Shipping;

use Config;

if(!class_exists('ShippingFedEx',false)) {
	/**
	 * Calculates shipping cost for FedEx shipping methods.						
	 */
	class ShippingFedEx  {
		/** The developer key of your FedEx account. */
		var $key;
		/** The password of your FedEx account. */
		var $password;
		/** The account number of your FedEx account. */
		var $account;
		/** The meter number of your FedEx account. */
		var $meter;
		/** The wsdl URL to use when getting shipping costs. */
		var $wsdl;
		/** Whether or not the fedex credentials are 'test' or 'development' credentials, as opposed to 'production' credentials. */
		var $test = 0;
		/** An array of methods to get shipping costs for. */
		var $methods = array(
			'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
			'FEDEX_1_DAY_FREIGHT',
			'FEDEX_2_DAY_FREIGHT',
			'FEDEX_2_DAY',
			'FEDEX_2_DAY_AM',
			'FEDEX_3_DAY_FREIGHT',
			'FEDEX_EXPRESS_SAVER',
			'FEDEX_GROUND',
			'FIRST_OVERNIGHT',
			'GROUND_HOME_DELIVERY',
			'INTERNATIONAL_ECONOMY',
			'INTERNATIONAL_ECONOMY_FREIGHT',
			'INTERNATIONAL_FIRST',
			'INTERNATIONAL_GROUND',
			'INTERNATIONAL_PRIORITY',
			'INTERNATIONAL_PRIORITY_FREIGHT',
			'PRIORITY_OVERNIGHT',
			'SMART_POST',
			'STANDARD_OVERNIGHT',
			'FEDEX_FREIGHT',
			'FEDEX_NATIONAL_FREIGHT',
		);
		/** An array of method codes and their corresponding names. */
		var $names = array(
			'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => 'Europe First Priority',
			'FEDEX_1_DAY_FREIGHT' => '1 Day Freight',
			'FEDEX_2_DAY' => '2 Day',
			'FEDEX_2_DAY_AM' => '2 Day AM',
			'FEDEX_2_DAY_FREIGHT' => '2 Day Freight',
			'FEDEX_3_DAY_FREIGHT' => '3 Day Freight',
			'FEDEX_EXPRESS_SAVER' => 'Express Saver',
			'FEDEX_FREIGHT' => 'Freight',
			'FEDEX_GROUND' => 'Ground',
			'FEDEX_NATIONAL_FREIGHT' => 'National Freight',
			'FIRST_OVERNIGHT' => 'First Overnight',
			'GROUND_HOME_DELIVERY' => 'Home Delivery',
			'INTERNATIONAL_ECONOMY' => 'International Economy',
			'INTERNATIONAL_ECONOMY_FREIGHT' => 'International Economy Freight',
			'INTERNATIONAL_FIRST' => 'International First',
			'INTERNATIONAL_GROUND' => 'International Ground',
			'INTERNATIONAL_PRIORITY' => 'International Priority',
			'INTERNATIONAL_PRIORITY_FREIGHT' => 'International Priority Freight',
			'PRIORITY_OVERNIGHT' => 'Priority Overnight',
			'SMART_POST' => 'Smart Post',
			'STANDARD_OVERNIGHT' => 'Standard Overnight',
		);
		
		/**
		 * Constructs the class.
		 *
		 * @param array $c An array of configuration values. Default = NULL
		 */
		function __construct($c = NULL) {
			self::ShippingFedEx($c);
		}
		function ShippingFedEx($c = NULL) {
			// Config
			if($c) {
				foreach($c as $k => $v) {
					$this->$k = $v;
				}
			}
			
			// Defaults
			if(!$this->key) $this->key = Config::get('shipping::logins.fedex.key');
			if(!$this->password) $this->password = Config::get('shipping::logins.fedex.password');
			if(!$this->account) $this->account = Config::get('shipping::logins.fedex.account');
			if(!$this->meter) $this->meter = Config::get('shipping::logins.fedex.meter');
			if(!$this->wsdl) $this->wsdl = __DIR__."\RateService_v10.wsdl";
		}
	
		/**
		 * Calculates the shipping cost(s).
		 *
		 * @param object $shipping The shipping class object which contains all the information about the shipment we want to calculate costs for.
		 * @return array An array of shipping costs in array('code' => 'cost') format.
		 */
		function calculate($shipping) {
			// Packages - make sure they don't exceed maximum
			$packages = $shipping->packages;
			//if($this->package_max) $packages = $shipping->packages_max($packages,$this->package_max); // Currently  has no max
			
			// Missing credentials
			if(!$this->key or !$this->password or !$this->account or !$this->meter) {
				throw new \Exception("FedEx credentials are missing.");	
			}
			// Missing shipping object
			if(!$shipping) {
				throw new \Exception("No 'shipping' object passed.");
			}
			// No methods
			if(!$this->methods) {
				throw new \Exception("No shipping methods defined.");
			}
			// No SoapClient class
			if(!class_exists('SoapClient')) {
				throw new \Exception("SoapClient clsas doesn't exist.");
			}
			
			// Rates
			$rates = NULL;
			
			/* Magento's methods:
			'method' => array(
				'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => Mage::helper('usa')->__('Europe First Priority'),
				'FEDEX_1_DAY_FREIGHT'                 => Mage::helper('usa')->__('1 Day Freight'),
				'FEDEX_2_DAY_FREIGHT'                 => Mage::helper('usa')->__('2 Day Freight'),
				'FEDEX_2_DAY'                         => Mage::helper('usa')->__('2 Day'),
				'FEDEX_2_DAY_AM'                      => Mage::helper('usa')->__('2 Day AM'),
				'FEDEX_3_DAY_FREIGHT'                 => Mage::helper('usa')->__('3 Day Freight'),
				'FEDEX_EXPRESS_SAVER'                 => Mage::helper('usa')->__('Express Saver'),
				'FEDEX_GROUND'                        => Mage::helper('usa')->__('Ground'),
				'FIRST_OVERNIGHT'                     => Mage::helper('usa')->__('First Overnight'),
				'GROUND_HOME_DELIVERY'                => Mage::helper('usa')->__('Home Delivery'),
				'INTERNATIONAL_ECONOMY'               => Mage::helper('usa')->__('International Economy'),
				'INTERNATIONAL_ECONOMY_FREIGHT'       => Mage::helper('usa')->__('Intl Economy Freight'),
				'INTERNATIONAL_FIRST'                 => Mage::helper('usa')->__('International First'),
				'INTERNATIONAL_GROUND'                => Mage::helper('usa')->__('International Ground'),
				'INTERNATIONAL_PRIORITY'              => Mage::helper('usa')->__('International Priority'),
				'INTERNATIONAL_PRIORITY_FREIGHT'      => Mage::helper('usa')->__('Intl Priority Freight'),
				'PRIORITY_OVERNIGHT'                  => Mage::helper('usa')->__('Priority Overnight'),
				'SMART_POST'                          => Mage::helper('usa')->__('Smart Post'),
				'STANDARD_OVERNIGHT'                  => Mage::helper('usa')->__('Standard Overnight'),
				'FEDEX_FREIGHT'                       => Mage::helper('usa')->__('Freight'),
				'FEDEX_NATIONAL_FREIGHT'              => Mage::helper('usa')->__('National Freight'),
			),
			'dropoff' => array(
				'REGULAR_PICKUP'          => Mage::helper('usa')->__('Regular Pickup'),
				'REQUEST_COURIER'         => Mage::helper('usa')->__('Request Courier'),
				'DROP_BOX'                => Mage::helper('usa')->__('Drop Box'),
				'BUSINESS_SERVICE_CENTER' => Mage::helper('usa')->__('Business Service Center'),
				'STATION'                 => Mage::helper('usa')->__('Station')
			),
			'packaging' => array(
				'FEDEX_ENVELOPE' => Mage::helper('usa')->__('FedEx Envelope'),
				'FEDEX_PAK'      => Mage::helper('usa')->__('FedEx Pak'),
				'FEDEX_BOX'      => Mage::helper('usa')->__('FedEx Box'),
				'FEDEX_TUBE'     => Mage::helper('usa')->__('FedEx Tube'),
				'FEDEX_10KG_BOX' => Mage::helper('usa')->__('FedEx 10kg Box'),
				'FEDEX_25KG_BOX' => Mage::helper('usa')->__('FedEx 25kg Box'),
				'YOUR_PACKAGING' => Mage::helper('usa')->__('Your Packaging')
			),*/
			
			// Client
			ini_set("soap.wsdl_cache_enabled", "0");
			$client = new \SoapClient($this->wsdl, array('trace' => 1));
			
			// Packages - we can send them all in 1 request, but doesn't differentiate totals in results so must send separate requests
			foreach($packages as $package_x => $package) {
				// Request
				$request = array(
					'WebAuthenticationDetail' => array(
						'UserCredential' => array(
							'Key' => $this->key,
							'Password' => $this->password
						)
					),
					'ClientDetail' => array(
						'AccountNumber' => $this->account,
						'MeterNumber' => $this->meter
					),
					'TransactionDetail' => array(
						'CustomerTransactionId' => ' *** Rate Available Services Request v10 using PHP ***'
					),
					'Version' => array(
						'ServiceId' => 'crs',
						'Major' => '10',
						'Intermediate' => '0',
						'Minor' => '0'
					),
					'ReturnTransitAndCommit' => true,
					'RequestedShipment' => array(
						'DropoffType' => 'REGULAR_PICKUP', // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
						'ShipTimestamp' => date('c'),
						'Shipper' => array(
							'Address' => array(
								/*'StreetLines' => array(
									$shipping->from_address
								),
								'City' => $shipping->from_city,*/
								'StateOrProvinceCode' => ($shipping->from_country == "US" ? $shipping->from_state : ""),
								'PostalCode' => $shipping->from_zip,
								'CountryCode' => $shipping->from_country
							)
						),
						'Recipient' => array(
							'Address' => array(
								/*'StreetLines' => array(
									$shipping->to_address
								),
								'City' => $shipping->to_city,*/
								'StateOrProvinceCode' => ($shipping->to_state == "US" ? $shipping->to_state : ""),
								'PostalCode' => $shipping->to_zip,
								'CountryCode' => $shipping->to_country
							)
						),
						'ShippingChargesPayment' => array(
							'PaymentType' => 'SENDER',
							/*'Payor' => array(
								'AccountNumber' => $this->account,
								'CountryCode' => 'US'
							)*/
						),
						'RateRequestTypes' => 'ACCOUNT',
						'RateRequestTypes' => 'LIST',
						'PackageCount' => count($packages)
					)
				);
			
				// Packages - normally we'd loop through here, but doesn't return separate prices for each pacakge so sending new request for each package (see above)
				//foreach($packages as $package_x => $package) {
					// Array
					$array = array(
						'SequenceNumber' => ($package_x + 1),
						'GroupPackageCount' => 1,
						'Weight' => array(
							'Value' => number_format($shipping->convert_weight($package['weight'],$package['weight_unit'],'lb'), 1, '.', ''),
							'Units' => 'LB'
						),
						'Dimensions' => array(
							'Length' => $shipping->convert_dimensions($package['dimensions_length'],$package['dimensions_unit'],"in",1),
							'Width' => $shipping->convert_dimensions($package['dimensions_width'],$package['dimensions_unit'],"in",1),
							'Height' => $shipping->convert_dimensions($package['dimensions_height'],$package['dimensions_unit'],"in",1),
							'Units' => 'IN'
						)
					);
					$request['RequestedShipment']['RequestedPackageLineItems'][] = $array;
				//}
			
				// Location - if using production account, must use https://ws.fedex.com:443/web-services, not the development URL in the wsdl file: https://wsbeta.fedex.com:443/web-services/rate
				if($this->test) $client->__setLocation("https://wsbeta.fedex.com:443/web-services/rate");
				else $client->__setLocation("https://ws.fedex.com:443/web-services");
				
				// Send
				$response = $client ->getRates($request);
					
				// Debug
				if($shipping->debug) {
					print "request: <xmp>".$client->__getLastRequest()."</xmp>";
					print "results: <xmp>".$client->__getLastResponse()."</xmp>";
				}
				
				// Rates
				if($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR') {
					foreach($response->RateReplyDetails as $rateReply) {   
						$method = $rateReply->ServiceType;
						if(in_array($method,$this->methods)) {
							$rate = $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
							if($rate) {
								if(!isset($rates['rates'][$method])) $rates['rates'][$method] = 0;
								$rates['rates'][$method] += $rate;
								$rates['packages'][$package_x]['package'] = $package;
								if(!isset($rates['packages'][$package_x]['rates'][$method])) $rates['packages'][$package_x]['rates'][$method] = 0;
								$rates['packages'][$package_x]['rates'][$method] += $rate;
								if(!isset($rates['names'][$method])) $rates['names'][$method] = $this->name($method);
							}
						}
					}
				}
			}
			
			// Return
			return $rates;
			
			/*** Old method - deprecated ***/
			/*// URL
			$url = "https://gatewaybeta.fedex.com/GatewayDC";
			
			// Packages
			$total = 0;
			foreach($packages as $package_x => $package) {
				// Weight (in lbs)
				$weight = $shipping->convert_weight($package['weight'],$package['weight_unit'],'lb');
				// XML
				$data = '<?xml version="1.0" encoding="UTF-8" ?>
	<FDXRateRequest xmlns:api="http://www.fedex.com/fsmapi" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="FDXRateRequest.xsd">
		<RequestHeader>
			<CustomerTransactionIdentifier>Express Rate</CustomerTransactionIdentifier>
			<AccountNumber>'.$this->account.'</AccountNumber>
			<MeterNumber>'.$this->meter.'</MeterNumber>
			<CarrierCode>'.(in_array($method,array('FEDEXGROUND','GROUNDHOMEDELIVERY')) ? 'FDXG' : 'FDXE').'</CarrierCode>
		</RequestHeader>
		<DropoffType>REGULARPICKUP</DropoffType>
		<Service>'.$method.'</Service>
		<Packaging>YOURPACKAGING</Packaging>
		<WeightUnits>LBS</WeightUnits>
		<Weight>'.number_format($weight, 1, '.', '').'</Weight>
		<OriginAddress>
			<StateOrProvinceCode>'.$shipping->from_state.'</StateOrProvinceCode>
			<PostalCode>'.$shipping->from_zip.'</PostalCode>
			<CountryCode>'.$shipping->from_country.'</CountryCode>
		</OriginAddress>
		<DestinationAddress>
			<StateOrProvinceCode>'.$shipping->to_state.'</StateOrProvinceCode>
			<PostalCode>'.$shipping->to_zip.'</PostalCode>
			<CountryCode>'.$shipping->to_country.'</CountryCode>
		</DestinationAddress>
		<Payment>
			<PayorType>SENDER</PayorType>
		</Payment>
		<PackageCount>1</PackageCount>
	</FDXRateRequest>';
			
				// Curl
				$results = $shipping->curl($url,$data);
				
				// Debug
				if($shipping->debug) {
					print "<xmp>".$data."</xmp><br />";
					print "<xmp>".$results."</xmp><br />";
				}
			
				// Match Rate
				preg_match('/<NetCharge>(.*?)<\/NetCharge>/',$results,$rate);
				
				// Error
				if(!$rate[1]) return false;
				
				// Total
				$total += $rate[1];
			}
			
			// Return
			return $total;*/
		}
		
		/**
		 * Returns name for the given method code.
		 *
		 * @param string $code The method code you want to get the name of.
		 * @return string The name for the given method code.
		 */
		function name($code) {
			return $this->names[$code];	
		}
	}
}
?>