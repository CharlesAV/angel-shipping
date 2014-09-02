<?php
namespace Angel\Shipping;

use Config;

if(!class_exists('ShippingUSPS',false)) {
	/**
	 * Calculates shipping cost for USPS shipping methods.						
	 */
	class ShippingUSPS  {
		/** The username for your USPS account. */
		var $username;
		/** Maximum weight of a single package (in lbs). */
		var $package_max = 70;
		/** An array of methods to get shipping costs for. */
		var $methods = array(
			// Domestic
			'First-Class Mail Large Envelope',
			'First-Class Mail Letter',
			'First-Class Mail Parcel',
			'First-Class Mail Postcards',
			'Priority Mail',
			'Priority Mail Express Hold For Pickup',
			'Priority Mail Express',
			'Standard Post',
			'Media Mail',
			'Library Mail',
			'Priority Mail Express Flat Rate Envelope',
			'First-Class Mail Large Postcards',
			'Priority Mail Flat Rate Envelope',
			'Priority Mail Medium Flat Rate Box',
			'Priority Mail Large Flat Rate Box',
			'Priority Mail Express Sunday/Holiday Delivery',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope',
			'Priority Mail Express Flat Rate Envelope Hold For Pickup',
			'Priority Mail Small Flat Rate Box',
			'Priority Mail Padded Flat Rate Envelope',
			'Priority Mail Express Legal Flat Rate Envelope',
			'Priority Mail Express Legal Flat Rate Envelope Hold For Pickup',
			'Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope',
			'Priority Mail Hold For Pickup',
			'Priority Mail Large Flat Rate Box Hold For Pickup',
			'Priority Mail Medium Flat Rate Box Hold For Pickup',
			'Priority Mail Small Flat Rate Box Hold For Pickup',
			'Priority Mail Flat Rate Envelope Hold For Pickup',
			'Priority Mail Gift Card Flat Rate Envelope',
			'Priority Mail Gift Card Flat Rate Envelope Hold For Pickup',
			'Priority Mail Window Flat Rate Envelope',
			'Priority Mail Window Flat Rate Envelope Hold For Pickup',
			'Priority Mail Small Flat Rate Envelope',
			'Priority Mail Small Flat Rate Envelope Hold For Pickup',
			'Priority Mail Legal Flat Rate Envelope',
			'Priority Mail Legal Flat Rate Envelope Hold For Pickup',
			'Priority Mail Padded Flat Rate Envelope Hold For Pickup',
			'Priority Mail Regional Rate Box A',
			'Priority Mail Regional Rate Box A Hold For Pickup',
			'Priority Mail Regional Rate Box B',
			'Priority Mail Regional Rate Box B Hold For Pickup',
			'First-Class Package Service Hold For Pickup',
			'Priority Mail Express Flat Rate Boxes',
			'Priority Mail Express Flat Rate Boxes Hold For Pickup',
			'Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes',
			'Priority Mail Regional Rate Box C',
			'Priority Mail Regional Rate Box C Hold For Pickup',
			'First-Class Package Service',
			'Priority Mail Express Padded Flat Rate Envelope',
			'Priority Mail Express Padded Flat Rate Envelope Hold For Pickup',
			'Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope',
			// International
			'Priority Mail Express International',
			'Priority Mail International',
			'Global Express Guaranteed (GXG)',
			'Global Express Guaranteed Document',
			'Global Express Guaranteed Non-Document Rectangular',
			'Global Express Guaranteed Non-Document Non-Rectangular',
			'Priority Mail International Flat Rate Envelope',
			'Priority Mail International Medium Flat Rate Box',
			'Priority Mail Express International Flat Rate Envelope',
			'Priority Mail International Large Flat Rate Box',
			'USPS GXG Envelopes',
			'First-Class Mail International Letter',
			'First-Class Mail International Large Envelope',
			'First-Class Package International Service',
			'Priority Mail International Small Flat Rate Box',
			'Priority Mail Express International Legal Flat Rate Envelope',
			'Priority Mail International Gift Card Flat Rate Envelope',
			'Priority Mail International Window Flat Rate Envelope',
			'Priority Mail International Small Flat Rate Envelope',
			'First-Class Mail International Postcard',
			'Priority Mail International Legal Flat Rate Envelope',
			'Priority Mail International Padded Flat Rate Envelope',
			'Priority Mail International DVD Flat Rate priced box',
			'Priority Mail International Large Video Flat Rate priced box',
			'Priority Mail Express International Flat Rate Boxes',
			'Priority Mail Express International Padded Flat Rate Envelope',
		);
		/** Holds the specific dimensions available for specific methods (because USPS is a wanker and doesn't filter this themselves) */
		var $dimensions = array(
			// Domestic
			'Priority Mail Medium Flat Rate Box' => array(array(13.625,11.875,3.375),array(11,8.5,5.5)),
			'Priority Mail Large Flat Rate Box' => array(array(12,12,5.5)),
			'Priority Mail Small Flat Rate Box' => array(array(8.625,5.375,1.625)),
			'Priority Mail Large Flat Rate Box Hold For Pickup' => array(array(12,12,5.5)),
			'Priority Mail Medium Flat Rate Box Hold For Pickup' => array(array(13.625,11.875,3.375),array(11,8.5,5.5)),
			'Priority Mail Small Flat Rate Box Hold For Pickup' => array(array(8.625,5.375,1.625)),
			// International
			'Priority Mail International Medium Flat Rate Box' => array(array(13.625,11.875,3.375),array(11,8.5,5.5)),
			'Priority Mail International Large Flat Rate Box' => array(array(12,12,5.5)),
			'Priority Mail International Small Flat Rate Box' => array(array(8.625,5.375,1.625)),
		);
		
		/**
		 * Constructs the class.
		 *
		 * @param array $c An array of configuration values. Default = NULL
		 */
		function __construct($c = NULL) {
			self::ShippingUSPS($c);
		}
		function ShippingUSPS($c = NULL) {
			// Config
			if($c) {
				foreach($c as $k => $v) {
					$this->$k = $v;
				}
			}
			
			// Default
			if(!$this->username) $this->username = Config::get('shipping::logins.usps.username');
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
			if(!$this->username) {
				throw new \Exception("USPS username is missing.");	
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
				
			// Strip - characters they add in to their 'codes' that we don't want
			$strip = array(
				'&amp;lt;sup&amp;gt;&amp;amp;reg;&amp;lt;/sup&amp;gt;',
				'&amp;lt;sup&amp;gt;&amp;amp;trade;&amp;lt;/sup&amp;gt;',
				'&amp;lt;sup&amp;gt;&amp;#174;&amp;lt;/sup&amp;gt;',
				'&amp;lt;sup&amp;gt;&amp;#8482;&amp;lt;/sup&amp;gt;',
				' 1-Day',
				' 2-Day',
				' 3-Day',
				' Military',
				' DPO',
				'*',
				'  ',
				'  ',
				'  ',
			);
			
			// Countries - United States - US territories that have their own country code
			$countries_us = array(
				'AS', // Samoa American
				'GU', // Guam
				'MP', // Northern Mariana Islands
				'PW', // Palau
				'PR', // Puerto Rico
				'VI' // Virgin Islands US
			);
			
			// United States (and US territories)
			if($shipping->to_country == "US" or in_array($shipping->to_country,$countries_us)) {
				$url = "http://production.shippingapis.com/ShippingAPI.dll";
				$xml = '
	<RateV4Request USERID="'.$this->username.'">';
				foreach($packages as $x => $package) {
					// Weight (in lbs) - already in lbs, but just in case
					$weight = $shipping->convert_weight($package['weight'],$package['weight_unit'],'lb');
					// Split into lbs and ozs
					$lbs = floor($weight);
					$ozs = ($weight - $lbs)  * 16;
					if($lbs == 0 and $ozs < 1) $ozs = 1;
					
					// Dimensions - have to re-convert here because requires a minimum of 1
					$width = $shipping->convert_dimensions($package['dimensions_width'],$package['dimensions_unit'],"in",1);
					$length = $shipping->convert_dimensions($package['dimensions_length'],$package['dimensions_unit'],"in",1);
					$height = $shipping->convert_dimensions($package['dimensions_height'],$package['dimensions_unit'],"in",1);
					// Package size
					$size = 'REGULAR';
					if($width > 12 or $length > 12 or $height > 12) $size = "LARGE";
					// Package container
					if($size == "LARGE") $container = 'RECTANGULAR';
					
					// XML
					$xml .= '
		<Package ID="'.($x + 1).'">
			<Service>'.(/*count($this->methods) == 1 ? reset($this->methods) : */"ALL").'</Service>
			<ZipOrigination>'.$shipping->from_zip.'</ZipOrigination>
			<ZipDestination>'.$shipping->to_zip.'</ZipDestination>
			<Pounds>'.$lbs.'</Pounds>
			<Ounces>'.$ozs.'</Ounces>
			<Container>'.(isset($container) ? $container : '').'</Container>
			<Size>'.$size.'</Size>
			<Width>'.$width.'</Width>  
			<Length>'.$length.'</Length>  
			<Height>'.$height.'</Height> 
			<Machinable>TRUE</Machinable>
		</Package>';
				}
				$xml .= '
	</RateV4Request>';
				$data = "API=RateV4&XML=".$xml;
			
				// Curl
				$results = $shipping->curl($url,$data);
				
				// Debug
				if($shipping->debug) {
					print "xml: <xmp>".$xml."</xmp><br />";
					print "results: <xmp>".$results."</xmp><br />";
				}
				
				// Match rate(s)
				preg_match_all('/<Package ID="([0-9]{1,3})">(.+?)<\/Package>/s',$results,$results_packages);
				foreach($results_packages[2] as $x => $results_package) {
					preg_match_all('/<Postage CLASSID="([0-9]{1,3})">(.+?)<\/Postage>/s',$results_package,$results_methods);
					foreach($results_methods[2] as $y => $results_method) {
						// Name
						preg_match('/<MailService>(.+?)<\/MailService>/',$results_method,$name);
						$name = str_replace($strip,'',$name[1]);
						
						// Use name, get rate
						if($name and in_array($name,$this->methods)) {
							preg_match('/<Rate>(.+?)<\/Rate>/',$results_method,$rate);
							if($rate[1]) {
								if($this->package_fits($shipping,$packages,$name)) {
									if(!isset($rates['rates'][$name])) $rates['rates'][$name] = 0;
									$rates['rates'][$name] += $rate[1];
									$rates['packages'][$x]['package'] = $packages[$x];
									if(!isset($rates['packages'][$x]['rates'][$name])) $rates['packages'][$x]['rates'][$name] = 0;
									$rates['packages'][$x]['rates'][$name] += $rate[1];
								}
							}
						}
					}
				}
			}
			// International
			else {
				// Counties - need to pass country name, not code
				 $countries = array(
					'AF' => 'Afghanistan',
					'AL' => 'Albania',
					'AX' => 'Aland Island (Finland)',
					'DZ' => 'Algeria',
					'AD' => 'Andorra',
					'AO' => 'Angola',
					'AI' => 'Anguilla',
					'AG' => 'Antigua and Barbuda',
					'AR' => 'Argentina',
					'AM' => 'Armenia',
					'AW' => 'Aruba',
					'AU' => 'Australia',
					'AT' => 'Austria',
					'AZ' => 'Azerbaijan',
					'BS' => 'Bahamas',
					'BH' => 'Bahrain',
					'BD' => 'Bangladesh',
					'BB' => 'Barbados',
					'BY' => 'Belarus',
					'BE' => 'Belgium',
					'BZ' => 'Belize',
					'BJ' => 'Benin',
					'BM' => 'Bermuda',
					'BT' => 'Bhutan',
					'BO' => 'Bolivia',
					'BA' => 'Bosnia-Herzegovina',
					'BW' => 'Botswana',
					'BR' => 'Brazil',
					'VG' => 'British Virgin Islands',
					'BN' => 'Brunei Darussalam',
					'BG' => 'Bulgaria',
					'BF' => 'Burkina Faso',
					'MM' => 'Burma',
					'BI' => 'Burundi',
					'KH' => 'Cambodia',
					'CM' => 'Cameroon',
					'CA' => 'Canada',
					'CV' => 'Cape Verde',
					'KY' => 'Cayman Islands',
					'CF' => 'Central African Republic',
					'TD' => 'Chad',
					'CL' => 'Chile',
					'CN' => 'China',
					'CX' => 'Christmas Island (Australia)',
					'CC' => 'Cocos Island (Australia)',
					'CO' => 'Colombia',
					'KM' => 'Comoros',
					'CG' => 'Congo, Republic of the',
					'CD' => 'Congo, Democratic Republic of the',
					'CK' => 'Cook Islands (New Zealand)',
					'CR' => 'Costa Rica',
					'CI' => 'Cote d Ivoire (Ivory Coast)',
					'HR' => 'Croatia',
					'CU' => 'Cuba',
					'CY' => 'Cyprus',
					'CZ' => 'Czech Republic',
					'DK' => 'Denmark',
					'DJ' => 'Djibouti',
					'DM' => 'Dominica',
					'DO' => 'Dominican Republic',
					'EC' => 'Ecuador',
					'EG' => 'Egypt',
					'SV' => 'El Salvador',
					'GQ' => 'Equatorial Guinea',
					'ER' => 'Eritrea',
					'EE' => 'Estonia',
					'ET' => 'Ethiopia',
					'FK' => 'Falkland Islands',
					'FO' => 'Faroe Islands',
					'FJ' => 'Fiji',
					'FI' => 'Finland',
					'FR' => 'France',
					'GF' => 'French Guiana',
					'PF' => 'French Polynesia',
					'GA' => 'Gabon',
					'GM' => 'Gambia',
					'GE' => 'Georgia, Republic of',
					'DE' => 'Germany',
					'GH' => 'Ghana',
					'GI' => 'Gibraltar',
					'GB' => 'Great Britain and Northern Ireland',
					'GR' => 'Greece',
					'GL' => 'Greenland',
					'GD' => 'Grenada',
					'GP' => 'Guadeloupe',
					'GT' => 'Guatemala',
					'GN' => 'Guinea',
					'GW' => 'Guinea-Bissau',
					'GY' => 'Guyana',
					'HT' => 'Haiti',
					'HN' => 'Honduras',
					'HK' => 'Hong Kong',
					'HU' => 'Hungary',
					'IS' => 'Iceland',
					'IN' => 'India',
					'ID' => 'Indonesia',
					'IR' => 'Iran',
					'IQ' => 'Iraq',
					'IE' => 'Ireland',
					'IL' => 'Israel',
					'IT' => 'Italy',
					'JM' => 'Jamaica',
					'JP' => 'Japan',
					'JO' => 'Jordan',
					'KZ' => 'Kazakhstan',
					'KE' => 'Kenya',
					'KI' => 'Kiribati',
					'KW' => 'Kuwait',
					'KG' => 'Kyrgyzstan',
					'LA' => 'Laos',
					'LV' => 'Latvia',
					'LB' => 'Lebanon',
					'LS' => 'Lesotho',
					'LR' => 'Liberia',
					'LY' => 'Libya',
					'LI' => 'Liechtenstein',
					'LT' => 'Lithuania',
					'LU' => 'Luxembourg',
					'MO' => 'Macao',
					'MK' => 'Macedonia, Republic of',
					'MG' => 'Madagascar',
					'MW' => 'Malawi',
					'MY' => 'Malaysia',
					'MV' => 'Maldives',
					'ML' => 'Mali',
					'MT' => 'Malta',
					'MQ' => 'Martinique',
					'MR' => 'Mauritania',
					'MU' => 'Mauritius',
					'YT' => 'Mayotte (France)',
					'MX' => 'Mexico',
					'FM' => 'Micronesia, Federated States of',
					'MD' => 'Moldova',
					'MC' => 'Monaco (France)',
					'MN' => 'Mongolia',
					'MS' => 'Montserrat',
					'MA' => 'Morocco',
					'MZ' => 'Mozambique',
					'NA' => 'Namibia',
					'NR' => 'Nauru',
					'NP' => 'Nepal',
					'NL' => 'Netherlands',
					'AN' => 'Netherlands Antilles',
					'NC' => 'New Caledonia',
					'NZ' => 'New Zealand',
					'NI' => 'Nicaragua',
					'NE' => 'Niger',
					'NG' => 'Nigeria',
					'KP' => 'North Korea (Korea, Democratic People\'s Republic of)',
					'NO' => 'Norway',
					'OM' => 'Oman',
					'PK' => 'Pakistan',
					'PA' => 'Panama',
					'PG' => 'Papua New Guinea',
					'PY' => 'Paraguay',
					'PE' => 'Peru',
					'PH' => 'Philippines',
					'PN' => 'Pitcairn Island',
					'PL' => 'Poland',
					'PT' => 'Portugal',
					'QA' => 'Qatar',
					'RE' => 'Reunion',
					'RO' => 'Romania',
					'RU' => 'Russia',
					'RW' => 'Rwanda',
					'SH' => 'Saint Helena',
					'KN' => 'Saint Kitts (St. Christopher and Nevis)',
					'LC' => 'Saint Lucia',
					'PM' => 'Saint Pierre and Miquelon',
					'VC' => 'Saint Vincent and the Grenadines',
					'SM' => 'San Marino',
					'ST' => 'Sao Tome and Principe',
					'SA' => 'Saudi Arabia',
					'SN' => 'Senegal',
					'RS' => 'Serbia',
					'SC' => 'Seychelles',
					'SL' => 'Sierra Leone',
					'SG' => 'Singapore',
					'SK' => 'Slovak Republic',
					'SI' => 'Slovenia',
					'SB' => 'Solomon Islands',
					'SO' => 'Somalia',
					'ZA' => 'South Africa',
					'GS' => 'South Georgia (Falkland Islands)',
					'KR' => 'South Korea (Korea, Republic of)',
					'ES' => 'Spain',
					'LK' => 'Sri Lanka',
					'SD' => 'Sudan',
					'SR' => 'Suriname',
					'SZ' => 'Swaziland',
					'SE' => 'Sweden',
					'CH' => 'Switzerland',
					'SY' => 'Syrian Arab Republic',
					'TW' => 'Taiwan',
					'TJ' => 'Tajikistan',
					'TZ' => 'Tanzania',
					'TH' => 'Thailand',
					'TL' => 'East Timor (Indonesia)',
					'TG' => 'Togo',
					'TK' => 'Tokelau (Union) Group (Western Samoa)',
					'TO' => 'Tonga',
					'TT' => 'Trinidad and Tobago',
					'TN' => 'Tunisia',
					'TR' => 'Turkey',
					'TM' => 'Turkmenistan',
					'TC' => 'Turks and Caicos Islands',
					'TV' => 'Tuvalu',
					'UG' => 'Uganda',
					'UA' => 'Ukraine',
					'AE' => 'United Arab Emirates',
					'UY' => 'Uruguay',
					'UZ' => 'Uzbekistan',
					'VU' => 'Vanuatu',
					'VA' => 'Vatican City',
					'VE' => 'Venezuela',
					'VN' => 'Vietnam',
					'WF' => 'Wallis and Futuna Islands',
					'WS' => 'Western Samoa',
					'YE' => 'Yemen',
					'ZM' => 'Zambia',
					'ZW' => 'Zimbabwe'
				);
				 
				$url = "http://production.shippingapis.com/ShippingAPI.dll";
				$xml = '
	<IntlRateV2Request USERID="'.$this->username.'">';
				foreach($packages as $x => $package) {
					// Weight (in lbs) - already in lbs, but just in case
					$weight = $shipping->convert_weight($package['weight'],$package['weight_unit'],'lb');
					// Split into lbs and ozs
					$lbs = floor($weight);
					$ozs = ($weight - $lbs)  * 16;
					if($lbs == 0 and $ozs < 1) $ozs = 1;
					
					// XML
					$xml .= '
		<Package ID="'.($x + 1).'">
			<Pounds>'.$lbs.'</Pounds>
			<Ounces>'.$ozs.'</Ounces>
			<Machinable>TRUE</Machinable>
			<MailType>Package</MailType>
			<GXG>
				<POBoxFlag>N</POBoxFlag>
				<GiftFlag>N</GiftFlag>
			</GXG>
			<ValueOfContents>0.00</ValueOfContents>
			<Country>'.$countries[$shipping->to_country].'</Country>
			<Container>RECTANGULAR</Container>
			<Size>REGULAR</Size>';
					// Dimensions - have to re-convert here because requires a minimum of 1
					$xml .= '
			<Width>'.$shipping->convert_dimensions($package['dimensions_width'],$package['dimensions_unit'],"in",1).'</Width>  
			<Length>'.$shipping->convert_dimensions($package['dimensions_length'],$package['dimensions_unit'],"in",1).'</Length>  
			<Height>'.$shipping->convert_dimensions($package['dimensions_height'],$package['dimensions_unit'],"in",1).'</Height> 
			<Girth>10</Girth> 
		</Package>';
				}
				$xml .= '
	</IntlRateV2Request>';
				$data = "API=IntlRateV2&XML=".$xml;
			
				// Curl
				$results = $shipping->curl($url,$data);
				
				// Debug
				if($shipping->debug) {
					print "xml: <xmp>".$xml."</xmp><br />";
					print "results: <xmp>".$results."</xmp><br />";
				}
				
				// Rate(s)
				preg_match_all('/<Package ID="([0-9]{1,3})">(.+?)<\/Package>/s',$results,$results_packages);
				foreach($results_packages[2] as $x => $results_package) {
					preg_match_all('/<Service ID="([0-9]{1,3})">(.+?)<\/Service>/s',$results_package,$results_methods);
					foreach($results_methods[2] as $y => $results_method) {
						// Name
						preg_match('/<SvcDescription>(.+?)<\/SvcDescription>/',$results_method,$name);
						$name = str_replace($strip,'',$name[1]);
						
						// Use name, get rate
						if($name and in_array($name,$this->methods)) {
							preg_match('/<Postage>(.+?)<\/Postage>/',$results_method,$rate);
							if($rate[1]) {
								if($this->package_fits($shipping,$packages,$name)) {
									$rates['rates'][$name] += $rate[1];
									$rates['packages'][$x]['package'] = $packages[$x];
									$rates['packages'][$x]['rates'][$name] += $rate[1];
								}
							}
						}
					}
				}
			}
			
			// Return
			return $rates;
		}
		
		/**
		 * Checks that the packages all meet the maximum dimensions allowed for the given shipping method.
		 *
		 * @param object $shipping The shipping class object which contains all the information about the shipment we want to calculate costs for.
		 * @param array $packages An array of packages we're shipping.
		 * @param string $method The method we're checking dimensions against.
		 * @return boolean Whether or not it fits.
		 */
		function package_fits($shipping,$packages,$method) {
			// Packages
			if(!$packages) return;
			
			// Boxes
			if(!isset($this->dimensions[$method])) return true;
			$boxes = $this->dimensions[$method];
			
			// Debug
			if($shipping->debug) print "<b>".$method."</b><br />";
			
			// Loop through available box dimensions
			$return = false;
			foreach($boxes as $box_x => $box_dimensions) {
				rsort($box_dimensions);
				if($shipping->debug) {
					print "testing box ".$box_x.": ";
					print_r($box_dimensions);
				}
				
				// Loop through all packages, makes sure they all fit
				$fits = true;
				$dimensions = NULL;
				foreach($packages as $package_x => $package) {
					// Check if package fits box dimensions
					$package_fits = true;
					$package_dimensions = array(
						$package['dimensions_width'],
						$package['dimensions_length'],
						$package['dimensions_height']
					);
					rsort($package_dimensions);
					if($shipping->debug) {
						print "trying to fit package ".$package_x.": ";
						print_r($package_dimensions);
					}
					foreach($package_dimensions as $package_x => $package_dimension) {
						// Check all box dimensions to see if package fits within
						foreach($box_dimensions as $box_dimension_x => $box_dimension) {
							if(isset($dimensions['box'][$box_dimension_x])) continue;
							if($box_dimension >= $package_dimension) {
								$dimensions['package'][$package_x] = $package_dimension;
								$dimensions['box'][$box_dimension_x] = $box_dimension;
								break;			
							}
						}
						// Doesn't fit
						if(!$dimensions['package'][$package_x]) {
							if($shipping->debug) print "package dimension: ".$package_dimension." doesn't fit box dimension: ".$box_dimension."<br />";
							$package_fits = false;
							break;
						}
					}
					if(!$package_fits) {
						if($shipping->debug) print "package doesn't fit in box ".$box_x."<br />";
						$fits = false;
						break;
					}
				}
				if($fits) {
					if($shipping->debug) print "FITS!<br /><br />";
					$return = true;
					break;
				}
			}
			
			// Return
			return $return;
		}
	}
}
?>