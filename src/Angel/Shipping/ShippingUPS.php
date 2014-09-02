<?php
namespace Angel\Shipping;

use Config;

if(!class_exists('ShippingUPS',false)) {
	/**
	 * Calculates shipping cost for UPS shipping methods.						
	 */
	class ShippingUPS  {
		/** The access license key of your UPS account. */
		var $access;
		/** The username of your UPS account. */
		var $username;
		/** The password of your UPS account. */
		var $password;
		/** The account number of your UPS account. */
		var $account;
		/** Maximum weight of a single package (in lbs). */
		var $package_max = 150;
		/** An array of methods to get shipping costs for. */
		var $methods = array(
			// United States Domestic Shipments
			'01', // UPS Next Day Air
			'02', // UPS Second Day Air
			'03', // UPS Ground
			'07', // UPS Worldwide Express
			'08', // UPS Worldwide Expedited
			'11', // UPS Standard
			'12', // UPS Three-Day Select
			'13', // UPS Next Day Air Saver
			'14', // UPS Next Day Air Early A.M.
			'54', // UPS Worldwide Express Plus
			'59', // UPS Second Day Air A.M.
			'65', // UPS Saver
			// Shipments Originating in United States
			/*'01', // UPS Next Day Air
			'02', // UPS Second Day Air
			'03', // UPS Ground
			'07', // UPS Worldwide Express
			'08', // UPS Worldwide Expedited
			'11', // UPS Standard
			'12', // UPS Three-Day Select
			'14', // UPS Next Day Air Early A.M.
			'54', // UPS Worldwide Express Plus
			'59', // UPS Second Day Air A.M.
			'65', // UPS Worldwide Saver
			// Shipments Originating in Canada
			'01', // UPS Express
			'02', // UPS Expedited
			'07', // UPS Worldwide Express
			'08', // UPS Worldwide Expedited
			'11', // UPS Standard
			'12', // UPS Three-Day Select
			'14', // UPS Express Early A.M.
			'65', // UPS Saver
			// Shipments Originating in the European Union
			'07', // UPS Express
			'08', // UPS Expedited
			'11', // UPS Standard
			'54', // UPS Worldwide Express PlusSM
			'65', // UPS Saver
			// Polish Domestic Shipments
			'07', // UPS Express
			'08', // UPS Expedited
			'11', // UPS Standard
			'54', // UPS Worldwide Express Plus
			'65', // UPS Saver
			'82', // UPS Today Standard
			'83', // UPS Today Dedicated Courrier
			'84', // UPS Today Intercity
			'85', // UPS Today Express
			'86', // UPS Today Express Saver
			// Puerto Rico Origin
			'01', // UPS Next Day Air
			'02', // UPS Second Day Air
			'03', // UPS Ground
			'07', // UPS Worldwide Express
			'08', // UPS Worldwide Expedited
			'14', // UPS Next Day Air Early A.M.
			'54', // UPS Worldwide Express Plus
			'65', // UPS Saver
			// Shipments Originating in Mexico
			'07', // UPS Express
			'08', // UPS Expedited
			'54', // UPS Express Plus
			'65', // UPS Saver
			// Shipments Originating in Other Countries
			'07', // UPS Express
			'08', // UPS Worldwide Expedited
			'11', // UPS Standard
			'54', // UPS Worldwide Express Plus
			'65', // UPS Saver*/
		);
		
		/**
		 * Constructs the class.
		 *
		 * @param array $c An array of configuration values. Default = NULL
		 */
		function __construct($c = NULL) {
			self::ShippingUPS($c);
		}
		function ShippingUPS($c = NULL) {
			// Config
			if($c) {
				foreach($c as $k => $v) {
					$this->$k = $v;
				}
			}
			
			// Default
			if(!$this->access) $this->access = Config::get('shipping::logins.ups.access');
			if(!$this->username) $this->username = Config::get('shipping::logins.ups.username');
			if(!$this->password) $this->password = Config::get('shipping::logins.ups.password');
			if(!$this->account) $this->account = Config::get('shipping::logins.ups.account');
		}
	
		/**
		 * Calculates the shipping cost(s).
		 *
		 * @param object $shipping The shipping class object which contains all the information about the shipment we want to calculate costs for.
		 * @return array An array of shipping costs in array('code' => 'cost') format.
		 */
		function calculate($shipping) {
			// Methods
			$methods = array_filter($this->methods);
			
			// Packages - make sure they don't exceed maximum
			$packages = $shipping->packages;
			if($this->package_max) $packages = $shipping->packages_max($packages,$this->package_max);
			
			// Missing credentials
			if(!$this->access or !$this->username or !$this->password or !$this->account) {
				throw new \Exception("UPS credentials are missing.");	
			}
			// Missing shipping object
			if(!$shipping) {
				throw new \Exception("No 'shipping' object passed.");
			}
			// No methods
			if(!$methods) {
				throw new \Exception("No shipping methods defined.");
			}
			
			// Rates
			$rates = NULL;
			
			// Magento
			/*'originShipment'=>array(
                // United States Domestic Shipments
                'United States Domestic Shipments' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS Second Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS Three-Day Select'),
                    '13' => Mage::helper('usa')->__('UPS Next Day Air Saver'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '59' => Mage::helper('usa')->__('UPS Second Day Air A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in United States
                'Shipments Originating in United States' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS Second Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS Three-Day Select'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '59' => Mage::helper('usa')->__('UPS Second Day Air A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Worldwide Saver'),
                ),
                // Shipments Originating in Canada
                'Shipments Originating in Canada' => array(
                    '01' => Mage::helper('usa')->__('UPS Express'),
                    '02' => Mage::helper('usa')->__('UPS Expedited'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '12' => Mage::helper('usa')->__('UPS Three-Day Select'),
                    '14' => Mage::helper('usa')->__('UPS Express Early A.M.'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in the European Union
                'Shipments Originating in the European Union' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express PlusSM'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Polish Domestic Shipments
                'Polish Domestic Shipments' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                    '82' => Mage::helper('usa')->__('UPS Today Standard'),
                    '83' => Mage::helper('usa')->__('UPS Today Dedicated Courrier'),
                    '84' => Mage::helper('usa')->__('UPS Today Intercity'),
                    '85' => Mage::helper('usa')->__('UPS Today Express'),
                    '86' => Mage::helper('usa')->__('UPS Today Express Saver'),
                ),
                // Puerto Rico Origin
                'Puerto Rico Origin' => array(
                    '01' => Mage::helper('usa')->__('UPS Next Day Air'),
                    '02' => Mage::helper('usa')->__('UPS Second Day Air'),
                    '03' => Mage::helper('usa')->__('UPS Ground'),
                    '07' => Mage::helper('usa')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '14' => Mage::helper('usa')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in Mexico
                'Shipments Originating in Mexico' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Expedited'),
                    '54' => Mage::helper('usa')->__('UPS Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver'),
                ),
                // Shipments Originating in Other Countries
                'Shipments Originating in Other Countries' => array(
                    '07' => Mage::helper('usa')->__('UPS Express'),
                    '08' => Mage::helper('usa')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('usa')->__('UPS Standard'),
                    '54' => Mage::helper('usa')->__('UPS Worldwide Express Plus'),
                    '65' => Mage::helper('usa')->__('UPS Saver')
                )
            ),

            'method'=>array(
                '1DM'    => Mage::helper('usa')->__('Next Day Air Early AM'),
                '1DML'   => Mage::helper('usa')->__('Next Day Air Early AM Letter'),
                '1DA'    => Mage::helper('usa')->__('Next Day Air'),
                '1DAL'   => Mage::helper('usa')->__('Next Day Air Letter'),
                '1DAPI'  => Mage::helper('usa')->__('Next Day Air Intra (Puerto Rico)'),
                '1DP'    => Mage::helper('usa')->__('Next Day Air Saver'),
                '1DPL'   => Mage::helper('usa')->__('Next Day Air Saver Letter'),
                '2DM'    => Mage::helper('usa')->__('2nd Day Air AM'),
                '2DML'   => Mage::helper('usa')->__('2nd Day Air AM Letter'),
                '2DA'    => Mage::helper('usa')->__('2nd Day Air'),
                '2DAL'   => Mage::helper('usa')->__('2nd Day Air Letter'),
                '3DS'    => Mage::helper('usa')->__('3 Day Select'),
                'GND'    => Mage::helper('usa')->__('Ground'),
                'GNDCOM' => Mage::helper('usa')->__('Ground Commercial'),
                'GNDRES' => Mage::helper('usa')->__('Ground Residential'),
                'STD'    => Mage::helper('usa')->__('Canada Standard'),
                'XPR'    => Mage::helper('usa')->__('Worldwide Express'),
                'WXS'    => Mage::helper('usa')->__('Worldwide Express Saver'),
                'XPRL'   => Mage::helper('usa')->__('Worldwide Express Letter'),
                'XDM'    => Mage::helper('usa')->__('Worldwide Express Plus'),
                'XDML'   => Mage::helper('usa')->__('Worldwide Express Plus Letter'),
                'XPD'    => Mage::helper('usa')->__('Worldwide Expedited'),
            ),

            'pickup'=>array(
                'RDP'    => array("label"=>'Regular Daily Pickup',"code"=>"01"),
                'OCA'    => array("label"=>'On Call Air',"code"=>"07"),
                'OTP'    => array("label"=>'One Time Pickup',"code"=>"06"),
                'LC'     => array("label"=>'Letter Center',"code"=>"19"),
                'CC'     => array("label"=>'Customer Counter',"code"=>"03"),
            ),

            'container'=>array(
                'CP'     => '00', // Customer Packaging
                'ULE'    => '01', // UPS Letter Envelope
                'CSP'    => '02', // Customer Supplied Package
                'UT'     => '03', // UPS Tube
                'PAK'    => '04', // PAK
                'UEB'    => '21', // UPS Express Box
                'UW25'   => '24', // UPS Worldwide 25 kilo
                'UW10'   => '25', // UPS Worldwide 10 kilo
                'PLT'    => '30', // Pallet
                'SEB'    => '2a', // Small Express Box
                'MEB'    => '2b', // Medium Express Box
                'LEB'    => '2c', // Large Express Box
            ),

            'container_description'=>array(
                'CP'     => Mage::helper('usa')->__('Customer Packaging'),
                'ULE'    => Mage::helper('usa')->__('UPS Letter Envelope'),
                'CSP'    => Mage::helper('usa')->__('Customer Supplied Package'),
                'UT'     => Mage::helper('usa')->__('UPS Tube'),
                'PAK'    => Mage::helper('usa')->__('PAK'),
                'UEB'    => Mage::helper('usa')->__('UPS Express Box'),
                'UW25'   => Mage::helper('usa')->__('UPS Worldwide 25 kilo'),
                'UW10'   => Mage::helper('usa')->__('UPS Worldwide 10 kilo'),
                'PLT'    => Mage::helper('usa')->__('Pallet'),
                'SEB'    => Mage::helper('usa')->__('Small Express Box'),
                'MEB'    => Mage::helper('usa')->__('Medium Express Box'),
                'LEB'    => Mage::helper('usa')->__('Large Express Box'),
            ),

            'dest_type'=>array(
                'RES'    => '01', // Residential
                'COM'    => '02', // Commercial
            ),*/
			
			// Methods
			foreach($methods as $method) {
				// https://developerkitcommunity.ups.com/index.php/Rating_Package_XML_Developers_Guide_-_January_02,_2012
				$url = "https://www.ups.com/ups.app/xml/Rate";
				$data = '<?xml version="1.0"?>  
<AccessRequest xml:lang="en-US">  
	<AccessLicenseNumber>'.$this->access.'</AccessLicenseNumber>  
	<UserId>'.$this->username.'</UserId>  
	<Password>'.$this->password.'</Password>  
</AccessRequest>  
<?xml version="1.0"?>
<RatingServiceSelectionRequest xml:lang="en-US">  
	<Request>  
		<TransactionReference>  
			<CustomerContext>Bare Bones Rate Request</CustomerContext>  
			<XpciVersion>1.0001</XpciVersion>  
		</TransactionReference>  
		<RequestAction>Rate</RequestAction>  
		<RequestOption>Rate</RequestOption>  
	</Request>';
				/*
				01 - Daily Pickup (default)
				03 - Customer Counter
				06 - One Time Pickup
				07 - On Call Air
				11 - Sugested Retail (undocumented, not sure if this works)
				19 - Letter Center
				20 - Air Service Center
				*/
				$data .= '
	<PickupType>  
		<Code>01</Code>  
	</PickupType>';
				/*
				00 - Rates Associated with Shipper Number
				01 - Daily Rates
				04 - Retail Rates
				53 - Standard List Rates
				The default value is 01 (Daily Rates) when the Pickup Type code is 01 (Daily pickup).
				The default value is 04 (Retail Rates) when the Pickup Type code is: 06 - One Time Pickup, 07 - On Call Air, 19 - Letter Center, or 20 - Air Service Center
				*/
				$data .= '
	<CustomerClassification>
		<Code>01</Code>
	</CustomerClassification>';
				$data .= '
	<Shipment>  
		<Shipper>  
			<Address>  
				<PostalCode>'.$shipping->from_zip.'</PostalCode>  
				<CountryCode>'.$shipping->from_country.'</CountryCode>  
			</Address>  
			<ShipperNumber>'.$this->account.'</ShipperNumber>  
		</Shipper>  
		<ShipTo>  
			<Address>  
				<PostalCode>'.$shipping->to_zip.'</PostalCode>  
				<CountryCode>'.$shipping->to_country.'</CountryCode> 
				<ResidentialAddressIndicator>'.(isset($shipping->to_residential) ? $shipping->to_residential : "").'</ResidentialAddressIndicator>
			</Address>  
		</ShipTo>  
		<ShipFrom>  
			<Address>  
				<PostalCode>'.$shipping->from_zip.'</PostalCode>  
				<CountryCode>'.$shipping->from_country.'</CountryCode>  
			</Address>  
		</ShipFrom>  
		<Service>  
			<Code>'.$method.'</Code>  
		</Service>';
				foreach($packages as $package) {
					$data .= '
		<Package>  
			<PackagingType>  
				<Code>02</Code>  
			</PackagingType>  
			<Dimensions>  
				<UnitOfMeasurement>  
					<Code>IN</Code>  
				</UnitOfMeasurement>  
				<Length>'.$shipping->convert_dimensions($package['dimensions_length'],$package['dimensions_unit'],"in",1).'</Length>  
				<Width>'.$shipping->convert_dimensions($package['dimensions_width'],$package['dimensions_unit'],"in",1).'</Width>  
				<Height>'.$shipping->convert_dimensions($package['dimensions_height'],$package['dimensions_unit'],"in",1).'</Height>  
			</Dimensions>  
			<PackageWeight>  
				<UnitOfMeasurement>  
					<Code>LBS</Code>  
				</UnitOfMeasurement>
				<Weight>'.$shipping->convert_weight($package['weight'],$package['weight_unit'],"lb").'</Weight>  
			</PackageWeight>  
		</Package>';
				}
				$data .= '
	</Shipment>
</RatingServiceSelectionRequest>';
			
				// Curl
				$results = $shipping->curl($url,$data);
				
				// Debug
				if($shipping->debug) {
					print "<xmp>".$data."</xmp><br />";
					print "<xmp>".$results."</xmp><br />";
				}
				
				// Packages
				preg_match_all('/<RatedPackage>(.*?)<TotalCharges>(.*?)<MonetaryValue>(.*?)<\/MonetaryValue>(.*?)<\/RatedPackage>/si',$results,$matches);
				foreach($matches[3] as $x => $rate) {
					if(!isset($rates['rates'][$method])) $rates['rates'][$method] = 0;
					$rates['rates'][$method] += $rate;
					$rates['packages'][$x]['package'] = $packages[$x];
					if(!isset($rates['packages'][$x]['rates'][$method])) $rates['packages'][$x]['rates'][$method] = 0;
					$rates['packages'][$x]['rates'][$method] += $rate;
				}
			}
			
			// Return
			return $rates;
		}
	}
}
?>