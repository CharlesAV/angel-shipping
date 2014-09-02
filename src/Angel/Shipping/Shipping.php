<?php
namespace Angel\Shipping;

if(!class_exists('Shipping',false)) {
	/**
	 * Abstract class for calculating shipping cost through various shipping companies.						
	 */
	class Shipping  {
		// Defaults
		/*var $weight = 1;
		var $weight_unit = "lb"; // gram, oz, lb, kg
		var $dimensions_length = 4;
		var $dimensions_width = 8;
		var $dimensions_height = 2;
		var $dimensions_unit = "in"; // cm, in, ft*/
		var $debug = 0; // Turn on and we'll 'print' info about requests sent and recieved 
		
		// Config (you can either set these here or send them in a config array when creating an instance of the class)
		var $from_zip;
		var $from_state;
		var $from_country;
		var $to_zip;
		var $to_state;
		var $to_country;
		
		/** Holds the shipping company class object which will actually calculate the shipping costs. */
		var $company;
		/** An array of packages this shipment have been placed in (based upon each companies max weight value). */
		var $packages;
		
		// Results
		var $rates;
		
		/**
		 * Constructs the class.
		 *
		 * @param array $c An array of configuration values. Default = NULL
		 */
		function __construct($c = NULL) {
			self::Shipping($c);
		}
		function Shipping($c = NULL) {
			// Config
			if($c) {
				foreach($c as $k => $v) {
					$this->$k = $v;
				}
			}
		
			// Zip Length (can only by 5 numbers)
			if($this->from_country == "US") $this->from_zip = substr($this->from_zip,0,5);
			if($this->to_country == "US") $this->to_zip = substr($this->to_zip,0,5);
			
			// Package passed in config
			if($c['dimensions_length'] and $c['dimensions_width'] and $c['dimensions_height'] and $c['weight']) {
				$package = array(
					'dimensions_length' => $c['dimensions_length'],
					'dimensions_width' => $c['dimensions_width'],
					'dimensions_height' => $c['dimensions_height'],
					'dimensions_unit' => $c['dimensions_unit'],
					'weight' => $c['weight'],
					'weight_unit' => $c['weight_unit']
				);
				$this->package($package);
			}
		}
		
		/**
		 * Stores the shipping company class which will actually get the shipping costs.
		 *
		 * @param object $company The shipping company class object.
		 */
		function company($company) {
			$this->company = $company;
		}
		
		/**
		 * Stores the given package array in our saved packages for this shipment calculation.
		 *
		 * Format should be:
		 * $package = array(
		 * 		'dimensions_length' => 10,
		 * 		'dimensions_width' => 6,
		 * 		'dimensions_height' => 2,
		 * 		'dimensions_unit' => 'in', // cm, in [default], ft
		 * 		'weight' => 1.2,
		 * 		'weight_unit' => 'lb', // gram, oz, lb [default], kg
		 *		'key' => 'abc123' // Optional, but will be returned with rates and may be useful for you to differentiate between multiple packages
		 * 	);
		 */
		function package($package) {
			// No package
			if(!$package) return;
			
			// Defaults
			if(!$package['dimensions_unit']) $package['dimensions_unit'] = 'in';
			if(!$package['weight_unit']) $package['weight_unit'] = 'lb';
			
			// Standardize
			$package['dimensions_length'] = $this->convert_dimensions($package['dimensions_length'],$package['dimensions_unit'],'in');
			$package['dimensions_width'] = $this->convert_dimensions($package['dimensions_width'],$package['dimensions_unit'],'in');
			$package['dimensions_height'] = $this->convert_dimensions($package['dimensions_height'],$package['dimensions_unit'],'in');
			$package['dimensions_unit'] = 'in';
			$package['weight'] = $this->convert_weight($package['weight'],$package['weight_unit'],'lb');
			$package['weight_unit'] = 'lb';	
		
			// Save
			$this->packages[] = $package;
		}
	
		/**
		 * Calculates the shipping costs via the stored shipping company class ($this->company).
		 *
		 * @return array An array of shipping costs in array('code' => 'cost') format.
		 */
		function calculate() {
			// Error
			if(!$this->company or !$this->from_zip or !$this->to_zip or !$this->packages) return;
			
			// Calculate
			$this->rates = $this->company->calculate($this);
			
			// Return
			return $this->rates;
		}
		
		/**
		 * Makes sure given packages don't exceed the given max weight. If they do, separates them into multiple packages.
		 *
		 * param array $packages An array of packages too test against the max weight.
		 * @param int $max The maximum (in lbs) that can be in this package).
		 * @return array The packages array with no packages exceeding the max limit.
		 */
		function packages_max($packages,$max) {
			// Error
			if(!$packages or !$max) return $packages;
			
			// Loop through packages
			foreach($packages as $x => $package) {
				// Weight
				$weight = $package['weight'];
				
				// Loop to create new packages if overweight
				for($y = 0;$y < 1;) {
					// Too heavy, create another package
					if($weight > $max) {
						// New package
						$package_new = $package;
						// Set weight of new package to max weight
						$package_new['weight'] = $max;
						// Update new package's dimensions
						$weight_percent = $package_new['weight'] / $package['weight'];
						$package_new['dimensions_width'] *= $weight_percent;
						$package_new['dimensions_height'] *= $weight_percent;
						$package_new['dimensions_length'] *= $weight_percent;
						
						// Add new package
						$packages[] = $package_new;
						// Reduce original weight by what we put in this package
						$weight -= $max;
					}
					// Light enough, update weight/dimenstions/volume (if we changed it at all)
					else {
						// Update package's dimensions
						$weight_percent = $weight / $package['weight'];
						$package['dimensions_width'] *= $weight_percent;
						$package['dimensions_height'] *= $weight_percent;
						$package['dimensions_length'] *= $weight_percent;
						// Update package's weight
						$package['weight'] = $weight;
						
						// Update package
						$packages[$x] = $package;
						
						// End loop
						$y = 1;
					}
				}
			}
			
			// Return
			return $packages;
		}
		
		// Curl
		function curl($url,$data = NULL) {
			if(function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);  
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				if($data) {
					curl_setopt($ch, CURLOPT_POST,1);  
					curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
				}  
				curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
				$contents = curl_exec ($ch);
				
				return $contents;
				
				curl_close ($ch);
			}
			else {
				$this->debug("curl is not installed");
			}
		}
		
		/**
		 * Converts the given weight from its old unit to a new unit.
		 *
		 * @param double $weight The current weight.
		 * @param string $old_unit The unit the current weight is in: gram, oz, lb, kg.
		 * @param string $new_unit The unit we want the new weight to be in: gram, oz, lb, kg.
		 * @param double $minimum The minimum value to return for the new weight. Defualt = .1
		 */
		function convert_weight($weight,$old_unit,$new_unit,$minimum = .1) {
			// Different unit?
			if($old_unit != $new_unit) {
				// Conversion values
				$units['oz'] = 1;
				$units['lb'] = 0.0625;
				$units['gram'] = 28.3495231;
				$units['kg'] = 0.0283495231;
				
				// Convert to ounces (if not already)
				if($old_unit != "oz") $weight = $weight / $units[$old_unit];
				
				// Convert to new unit
				$weight = $weight * $units[$new_unit];
			}
			
			// Minimum weight
			if($weight < $minimum) $weight = $minimum;
			
			// Return new weight
			$weight = round($weight,2);
			return $weight;
			
		}
		
		/**
		 * Converts the given dimensions from its old unit to a new unit.
		 *
		 * @param double $dimension The current dimension.
		 * @param string $old_unit The unit the current dimension is in: cm, in, ft
		 * @param string $new_unit The unit we want the new dimension to be in: cm, in, ft
		 * @param double $minimum The minimum value to return for the new dimension. Defualt = .1
		 */
		function convert_dimensions($dimension,$old_unit,$new_unit,$minimum = .1) {
			// Different unit?
			if($old_unit != $new_unit) {
				// Conversion values
				$units['in'] = 1;
				$units['cm'] = 2.54;
				$units['ft'] = 0.083333;
				
				// Convert to inches (if not already)
				if($old_unit != "in") $dimension = $dimension / $units[$old_unit];
				
				// Convert to new unit
				$dimension = $dimension * $units[$new_unit];
			}
				
			// Minimum dimension
			if($dimension < $minimum) $dimension = $minimum;
			
			// Return new dimension
			return round($dimension,2);
		}
		
		/**
		 * Returns and (if $value passed) sets the value for the given $key.
		 *
		 * @param string $key The key you want to get (or set) the value of.
		 * @param mixed $value The value you want to set the $key to. Default = NULL
		 * @return mixed The given $key's value.
		 */
		function value($key,$value = NULL) {
			// Error
			if(!$key) return;
			
			// Set
			if(strlen($value)) $this->$key = $value;
			
			// Return
			return $this->$key;
		}
		
		/**
		 * Displays given 'debug' message if $this->debug is true.
		 *
		 * @param string $message The debugging message we want to display.
		 */
		function debug($message) {
			// Error
			if(!$message) return;
			
			// Message
			if($this->debug) print "
".$message."<br />";
		}
	}
}
?>